<?php
/**
 * Lyrargon 服务状态监控 (Uptime Status) 服务端中继与缓存模块
 *
 * 负责通过服务端调用 UptimeRobot API，防止 API Key 前端泄露，消除 CORS 限制，
 * 提供基于 WP-Cron 自动后台轮询与 Transient/Persistent 双层缓存，
 * 绝不在前端页面渲染阶段同步阻塞外部网络，实现真正的 0 毫秒首屏直出。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 注册 AJAX 接口：获取监控数据
 */
add_action( 'wp_ajax_lyrargon_get_status', 'lyrargon_ajax_get_status' );
add_action( 'wp_ajax_nopriv_lyrargon_get_status', 'lyrargon_ajax_get_status' );

/**
 * 注册与排队前端脚本：仅在服务状态监控页面按需加载
 */
add_action( 'wp_enqueue_scripts', 'lyrargon_uptime_status_scripts' );
function lyrargon_uptime_status_scripts() {
	if ( get_option( 'lyrargon_status_enabled' ) === 'true' ) {
		if ( is_page_template( 'status.php' ) || is_page( 'status' ) ) {
			$version = function_exists( 'lyrargon_theme_version' ) ? lyrargon_theme_version() : '2.0.30';
			$assets = function_exists( 'lyrargon_assets_path' ) ? lyrargon_assets_path() : get_template_directory_uri();
			wp_enqueue_script(
				'lyrargon-uptime-status',
				$assets . '/assets/js/uptime-status.js',
				array( 'jquery', 'lyrargon_theme' ),
				$version,
				true
			);
		}
	}
}

/**
 * 注册 WP-Cron 自动后台定时拉取任务（默认每 5 分钟在后台静默刷新一次缓存）
 */
add_filter( 'cron_schedules', 'lyrargon_uptime_cron_intervals' );
function lyrargon_uptime_cron_intervals( $schedules ) {
	if ( ! isset( $schedules['lyrargon_five_minutes'] ) ) {
		$schedules['lyrargon_five_minutes'] = array(
			'interval' => 300,
			'display'  => esc_html__( 'Every 5 Minutes (Lyrargon Uptime)', 'lyrargon' ),
		);
	}
	return $schedules;
}

add_action( 'init', 'lyrargon_uptime_schedule_cron' );
function lyrargon_uptime_schedule_cron() {
	if ( get_option( 'lyrargon_status_enabled' ) === 'true' ) {
		if ( ! wp_next_scheduled( 'lyrargon_uptime_cron_event' ) ) {
			wp_schedule_event( time() + 60, 'lyrargon_five_minutes', 'lyrargon_uptime_cron_event' );
		}
	} else {
		$timestamp = wp_next_scheduled( 'lyrargon_uptime_cron_event' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'lyrargon_uptime_cron_event' );
		}
	}
}

add_action( 'lyrargon_uptime_cron_event', 'lyrargon_uptime_do_cron_fetch' );
function lyrargon_uptime_do_cron_fetch() {
	if ( get_option( 'lyrargon_status_enabled' ) === 'true' ) {
		lyrargon_fetch_uptime_data( true );
	}
}

/**
 * 核心数据抓取与双层缓存逻辑
 * 
 * @param bool $force_refresh 是否强制请求最新数据
 * @return array|WP_Error 返回数据数组或 WP_Error
 */
function lyrargon_fetch_uptime_data( $force_refresh = false ) {
	if ( get_option( 'lyrargon_status_enabled' ) !== 'true' ) {
		return new WP_Error( 'disabled', __( '服务状态监控功能未开启', 'lyrargon' ) );
	}

	$raw_keys = get_option( 'lyrargon_status_apikeys', '' );
	if ( empty( trim( $raw_keys ) ) ) {
		return new WP_Error( 'no_key', __( '未配置 UptimeRobot API Key', 'lyrargon' ) );
	}

	// 支持换行或逗号分隔的多个 API Key
	$raw_keys_arr = preg_split( '/[\r\n,]+/', $raw_keys );
	$api_keys = array();
	foreach ( $raw_keys_arr as $k ) {
		$k = trim( $k );
		if ( ! empty( $k ) ) {
			$api_keys[] = $k;
		}
	}

	if ( empty( $api_keys ) ) {
		return new WP_Error( 'empty_keys', __( '有效 API Key 列表为空', 'lyrargon' ) );
	}

	$days = intval( get_option( 'lyrargon_status_days', 90 ) );
	if ( $days <= 0 || $days > 180 ) {
		$days = 90;
	}

	$cache_time = intval( get_option( 'lyrargon_status_cache_time', 300 ) );
	if ( $cache_time < 30 ) {
		$cache_time = 30; // 最短缓存 30 秒以防频刷
	}

	$custom_endpoint = trim( get_option( 'lyrargon_status_api_endpoint', '' ) );
	$api_endpoint = ! empty( $custom_endpoint ) ? $custom_endpoint : 'https://api.uptimerobot.com/v2/getMonitors';

	$cache_key = 'lyrargon_uptime_' . md5( implode( '|', $api_keys ) . '_' . $days . '_' . $api_endpoint );
	$last_fetch_key = 'lyrargon_uptime_last_fetch_' . md5( implode( '|', $api_keys ) );
	$last_fetch_time = intval( get_transient( $last_fetch_key ) );
	$throttle_seconds = 10; // 10秒冷却时间

	$cached_data = get_transient( $cache_key );

	if ( $force_refresh ) {
		// 处于冷却期内：返回现有缓存并标记 cooling 状态
		if ( ( time() - $last_fetch_time ) < $throttle_seconds && false !== $cached_data && is_array( $cached_data ) ) {
			$cached_data['cached'] = true;
			$cached_data['cooling'] = true;
			$cached_data['cooling_remain'] = $throttle_seconds - ( time() - $last_fetch_time );
			$cached_data['notice_msg'] = __( '刷新过于频繁，数据已是最新（10秒冷却中）', 'lyrargon' );
			return $cached_data;
		}
	} else {
		// 常规请求，有有效 Transient 直接返回
		if ( false !== $cached_data && is_array( $cached_data ) ) {
			$cached_data['cached'] = true;
			$cached_data['notice'] = get_option( 'lyrargon_status_notice', '' );
			$cached_data['show_link'] = ( get_option( 'lyrargon_status_show_link' ) === 'true' );
			return $cached_data;
		}
	}

	// 计算时间戳切片范围
	$now = time();
	$today_start = strtotime( 'today', $now );
	$ranges = array();
	$dates_info = array();

	for ( $d = 0; $d < $days; $d++ ) {
		$d_start = $today_start - ( $d * 86400 );
		$d_end = $d_start + 86400;
		$ranges[] = "{$d_start}_{$d_end}";
		$dates_info[] = date( 'Y-m-d', $d_start );
	}

	$total_start = $today_start - ( ( $days - 1 ) * 86400 );
	$total_end = $today_start + 86400;
	$ranges[] = "{$total_start}_{$total_end}";
	$ranges_str = implode( '-', $ranges );

	$all_monitors = array();
	$request_errors = array();

	foreach ( $api_keys as $key ) {
		$body = array(
			'api_key'              => $key,
			'format'               => 'json',
			'logs'                 => 1,
			'log_types'            => '1-2',
			'logs_start_date'      => $total_start,
			'logs_end_date'        => $total_end,
			'custom_uptime_ranges' => $ranges_str,
		);

		// 设置更合理的超时时间（8秒）
		$response = wp_remote_post( $api_endpoint, array(
			'timeout' => 8,
			'headers' => array(
				'content-type'  => 'application/x-www-form-urlencoded',
				'cache-control' => 'no-cache',
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
			$request_errors[] = $response->get_error_message();
			continue;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( 429 === $status_code ) {
			$request_errors[] = 'UptimeRobot API 频率受限 (429 Too Many Requests)';
			continue;
		}

		if ( empty( $data ) || ! isset( $data['stat'] ) || 'ok' !== $data['stat'] ) {
			$err_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'API 请求未返回成功状态';
			$request_errors[] = $err_msg;
			continue;
		}

		if ( isset( $data['monitors'] ) && is_array( $data['monitors'] ) ) {
			foreach ( $data['monitors'] as $m ) {
				$all_monitors[] = $m;
			}
		}
	}

	if ( empty( $all_monitors ) ) {
		// 如果远程请求失败，优先回退持久化备份缓存，保证前端永远有数据展示
		$persistent_data = get_option( 'lyrargon_uptime_persistent_cache' );
		if ( ! empty( $persistent_data ) && is_array( $persistent_data ) ) {
			$persistent_data['cached'] = true;
			$persistent_data['stale'] = true;
			$persistent_data['notice_msg'] = __( '远程接口响应超时，当前为您展示上一期缓存数据', 'lyrargon' );
			$persistent_data['notice'] = get_option( 'lyrargon_status_notice', '' );
			$persistent_data['show_link'] = ( get_option( 'lyrargon_status_show_link' ) === 'true' );
			return $persistent_data;
		}

		return new WP_Error(
			'fetch_failed',
			! empty( $request_errors ) ? implode( '; ', $request_errors ) : __( '未能从 UptimeRobot 获取到任何监控项目，请检查 API Key 配置或网络连通性', 'lyrargon' )
		);
	}

	$result = array(
		'stat'        => 'ok',
		'monitors'    => $all_monitors,
		'count_days'  => $days,
		'cached'      => false,
		'cached_time' => time(),
		'notice'      => get_option( 'lyrargon_status_notice', '' ),
		'show_link'   => ( get_option( 'lyrargon_status_show_link' ) === 'true' ),
	);

	// 存入 WordPress Transient 短期缓存
	set_transient( $cache_key, $result, $cache_time );
	set_transient( $last_fetch_key, time(), 3600 );
	// 同时存入持久化选项备份（永不过期，除非新数据覆盖）
	update_option( 'lyrargon_uptime_persistent_cache', $result, false );

	return $result;
}

/**
 * 获取状态数据（用于 status.php 服务端预载）
 * 严格只读本地缓存（Transient -> Persistent Option），绝对不在页面同步渲染阶段发起阻塞式外部 HTTP 请求！
 * 实现真正的 0 毫秒首帧直出。
 */
function lyrargon_get_uptime_cached_data() {
	if ( get_option( 'lyrargon_status_enabled' ) !== 'true' ) {
		return null;
	}

	$raw_keys = get_option( 'lyrargon_status_apikeys', '' );
	if ( empty( trim( $raw_keys ) ) ) {
		return null;
	}

	$raw_keys_arr = preg_split( '/[\r\n,]+/', $raw_keys );
	$api_keys = array();
	foreach ( $raw_keys_arr as $k ) {
		$k = trim( $k );
		if ( ! empty( $k ) ) {
			$api_keys[] = $k;
		}
	}

	if ( empty( $api_keys ) ) {
		return null;
	}

	$days = intval( get_option( 'lyrargon_status_days', 90 ) );
	if ( $days <= 0 || $days > 180 ) {
		$days = 90;
	}
	$custom_endpoint = trim( get_option( 'lyrargon_status_api_endpoint', '' ) );
	$api_endpoint = ! empty( $custom_endpoint ) ? $custom_endpoint : 'https://api.uptimerobot.com/v2/getMonitors';

	$cache_key = 'lyrargon_uptime_' . md5( implode( '|', $api_keys ) . '_' . $days . '_' . $api_endpoint );
	$cached_data = get_transient( $cache_key );

	if ( false !== $cached_data && is_array( $cached_data ) ) {
		$cached_data['cached'] = true;
		$cached_data['notice'] = get_option( 'lyrargon_status_notice', '' );
		$cached_data['show_link'] = ( get_option( 'lyrargon_status_show_link' ) === 'true' );
		return $cached_data;
	}

	// 若 Transient 过期，回退到持久化备份缓存（保证 0ms 首屏有数据直出）
	$persistent_data = get_option( 'lyrargon_uptime_persistent_cache' );
	if ( ! empty( $persistent_data ) && is_array( $persistent_data ) ) {
		$persistent_data['cached'] = true;
		$persistent_data['notice'] = get_option( 'lyrargon_status_notice', '' );
		$persistent_data['show_link'] = ( get_option( 'lyrargon_status_show_link' ) === 'true' );
		return $persistent_data;
	}

	return null;
}

/**
 * AJAX 接口处理
 */
function lyrargon_ajax_get_status() {
	$is_refresh = ( isset( $_GET['refresh'] ) && $_GET['refresh'] === '1' );
	$data = lyrargon_fetch_uptime_data( $is_refresh );

	if ( is_wp_error( $data ) ) {
		wp_send_json_error( array(
			'message' => $data->get_error_message()
		), 500 );
	}

	wp_send_json_success( $data );
}

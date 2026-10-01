<?php
/**
 * AI Agent Module
 *
 * 提供 REST API 端点用于 AI 助手功能：
 *   - GET  /lyrargon/v1/agent/posts         获取最近文章
 *   - GET  /lyrargon/v1/agent/shuoshuo       获取最近说说
 *   - GET  /lyrargon/v1/agent/announcement   获取侧边栏公告
 *   - POST /lyrargon/v1/agent/chat           代理调用 OpenAI 兼容 API
 *
 * 内置 IP 时间 QoS 和日总消耗 QoS 限制。
 */

defined( 'ABSPATH' ) || exit;

// ============== QoS 辅助函数 ==============

/**
 * 获取客户端真实 IP（考虑代理）
 */
function lyrargon_agent_get_client_ip() {
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		return trim( $ips[0] );
	}
	if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		return $_SERVER['HTTP_X_REAL_IP'];
	}
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return $_SERVER['REMOTE_ADDR'];
	}
	return '127.0.0.1';
}

/**
 * 检查 IP 时间 QoS（每秒请求数限制）
 *
 * @param int $max_requests 允许的最大请求数
 * @param int $window_seconds 时间窗口（秒）
 * @return array [allowed: bool, remaining: int]
 */
function lyrargon_agent_check_ip_qos( $max_requests = 6, $window_seconds = 60 ) {
	if ( $max_requests <= 0 ) {
		return array( 'allowed' => true, 'remaining' => 999 );
	}
	$ip    = lyrargon_agent_get_client_ip();
	$key   = 'lyr_agent_ip_qos_' . md5( $ip );
	$data  = get_transient( $key );
	if ( false === $data || ! is_array( $data ) ) {
		$data = array();
	}
	$now = time();
	// 清理过期记录
	$data = array_values( array_filter( $data, function ( $t ) use ( $now, $window_seconds ) {
		return $t > $now - $window_seconds;
	} ) );
	$remaining = max( 0, $max_requests - count( $data ) );
	if ( $remaining <= 0 ) {
		return array( 'allowed' => false, 'remaining' => 0 );
	}
	$data[] = $now;
	set_transient( $key, $data, $window_seconds );
	return array( 'allowed' => true, 'remaining' => $remaining );
}

/**
 * 检查日总消耗 QoS。
 * 每日消费计数存储为 WordPress 选项，每天零点自动重置。
 *
 * @param float $estimated_cost 预估消耗（以 token 数为单位，简化为每次请求 = 1 单位）
 * @param int   $daily_limit    每日上限（单位数）
 * @return array [allowed: bool, current: int, limit: int]
 */
function lyrargon_agent_check_daily_qos( $daily_limit = 100 ) {
	if ( $daily_limit <= 0 ) {
		return array( 'allowed' => true, 'current' => 0, 'limit' => 0 );
	}
	$today   = gmdate( 'Y-m-d' );
	$key     = 'lyr_agent_daily_count_' . $today;
	$current = (int) get_option( $key, 0 );
	if ( $current >= $daily_limit ) {
		return array( 'allowed' => false, 'current' => $current, 'limit' => $daily_limit );
	}
	update_option( $key, $current + 1 );
	// 确保每天过期后会重新计数（用 cron 或下次写入时重置）
	if ( $current === 0 ) {
		wp_schedule_single_event( strtotime( 'tomorrow' ) + 60, 'lyrargon_agent_reset_daily_qos' );
	}
	return array( 'allowed' => true, 'current' => $current + 1, 'limit' => $daily_limit );
}

/**
 * 重置日消耗计数器（由 cron 触发）
 */
function lyrargon_agent_reset_daily_qos_action() {
	$today = gmdate( 'Y-m-d' );
	delete_option( 'lyr_agent_daily_count_' . $today );
}
add_action( 'lyrargon_agent_reset_daily_qos', 'lyrargon_agent_reset_daily_qos_action' );

// ============== REST API 回调 ==============

/**
 * 获取最近文章
 */
function lyrargon_agent_rest_posts( $request ) {
	$per_page = min( (int) $request->get_param( 'per_page' ), 50 );
	if ( $per_page <= 0 ) {
		$per_page = 20;
	}
	$query = new WP_Query( array(
		'post_type'           => array( 'post' ),
		'posts_per_page'      => $per_page,
		'post_status'         => 'publish',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	) );
	$results = array();
	foreach ( $query->posts as $post ) {
		$results[] = array(
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'excerpt' => wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 60, '...' ),
			'date'    => $post->post_date,
		);
	}
	return new WP_REST_Response( $results, 200 );
}

/**
 * 获取最近说说
 */
function lyrargon_agent_rest_shuoshuo( $request ) {
	$per_page = min( (int) $request->get_param( 'per_page' ), 50 );
	if ( $per_page <= 0 ) {
		$per_page = 20;
	}
	$query = new WP_Query( array(
		'post_type'           => array( 'shuoshuo' ),
		'posts_per_page'      => $per_page,
		'post_status'         => 'publish',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	) );
	$results = array();
	foreach ( $query->posts as $post ) {
		$results[] = array(
			'id'      => $post->ID,
			'content' => wp_strip_all_tags( $post->post_content ),
			'date'    => $post->post_date,
		);
	}
	return new WP_REST_Response( $results, 200 );
}

/**
 * 获取侧边栏公告
 */
function lyrargon_agent_rest_announcement() {
	$content = get_option( 'lyrargon_sidebar_announcement', '' );
	return new WP_REST_Response( array(
		'content' => $content,
	), 200 );
}

/**
 * 构建知识库上下文文本
 */
function lyrargon_agent_build_context() {
	$parts = array();

	// 公告
	$announcement = get_option( 'lyrargon_sidebar_announcement', '' );
	if ( ! empty( $announcement ) ) {
		$parts[] = "=== 博客公告 ===\n" . wp_strip_all_tags( $announcement ) . "\n";
	}

	// 最近文章
	$post_count = (int) get_option( 'lyrargon_agent_context_post_count', 10 );
	if ( $post_count < 1 ) {
		$post_count = 10;
	}
	$posts = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => $post_count,
		'post_status'    => 'publish',
	) );
	if ( ! empty( $posts ) ) {
		$lines = array();
		foreach ( $posts as $p ) {
			$lines[] = '- ' . get_the_title( $p ) . ' | ' . get_permalink( $p );
		}
		$parts[] = "=== 最近文章 ===\n" . implode( "\n", $lines ) . "\n";
	}

	// 最近说说
	$shuoshuo_count = (int) get_option( 'lyrargon_agent_context_shuoshuo_count', 10 );
	if ( $shuoshuo_count < 1 ) {
		$shuoshuo_count = 10;
	}
	$shuoshuo = get_posts( array(
		'post_type'      => 'shuoshuo',
		'posts_per_page' => $shuoshuo_count,
		'post_status'    => 'publish',
	) );
	if ( ! empty( $shuoshuo ) ) {
		$lines = array();
		foreach ( $shuoshuo as $s ) {
			$lines[] = '- ' . wp_strip_all_tags( $s->post_content );
		}
		$parts[] = "=== 最近说说 ===\n" . implode( "\n", $lines ) . "\n";
	}

	return implode( "\n", $parts );
}

/**
 * 搜索博客内容（用于 Agent 搜索功能）
 */
function lyrargon_agent_search_content( $term ) {
	if ( empty( trim( $term ) ) ) {
		return array();
	}
	$types = array( 'post', 'shuoshuo' );
	$query = new WP_Query( array(
		'post_type'           => $types,
		's'                   => $term,
		'posts_per_page'      => 8,
		'post_status'         => 'publish',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	) );
	$results = array();
	foreach ( $query->posts as $post ) {
		$results[] = array(
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'type'    => $post->post_type,
			'excerpt' => wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 40, '...' ),
		);
	}
	return $results;
}

/**
 * 代理调用 OpenAI 兼容 API（chat/completions）
 */
function lyrargon_agent_rest_chat( $request ) {
	// --- QoS 检查 ---
	$ip_limit      = (int) get_option( 'lyrargon_agent_ip_qos_limit', 30 );
	$ip_window     = (int) get_option( 'lyrargon_agent_ip_qos_window', 60 );
	$daily_limit   = (int) get_option( 'lyrargon_agent_daily_qos_limit', 200 );

	$ip_check = lyrargon_agent_check_ip_qos( $ip_limit, $ip_window );
	if ( ! $ip_check['allowed'] ) {
		return new WP_REST_Response( array(
			'error'   => 'rate_limited',
			'message' => __( '请求过于频繁，请稍后再试', 'lyrargon' ),
			'retry_after_seconds' => 60,
		), 429 );
	}

	$daily_check = lyrargon_agent_check_daily_qos( $daily_limit );
	if ( ! $daily_check['allowed'] ) {
		return new WP_REST_Response( array(
			'error'   => 'daily_limit_reached',
			'message' => __( '今日 API 调用次数已达上限', 'lyrargon' ),
		), 429 );
	}

	// --- 读取配置 ---
	$api_endpoint = get_option( 'lyrargon_agent_api_endpoint', 'https://api.deepseek.com/v1/chat/completions' );
	$api_key      = get_option( 'lyrargon_agent_api_key', '' );
	$model        = get_option( 'lyrargon_agent_model', 'deepseek-chat' );
	$temperature  = (float) get_option( 'lyrargon_agent_temperature', '0.7' );
	$max_tokens   = (int) get_option( 'lyrargon_agent_max_tokens', '2048' );
	$system_prompt_template = get_option( 'lyrargon_agent_system_prompt',
		'你是一个博客助手，基于以下博客内容回答用户问题。请从博客内容中查找相关信息并给出准确回答。如果找不到相关信息，请如实告知。回答使用 Markdown 格式，文章链接请用 [标题](URL) 形式展示。'
	);

	if ( empty( $api_key ) ) {
		return new WP_REST_Response( array(
			'error'   => 'not_configured',
			'message' => __( 'AI Agent 尚未配置 API Key', 'lyrargon' ),
		), 503 );
	}

	// --- 解析请求 ---
	$body_json = $request->get_body();
	$body      = json_decode( $body_json, true );
	if ( empty( $body ) || ! isset( $body['message'] ) ) {
		return new WP_REST_Response( array(
			'error'   => 'invalid_request',
			'message' => __( '请求参数无效', 'lyrargon' ),
		), 400 );
	}

	$user_message   = trim( $body['message'] );
	$search_enabled = get_option( 'lyrargon_agent_search_enabled', 'true' ) === 'true';
	$context        = '';

	// 获取知识库上下文
	if ( $search_enabled ) {
		$context = lyrargon_agent_build_context();
	}

	// 如有搜索词，额外搜索
	$search_results = array();
	if ( $search_enabled && strlen( $user_message ) > 1 ) {
		$search_results = lyrargon_agent_search_content( $user_message );
	}
	if ( ! empty( $search_results ) ) {
		$search_text = "=== 搜索结果 ===\n";
		foreach ( $search_results as $r ) {
			$search_text .= "- [{$r['title']}]({$r['url']}) ({$r['type']}): {$r['excerpt']}\n";
		}
		$context .= "\n" . $search_text;
	}

	// 构建 System Prompt
	$system_content = $system_prompt_template;
	if ( ! empty( $context ) ) {
		$system_content .= "\n\n以下是博客当前的内容，请基于此回答：\n" . $context;
	}

	// --- 构建请求消息 ---
	$messages = array(
		array( 'role' => 'system', 'content' => $system_content ),
	);
	if ( ! empty( $body['history'] ) && is_array( $body['history'] ) ) {
		foreach ( $body['history'] as $msg ) {
			if ( isset( $msg['role'], $msg['content'] ) ) {
				$messages[] = array(
					'role'    => $msg['role'],
					'content' => $msg['content'],
				);
			}
		}
	}
	$messages[] = array( 'role' => 'user', 'content' => $user_message );

	// --- 调用 AI API ---
	$request_args = array(
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		'body'    => wp_json_encode( array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
			'stream'      => false,
		) ),
		'timeout' => 60,
	);

	$response = wp_remote_post( $api_endpoint, $request_args );

	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response( array(
			'error'   => 'api_error',
			'message' => $response->get_error_message(),
		), 502 );
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$response_data = json_decode( $response_body, true );

	if ( $response_code !== 200 || empty( $response_data ) ) {
		return new WP_REST_Response( array(
			'error'         => 'api_error',
			'message'       => isset( $response_data['error']['message'] ) ? $response_data['error']['message'] : __( 'AI API 返回错误', 'lyrargon' ),
			'response_code' => $response_code,
		), 502 );
	}

	$reply = '';
	if ( isset( $response_data['choices'][0]['message']['content'] ) ) {
		$reply = $response_data['choices'][0]['message']['content'];
	}

	return new WP_REST_Response( array(
		'reply'     => $reply,
		'qos'       => array(
			'ip_remaining'      => $ip_check['remaining'],
			'daily_current'     => $daily_check['current'],
			'daily_limit'       => $daily_check['limit'],
		),
	), 200 );
}

// ============== 注册 REST API 路由 ==============

add_action( 'rest_api_init', function () {
	// 获取文章
	register_rest_route( 'lyrargon/v1', '/agent/posts', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'lyrargon_agent_rest_posts',
		'permission_callback' => '__return_true',
		'args'                => array(
			'per_page' => array(
				'required'          => false,
				'sanitize_callback' => 'absint',
				'default'           => 20,
			),
		),
	) );

	// 获取说说
	register_rest_route( 'lyrargon/v1', '/agent/shuoshuo', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'lyrargon_agent_rest_shuoshuo',
		'permission_callback' => '__return_true',
		'args'                => array(
			'per_page' => array(
				'required'          => false,
				'sanitize_callback' => 'absint',
				'default'           => 20,
			),
		),
	) );

	// 获取公告
	register_rest_route( 'lyrargon/v1', '/agent/announcement', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'lyrargon_agent_rest_announcement',
		'permission_callback' => '__return_true',
	) );

	// 聊天代理（POST）
	register_rest_route( 'lyrargon/v1', '/agent/chat', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'lyrargon_agent_rest_chat',
		'permission_callback' => '__return_true',
	) );
} );

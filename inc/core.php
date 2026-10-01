<?php
if (version_compare( $GLOBALS['wp_version'], '4.4-alpha', '<' )) {
	echo "<div style='background: #2196f3;color: #fff;font-size: 30px;padding: 50px 30px;position: fixed;width: 100%;left: 0;right: 0;bottom: 0;z-index: 2147483647;'>" . __("Lyrargon 主题不支持 Wordpress 4.4 以下版本，请更新 Wordpress", 'lyrargon') . "</div>";
}

// ========== 数据库迁移：argon_* → lyrargon_* ==========
// 旧版使用 argon_ 前缀存储选项，新版统一改为 lyrargon_。
// 此函数在主题加载时自动运行，将旧选项复制到新名称下。
function lyrargon_migrate_old_options() {
	$migrated = get_option('lyrargon_db_version');
	if (!empty($migrated)) {
		return; // 已迁移过
	}
	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT option_name, option_value FROM {$wpdb->options}
		 WHERE option_name LIKE 'argon\_%'"
	);
	$count = 0;
	foreach ($rows as $row) {
		$new_name = 'lyrargon_' . substr($row->option_name, 6);
		if (get_option($new_name) === false) {
			update_option($new_name, maybe_unserialize($row->option_value));
			$count++;
		}
	}
	$version = !(wp_get_theme() -> Template) ? wp_get_theme() -> Version : wp_get_theme(wp_get_theme() -> Template) -> Version;
	update_option('lyrargon_db_version', $version);
}
lyrargon_migrate_old_options();

function theme_slug_setup() {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('editor-styles');
	add_editor_style('gutenberg/dist/blocks.editor.build.css');
	load_theme_textdomain('lyrargon', get_template_directory() . '/languages');
	// 手动语言覆盖（设置 - 全局）：强制加载对应 .mo，确保前后台都生效
	$override = get_option('lyrargon_language_override', 'follow');
	if (!empty($override) && $override != 'follow') {
		unload_textdomain('lyrargon');
		$mofile = get_template_directory() . '/languages/lyrargon-' . $override . '.mo';
		if (is_readable($mofile)) {
			load_textdomain('lyrargon', $mofile);
		}
	}
}
add_action('after_setup_theme','theme_slug_setup');

/**
 * 主题版本号访问器（替代 $GLOBALS['theme_version']）
 * 优先取父主题（子主题场景）版本，结果静态缓存以避免重复调用 wp_get_theme()。
 */
if ( ! function_exists( 'lyrargon_theme_version' ) ) {
	function lyrargon_theme_version() {
		static $version = null;
		if ( $version === null ) {
			$current = wp_get_theme();
			$version = $current->Template
				? wp_get_theme( $current->Template )->Version
				: $current->Version;
		}
		return $version;
	}
}

/**
 * 静态资源基址访问器（替代 $GLOBALS['assets_path']）
 * 支持本地、lyra-api CDN、自定义地址三种来源，结果静态缓存。
 */
if ( ! function_exists( 'lyrargon_assets_path' ) ) {
	function lyrargon_assets_path() {
		static $path = null;
		if ( $path === null ) {
			$source = get_option( 'lyrargon_assets_path' );
			switch ( $source ) {
				case 'lyrargon':
					$path = 'https://lyra-api.wenlei.top/lyrargon/v' . lyrargon_theme_version();
					break;
				case 'custom':
					$path = preg_replace( '/\/$/', '', get_option( 'lyrargon_custom_assets_path' ) );
					$path = preg_replace( '/%theme_version%/', lyrargon_theme_version(), $path );
					break;
				default:
					$path = get_bloginfo( 'template_url' );
			}
		}
		return $path;
	}
}

if ( ! function_exists( 'lyrargon_wp_path' ) ) {
	function lyrargon_wp_path() {
		$path = get_option( 'lyrargon_wp_path' );
		return $path == '' ? '/' : $path;
	}
}

// ========== 前端资源入队（统一走 wp_enqueue_scripts 钩子）==========
// 原先在 header.php / footer.php 中直接调用 wp_enqueue_*，现集中到此处，
// 便于依赖管理与条件加载；argonjs 此前在头部与尾部重复入队同一句柄，现仅入队一次并延迟到 footer。
function lyrargon_enqueue_scripts() {
	$assets = lyrargon_assets_path();
	$version = lyrargon_theme_version();

	wp_enqueue_style( 'lyrargon_css_merged', $assets . '/assets/argon_css_merged.css', array(), $version );
	wp_enqueue_style( 'font-awesome-6', $assets . '/assets/vendor/fontawesome-free-7.3.0-web/css/all.min.css', array(), '7.3.0' );
	wp_enqueue_style( 'font-awesome-v4-shims', $assets . '/assets/vendor/fontawesome-free-7.3.0-web/css/v4-shims.min.css', array( 'font-awesome-6' ), '7.3.0' );
	wp_enqueue_style( 'style', $assets . '/style.css', array(), $version );
	if ( get_option( 'lyrargon_disable_googlefont' ) != 'true' ) {
		wp_enqueue_style( 'googlefont', '//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Serif+SC:300,600&display=swap' );
	}

	wp_enqueue_script( 'jquery' );
	wp_add_inline_script( 'jquery', 'window.$ = window.jQuery;', 'after' );
	wp_enqueue_script( 'lyrargon_js_merged', $assets . '/assets/argon_js_merged.js', array( 'jquery' ), $version, false );
	wp_add_inline_script( 'lyrargon_js_merged', 'window.$ = window.jQuery;', 'after' );
	wp_enqueue_script( 'argonjs', $assets . '/assets/js/argon.js', array( 'jquery' ), $version, true );
	wp_enqueue_script( 'lyrargon_theme', $assets . '/argontheme.js', array( 'jquery', 'lyrargon_js_merged', 'argonjs' ), $version, true );
}
add_action( 'wp_enqueue_scripts', 'lyrargon_enqueue_scripts' );

//翻译 Hook
function argon_locate_filter($locate){
	if (substr($locate, 0, 2) == 'zh'){
		if ($locate == 'zh_TW'){
			return $locate;
		}
		return 'zh_CN';
	}
	if (substr($locate, 0, 2) == 'en'){
		return 'en_US';
	}
	if (substr($locate, 0, 2) == 'ru'){
		return 'ru_RU';
	}
	return 'en_US';
}
function argon_get_locate(){
	// 手动语言覆盖（设置 - 全局）：默认跟随 WordPress
	$override = get_option('lyrargon_language_override', 'follow');
	if (!empty($override) && $override != 'follow'){
		return argon_locate_filter($override);
	}
	if (function_exists("determine_locale")){
		return argon_locate_filter(determine_locale());
	}
	$determined_locale = get_locale();
	if (is_admin()){
		$determined_locale = get_user_locale();
	}
	return argon_locate_filter($determined_locale);
}
function theme_locale_hook($locate, $domain){
	if ($domain == 'lyrargon'){
		// 手动语言覆盖（设置 - 全局）：默认跟随 WordPress
		$override = get_option('lyrargon_language_override', 'follow');
		if (!empty($override) && $override != 'follow'){
			return $override;
		}
		return argon_locate_filter($locate);
	}
	return $locate;
}
add_filter('theme_locale', 'theme_locale_hook', 10, 2);

//更新主题版本后的兼容
$argon_last_version = get_option("lyrargon_last_version");
if ($argon_last_version == ""){
	$argon_last_version = "0.0";
}
if (version_compare($argon_last_version, lyrargon_theme_version(), '<' )){
	if (version_compare($argon_last_version, '0.940', '<')){
		if (get_option('lyrargon_mathjax_v2_enable') == 'true' && get_option('lyrargon_mathjax_enable') != 'true'){
			update_option("lyrargon_math_render", 'mathjax2');
		}
		if (get_option('lyrargon_mathjax_enable') == 'true'){
			update_option("lyrargon_math_render", 'mathjax3');
		}
	}
	if (version_compare($argon_last_version, '0.970', '<')){
		if (get_option('lyrargon_show_author') == 'true'){
			update_option("lyrargon_article_meta", 'time|views|comments|categories|author');
		}
	}
	if (version_compare($argon_last_version, '1.1.0', '<')){
		if (get_option('lyrargon_enable_zoomify') != 'false'){
			update_option("lyrargon_enable_fancybox", 'true');
			update_option("lyrargon_enable_zoomify", 'false');
		}
	}
	if (version_compare($argon_last_version, '1.3.4', '<')){
		switch (get_option('lyrargon_search_post_filter', 'post,page')){
			case 'post,page':
				update_option("lyrargon_enable_search_filters", 'true');
				update_option("lyrargon_search_filters_type", '*post,*page,shuoshuo');
				break;
			case 'post,page,shuoshuo':
				update_option("lyrargon_enable_search_filters", 'true');
				update_option("lyrargon_search_filters_type", '*post,*page,*shuoshuo');
				break;
			case 'post,page,hide_shuoshuo':
				update_option("lyrargon_enable_search_filters", 'true');
				update_option("lyrargon_search_filters_type", '*post,*page');
				break;
			case 'off':
			default:
				update_option("lyrargon_enable_search_filters", 'false');
				break;
		}		
	}
	if (version_compare($argon_last_version, '2.0.27', '<')){
		/*
		 * 2.0.27 将「毛玻璃模糊特效」(lyrargon_enable_glass_blur) 与
		 * 「卡片毛玻璃效果 (Lite)」(lyrargon_card_blur) 合并为 lyrargon_glass_blur。
		 */
		if (get_option('lyrargon_glass_blur') === false){
			$legacy_global = get_option('lyrargon_enable_glass_blur', 'always');
			$legacy_card = get_option('lyrargon_card_blur');
			if ($legacy_global === 'disabled' || $legacy_global === 'false'){
				$merged_glass_blur = 'disabled';
			} else if ($legacy_global === 'desktop_only' || $legacy_global === 'no_mobile'){
				$merged_glass_blur = 'desktop_only';
			} else if (in_array($legacy_card, array('weak', 'medium', 'strong'), true)){
				$merged_glass_blur = $legacy_card;
			} else {
				$merged_glass_blur = 'medium';
			}
			update_option("lyrargon_glass_blur", $merged_glass_blur);
		}
	}
	update_option("lyrargon_last_version", lyrargon_theme_version());
}


//检测更新
require_once(get_template_directory() . '/theme-update-checker/plugin-update-checker.php');
$argon_update_source = get_option('lyrargon_update_source');
switch ($argon_update_source) {
	case "stop":
		break;
	case "lyra_api":
		$argonThemeUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://lyra-api.wenlei.top/theme/info.json',
			get_template_directory() . '/functions.php',
			''
		);
		break;
	case "github":
    default:
		$argonThemeUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://raw.githubusercontent.com/Andy17269/lyrargon/main/info.json',
			get_template_directory() . '/functions.php',
			''
		);
}

//初次使用时发送安装量统计信息 (数据仅用于统计安装量)
function post_analytics_info(){
	if(function_exists('file_get_contents')){
		$contexts = stream_context_create(
			array(
				'http' => array(
					'method'=>"GET",
					'header'=>"User-Agent: ArgonTheme\r\n"
				)
			)
		);
		$result = file_get_contents('http://lyra-api.wenlei.top/theme-analytics.php?domain=' . urlencode($_SERVER['HTTP_HOST']) . '&version='. urlencode(lyrargon_theme_version()), false, $contexts);
		update_option('lyrargon_has_inited', 'true');
		return $result;
	}else{
		update_option('lyrargon_has_inited', 'true');
	}
}
//if (get_option('lyrargon_has_inited') != 'true'){
//	post_analytics_info();
//}
//时区修正
if (get_option('lyrargon_enable_timezone_fix') == 'true'){
	date_default_timezone_set('UTC');
}

/**
 * 首次启用主题或全新安装时初始化最佳默认配置
 *
 * 使用 add_option() 确保仅在选项尚不存在时写入，绝不覆盖老用户或已配置站点的已有数据。
 */
function lyrargon_init_default_options() {
	$defaults = array(
		// 外观与主题
		'lyrargon_theme_color'                          => '#2196f3',
		'lyrargon_page_layout'                          => 'double',
		'lyrargon_banner_size'                          => 'full',
		'lyrargon_card_radius'                          => '24',
		'lyrargon_card_shadow'                          => 'default',
		'lyrargon_enable_large_radius'                  => 'true',
		'lyrargon_glass_blur'                           => 'medium',
		'lyrargon_toolbar_blur'                         => 'true',
		'lyrargon_enable_immersion_color'               => 'true',
		'lyrargon_font'                                 => 'sans-serif',
		'lyrargon_assets_path'                          => 'default',
		'lyrargon_dateformat'                           => 'YMD',
		'lyrargon_enable_headroom'                      => 'absolute',
		'lyrargon_page_background_banner_style'         => 'false',
		'lyrargon_banner_background_color_type'         => 'shape-primary',
		'lyrargon_banner_background_hide_shapes'        => 'false',
		'lyrargon_enable_banner_title_typing_effect'    => 'false',
		'lyrargon_banner_typing_effect_interval'        => '150',
		'lyrargon_show_customize_theme_color_picker'    => 'false',
		// 夜间模式
		'lyrargon_darkmode_autoswitch'                  => 'system',
		'lyrargon_enable_amoled_dark'                   => 'false',
		// 侧边栏
		'lyrargon_sidebar_author_mode'                  => 'single',
		// 浮动按钮
		'lyrargon_fab_show_settings_button'             => 'false',
		'lyrargon_fab_show_darkmode_button'             => 'true',
		'lyrargon_fab_show_gotocomment_button'          => 'true',
		// 文章与阅读
		'lyrargon_show_readingtime'                     => 'true',
		'lyrargon_reading_speed'                        => '300',
		'lyrargon_reading_speed_en'                     => '160',
		'lyrargon_reading_speed_code'                   => '20',
		'lyrargon_show_thumbnail_in_banner_in_content_page' => 'false',
		'lyrargon_first_image_as_thumbnail_by_default'  => 'false',
		'lyrargon_reference_list_title'                 => '',
		'lyrargon_show_sharebtn'                        => 'true',
		'lyrargon_share_platforms'                      => 'wechat|douban|qq|qzone|weibo|facebook|twitter|telegram|copy',
		'lyrargon_show_headindex_number'                => 'false',
		'lyrargon_related_post'                         => 'disabled',
		'lyrargon_related_post_sort_orderby'            => 'date',
		'lyrargon_related_post_sort_order'              => 'DESC',
		'lyrargon_related_post_limit'                   => '10',
		'lyrargon_article_header_style'                 => 'article-header-style-default',
		'lyrargon_article_meta'                         => 'time|views|comments|categories',
		'lyrargon_article_list_waterflow'               => '1',
		'lyrargon_trim_words_count'                     => '175',
		'lyrargon_outdated_info_time_type'              => 'modifiedtime',
		'lyrargon_outdated_info_days'                   => '-1',
		'lyrargon_outdated_info_tip_type'               => 'inpost',
		'lyrargon_archives_timeline_show_month'         => 'true',
		// 功能扩展
		'lyrargon_enable_code_highlight'                => 'true',
		'lyrargon_code_theme'                           => 'vs2015',
		'lyrargon_code_highlight_hide_linenumber'       => 'false',
		'lyrargon_code_highlight_break_line'            => 'false',
		'lyrargon_code_highlight_transparent_linenumber'=> 'false',
		'lyrargon_math_render'                          => 'none',
		'lyrargon_enable_lazyload'                      => 'true',
		'lyrargon_lazyload_threshold'                   => '800',
		'lyrargon_lazyload_effect'                      => 'fadeIn',
		'lyrargon_lazyload_loading_style'               => '1',
		'lyrargon_enable_fancybox'                      => 'true',
		'lyrargon_enable_zoomify'                       => 'false',
		'lyrargon_enable_pangu'                         => 'false',
		'lyrargon_enable_smoothscroll_type'             => '1',
		'lyrargon_enable_into_article_animation'        => 'false',
		'lyrargon_disable_pjax_animation'               => 'false',
		'lyrargon_pjax_disabled'                        => 'false',
		// 评论
		'lyrargon_comment_pagination_type'              => 'feed',
		'lyrargon_comment_emotion_keyboard'             => 'true',
		'lyrargon_hide_name_email_site_input'           => 'false',
		'lyrargon_comment_need_captcha'                 => 'true',
		'lyrargon_get_captcha_by_ajax'                  => 'false',
		'lyrargon_comment_allow_markdown'               => 'true',
		'lyrargon_comment_allow_editing'                => 'true',
		'lyrargon_comment_allow_privatemode'            => 'false',
		'lyrargon_comment_allow_mailnotice'             => 'false',
		'lyrargon_comment_enable_qq_avatar'             => 'false',
		'lyrargon_comment_avatar_vcenter'               => 'false',
		'lyrargon_who_can_visit_comment_edit_history'   => 'admin',
		'lyrargon_enable_comment_pinning'               => 'false',
		'lyrargon_enable_comment_upvote'                => 'false',
		'lyrargon_comment_ua'                           => 'hidden',
		'lyrargon_show_comment_parent_info'             => 'true',
		'lyrargon_fold_long_comments'                   => 'false',
		'lyrargon_text_gravatar'                        => 'false',
		// 搜索与杂项
		'lyrargon_enable_search_filters'                => 'true',
		'lyrargon_search_filters_type'                  => '*post,*page,shuoshuo',
		'lyrargon_enable_login_css'                     => 'false',
		'lyrargon_home_show_shuoshuo'                   => 'false',
		'lyrargon_fold_long_shuoshuo'                   => 'false',
		'lyrargon_enable_timezone_fix'                  => 'false',
		'lyrargon_hide_shortcode_in_preview'            => 'false',
		'lyrargon_enable_mobile_scale'                  => 'false',
		'lyrargon_disable_googlefont'                   => 'false',
		'lyrargon_disable_codeblock_style'              => 'false',
		'lyrargon_update_source'                        => 'lyra_api',
		'lyrargon_hide_footer_author'                   => 'false',
		'lyrargon_language_override'                    => 'follow',
		'lyrargon_status_enabled'                       => 'false',
		'lyrargon_status_days'                          => '90',
		'lyrargon_status_cache_time'                    => '300',
	);

	foreach ( $defaults as $option_name => $option_value ) {
		add_option( $option_name, $option_value );
	}
}
add_action( 'after_switch_theme', 'lyrargon_init_default_options' );

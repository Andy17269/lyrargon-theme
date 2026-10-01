<?php
//Gutenberg 编辑器区块
function argon_init_gutenberg_blocks() {
	$dist = get_template_directory();
	wp_register_script(
		'argon-gutenberg-block-js',
		lyrargon_assets_path().'/gutenberg/dist/blocks.build.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-block-editor', 'wp-components'),
		filemtime($dist . '/gutenberg/dist/blocks.build.js'),
		true
	);
	wp_register_style(
		'argon-gutenberg-block-backend-css',
		lyrargon_assets_path().'/gutenberg/dist/blocks.editor.build.css',
		array('wp-edit-blocks'),
		filemtime($dist . '/gutenberg/dist/blocks.editor.build.css')
	);
	// 区块前端样式（在前台渲染区块时由 WordPress 自动入队）
	wp_register_style(
		'argon-gutenberg-block-frontend-css',
		lyrargon_assets_path().'/gutenberg/dist/blocks.style.build.css',
		array(),
		filemtime($dist . '/gutenberg/dist/blocks.style.build.css')
	);
	register_block_type(
		'argon/argon-gutenberg-block', array(
			'style'         => 'argon-gutenberg-block-frontend-css',
			'editor_script' => 'argon-gutenberg-block-js',
			'editor_style'  => 'argon-gutenberg-block-backend-css',
		)
	);

	// 让区块编辑器 JS 使用主题文本域 lyrargon 的翻译
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'argon-gutenberg-block-js', 'lyrargon', $dist . '/languages' );
	}

	function argon_render_gutenberg_shortcode_block($attributes, $content, $block) {
		$block_name = str_replace('argon/', '', $block->name);
		if ($block_name == 'sfriendlinks') {
			return shortcode_friend_link_simple($attributes);
		} else if ($block_name == 'friendlinks') {
			return shortcode_friend_link($attributes);
		} else if ($block_name == 'video') {
			return shortcode_video($attributes);
		}
		return '';
	}

	register_block_type('argon/friendlinks', array(
		'render_callback' => 'argon_render_gutenberg_shortcode_block',
		'style' => 'argon-gutenberg-block-frontend-css',
		'supports' => array('inserter' => false),
	));
	register_block_type('argon/sfriendlinks', array(
		'render_callback' => 'argon_render_gutenberg_shortcode_block',
		'style' => 'argon-gutenberg-block-frontend-css',
		'supports' => array('inserter' => false),
	));
	register_block_type('argon/video', array('render_callback' => 'argon_render_gutenberg_shortcode_block', 'style' => 'argon-gutenberg-block-frontend-css'));
}
add_action('init', 'argon_init_gutenberg_blocks');

// 前端渲染时入队区块样式，覆盖以 JS 注册的区块（其样式在构建产物中声明）
function lyrargon_gutenberg_enqueue_frontend_styles() {
	if ( is_admin() || ! is_singular() ) {
		return;
	}
	wp_enqueue_style('argon-gutenberg-block-frontend-css');
}
add_action('wp_enqueue_scripts', 'lyrargon_gutenberg_enqueue_frontend_styles');

function argon_enqueue_extra_blocks() {
	$assets = lyrargon_assets_path();
	$dist = get_template_directory();

	// 1. 图标字体
	wp_enqueue_style(
		'argon-gutenberg-fontawesome',
		$assets . '/assets/vendor/fontawesome-free-7.3.0-web/css/all.min.css',
		array(),
		'7.3.0'
	);
	wp_enqueue_style(
		'argon-gutenberg-fontawesome-v4shims',
		$assets . '/assets/vendor/fontawesome-free-7.3.0-web/css/v4-shims.min.css',
		array('argon-gutenberg-fontawesome'),
		'7.3.0'
	);
	wp_enqueue_style(
		'argon-gutenberg-nucleo',
		$assets . '/assets/vendor/nucleo/css/nucleo.css',
		array(),
		'1.0.0'
	);

	// 2. 编辑器区块样式
	wp_enqueue_style('argon-gutenberg-block-backend-css');

	// 3. 动态注入主题色与圆角 CSS 变量（兼容 iframe 与非 iframe 渲染模式）
	$themecolor = get_option("lyrargon_theme_color", "#2196f3");
	if (!function_exists('hexstr2rgb')) {
		require_once get_template_directory() . '/inc/helpers.php';
	}
	$RGB = function_exists('hexstr2rgb') ? hexstr2rgb($themecolor) : array('R' => 33, 'G' => 150, 'B' => 243);
	$HSL = function_exists('rgb2hsl') ? rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']) : array('H' => 207, 'S' => 90, 'L' => 54);
	$rgb_str = $RGB['R'] . ', ' . $RGB['G'] . ', ' . $RGB['B'];
	$color_light = function_exists('hsl2hex') ? hsl2hex($HSL['H'], $HSL['S'], min(100, $HSL['L'] + 10)) : '#42a5f5';
	$color_dark = function_exists('hsl2hex') ? hsl2hex($HSL['H'], $HSL['S'], max(0, $HSL['L'] - 10)) : '#1e88e5';
	$color_dark2 = function_exists('hsl2hex') ? hsl2hex($HSL['H'], $HSL['S'], max(0, $HSL['L'] - 20)) : '#1976d2';

	$cardradius = get_option('lyrargon_card_radius', '4');
	if (empty($cardradius)) {
		$cardradius = '4';
	}
	if (isset($_COOKIE['lyrargon_card_radius']) && !empty($_COOKIE['lyrargon_card_radius'])) {
		$cardradius = $_COOKIE['lyrargon_card_radius'];
	}
	$card_radius_val = ($cardradius === '0' || $cardradius === 0) ? '0px' : "{$cardradius}px";
	if (get_option('lyrargon_enable_large_radius') == 'true') {
		$card_radius_val = '22px';
	}

	$custom_css = "
		:root, body, .editor-styles-wrapper, .block-editor-block-list__layout {
			--themecolor: {$themecolor} !important;
			--themecolor-R: {$RGB['R']} !important;
			--themecolor-G: {$RGB['G']} !important;
			--themecolor-B: {$RGB['B']} !important;
			--themecolor-rgbstr: {$rgb_str} !important;
			--themecolor-rgb: {$rgb_str} !important;
			--themecolor-H: {$HSL['H']} !important;
			--themecolor-S: {$HSL['S']} !important;
			--themecolor-L: {$HSL['L']} !important;
			--themecolor-light: {$color_light} !important;
			--themecolor-dark: {$color_dark} !important;
			--themecolor-dark2: {$color_dark2} !important;
			--themecolor-gradient: linear-gradient(150deg, {$color_light} 15%, {$themecolor} 70%, {$color_dark} 94%) !important;
			--card-radius: {$card_radius_val} !important;
		}
		.alert, .admonition, .collapse-block, .github-info-card, .argon-timeline-card {
			border-radius: var(--card-radius, {$card_radius_val}) !important;
		}
	";
	wp_add_inline_style('argon-gutenberg-block-backend-css', $custom_css);

	// 4. 注册并入队扩展区块 JS
	wp_enqueue_script(
		'argon-gutenberg-extra-blocks-js',
		$assets . '/gutenberg/dist/extra-blocks.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'argon-gutenberg-block-js' ),
		filemtime($dist . '/gutenberg/dist/extra-blocks.js'),
		true
	);
}
add_action('enqueue_block_editor_assets', 'argon_enqueue_extra_blocks');

function argon_add_gutenberg_category($block_categories, $editor_context) {
	if (!empty($editor_context->post)){
		array_push(
			$block_categories,
			array(
				'slug'  => 'argon',
				'title' => 'Lyrargon 区块',
				'icon'  => null,
			),
			array(
				'slug'  => 'lyrargon',
				'title' => 'Lyrargon',
				'icon'  => null,
			)
		);
	}
	return $block_categories;
}
add_filter('block_categories_all', 'argon_add_gutenberg_category', 10, 2);
function argon_admin_i18n_info(){
	echo "<script>var argon_language = '" . argon_get_locate() . "';</script>";
}
add_filter('admin_head', 'argon_admin_i18n_info');

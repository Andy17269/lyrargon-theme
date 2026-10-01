<!DOCTYPE html>
<?php
	$htmlclasses = "";
	if (get_option('lyrargon_page_layout') == "single"){
		$htmlclasses .= "single-column ";
	}
	if (get_option('lyrargon_page_layout') == "triple"){
		$htmlclasses .= "triple-column ";
	}
	if (get_option('lyrargon_page_layout') == "double-reverse"){
		$htmlclasses .= "double-column-reverse ";
	}
	if (get_option('lyrargon_enable_immersion_color') == "true"){
		$htmlclasses .= "immersion-color ";
	}
	if (get_option('lyrargon_enable_amoled_dark') == "true"){
		$htmlclasses .= "amoled-dark ";
	}
	if (get_option('lyrargon_card_shadow') == 'big'){
		$htmlclasses .= 'use-big-shadow ';
	}
	if (get_option('lyrargon_font') == 'serif'){
		$htmlclasses .= 'use-serif ';
	}
	if (get_option('lyrargon_disable_codeblock_style') == 'true'){
		$htmlclasses .= 'disable-codeblock-style ';
	}
	if (get_option('lyrargon_enable_headroom') == 'absolute'){
		$htmlclasses .= 'navbar-absolute ';
	}
	$banner_size = get_option('lyrargon_banner_size', 'full');
	if ($banner_size != 'full'){
		if ($banner_size == 'mini'){
			$htmlclasses .= 'banner-mini ';
		}else if ($banner_size == 'hide'){
			$htmlclasses .= 'no-banner ';
		}else if ($banner_size == 'fullscreen'){
			$htmlclasses .= 'banner-as-cover ';
		}
	}
	if (get_option('lyrargon_toolbar_blur', 'false') == 'true'){
		$htmlclasses .= 'toolbar-blur ';
	}
	/* 毛玻璃材质统一强度（合并原「毛玻璃模糊特效」与「卡片毛玻璃效果 (Lite)」） */
	$glass_blur = lyrargon_get_glass_blur_level();
	$glass_blur_scale = lyrargon_glass_blur_scale($glass_blur);
	if ($glass_blur == 'disabled') {
		$htmlclasses .= 'glass-blur-disabled ';
	} else if ($glass_blur == 'desktop_only') {
		$htmlclasses .= 'glass-blur-enabled glass-blur-desktop-only ';
	} else {
		$htmlclasses .= 'glass-blur-enabled glass-blur-' . $glass_blur . ' ';
	}
	$htmlclasses .= get_option('lyrargon_article_header_style', 'article-header-style-default') . ' ';
	/* using-safari 类改由客户端 JS 探测（见下方脚本），避免依赖服务端 UA 判断导致缓存/伪造问题 */
?>
<html <?php language_attributes(); ?> class="no-js <?php echo $htmlclasses;?>" style="--glass-blur-scale: <?php echo esc_attr($glass_blur_scale); ?>;">
<?php
	$themecolor = get_option("lyrargon_theme_color", "#2196f3");
	$themecolor_origin = $themecolor;
	$custom_color_cookie = isset($_COOKIE["lyrargon_custom_theme_color"]) ? $_COOKIE["lyrargon_custom_theme_color"] : (isset($_COOKIE["argon_custom_theme_color"]) ? $_COOKIE["argon_custom_theme_color"] : null);
	if ($custom_color_cookie && checkHEX($custom_color_cookie) && get_option('lyrargon_show_customize_theme_color_picker', 'false') === 'true'){
		$themecolor = $custom_color_cookie;
	}
	if (hex2gray($themecolor) < 50){
		echo '<script>document.getElementsByTagName("html")[0].classList.add("themecolor-toodark");</script>';
	}
?>
<?php
	$cardradius = get_option('lyrargon_card_radius', '24');
	if ($cardradius == ""){
		$cardradius = "24";
	}
	$cardradius_origin = $cardradius;
	if (isset($_COOKIE["lyrargon_card_radius"]) && $_COOKIE["lyrargon_card_radius"] != ""){
		$cardradius = $_COOKIE["lyrargon_card_radius"];
	}
?>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php if (get_option('lyrargon_enable_mobile_scale') != 'true'){ ?>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<?php }else{ ?>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
	<?php } ?>
	<meta property="og:title" content="<?php echo wp_get_document_title();?>">
	<meta property="og:type" content="article">
	<meta property="og:url" content="<?php echo home_url(add_query_arg(array(),$wp->request));?>">
	<?php
		$seo_description = get_seo_description();
		if ($seo_description != ''){ ?>
			<meta name="description" content="<?php echo $seo_description?>">
			<meta property="og:description" content="<?php echo $seo_description?>">
	<?php } ?>

	<?php
		$seo_keywords = get_seo_keywords();
		if ($seo_keywords != ''){ ?>
			<meta name="keywords" content="<?php echo get_seo_keywords();?>">
	<?php } ?>

	<?php
		if (is_single() || is_page()){
			$og_image = get_og_image();
			if ($og_image != ''){ ?>
				<meta property="og:image" content="<?php echo $og_image?>" />
	<?php 	}
		} ?>

	<meta name="theme-color" content="<?php echo $themecolor; ?>">
	<meta name="theme-color-rgb" content="<?php echo hex2str($themecolor); ?>">
	<meta name="theme-color-origin" content="<?php echo $themecolor_origin; ?>">
	<meta name="argon-enable-custom-theme-color" content="<?php echo (get_option('lyrargon_show_customize_theme_color_picker', 'false') === 'true' ? 'true' : 'false'); ?>">


	<meta name="theme-card-radius" content="<?php echo $cardradius; ?>">
	<meta name="theme-card-radius-origin" content="<?php echo $cardradius_origin; ?>">

	<meta name="theme-version" content="<?php echo lyrargon_theme_version(); ?>">

	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php if ( is_singular() && pings_open( get_queried_object() ) ) : ?>
	<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>">
	<?php endif; ?>
	<?php /* 前端样式与脚本统一由 inc/core.php 中的 lyrargon_enqueue_scripts()（wp_enqueue_scripts 钩子）入队 */ ?>
	<meta name="default-banner-bg" content="<?php echo (get_option('lyrargon_banner_background_url') != '') ? esc_url(get_banner_background_url()) : ''; ?>">
	<?php wp_head(); ?>
	<?php /* wp_path 通过 lyrargon_wp_path() 访问 */ ?>
	<script>
		document.documentElement.classList.remove("no-js");
		var argonConfig = {
			wp_path: "<?php echo lyrargon_wp_path(); ?>",
			language: "<?php echo argon_get_locate(); ?>",
			dateFormat: "<?php echo get_option('lyrargon_dateformat', 'YMD'); ?>",
			default_banner_bg: "<?php echo (get_option('lyrargon_banner_background_url') != '') ? esc_url(get_banner_background_url()) : ''; ?>",
			<?php if (get_option('lyrargon_enable_zoomify') == 'true'){ ?>
				zoomify: {
					duration: <?php echo get_option('lyrargon_zoomify_duration', 200); ?>,
					easing: "<?php echo get_option('lyrargon_zoomify_easing', 'cubic-bezier(0.4,0,0,1)'); ?>",
					scale: <?php echo get_option('lyrargon_zoomify_scale', 0.9); ?>
				},
			<?php } else { ?>
				zoomify: false,
			<?php } ?>
			pangu: "<?php echo get_option('lyrargon_enable_pangu', 'false'); ?>",
			<?php if (get_option('lyrargon_enable_lazyload') != 'false'){ ?>
				lazyload: {
					threshold: <?php echo get_option('lyrargon_lazyload_threshold', 800); ?>,
					effect: "<?php echo get_option('lyrargon_lazyload_effect', 'fadeIn'); ?>"
				},
			<?php } else { ?>
				lazyload: false,
			<?php } ?>
			fold_long_comments: <?php echo get_option('lyrargon_fold_long_comments', 'false'); ?>,
			fold_long_shuoshuo: <?php echo get_option('lyrargon_fold_long_shuoshuo', 'false'); ?>,
			disable_pjax: <?php echo get_option('lyrargon_pjax_disabled', 'false'); ?>,
			pjax_animation_durtion: <?php echo (get_option("lyrargon_disable_pjax_animation") == 'true' ? '0' : '600'); ?>,
			headroom: "<?php echo get_option('lyrargon_enable_headroom', 'false'); ?>",
			waterflow_columns: "<?php echo get_option('lyrargon_article_list_waterflow', '1'); ?>",
			code_highlight: {
				enable: <?php echo get_option('lyrargon_enable_code_highlight', 'false'); ?>,
				hide_linenumber: <?php echo get_option('lyrargon_code_highlight_hide_linenumber', 'false'); ?>,
				transparent_linenumber: <?php echo get_option('lyrargon_code_highlight_transparent_linenumber', 'false'); ?>,
				break_line: <?php echo get_option('lyrargon_code_highlight_break_line', 'false'); ?>,
				theme_url: "<?php echo lyrargon_assets_path(); ?>/assets/vendor/highlight/styles/<?php echo get_option('lyrargon_code_theme') == '' ? 'vs2015' : get_option('lyrargon_code_theme'); ?>.css"
			}
		}
	</script>
	<script>
		var darkmodeAutoSwitch = "<?php echo (get_option("lyrargon_darkmode_autoswitch") == '' ? 'false' : get_option("lyrargon_darkmode_autoswitch"));?>";
		function setDarkmode(enable){
			if (enable == true){
				jQuery("html").addClass("darkmode");
			}else{
				jQuery("html").removeClass("darkmode");
			}
			jQuery(window).trigger("scroll");
		}
		function toggleDarkmode(){
			if (jQuery("html").hasClass("darkmode")){
				setDarkmode(false);
				sessionStorage.setItem("Argon_Enable_Dark_Mode", "false");
			}else{
				setDarkmode(true);
				sessionStorage.setItem("Argon_Enable_Dark_Mode", "true");
			}
		}
		if (sessionStorage.getItem("Argon_Enable_Dark_Mode") == "true"){
			setDarkmode(true);
		}
		function toggleDarkmodeByPrefersColorScheme(media){
			if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){
				return;
			}
			if (media.matches){
				setDarkmode(true);
			}else{
				setDarkmode(false);
			}
		}
		function toggleDarkmodeByTime(){
			if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){
				return;
			}
			let hour = new Date().getHours();
			if (<?php echo apply_filters("lyrargon_darkmode_time_check", "hour < 7 || hour >= 22")?>){
				setDarkmode(true);
			}else{
				setDarkmode(false);
			}
		}
		if (darkmodeAutoSwitch == 'system'){
			var darkmodeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
			darkmodeMediaQuery.addListener(toggleDarkmodeByPrefersColorScheme);
			toggleDarkmodeByPrefersColorScheme(darkmodeMediaQuery);
		}
		if (darkmodeAutoSwitch == 'time'){
			toggleDarkmodeByTime();
		}
		if (darkmodeAutoSwitch == 'alwayson'){
			setDarkmode(true);
		}

		function toggleAmoledDarkMode(){
			jQuery("html").toggleClass("amoled-dark");
			if (jQuery("html").hasClass("amoled-dark")){
				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "true");
			}else{
				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "false");
			}
		}
		if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "true"){
			jQuery("html").addClass("amoled-dark");
		}else if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "false"){
			jQuery("html").removeClass("amoled-dark");
		}
	</script>
	<script>
		if (/^((?!chrome|android|crios|fxios|edgios|opios).)*safari/i.test(navigator.userAgent)){
			jQuery("html").addClass("using-safari");
		}
	</script>

	<?php if (get_option('lyrargon_enable_smoothscroll_type') == '2') { /*平滑滚动*/?>
		<script src="<?php echo lyrargon_assets_path(); ?>/assets/vendor/smoothscroll/smoothscroll2.js"></script>
	<?php }else if (get_option('lyrargon_enable_smoothscroll_type') == '3'){?>
		<script src="<?php echo lyrargon_assets_path(); ?>/assets/vendor/smoothscroll/smoothscroll3.min.js"></script>
	<?php }else if (get_option('lyrargon_enable_smoothscroll_type') == '1_pulse'){?>
		<script src="<?php echo lyrargon_assets_path(); ?>/assets/vendor/smoothscroll/smoothscroll1_pulse.js"></script>
	<?php }else if (get_option('lyrargon_enable_smoothscroll_type') != 'disabled'){?>
		<script src="<?php echo lyrargon_assets_path(); ?>/assets/vendor/smoothscroll/smoothscroll1.js"></script>
	<?php }?>
</head>

<?php echo get_option('lyrargon_custom_html_head'); ?>

<style id="themecolor_css">
	<?php
		$themecolor_rgbstr = hex2str($themecolor);
		$RGB = hexstr2rgb($themecolor);
		$HSL = rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']);
	?>
	:root{
		--themecolor: <?php echo $themecolor; ?>;
		--themecolor-R: <?php echo $RGB['R']; ?>;
		--themecolor-G: <?php echo $RGB['G']; ?>;
		--themecolor-B: <?php echo $RGB['B']; ?>;
		--themecolor-H: <?php echo $HSL['H']; ?>;
		--themecolor-S: <?php echo $HSL['S']; ?>;
		--themecolor-L: <?php echo $HSL['L']; ?>;
		--themecolor-rgb: <?php echo $themecolor_rgbstr; ?>;
		--themecolor-rgbstr: <?php echo $themecolor_rgbstr; ?>;
	}
</style>
<style id="theme_cardradius_css">
	:root{
		--card-radius: <?php echo $cardradius; ?>px;
	}
</style>
<?php if (get_option('lyrargon_enable_large_radius') == 'true') { ?>
<style id="theme_large_radius_css">
	:root{
		--card-radius: 22px;
	}
	.btn, .form-control, .custom-select, .post-tag, .tag-cloud-link, .badge, .nav-pills .nav-link, .iziToast, .related-post-card, .post-comment-submit-btn, .post-comment-site-btn, #post_comment_edit_cancel, #mobile_search_input_container .input-group-alternative, .post-comment-reply-cancel-btn {
		border-radius: 50rem !important;
	}
	.input-group > .form-control:not(:last-child),
	.input-group > .custom-select:not(:last-child) {
		border-top-right-radius: 0 !important;
		border-bottom-right-radius: 0 !important;
	}
	.input-group > .form-control:not(:first-child),
	.input-group > .custom-select:not(:first-child) {
		border-top-left-radius: 0 !important;
		border-bottom-left-radius: 0 !important;
	}
	.input-group > .input-group-prepend > .input-group-text,
	.input-group > .input-group-prepend > .btn {
		border-top-left-radius: 50rem !important;
		border-bottom-left-radius: 50rem !important;
		border-top-right-radius: 0 !important;
		border-bottom-right-radius: 0 !important;
	}
	.input-group > .input-group-append > .input-group-text,
	.input-group > .input-group-append > .btn {
		border-top-right-radius: 50rem !important;
		border-bottom-right-radius: 50rem !important;
		border-top-left-radius: 0 !important;
		border-bottom-left-radius: 0 !important;
	}
	.input-group > .input-group-prepend:not(:first-child) > .input-group-text,
	.input-group > .input-group-prepend:not(:first-child) > .btn,
	.input-group > .input-group-append:not(:last-child) > .input-group-text,
	.input-group > .input-group-append:not(:last-child) > .btn {
		border-radius: 0 !important;
	}
	.page-numbers {
		border-radius: 50% !important;
	}
	.page-numbers.next, .page-numbers.prev {
		border-radius: 50rem !important;
	}
</style>
<?php } ?>

<body <?php body_class(); ?>>
<?php /*wp_body_open();*/ ?>
<div id="toolbar">
	<header class="header-global">
		<nav id="navbar-main" class="navbar navbar-main navbar-expand-lg navbar-transparent navbar-light bg-primary headroom--not-bottom headroom--not-top headroom--pinned">
			<div class="container">
				<button id="navbar_mobile_menu_btn" class="navbar-toggler" type="button" aria-expanded="false" aria-label="<?php _e('打开导航菜单', 'lyrargon'); ?>">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="navbar-brand mr-0">
					<?php if (get_option('lyrargon_toolbar_icon') != '') { /*顶栏ICON(如果选项中开启)*/?>
						<a class="navbar-brand navbar-icon mr-lg-5" href="<?php echo get_option('lyrargon_toolbar_icon_link'); ?>">
							<img src="<?php echo get_option('lyrargon_toolbar_icon'); ?>">
						</a>
					<?php }?>
					<?php
						//顶栏标题
						$toolbar_title = get_option('lyrargon_toolbar_title') == '' ? get_bloginfo('name') : get_option('lyrargon_toolbar_title');
						if ($toolbar_title == '--hidden--'){
							$toolbar_title = '';
						}
					?>
					<a class="navbar-brand navbar-title" href="<?php bloginfo('url'); ?>"><?php echo $toolbar_title;?></a>
				</div>
				<div class="navbar-collapse collapse" id="navbar_global">
					<div class="navbar-collapse-header">
						<div class="row" style="display: none;">
							<div class="col-6 collapse-brand"></div>
							<div class="col-6 collapse-close">
								<button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbar_global" aria-controls="navbar_global" aria-expanded="false" aria-label="Toggle navigation">
									<span></span>
									<span></span>
								</button>
							</div>
						</div>
					</div>
					<?php
						/*顶栏菜单*/
						if (!class_exists('toolbarMenuWalker')) {
							class toolbarMenuWalker extends Walker_Nav_Menu{
								public function start_lvl( &$output, $depth = 0, $args = array() ) {
									$indent = str_repeat("\t", $depth);
									$output .= "\n$indent<div class=\"dropdown-menu\">\n";
								}
								public function end_lvl( &$output, $depth = 0, $args = array() ) {
									$indent = str_repeat("\t", $depth);
									$output .= "\n$indent</div>\n";
								}
								public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
									$is_active = ( ! empty( $object->current ) || ! empty( $object->current_item_ancestor ) || ! empty( $object->current_item_parent ) );
									$active_class = $is_active ? ' active' : '';
									$title = apply_filters( 'the_title', $object->title, $object->ID );
									if ($depth == 0){
										if ($args -> walker -> has_children == 1){
											$output .= "\n
											<li class='nav-item dropdown{$active_class}'>
												<a href='" . esc_url( $object -> url ) . "' class='nav-link{$active_class}' data-toggle='dropdown' no-pjax onclick='return false;' title='" . esc_attr( $object -> description ) . "'>
											  		<i class='ni ni-book-bookmark d-lg-none'></i>
													<span class='nav-link-inner--text'>" . $title . "</span>
											  </a>";
										}else{
											$output .= "\n
											<li class='nav-item{$active_class}'>
												<a href='" . esc_url( $object -> url ) . "' class='nav-link{$active_class}' target='" . esc_attr( $object -> target ) . "' title='" . esc_attr( $object -> description ) . "'>
											  		<i class='ni ni-book-bookmark d-lg-none'></i>
													<span class='nav-link-inner--text'>" . $title . "</span>
											  </a>";
										}
									}else if ($depth == 1){
										$output .= "<a href='" . esc_url( $object -> url ) . "' class='dropdown-item{$active_class}' target='" . esc_attr( $object -> target ) . "' title='" . esc_attr( $object -> description ) . "'>" . $title . "</a>";
									}
								}
								public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
									if ($depth == 0){
										$output .= "\n</li>";
									}
								}
							}
						}
						if (!class_exists('mobileMenuWalker')) {
							class mobileMenuWalker extends Walker_Nav_Menu {
								public function start_lvl( &$output, $depth = 0, $args = array() ) {
									$indent = str_repeat("\t", $depth);
									$output .= "\n$indent<ul class=\"mobile-submenu\" style=\"display: none;\">\n";
								}
								public function end_lvl( &$output, $depth = 0, $args = array() ) {
									$indent = str_repeat("\t", $depth);
									$output .= "\n$indent</ul>\n";
								}
								public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
									$has_children = !empty($args->walker->has_children);
									$is_active = ( ! empty( $object->current ) || ! empty( $object->current_item_ancestor ) || ! empty( $object->current_item_parent ) );
									$active_class = $is_active ? ' current active' : '';
									$has_children_class = $has_children ? ' has-children' : '';
									$title = apply_filters( 'the_title', $object->title, $object->ID );
									$output .= "\n<li class='mobile-menu-item{$active_class}{$has_children_class}'>";
									if ($has_children) {
										$output .= "<div class='mobile-menu-item-row'>";
										$output .= "<a href='" . esc_url($object->url) . "' class='mobile-menu-link{$active_class}' target='" . esc_attr($object->target) . "' title='" . esc_attr($object->description) . "'>";
										$output .= "<span class='mobile-menu-text'>" . $title . "</span>";
										$output .= "</a>";
										$output .= "<button type='button' class='mobile-submenu-toggle' aria-label='Toggle submenu'><i class='fa fa-angle-down'></i></button>";
										$output .= "</div>";
									} else {
										$output .= "<a href='" . esc_url($object->url) . "' class='mobile-menu-link{$active_class}' target='" . esc_attr($object->target) . "' title='" . esc_attr($object->description) . "'>";
										$output .= "<span class='mobile-menu-text'>" . $title . "</span>";
										$output .= "</a>";
									}
								}
								public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
									$output .= "</li>\n";
								}
							}
						}
						if ( has_nav_menu('toolbar_menu') ){
							echo "<ul id='toolbar_nav_menu' class='navbar-nav navbar-nav-hover align-items-lg-center'>";
							wp_nav_menu( array(
								'container'  => '',
								'theme_location'  => 'toolbar_menu',
								'items_wrap'  => '%3$s',
								'depth' => 0,
								'walker' => new toolbarMenuWalker()
							) );
							echo "</ul>";
						}
					?>
					<ul class="navbar-nav align-items-lg-center ml-lg-auto">
						<li id="navbar_search_container" class="nav-item" data-toggle="modal">
							<div id="navbar_search_input_container">
								<div class="input-group input-group-alternative">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fa fa-search"></i></span>
									</div>
									<input id="navbar_search_input" class="form-control" placeholder="<?php _e('搜索什么...', 'lyrargon');?>" type="text" autocomplete="off">
								</div>
							</div>
						</li>
					</ul>
				</div>
				<button id="navbar_mobile_search_btn" class="navbar-toggler" type="button" aria-expanded="false" aria-label="<?php _e('打开搜索与站点信息', 'lyrargon'); ?>">
					<span class="navbar-toggler-icon navbar-toggler-searcg-icon"></span>
				</button>
			</div>
		</nav>
	</header>
</div>

<div id="mobile_drawer_mask"></div>

<!-- 移动端主导航菜单抽屉 (左侧滑出) -->
<div id="mobile_menu_drawer" class="mobile-drawer mobile-drawer-left">
	<div class="mobile-drawer-header">
		<div class="mobile-drawer-brand">
			<?php if (get_option('lyrargon_toolbar_icon') != '') { ?>
				<img src="<?php echo get_option('lyrargon_toolbar_icon'); ?>" class="mobile-drawer-logo" alt="Logo">
			<?php } ?>
			<span class="mobile-drawer-title"><?php echo $toolbar_title;?></span>
		</div>
		<button type="button" class="mobile-drawer-close" aria-label="<?php _e('关闭', 'lyrargon'); ?>">
			<i class="fa fa-times"></i>
		</button>
	</div>
	<div class="mobile-drawer-body">
		<div id="mobile_nav_menu_container">
			<div class="mobile-nav-section-title"><?php _e('导航菜单', 'lyrargon'); ?></div>
			<?php
				if ( has_nav_menu('toolbar_menu') ){
					echo "<ul class='mobile-nav-list'>";
					wp_nav_menu( array(
						'container'  => '',
						'theme_location'  => 'toolbar_menu',
						'items_wrap'  => '%3$s',
						'depth' => 0,
						'walker' => new mobileMenuWalker()
					) );
					echo "</ul>";
				}
				if ( has_nav_menu('leftbar_menu') ){
					echo "<div class='mobile-nav-section-title mt-3'>" . __('更多导航', 'lyrargon') . "</div>";
					echo "<ul class='mobile-nav-list'>";
					wp_nav_menu( array(
						'container'  => '',
						'theme_location'  => 'leftbar_menu',
						'items_wrap'  => '%3$s',
						'depth' => 0,
						'walker' => new mobileMenuWalker()
					) );
					echo "</ul>";
				}
			?>
		</div>
	</div>
</div>

<!-- 移动端搜索与站点信息面板 (右侧滑出) -->
<div id="mobile_search_panel" class="mobile-drawer mobile-drawer-right">
	<div class="mobile-drawer-header">
		<div class="mobile-drawer-brand">
			<span class="mobile-drawer-title"><i class="fa fa-search mr-2"></i><?php _e('搜索与站点', 'lyrargon'); ?></span>
		</div>
		<button type="button" class="mobile-drawer-close" aria-label="<?php _e('关闭', 'lyrargon'); ?>">
			<i class="fa fa-times"></i>
		</button>
	</div>
	<div class="mobile-drawer-body">
		<!-- 1. 顶部：搜索框 -->
		<div class="mobile-search-section">
			<div id="mobile_search_input_container">
				<div class="input-group input-group-alternative">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa fa-search"></i></span>
					</div>
					<input id="mobile_search_input" class="form-control" placeholder="<?php _e('搜索什么...', 'lyrargon');?>" type="text" autocomplete="off">
				</div>
			</div>
		</div>

		<!-- 2. 公告卡片（与桌面端一致） -->
		<?php if (get_option('lyrargon_sidebar_announcement') != '') { ?>
			<div id="mobile_announcement" class="card bg-white shadow-sm border-0 mb-3">
				<div class="leftbar-announcement-body">
					<div class="leftbar-announcement-title text-white"><?php _e('公告', 'lyrargon');?></div>
					<div class="leftbar-announcement-content text-white"><?php echo get_option('lyrargon_sidebar_announcement'); ?></div>
				</div>
			</div>
		<?php } ?>

		<!-- 3. 站点概览 & 目录卡片（与桌面端外观与组件结构完全一致） -->
		<div id="mobile_leftbar_part2" class="widget widget_search card bg-white shadow-sm border-0 mb-3">
			<div id="mobile_leftbar_part2_inner" class="card-body">
				<?php
					$mobileActiveTab = 1;
					if (have_catalog()){
						$mobileActiveTab = 0;
					}
				?>
				<div class="nav-wrapper" style="padding-top: 5px;<?php if (!have_catalog()) { echo ' display:none;'; }?>">
	                <ul class="nav nav-pills nav-fill" role="tablist">
						<?php if (have_catalog()) { ?>
							<li class="nav-item sidebar-tab-switcher">
								<a class="<?php if ($mobileActiveTab == 0) { echo 'active show'; }?>" id="mobile_tab_catalog_btn" data-toggle="tab" href="#mobile_tab_catalog" role="tab" aria-controls="mobile_tab_catalog" no-pjax><?php _e('文章目录', 'lyrargon');?></a>
							</li>
						<?php } ?>
						<li class="nav-item sidebar-tab-switcher">
							<a class="<?php if ($mobileActiveTab == 1) { echo 'active show'; }?>" id="mobile_tab_overview_btn" data-toggle="tab" href="#mobile_tab_overview" role="tab" aria-controls="mobile_tab_overview" no-pjax><?php _e('站点概览', 'lyrargon');?></a>
						</li>
	                </ul>
				</div>
				<div>
					<div class="tab-content" style="padding: 10px 10px 0 10px;">
						<?php if (have_catalog()) { ?>
							<div class="tab-pane fade<?php if ($mobileActiveTab == 0) { echo ' active show'; }?>" id="mobile_tab_catalog" role="tabpanel" aria-labelledby="mobile_tab_catalog_btn">
								<div id="mobile_catalog" class="mobile-catalog-content"></div>
							</div>
						<?php } ?>
						<div class="tab-pane fade text-center<?php if ($mobileActiveTab == 1) { echo ' active show'; }?>" id="mobile_tab_overview" role="tabpanel" aria-labelledby="mobile_tab_overview_btn">
							<?php
								$author_mode = get_option('lyrargon_sidebar_author_mode', 'single');
								$default_avatar = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCAxMDAgMTAwIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cmVjdCBmaWxsPSIjNUU3MkU0MjIiIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIi8+PGc+PGcgb3BhY2l0eT0iMC4zIj48cGF0aCBmaWxsPSIjNUU3MkU0IiBkPSJNNzQuMzksMzIuODZjLTAuOTgtMS43LTMuMzktMy4wOS01LjM1LTMuMDlINDUuNjJjLTEuOTYsMC00LjM3LDEuMzktNS4zNSwzLjA5TDI4LjU3LDUzLjE1Yy0wLjk4LDEuNy0wLjk4LDQuNDgsMCw2LjE3bDExLjcxLDIwLjI5YzAuOTgsMS43LDMuMzksMy4wOSw1LjM1LDMuMDloMjMuNDNjMS45NiwwLDQuMzctMS4zOSw1LjM1LTMuMDlMODYuMSw1OS4zMmMwLjk4LTEuNywwLjk4LTQuNDgsMCw2LjE3TDc0LjM5LDMyLjg2eiIvPjwvZz48ZyBvcGFjaXR5PSIwLjgiPjxwYXRoIGZpbGw9IiM1RTcyRTQiIGQ9Ik02Mi4wNCwyMC4zOWMtMC45OC0xLjctMy4zOS0zLjA5LTUuMzUtMy4wOUgzMS43M2MtMS45NiwwLTQuMzcsMS4zOS01LjM1LDMuMDlMMTMuOSw0Mi4wMWMtMC45OCwxLjctMC45OCw0LjQ4LDAsNi4xN2wxMi40OSwyMS42MmMwLjk4LDEuNywzLjM5LDMuMDksNS4zNSwzLjA5aDI0Ljk3YzEuOTYsMCw0LjM3LTEuMzksNS4zNS0zLjA5bDEyLjQ5LTIxLjYyYzAuOTgtMS43LDAuOTgtNC40OCwwLTYuMTdMNjIuMDQsMjAuMzl6Ii8+PC9nPjwvZz48L3N2Zz4=';
								$author1_name = get_option('lyrargon_sidebar_auther_name') == '' ? get_bloginfo('name') : get_option('lyrargon_sidebar_auther_name');
								$author1_image = get_option('lyrargon_sidebar_auther_image') == '' ? $default_avatar : get_option('lyrargon_sidebar_auther_image');
								$author1_desc = get_option('lyrargon_sidebar_author_description', '');

								$author2_name = get_option('lyrargon_sidebar_author2_name', '');
								$author2_image = get_option('lyrargon_sidebar_author2_image', '') == '' ? $default_avatar : get_option('lyrargon_sidebar_author2_image');
								$author2_desc = get_option('lyrargon_sidebar_author2_description', '');

								$initial_author = wp_rand(0, 1) === 1 ? 2 : 1;
								$init_name = ($initial_author === 1) ? $author1_name : $author2_name;
								$init_image = ($initial_author === 1) ? $author1_image : $author2_image;
								$init_desc = ($initial_author === 1) ? $author1_desc : $author2_desc;
								$badge_name = ($initial_author === 1) ? $author2_name : $author1_name;
								$badge_image = ($initial_author === 1) ? $author2_image : $author1_image;
							?>
							<?php if ($author_mode == 'dual') { ?>
								<div class="lyra-dual-avatar-wrap active-author-<?php echo $initial_author; ?>" 
									data-author1-name="<?php echo esc_attr($author1_name); ?>" 
									data-author1-desc="<?php echo esc_attr($author1_desc); ?>" 
									data-author1-image="<?php echo esc_url($author1_image); ?>"
									data-author2-name="<?php echo esc_attr($author2_name); ?>" 
									data-author2-desc="<?php echo esc_attr($author2_desc); ?>"
									data-author2-image="<?php echo esc_url($author2_image); ?>"
									title="<?php echo esc_attr(sprintf(__('点击切换作者 (%s / %s)', 'lyrargon'), $author1_name, $author2_name)); ?>">
									<div class="lyra-dual-main-avatar shadow-sm" style="background-image: url(<?php echo esc_url($init_image); ?>);"></div>
									<div class="lyra-dual-badge-avatar shadow-sm" style="background-image: url(<?php echo esc_url($badge_image); ?>);" title="<?php echo esc_attr(sprintf(__('切换至 %s', 'lyrargon'), $badge_name)); ?>">
										<span class="lyra-dual-badge-switch-icon"><i class="fa fa-refresh"></i></span>
									</div>
								</div>
								<h6 id="leftbar_overview_author_name" class="lyra-author-name-transition"><?php echo esc_html($init_name); ?></h6>
								<h6 id="leftbar_overview_author_description" class="lyra-author-desc-transition" style="<?php echo empty($init_desc) ? 'display:none;' : ''; ?>"><?php echo esc_html($init_desc); ?></h6>
							<?php } else { ?>
								<div id="leftbar_overview_author_image" style="background-image: url(<?php echo esc_url($author1_image); ?>)" class="rounded-circle shadow-sm" alt="avatar"></div>
								<h6 id="leftbar_overview_author_name"><?php echo esc_html($author1_name); ?></h6>
								<?php if (!empty($author1_desc)) {echo '<h6 id="leftbar_overview_author_description">'. esc_html($author1_desc) .'</h6>';} ?>
							<?php } ?>
							<nav class="site-state">
								<div class="site-state-item site-state-posts">
									<a <?php $archives_page_url = get_option('lyrargon_archives_timeline_url'); echo (empty($archives_page_url) ? ' style="cursor: default;"' : 'href="' . $archives_page_url . '"');?>>
										<span class="site-state-item-count"><?php echo wp_count_posts() -> publish; ?></span>
										<span class="site-state-item-name"><?php _e('文章', 'lyrargon');?></span>
									</a>
								</div>
								<div class="site-state-item site-state-categories">
									<a data-toggle="modal" data-target="#blog_categories">
										<span class="site-state-item-count"><?php echo wp_count_terms('category'); ?></span>
										<span class="site-state-item-name"><?php _e('分类', 'lyrargon');?></span>
									</a>
								</div>      
								<div class="site-state-item site-state-tags">
									<a data-toggle="modal" data-target="#blog_tags">
										<span class="site-state-item-count"><?php echo wp_count_terms('post_tag'); ?></span>
										<span class="site-state-item-name"><?php _e('标签', 'lyrargon');?></span>
									</a>
								</div>
							</nav>
							<?php
								if ( has_nav_menu('leftbar_author_links') ){
									echo "<div class='site-author-links'>";
									wp_nav_menu( array(
										'container'  => '',
										'theme_location'  => 'leftbar_author_links',
										'items_wrap'  => '%3$s',
										'depth' => 0,
										'walker' => new leftbarAuthorLinksWalker()
									) );
									echo "</div>";
								}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="lyrargon_search_modal" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?php _e('搜索', 'lyrargon');?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<?php get_search_form(); ?>
			</div>
		</div>
	</div>
</div>
<!--Banner-->
<section id="banner" class="banner section section-lg section-shaped">
	<div class="shape <?php echo get_option('lyrargon_banner_background_hide_shapes') == 'true' ? '' : 'shape-style-1' ?> <?php echo get_option('lyrargon_banner_background_color_type') == '' ? 'shape-primary' : get_option('lyrargon_banner_background_color_type'); ?>">
		<span></span>
		<span></span>
		<span></span>
		<span></span>
		<span></span>
		<span></span>
		<span></span>
		<span></span>
		<span></span>
	</div>

	<?php
		$banner_title = get_option('lyrargon_banner_title') == '' ? get_bloginfo('name') : get_option('lyrargon_banner_title');
		$enable_banner_title_typing_effect = get_option('lyrargon_enable_banner_title_typing_effect') != 'true' ? "false" : get_option('lyrargon_enable_banner_title_typing_effect');
	?>
	<div id="banner_container" class="banner-container container text-center">
		<?php if ($enable_banner_title_typing_effect != "true"){?>
			<div class="banner-title text-white"><span class="banner-title-inner"><?php echo apply_filters('lyrargon_banner_title_html', $banner_title); ?></span>
			<?php echo get_option('lyrargon_banner_subtitle') == '' ? '' : '<span class="banner-subtitle d-block">' . get_option('lyrargon_banner_subtitle') . '</span>'; ?></div>
		<?php } else {?>
			<div class="banner-title text-white" data-interval="<?php echo get_option('lyrargon_banner_typing_effect_interval', 100); ?>"><span data-text="<?php echo $banner_title; ?>" class="banner-title-inner">&nbsp;</span>
			<?php echo get_option('lyrargon_banner_subtitle') == '' ? '' : '<span data-text="' . get_option('lyrargon_banner_subtitle') . '" class="banner-subtitle d-block">&nbsp;</span>'; ?></div>
		<?php }?>
	</div>
	<?php
		$banner_bg_style_url = '';
		if (is_single() && argon_has_post_thumbnail() && argon_get_post_thumbnail_display_type() === 'banner') {
			$banner_bg_style_url = argon_get_post_thumbnail();
		} else if (get_option('lyrargon_banner_background_url') != '') {
			$banner_bg_style_url = get_banner_background_url();
		}
	?>
	<style id="banner_bg_style">
		<?php if (!empty($banner_bg_style_url)) { ?>
			section.banner{
				background-image: url(<?php echo esc_url($banner_bg_style_url); ?>) !important;
			}
		<?php } ?>
	</style>
	<?php if ($banner_size == 'fullscreen') { ?>
		<div class="cover-scroll-down">
			<i class="fa fa-angle-down" aria-hidden="true"></i>
		</div>
	<?php } ?>
</section>

<?php if (apply_filters('lyrargon_page_background_url', get_option('lyrargon_page_background_url')) != '') { ?>
	<style>
		<?php if (get_option('lyrargon_page_background_banner_style', 'false') == 'transparent') { ?>
			#banner, #banner .shape {
				background: transparent !important;
			}
		<?php } ?>
		#content:before {
			content: '';
			display: block;
			position: fixed;
			left: 0;
			right: 0;
			top: 0;
			bottom: 0;
			z-index: -2;
			background: url(<?php echo apply_filters('lyrargon_page_background_url', get_option('lyrargon_page_background_url'));?>);
			background-position: center;
			background-size: cover;
			background-repeat: no-repeat;
			opacity: <?php echo (get_option('lyrargon_page_background_opacity') == '' ? '1' : get_option('lyrargon_page_background_opacity')); ?>;
			transition: opacity .5s ease;
		}
		html.darkmode #content:before{
			filter: brightness(0.65);
		}
		<?php if (apply_filters('lyrargon_page_background_dark_url', get_option('lyrargon_page_background_dark_url')) != '') { ?>
			#content:after {
				content: '';
				display: block;
				position: fixed;
				left: 0;
				right: 0;
				top: 0;
				bottom: 0;
				z-index: -2;
				background: url(<?php echo apply_filters('lyrargon_page_background_dark_url', get_option('lyrargon_page_background_dark_url'));?>);
				background-position: center;
				background-size: cover;
				background-repeat: no-repeat;
				opacity: 0;
				transition: opacity .5s ease;
			}
			html.darkmode #content:after {
				opacity: <?php echo (get_option('lyrargon_page_background_opacity') == '' ? '1' : get_option('lyrargon_page_background_opacity')); ?>;
			}
			html.darkmode #content:before {
				opacity: 0;
			}
		<?php } ?>
	</style>
<?php } ?>

<?php if (get_option('lyrargon_show_toolbar_mask') == 'true') { ?>
	<style>
		#banner:after {
			content: '';
			width: 100vw;
			position: absolute;
			left: 0;
			top: 0;
			height: 150px;
			background: linear-gradient(180deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0) 100%);
			display: block;
			z-index: -1;
		}
		.banner-title {
			text-shadow: 0 5px 15px rgba(0, 0, 0, .2);
		}
	</style>
<?php } ?>

<div id="float_action_buttons" class="float-action-buttons fabtns-unloaded">
	<button id="fabtn_back_to_top" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-label="Back To Top" tooltip="<?php _e('回到顶部', 'lyrargon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-angle-up"></i></span>
	</button>
	<button id="fabtn_go_to_comment" class="btn btn-icon btn-neutral fabtn shadow-sm d-none" type="button" <?php if (get_option('lyrargon_fab_show_gotocomment_button') != 'true') echo " style='display: none;'";?> aria-label="Comment" tooltip="<?php _e('评论', 'lyrargon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-comment-o"></i></span>
	</button>
	<button id="fabtn_toggle_darkmode" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" <?php if (get_option('lyrargon_fab_show_darkmode_button') != 'true') echo " style='display: none;'";?> aria-label="Toggle Darkmode" tooltip-darkmode="<?php _e('夜间模式', 'lyrargon'); ?>" tooltip-blackmode="<?php _e('暗黑模式', 'lyrargon'); ?>" tooltip-lightmode="<?php _e('日间模式', 'lyrargon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-moon-o"></i><i class='fa fa-lightbulb-o'></i></span>
	</button>
	<button id="fabtn_toggle_blog_settings_popup" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" <?php if (get_option('lyrargon_fab_show_settings_button') == 'false') echo " style='display: none;'";?> aria-label="Open Blog Settings Menu" tooltip="<?php _e('设置', 'lyrargon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-cog"></i></span>
	</button>
	<div id="fabtn_blog_settings_popup" class="card shadow-sm" style="opacity: 0;" aria-hidden="true">
		<div id="close_blog_settings"><i class="fa fa-close"></i></div>
		<div class="blog-setting-item mt-3">
			<div style="transform: translateY(-4px);"><div id="blog_setting_toggle_darkmode_and_amoledarkmode" tooltip-switch-to-darkmode="<?php _e('切换到夜间模式', 'lyrargon'); ?>" tooltip-switch-to-blackmode="<?php _e('切换到暗黑模式', 'lyrargon'); ?>"><span><?php _e('夜间模式', 'lyrargon');?></span><span><?php _e('暗黑模式', 'lyrargon');?></span></div></div>
			<div style="flex: 1;"></div>
			<label id="blog_setting_darkmode_switch" class="custom-toggle">
				<span class="custom-toggle-slider rounded-circle"></span>
			</label>
		</div>
		<div class="blog-setting-item mt-3">
			<div style="flex: 1;"><?php _e('字体', 'lyrargon');?></div>
			<div>
				<button id="blog_setting_font_sans_serif" type="button" class="blog-setting-font btn btn-outline-primary blog-setting-selector-left">Sans Serif</button><button id="blog_setting_font_serif" type="button" class="blog-setting-font btn btn-outline-primary blog-setting-selector-right">Serif</button>
			</div>
		</div>
		<div class="blog-setting-item mt-3">
			<div style="flex: 1;"><?php _e('阴影', 'lyrargon');?></div>
			<div>
				<button id="blog_setting_shadow_small" type="button" class="blog-setting-shadow btn btn-outline-primary blog-setting-selector-left"><?php _e('浅阴影', 'lyrargon');?></button><button id="blog_setting_shadow_big" type="button" class="blog-setting-shadow btn btn-outline-primary blog-setting-selector-right"><?php _e('深阴影', 'lyrargon');?></button>
			</div>
		</div>
		<div class="blog-setting-item mt-3 mb-3">
			<div style="flex: 1;"><?php _e('滤镜', 'lyrargon');?></div>
			<div id="blog_setting_filters" class="ml-3">
				<button id="blog_setting_filter_off" type="button" class="blog-setting-filter-btn ml-0" filter-name="off"><?php _e('关闭', 'lyrargon');?></button>
				<button id="blog_setting_filter_sunset" type="button" class="blog-setting-filter-btn" filter-name="sunset"><?php _e('日落', 'lyrargon');?></button>
				<button id="blog_setting_filter_darkness" type="button" class="blog-setting-filter-btn" filter-name="darkness"><?php _e('暗化', 'lyrargon');?></button>
				<button id="blog_setting_filter_grayscale" type="button" class="blog-setting-filter-btn" filter-name="grayscale"><?php _e('灰度', 'lyrargon');?></button>
			</div>
		</div>
		<div class="blog-setting-item mb-3">
			<div id="blog_setting_card_radius_to_default" style="cursor: pointer;" tooltip="<?php _e('恢复默认', 'lyrargon'); ?>"><?php _e('圆角', 'lyrargon');?></div>
			<div style="flex: 1;margin-left: 20px;margin-right: 8px;transform: translateY(2px);">
				<div id="blog_setting_card_radius"></div>
			</div>
		</div>
		<?php if (get_option('lyrargon_show_customize_theme_color_picker') != 'false') {?>
			<div class="blog-setting-item mt-1 mb-3">
				<div style="flex: 1;"><?php _e('主题色', 'lyrargon');?></div>
				<div id="theme-color-picker" class="ml-3"></div>
			</div>
		<?php }?>
	</div>
	<button id="fabtn_reading_progress" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-hidden="true" tooltip="<?php _e('阅读进度', 'lyrargon'); ?>">
		<span id="fabtn_reading_progress_details">0%</span>
	</button>
</div>

<div id="content" class="site-content">

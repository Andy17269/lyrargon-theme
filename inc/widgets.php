<?php
//注册小工具
function argon_widgets_init() {
	register_sidebar(
		array(
			'name'          => __('左侧栏小工具', 'lyrargon'),
			'id'            => 'leftbar-tools',
			'description'   => __( '左侧栏小工具 (如果设置会在侧栏增加一个 Tab)', 'lyrargon'),
			'before_widget' => '<div id="%1$s" class="widget %2$s card bg-white border-0">',
			'after_widget'  => '</div>',
			'before_title'  => '<h6 class="font-weight-bold text-black">',
			'after_title'   => '</h6>',
		)
	);
	register_sidebar(
		array(
			'name'          => __('右侧栏小工具', 'lyrargon'),
			'id'            => 'rightbar-tools',
			'description'   => __( '右侧栏小工具 (在 "Lyrargon 主题选项" 中选择 "三栏布局" 才会显示)', 'lyrargon'),
			'before_widget' => '<div id="%1$s" class="widget %2$s card shadow-sm bg-white border-0">',
			'after_widget'  => '</div>',
			'before_title'  => '<h6 class="font-weight-bold text-black">',
			'after_title'   => '</h6>',
		)
	);
	register_sidebar(
		array(
			'name'          => __('站点概览额外内容', 'lyrargon'),
			'id'            => 'leftbar-siteinfo-extra-tools',
			'description'   => __( '站点概览额外内容', 'lyrargon'),
			'before_widget' => '<div id="%1$s" class="widget %2$s card bg-white border-0">',
			'after_widget'  => '</div>',
			'before_title'  => '<h6 class="font-weight-bold text-black">',
			'after_title'   => '</h6>',
		)
	);
}
add_action('widgets_init', 'argon_widgets_init');
//注册新后台主题配色方案
function argon_add_admin_color(){
	wp_admin_css_color(
		'lyrargon',
		'Lyrargon',
		get_bloginfo('template_directory') . "/admin.css",
		array("#2196f3", "#324cdc", "#e8ebfb"),
		array('base' => '#525f7f', 'focus' => '#2196f3', 'current' => '#fff')
	);
}
add_action('admin_init', 'argon_add_admin_color');
function argon_admin_themecolor_css(){
	$themecolor = get_option("lyrargon_theme_color", "#2196f3");
	$RGB = hexstr2rgb($themecolor);
	$HSL = rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']);
	$rgb_str = $RGB['R'] . ', ' . $RGB['G'] . ', ' . $RGB['B'];
	echo "
		<style id='themecolor_css'>
			:root{
				--themecolor: {$themecolor} ;
				--themecolor-R: {$RGB['R']} ;
				--themecolor-G: {$RGB['G']} ;
				--themecolor-B: {$RGB['B']} ;
				--themecolor-rgbstr: {$rgb_str} ;
				--themecolor-rgb: {$rgb_str} ;
				--themecolor-H: {$HSL['H']} ;
				--themecolor-S: {$HSL['S']} ;
				--themecolor-L: {$HSL['L']} ;
			}
		</style>
	";
	if (get_option("lyrargon_enable_immersion_color", "false") == "true"){
		echo "<script> document.documentElement.classList.add('immersion-color'); </script>";
	}
}
add_filter('admin_head', 'argon_admin_themecolor_css');
function array_remove(&$arr, $item){
	$pos = array_search($item, $arr);
	if ($pos !== false){
		array_splice($arr, $pos, 1);
	}
}
//数字格式化
function format_number_in_kilos($number) {
	if ($number < 1000){
		return $number;
	}
	if (1000 <= $number && $number < 1000000){
		if (1000 <= $number && $number < 10000){
			return round($number / 1000, 1) . "K";
		}else{
			return round($number / 1000, 0) . "K";
		}
	}
	if (1000000 <= $number && $number <= 10000000){
		return round($number / 1000000, 1) . "M";
	}else{
		return round($number / 1000000, 0) . "M";
	}
}
//表情包

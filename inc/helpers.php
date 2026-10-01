<?php
//说说点赞
function get_shuoshuo_upvotes($ID){
	$count_key = 'upvotes';
	$count = get_post_meta($ID, $count_key, true);
	if ($count==''){
		delete_post_meta($ID, $count_key);
		add_post_meta($ID, $count_key, '0');
		$count = '0';
	}
	return number_format_i18n($count);
}
function set_shuoshuo_upvotes($ID){
	if (get_post_type($ID) != 'shuoshuo'){
		return;
	}
	$count_key = 'upvotes';
	$count = get_post_meta($ID, $count_key, true);
	if ($count==''){
		delete_post_meta($ID, $count_key);
		add_post_meta($ID, $count_key, '1');
	} else {
		update_post_meta($ID, $count_key, $count + 1);
	}
}
function upvote_shuoshuo(){
	header('Content-Type:application/json; charset=utf-8');
	$ID = $_POST["shuoshuo_id"];
	$upvotedList = isset( $_COOKIE['lyrargon_shuoshuo_upvoted'] ) ? $_COOKIE['lyrargon_shuoshuo_upvoted'] : '';
	if (in_array($ID, explode(',', $upvotedList))){
		exit(json_encode(array(
			'status' => 'failed',
			'msg' => __('该说说已被赞过', 'lyrargon'),
			'total_upvote' => get_shuoshuo_upvotes($ID)
		)));
	}
	set_shuoshuo_upvotes($ID);
	setcookie('lyrargon_shuoshuo_upvoted', $upvotedList . $ID . "," , time() + 3153600000 , '/');
	exit(json_encode(array(
		'ID' => $ID,
		'status' => 'success',
		'msg' => __('点赞成功', 'lyrargon'),
		'total_upvote' => get_shuoshuo_upvotes($ID)
	)));
}
add_action('wp_ajax_upvote_shuoshuo' , 'upvote_shuoshuo');
add_action('wp_ajax_nopriv_upvote_shuoshuo' , 'upvote_shuoshuo');
//颜色计算
function rgb2hsl($R,$G,$B){
	$r = $R / 255;
	$g = $G / 255;
	$b = $B / 255;

	$var_Min = min($r, $g, $b);
	$var_Max = max($r, $g, $b);
	$del_Max = $var_Max - $var_Min;

	$L = ($var_Max + $var_Min) / 2;

	if ($del_Max == 0){
		$H = 0;
		$S = 0;
	}else{
		if ($L < 0.5){
			$S = $del_Max / ($var_Max + $var_Min);
		}else{
			$S = $del_Max / (2 - $var_Max - $var_Min);
		}

		$del_R = ((($var_Max - $r) / 6) + ($del_Max / 2)) / $del_Max;
		$del_G = ((($var_Max - $g) / 6) + ($del_Max / 2)) / $del_Max;
		$del_B = ((($var_Max - $b) / 6) + ($del_Max / 2)) / $del_Max;

		if ($r == $var_Max){
			$H = $del_B - $del_G;
		}
		else if ($g == $var_Max){
			$H = (1 / 3) + $del_R - $del_B;
		}
		else if ($b == $var_Max){
			$H = (2 / 3) + $del_G - $del_R;
		}
		if ($H < 0) $H += 1;
		if ($H > 1) $H -= 1;
	}
	return array(
		'h' => $H,//0~1
		's' => $S,
		'l' => $L,
		'H' => round($H * 360),//0~360
		'S' => round($S * 100),//0~100
		'L' => round($L * 100),//0~100
	);
}
function Hue_2_RGB($v1,$v2,$vH){
	if ($vH < 0) $vH += 1;
	if ($vH > 1) $vH -= 1;
	if ((6 * $vH) < 1) return ($v1 + ($v2 - $v1) * 6 * $vH);
	if ((2 * $vH) < 1) return $v2;
	if ((3 * $vH) < 2) return ($v1 + ($v2 - $v1) * ((2 / 3) - $vH) * 6);
	return $v1;
}
function hsl2rgb($h,$s,$l){
	if ($s == 0){
		$r = $l;
		$g = $l;
		$b = $l;
	}
	else{
		if ($l < 0.5){
			$var_2 = $l * (1 + $s);
		}
		else{
			$var_2 = ($l + $s) - ($s * $l);
		}
		$var_1 = 2 * $l - $var_2;
		$r = Hue_2_RGB($var_1, $var_2, $h + (1 / 3));
		$g = Hue_2_RGB($var_1, $var_2, $h);
		$b = Hue_2_RGB($var_1, $var_2, $h - (1 / 3));
	}
	return array(
		'R' => round($r * 255),//0~255
		'G' => round($g * 255),
		'B' => round($b * 255),
		'r' => $r,//0~1
		'g' => $g,
		'b' => $b
	);
}
function rgb2hex($r,$g,$b){
	$hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	$rh = "";
	$gh = "";
	$bh = "";
	while (strlen($rh) < 2){
		$rh = $hex[$r%16] . $rh;
		$r = floor($r / 16);
	}
	while (strlen($gh) < 2){
		$gh = $hex[$g%16] . $gh;
		$g = floor($g / 16);
	}
	while (strlen($bh) < 2){
		$bh = $hex[$b%16] . $bh;
		$b = floor($b / 16);
	}
	return "#".$rh.$gh.$bh;
}
function hsl2hex($h, $s, $l){
	if ($h > 1) $h = $h / 360;
	if ($s > 1) $s = $s / 100;
	if ($l > 1) $l = $l / 100;
	$rgb = hsl2rgb($h, $s, $l);
	return rgb2hex($rgb['R'], $rgb['G'], $rgb['B']);
}
function hexstr2rgb($hex){
	//$hex: #XXXXXX
	return array(
		'R' => hexdec(substr($hex,1,2)),//0~255
		'G' => hexdec(substr($hex,3,2)),
		'B' => hexdec(substr($hex,5,2)),
		'r' => hexdec(substr($hex,1,2)) / 255,//0~1
		'g' => hexdec(substr($hex,3,2)) / 255,
		'b' => hexdec(substr($hex,5,2)) / 255
	);
}
function rgb2str($rgb){
	return $rgb['R']. "," .$rgb['G']. "," .$rgb['B'];
}
function hex2str($hex){
	return rgb2str(hexstr2rgb($hex));
}
function rgb2gray($R,$G,$B){
	return round($R * 0.299 + $G * 0.587 + $B * 0.114);
}
function hex2gray($hex){
	$rgb_array = hexstr2rgb($hex);
	return rgb2gray($rgb_array['R'], $rgb_array['G'], $rgb_array['B']);
}
function checkHEX($hex){
	if (strlen($hex) != 7){
		return False;
	}
	if (substr($hex,0,1) != "#"){
		return False;
	}
	return True;
}

/**
 * 毛玻璃材质强度解析器
 *
 * 2.0.27 起，「毛玻璃模糊特效」与「卡片毛玻璃效果 (Lite)」合并为单一选项
 * lyrargon_glass_blur，统一控制站点内全部毛玻璃材质（顶栏、卡片、侧栏、
 * 悬浮按钮、搜索框、弹窗、移动端抽屉等）的模糊强度与开关。
 *
 * 返回值：
 *   disabled      关闭（全部组件回退为不透明材质）
 *   weak          弱
 *   medium        中（默认）
 *   strong        强
 *   desktop_only  仅在非移动端启用
 *
 * 若新选项尚未写入（老站点升级场景），则回退读取旧的
 * lyrargon_enable_glass_blur / lyrargon_card_blur 选项并映射，保证升级后外观不变。
 */
if ( ! function_exists( 'lyrargon_get_glass_blur_level' ) ) {
	function lyrargon_get_glass_blur_level() {
		$level = get_option( 'lyrargon_glass_blur' );
		$allowed = array( 'disabled', 'weak', 'medium', 'strong', 'desktop_only' );
		if ( in_array( $level, $allowed, true ) ) {
			return $level;
		}

		// 旧选项回退：全局开关优先决定是否启用
		$legacy_global = get_option( 'lyrargon_enable_glass_blur', 'always' );
		if ( $legacy_global === 'disabled' || $legacy_global === 'false' ) {
			return 'disabled';
		}
		if ( $legacy_global === 'desktop_only' || $legacy_global === 'no_mobile' ) {
			return 'desktop_only';
		}

		$legacy_card = get_option( 'lyrargon_card_blur' );
		if ( $legacy_card === 'weak' || $legacy_card === 'medium' || $legacy_card === 'strong' ) {
			return $legacy_card;
		}
		return 'medium';
	}
}

/**
 * 返回毛玻璃强度对应的模糊缩放系数（供 --glass-blur-scale 使用）。
 */
if ( ! function_exists( 'lyrargon_glass_blur_scale' ) ) {
	function lyrargon_glass_blur_scale( $level = null ) {
		if ( $level === null ) {
			$level = lyrargon_get_glass_blur_level();
		}
		switch ( $level ) {
			case 'weak':
				return '0.45';
			case 'strong':
				return '1.8';
			case 'disabled':
				return '0';
			default:
				return '1';
		}
	}
}

/**
 * 文章分享平台默认值
 *
 * 「显示文章分享按钮」在 2.0.27 中简化为「开启 / 关闭」两个选项，平台列表完全由
 * lyrargon_share_platforms 决定。对于从未保存过平台列表的老站点，这里按旧的
 * domestic / abroad 模式推导默认平台，避免升级后显示范围突然变化。
 */
if ( ! function_exists( 'lyrargon_default_share_platforms' ) ) {
	function lyrargon_default_share_platforms() {
		$all = 'wechat|douban|qq|qzone|weibo|facebook|twitter|telegram|copy';
		switch ( get_option( 'lyrargon_show_sharebtn' ) ) {
			case 'domestic':
				return 'wechat|douban|qq|qzone|weibo|copy';
			case 'abroad':
				return 'facebook|twitter|telegram|copy';
			default:
				return $all;
		}
	}
}

/**
 * 获取文章列表布局版本（已将旧版布局 3 归并到布局 1）
 */
if ( ! function_exists( 'lyrargon_get_article_list_layout' ) ) {
	function lyrargon_get_article_list_layout() {
		$layout = get_option( 'lyrargon_article_list_layout', '1' );
		if ( $layout === '2' ) {
			return '2';
		}
		return '1';
	}
}

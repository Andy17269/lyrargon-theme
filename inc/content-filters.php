<?php
//Lazyload 对 <img> 标签预处理以加载 Lazyload
function argon_lazyload($content){
	$lazyload_loading_style = get_option('lyrargon_lazyload_loading_style');
	if ($lazyload_loading_style == ''){
		$lazyload_loading_style = 'none';
	}
	$lazyload_loading_style = "lazyload-style-" . $lazyload_loading_style;

	if(!is_feed() && !is_robots() && !is_home()){
		$content = preg_replace('/<img(.*?)src=[\'"](.*?)[\'"](.*?)((\/>)|(<\/img>))/i',"<img class=\"lazyload " . $lazyload_loading_style . "\" src=\"data:image/svg+xml;base64,PCEtLUFyZ29uTG9hZGluZy0tPgo8c3ZnIHdpZHRoPSIxIiBoZWlnaHQ9IjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgc3Ryb2tlPSIjZmZmZmZmMDAiPjxnPjwvZz4KPC9zdmc+\" \$1data-original=\"\$2\" src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAANSURBVBhXYzh8+PB/AAffA0nNPuCLAAAAAElFTkSuQmCC\"\$3$4" , $content);
		$content = preg_replace('/<img(.*?)data-full-url=[\'"]([^\'"]+)[\'"](.*)>/i',"<img$1data-full-url=\"$2\" data-original=\"$2\"$3>" , $content);
		$content = preg_replace('/<img(.*?)srcset=[\'"](.*?)[\'"](.*?)>/i',"<img$1$3>" , $content);
	}
	return $content;
}
function argon_fancybox($content){
	if(!is_feed() && !is_robots() && !is_home()){
		if (get_option('lyrargon_enable_lazyload') != 'false'){
			$content = preg_replace('/<img(.*?)data-original=[\'"](.*?)[\'"](.*?)((\/>)|>|(<\/img>))/i',"<div class='fancybox-wrapper lazyload-container-unload' data-fancybox='post-images' href='$2'>$0</div>" , $content);
		}else{
			$content = preg_replace('/<img(.*?)src=[\'"](.*?)[\'"](.*?)((\/>)|>|(<\/img>))/i',"<div class='fancybox-wrapper' data-fancybox='post-images' href='$2'>$0</div>" , $content);
		}
	}
	return $content;
}
function the_content_filter($content){
	if (get_option('lyrargon_enable_lazyload') != 'false'){
		$content = argon_lazyload($content);
	}
	if (get_option('lyrargon_enable_fancybox') != 'false' && get_option('lyrargon_enable_zoomify') == 'false'){
		$content = argon_fancybox($content);
	}
	global $post;
	$custom_css = get_post_meta($post -> ID, 'lyrargon_custom_css', true);
	if (!empty($custom_css)){
		$content .= "<style>" . $custom_css . "</style>";
	}

	return $content;
}
add_filter('the_content' , 'the_content_filter',20);
//使用 CDN 加速 gravatar
function gravatar_cdn($url){
	$cdn = get_option('lyrargon_gravatar_cdn', 'gravatar.pho.ink/avatar/');
	$cdn = str_replace("http://", "", $cdn);
	$cdn = str_replace("https://", "", $cdn);
	if (substr($cdn, -1) != '/'){
		$cdn .= "/";
	}
	$url = preg_replace("/\/\/(.*?).gravatar.com\/avatar\//", "//" . $cdn, $url);
	return $url;
}
if (get_option('lyrargon_gravatar_cdn' , '') != ''){
	add_filter('get_avatar_url', 'gravatar_cdn');
}
function text_gravatar($url){
	$url = preg_replace("/[?&]d[^&]+/i", "" , $url);
	$url .= '&d=404';
	return $url;
}
if (get_option('lyrargon_text_gravatar', 'false') == 'true' && !is_admin()){
	add_filter('get_avatar_url', 'text_gravatar');
}

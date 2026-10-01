<?php
/*主题菜单*/
add_action('init', 'init_nav_menus');
function init_nav_menus(){
	register_nav_menus( array(
		'toolbar_menu' => __('顶部导航', 'lyrargon'),
		'leftbar_menu' => __('左侧栏菜单', 'lyrargon'),
		'leftbar_author_links' => __('左侧栏作者个人链接', 'lyrargon'),
		'leftbar_friend_links' => __('左侧栏友情链接', 'lyrargon')
	));
}

//隐藏 admin 管理条
//show_admin_bar(false);

/*说说*/
add_action('init', 'init_shuoshuo');
function init_shuoshuo(){
	$labels = array(
		'name' => __('说说', 'lyrargon'),
		'singular_name' => __('说说', 'lyrargon'),
		'add_new' => __('发表说说', 'lyrargon'),
		'add_new_item' => __('发表说说', 'lyrargon'),
		'edit_item' => __('编辑说说', 'lyrargon'),
		'new_item' => __('新说说', 'lyrargon'),
		'view_item' => __('查看说说', 'lyrargon'),
		'search_items' => __('搜索说说', 'lyrargon'),
		'not_found' => __('暂无说说', 'lyrargon'),
		'not_found_in_trash' => __('没有已遗弃的说说', 'lyrargon'),
		'parent_item_colon' => '',
		'menu_name' => __('说说', 'lyrargon')
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'exclude_from_search' => true,
		'query_var' => true,
		'rewrite' => array(
			'slug' => 'shuoshuo',
			'with_front' => false
		),
		'capability_type' => 'post',
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => null,
		'menu_icon' => 'dashicons-format-quote',
		'supports' => array('editor', 'author', 'title', 'custom-fields', 'comments')
	);
	register_post_type('shuoshuo', $args);
}

function argon_get_search_post_type_array(){
	$search_filters_type = get_option("lyrargon_search_filters_type", "*post,*page,shuoshuo");
	$search_filters_type = explode(',', $search_filters_type);
	if (!isset($_GET['post_type'])) {
		$default = array_filter($search_filters_type, function ($str) {	return $str[0] == '*'; });
		$default = array_map(function ($str) { return substr($str, 1) ;}, $default);
		return $default;
	}
	$search_filters_type = array_map(function ($str) { return $str[0] == '*' ? substr($str, 1) : $str; }, $search_filters_type);
	$post_type = explode(',', $_GET['post_type']);
	$arr = array();
	foreach ($search_filters_type as $type) {
		if (in_array($type, $post_type)) {
			array_push($arr, $type);
		}
	}
	if (count($arr) == 0) {
		array_push($arr, 'none');
	}
	return $arr;
}
function search_filter($query) {
	if (!$query -> is_search || is_admin()) {
		return $query;
	}
	if (get_option('lyrargon_enable_search_filters', 'true') == 'false'){
		return $query;
	}
	$query -> set('post_type', argon_get_search_post_type_array());
	return $query;
}
add_filter('pre_get_posts', 'search_filter');

/*恢复链接管理器*/
add_filter('pre_option_link_manager_enabled', '__return_true');

/*登录界面 CSS*/
function argon_login_page_style() {
	wp_enqueue_style("lyrargon_login_css", lyrargon_assets_path() . "/login.css", null, lyrargon_theme_version());
}
if (get_option('lyrargon_enable_login_css') == 'true'){
	add_action('login_head', 'argon_login_page_style');
}

// 注入自定义表情包
function argon_inject_custom_emojis($emotionListDefault) {
    $custom_emojis = get_option('lyrargon_custom_emojis', array());
    if (!empty($custom_emojis) && is_array($custom_emojis)) {
        foreach ($custom_emojis as $pack) {
            $emotionListDefault[] = $pack;
        }
    }
    return $emotionListDefault;
}
add_filter('lyrargon_emotion_list', 'argon_inject_custom_emojis');

<?php
//编辑文章界面新增 Meta 编辑模块
function argon_meta_box_1(){
	wp_nonce_field("lyrargon_meta_box_nonce_action", "lyrargon_meta_box_nonce");
	global $post;
	?>
		<h4><?php _e("显示字数和预计阅读时间", 'lyrargon');?></h4>
		<?php $argon_meta_hide_readingtime = get_post_meta($post->ID, "lyrargon_hide_readingtime", true);?>
		<select name="lyrargon_meta_hide_readingtime" id="lyrargon_meta_hide_readingtime">
			<option value="false" <?php if ($argon_meta_hide_readingtime=='false'){echo 'selected';} ?>><?php _e("跟随全局设置", 'lyrargon');?></option>
			<option value="true" <?php if ($argon_meta_hide_readingtime=='true'){echo 'selected';} ?>><?php _e("不显示", 'lyrargon');?></option>
		</select>
		<p style="margin-top: 15px;"><?php _e("是否显示字数和预计阅读时间 Meta 信息", 'lyrargon');?></p>
		<h4><?php _e("Meta 中隐藏发布时间和分类", 'lyrargon');?></h4>
		<?php $argon_meta_simple = get_post_meta($post->ID, "lyrargon_meta_simple", true);?>
		<select name="lyrargon_meta_simple" id="lyrargon_meta_simple">
			<option value="false" <?php if ($argon_meta_simple=='false'){echo 'selected';} ?>><?php _e("不隐藏", 'lyrargon');?></option>
			<option value="true" <?php if ($argon_meta_simple=='true'){echo 'selected';} ?>><?php _e("隐藏", 'lyrargon');?></option>
		</select>
		<p style="margin-top: 15px;"><?php _e("适合特定的页面，例如友链页面。开启后文章 Meta 的第一行只显示阅读数和评论数。", 'lyrargon');?></p>
		<h4><?php _e("文章封面显示样式", 'lyrargon');?></h4>
		<?php $argon_post_thumbnail_display = get_post_meta($post->ID, "lyrargon_post_thumbnail_display", true);?>
		<select name="lyrargon_post_thumbnail_display" id="lyrargon_post_thumbnail_display">
			<option value="default" <?php if ($argon_post_thumbnail_display=='' || $argon_post_thumbnail_display=='default'){echo 'selected';} ?>><?php _e("默认禁用（卡片内正常显示）", 'lyrargon');?></option>
			<option value="banner" <?php if ($argon_post_thumbnail_display=='banner'){echo 'selected';} ?>><?php _e("开启全屏（作为顶部 Banner 背景）", 'lyrargon');?></option>
			<option value="none" <?php if ($argon_post_thumbnail_display=='none'){echo 'selected';} ?>><?php _e("什么都不显示", 'lyrargon');?></option>
		</select>
		<p style="margin-top: 15px;"><?php _e("控制阅读文章时封面的展示方式。默认在卡片顶端正常显示；开启全屏后作为顶部大图背景；亦可选择完全隐藏。", 'lyrargon');?></p>
		<h4><?php _e("使用文章中第一张图作为头图", 'lyrargon');?></h4>
		<?php $argon_first_image_as_thumbnail = get_post_meta($post->ID, "lyrargon_first_image_as_thumbnail", true);?>
		<select name="lyrargon_first_image_as_thumbnail" id="lyrargon_first_image_as_thumbnail">
			<option value="default" <?php if ($argon_first_image_as_thumbnail=='default'){echo 'selected';} ?>><?php _e("跟随全局设置", 'lyrargon');?></option>
			<option value="true" <?php if ($argon_first_image_as_thumbnail=='true'){echo 'selected';} ?>><?php _e("使用", 'lyrargon');?></option>
			<option value="false" <?php if ($argon_first_image_as_thumbnail=='false'){echo 'selected';} ?>><?php _e("不使用", 'lyrargon');?></option>
		</select>
		<h4><?php _e("显示文章过时信息", 'lyrargon');?></h4>
		<?php $argon_show_post_outdated_info = get_post_meta($post->ID, "lyrargon_show_post_outdated_info", true);?>
		<div style="display: flex;">
			<select name="lyrargon_show_post_outdated_info" id="lyrargon_show_post_outdated_info">
				<option value="default" <?php if ($argon_show_post_outdated_info=='default'){echo 'selected';} ?>><?php _e("跟随全局设置", 'lyrargon');?></option>
				<option value="always" <?php if ($argon_show_post_outdated_info=='always'){echo 'selected';} ?>><?php _e("一直显示", 'lyrargon');?></option>
				<option value="never" <?php if ($argon_show_post_outdated_info=='never'){echo 'selected';} ?>><?php _e("永不显示", 'lyrargon');?></option>
			</select>
			<button id="apply_show_post_outdated_info" type="button" class="components-button is-primary" style="height: 22px; display: none;"><?php _e("应用", 'lyrargon');?></button>
		</div>
		<p style="margin-top: 15px;"><?php _e("单独控制该文章的过时信息显示。", 'lyrargon');?></p>
		<h4><?php _e("文末附加内容", 'lyrargon');?></h4>
		<?php $argon_after_post = get_post_meta($post->ID, "lyrargon_after_post", true);?>
		<textarea name="lyrargon_after_post" id="lyrargon_after_post" rows="3" cols="30" style="width:100%;"><?php if (!empty($argon_after_post)){echo $argon_after_post;} ?></textarea>
		<p style="margin-top: 15px;"><?php _e("给该文章设置单独的文末附加内容，留空则跟随全局，设为 <code>--none--</code> 则不显示。", 'lyrargon');?></p>
		<h4><?php _e("自定义 CSS", 'lyrargon');?></h4>
		<?php $argon_custom_css = get_post_meta($post->ID, "lyrargon_custom_css", true);?>
		<textarea name="lyrargon_custom_css" id="lyrargon_custom_css" rows="5" cols="30" style="width:100%;"><?php if (!empty($argon_custom_css)){echo $argon_custom_css;} ?></textarea>
		<p style="margin-top: 15px;"><?php _e("给该文章添加单独的 CSS", 'lyrargon');?></p>

		<script>$ = window.jQuery;</script>
		<script>
			function showAlert(type, message){
				if (!wp.data){
					alert(message);
					return;
				}
				wp.data.dispatch('core/notices').createNotice(
					type,
					message,
					{ type: "snackbar", isDismissible: true, }
				);
			}
			$("select[name=argon_show_post_outdated_info").change(function(){
				$("#apply_show_post_outdated_info").css("display", "");
			});
			$("#apply_show_post_outdated_info").click(function(){
				$("#apply_show_post_outdated_info").addClass("is-busy").attr("disabled", "disabled").css("opacity", "0.5");
				$("#argon_show_post_outdated_info").attr("disabled", "disabled");
				var data = {
					action: 'update_post_meta_ajax',
					argon_meta_box_nonce: $("#argon_meta_box_nonce").val(),
					post_id: <?php echo $post->ID; ?>,
					meta_key: 'lyrargon_show_post_outdated_info',
					meta_value: $("select[name=argon_show_post_outdated_info]").val()
				};
				$.ajax({
					url: ajaxurl,
					type: 'post',
					data: data,
					success: function(response) {
						$("#apply_show_post_outdated_info").removeClass("is-busy").removeAttr("disabled").css("opacity", "1");
						$("#argon_show_post_outdated_info").removeAttr("disabled");
						if (response.status == "failed"){
							showAlert("failed", "<?php _e("应用失败", 'lyrargon');?>");
							return;
						}
						$("#apply_show_post_outdated_info").css("display", "none");
						showAlert("success", "<?php _e("应用成功", 'lyrargon');?>");
					},
					error: function(response) {
						$("#apply_show_post_outdated_info").removeClass("is-busy").removeAttr("disabled").css("opacity", "1");
						$("#argon_show_post_outdated_info").removeAttr("disabled");
						showAlert("failed", "<?php _e("应用失败", 'lyrargon');?>");
					}
				});
			});
		</script>
	<?php
}
function argon_add_meta_boxes(){
	add_meta_box('lyrargon_meta_box_1', __("文章设置", 'lyrargon'), 'argon_meta_box_1', array('post', 'page'), 'side', 'low');
}
add_action('admin_menu', 'argon_add_meta_boxes');
function argon_save_meta_data($post_id){
	if (!isset($_POST['lyrargon_meta_box_nonce'])){
		return $post_id;
	}
	$nonce = $_POST['lyrargon_meta_box_nonce'];
	if (!wp_verify_nonce($nonce, 'lyrargon_meta_box_nonce_action')){
		return $post_id;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
		return $post_id;
	}
	if ($_POST['post_type'] == 'post'){
		if (!current_user_can('edit_post', $post_id)){
			return $post_id;
		}
	}
	if ($_POST['post_type'] == 'page'){
		if (!current_user_can('edit_page', $post_id)){
			return $post_id;
		}
	}
	if (isset($_POST['lyrargon_post_thumbnail_display'])){
		update_post_meta($post_id, 'lyrargon_post_thumbnail_display', $_POST['lyrargon_post_thumbnail_display']);
	}
	update_post_meta($post_id, 'lyrargon_hide_readingtime', $_POST['lyrargon_meta_hide_readingtime']);
	update_post_meta($post_id, 'lyrargon_meta_simple', $_POST['lyrargon_meta_simple']);
	update_post_meta($post_id, 'lyrargon_first_image_as_thumbnail', $_POST['lyrargon_first_image_as_thumbnail']);
	update_post_meta($post_id, 'lyrargon_show_post_outdated_info', $_POST['lyrargon_show_post_outdated_info']);
	update_post_meta($post_id, 'lyrargon_after_post', $_POST['lyrargon_after_post']);
	update_post_meta($post_id, 'lyrargon_custom_css', $_POST['lyrargon_custom_css']);
}
add_action('save_post', 'argon_save_meta_data');
function update_post_meta_ajax(){
	if (!isset($_POST['lyrargon_meta_box_nonce'])){
		return;
	}
	$nonce = $_POST['lyrargon_meta_box_nonce'];
	if (!wp_verify_nonce($nonce, 'lyrargon_meta_box_nonce_action')){
		return;
	}
	header('Content-Type:application/json; charset=utf-8');
	$post_id = intval($_POST["post_id"]);
	$meta_key = $_POST["meta_key"];
	$meta_value = $_POST["meta_value"];

	if (get_post_meta($post_id, $meta_key, true) == $meta_value){
		exit(json_encode(array(
			'status' => 'success'
		)));
		return;
	}

	$result = update_post_meta($post_id, $meta_key, $meta_value);

	if ($result){
		exit(json_encode(array(
			'status' => 'success'
		)));
	}else{
		exit(json_encode(array(
			'status' => 'failed'
		)));
	}
}
add_action('wp_ajax_update_post_meta_ajax' , 'update_post_meta_ajax');
add_action('wp_ajax_nopriv_update_post_meta_ajax' , 'update_post_meta_ajax');
//首页显示说说
function argon_home_add_post_type_shuoshuo($query){
	if (is_home() && $query -> is_main_query()){
		$query -> set('post_type', array('post', 'shuoshuo'));
	}
	return $query;
}
if (get_option("lyrargon_home_show_shuoshuo") == "true"){
	add_action('pre_get_posts', 'argon_home_add_post_type_shuoshuo');
}
//首页隐藏特定分类文章
function argon_home_hide_categories($query){
	if (is_home() && $query -> is_main_query()){
		$excludeCategories = explode(",", get_option("lyrargon_hide_categories"));
		$excludeCategories = array_map(function($cat) { return -$cat; }, $excludeCategories);
		$query -> set('category__not_in', $excludeCategories);
		$query -> set('tag__not_in', $excludeCategories);
	}
	return $query;
}
if (get_option("lyrargon_hide_categories") != ""){
	add_action('pre_get_posts', 'argon_home_hide_categories');
}
//文章过时信息显示
function argon_get_post_outdated_info(){
	global $post;
	$post_show_outdated_info_status = strval(get_post_meta($post -> ID, 'lyrargon_show_post_outdated_info', true));
	if (get_option("lyrargon_outdated_info_tip_type") == "toast"){
		$before = "<div id='post_outdate_toast' style='display:none;' data-text='";
		$after = "'></div>";
	}else{
		$before = "<div class='post-outdated-info'><i class='fa fa-info-circle' aria-hidden='true'></i>";
		$after = "</div>";
	}
	$content = get_option('lyrargon_outdated_info_tip_content') == '' ? '本文最后更新于 %date_delta% 天前，其中的信息可能已经有所发展或是发生改变。' : get_option('lyrargon_outdated_info_tip_content');
	$delta = get_option('lyrargon_outdated_info_days') == '' ? (-1) : get_option('lyrargon_outdated_info_days');
	if ($delta == -1){
		$delta = 2147483647;
	}
	$post_date_delta = floor((current_time('timestamp') - get_the_time("U")) / (60 * 60 * 24));
	$modify_date_delta = floor((current_time('timestamp') - get_the_modified_time("U")) / (60 * 60 * 24));
	if (get_option("lyrargon_outdated_info_time_type") == "createdtime"){
		$date_delta = $post_date_delta;
	}else{
		$date_delta = $modify_date_delta;
	}
	if (($date_delta <= $delta && $post_show_outdated_info_status != 'always') || $post_show_outdated_info_status == 'never'){
		return "";
	}
	$content = str_replace("%date_delta%", $date_delta, $content);
	$content = str_replace("%modify_date_delta%", $modify_date_delta, $content);
	$content = str_replace("%post_date_delta%", $post_date_delta, $content);
	return $before . $content . $after;
}

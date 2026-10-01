<?php
	$share_platforms = get_option('lyrargon_share_platforms', false);
	if ($share_platforms === false || trim($share_platforms) === '') {
		$share_platforms = lyrargon_default_share_platforms();
	}
	$share_platform_list = array_filter(explode('|', trim($share_platforms)));
	if (empty($share_platform_list)) return;
?>
<div id="share_container">
	<div id="share" data-initialized="true">
		<?php
			foreach ($share_platform_list as $share_platform) {
				switch ($share_platform) {
					case 'wechat': ?>
			<a class="no-pjax icon-wechat" tooltip="<?php _e('分享到微信', 'lyrargon'); ?>">
				<button class="btn btn-icon btn-success">
					<span class="btn-inner--icon"><i class="fa fa-weixin"></i></span>
				</button>
			</a>
					<?php break;
					case 'douban': ?>
			<a target="_blank" class="no-pjax icon-douban" tooltip="<?php _e('分享到豆瓣', 'lyrargon'); ?>">
				<button class="btn btn-icon btn-primary" style="background: #209261;border: none;">
					<span aria-hidden="true">豆</span>
				</button>
			</a>
					<?php break;
					case 'qq': ?>
			<a target="_blank" class="no-pjax icon-qq" tooltip="<?php _e('分享到 QQ', 'lyrargon'); ?>">
				<button class="btn btn-icon btn-primary" style="background: #2196f3;border: none;">
					<span class="btn-inner--icon"><i class="fa fa-qq"></i></span>
				</button>
			</a>
					<?php break;
					case 'qzone': ?>
			<a target="_blank" class="no-pjax icon-qzone" tooltip="<?php _e('分享到 QQ 空间', 'lyrargon'); ?>">
				<button class="btn btn-icon btn-primary" style="background: #ffc107;border: none;">
					<span class="btn-inner--icon"><i class="fa fa-star"></i></span>
				</button>
			</a>
					<?php break;
					case 'weibo': ?>
			<a target="_blank" class="no-pjax icon-weibo" tooltip="<?php _e('分享到微博', 'lyrargon'); ?>">
				<button class="btn btn-icon btn-warning">
					<span class="btn-inner--icon"><i class="fa fa-weibo"></i></span>
				</button>
			</a>
					<?php break;
					case 'facebook': ?>
		<a target="_blank" class="no-pjax icon-facebook" tooltip="<?php _e('分享到 Facebook', 'lyrargon'); ?>">
			<button class="btn btn-icon btn-primary" style="background: #283593;border: none;">
				<span class="btn-inner--icon"><i class="fa fa-facebook"></i></span>
			</button>
		</a>
					<?php break;
					case 'twitter': ?>
		<a target="_blank" class="no-pjax icon-twitter" tooltip="<?php _e('分享到 Twitter', 'lyrargon'); ?>">
			<button class="btn btn-icon btn-primary" style="background: #03a9f4;border: none;">
				<span class="btn-inner--icon"><i class="fa fa-twitter"></i></span>
			</button>
		</a>
					<?php break;
					case 'telegram': ?>
		<a target="_blank" class="no-pjax icon-telegram" href="https://telegram.me/share/url?url=<?php echo urlencode(
$_SERVER['HTTP_HOST']);?>&text=<?php echo urlencode(html_entity_decode(get_the_title()));?>" tooltip="<?php _e('分享到 Telegram', 'lyrargon'); ?>">
			<button class="btn btn-icon btn-primary" style="background: #42a5f5;border: none;">
				<span class="btn-inner--icon"><i class="fa fa-telegram"></i></span>
			</button>
		</a>
					<?php break;
					case 'copy': ?>
		<a target="_blank" class="no-pjax icon-copy-link" id="share_copy_link" tooltip="<?php _e('复制链接', 'lyrargon'); ?>">
			<button class="btn btn-icon btn-default">
				<span class="btn-inner--icon"><i class="fa fa-link"></i></span>
			</button>
		</a>
					<?php break;
				}
			}
		?>
	</div>
	<button id="share_show" class="btn btn-icon btn-primary" tooltip="<?php _e('分享', 'lyrargon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-share"></i></span>
	</button>
</div>
<script type="text/javascript">
	socialShare("#share", {
	    title : '<?php echo addslashes(html_entity_decode(get_the_title())); ?>',
	    description : '<?php echo addslashes(html_entity_decode(wp_trim_words(html_entity_decode(get_the_content()), 50)));?>',
	    wechatQrcodeTitle : "<?php _e('分享到微信', 'lyrargon');?>",
	    wechatQrcodeHelper : '<?php _e('微信扫描二维码', 'lyrargon');?>',
	    source : '<?php global $post; echo get_permalink($post -> ID); ?>'
	});
	$("#share_show")[0].onclick = function(){
		$("#share_container").addClass("opened");
	};
	if ($("#share_copy_link").length) $("#share_copy_link")[0].onclick = function(){
		let input = document.createElement('input');
		document.body.appendChild(input);
		input.setAttribute("value", window.location.href);
		input.setAttribute("readonly", "readonly");
		input.setAttribute("style", "opacity: 0;mouse-events:none;");
		input.select();
		if (document.execCommand('copy')){
			iziToast.show({
				title: '<?php _e('链接已复制', 'lyrargon');?>',
				message: "<?php _e('链接已复制到剪贴板', 'lyrargon');?>",
				class: 'shadow',
				position: 'topRight',
				backgroundColor: '#2dce89',
				titleColor: '#ffffff',
				messageColor: '#ffffff',
				iconColor: '#ffffff',
				progressBarColor: '#ffffff',
				icon: 'fa fa-check',
				timeout: 5000
			});
		}else{
			iziToast.show({
				title: '<?php _e('复制失败', 'lyrargon');?>',
				message: "<?php _e('请手动复制链接', 'lyrargon');?>",
				class: 'shadow',
				position: 'topRight',
				backgroundColor: '#f5365c',
				titleColor: '#ffffff',
				messageColor: '#ffffff',
				iconColor: '#ffffff',
				progressBarColor: '#ffffff',
				icon: 'fa fa-close',
				timeout: 5000
			});
		}
		document.body.removeChild(input);
	};
</script>
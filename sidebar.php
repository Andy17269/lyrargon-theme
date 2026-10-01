<?php if (get_option('lyrargon_page_layout', 'double') == 'single') {
	return;
} ?>
<div id="sidebar_mask"></div>
<aside id="leftbar" class="leftbar widget-area" role="complementary">
		<?php if (get_option('lyrargon_sidebar_announcement') != '') { ?>
			<div id="leftbar_announcement" class="card bg-white shadow-sm border-0">
				<div class="leftbar-announcement-body">
					<div class="leftbar-announcement-title text-white"><?php _e('公告', 'lyrargon');?></div>
					<div class="leftbar-announcement-content text-white"><?php echo get_option('lyrargon_sidebar_announcement'); ?></div>
				</div>
			</div>
		<?php } ?>
		<div id="leftbar_part2" class="widget widget_search card bg-white shadow-sm border-0">
			<div id="leftbar_part2_inner" class="card-body">
				<?php
					$nowActiveTab = 1;/*默认激活的标签*/
					if (have_catalog()){
						$nowActiveTab = 0;
					}
				?>
				<div class="nav-wrapper" style="padding-top: 5px;<?php if (!have_catalog() && !is_active_sidebar('leftbar-tools')) { echo ' display:none;'; }?>">
	                <ul class="nav nav-pills nav-fill" role="tablist">
						<?php if (have_catalog()) { ?>
							<li class="nav-item sidebar-tab-switcher">
								<a class="<?php if ($nowActiveTab == 0) { echo 'active show'; }?>" id="leftbar_tab_catalog_btn" data-toggle="tab" href="#leftbar_tab_catalog" role="tab" aria-controls="leftbar_tab_catalog" no-pjax><?php _e('文章目录', 'lyrargon');?></a>
							</li>
						<?php } ?>
						<li class="nav-item sidebar-tab-switcher">
							<a class="<?php if ($nowActiveTab == 1) { echo 'active show'; }?>" id="leftbar_tab_overview_btn" data-toggle="tab" href="#leftbar_tab_overview" role="tab" aria-controls="leftbar_tab_overview" no-pjax><?php _e('站点概览', 'lyrargon');?></a>
						</li>
						<?php if ( is_active_sidebar( 'leftbar-tools' ) ){?>
							<li class="nav-item sidebar-tab-switcher">
								<a class="<?php if ($nowActiveTab == 2) { echo 'active show'; }?>" id="leftbar_tab_tools_btn" data-toggle="tab" href="#leftbar_tab_tools" role="tab" aria-controls="leftbar_tab_tools" no-pjax><?php _e('功能', 'lyrargon');?></a>
							</li>
						<?php }?>
	                </ul>
				</div>
				<div>
					<div class="tab-content" style="padding: 10px 10px 0 10px;">
						<?php if (have_catalog()) { ?>
							<div class="tab-pane fade<?php if ($nowActiveTab == 0) { echo ' active show'; }?>" id="leftbar_tab_catalog" role="tabpanel" aria-labelledby="leftbar_tab_catalog_btn">
								<div id="leftbar_catalog"></div>
								<script type="text/javascript">
									jQuery(function ($) {
										$(document).headIndex({
											articleWrapSelector: '#post_content',
											indexBoxSelector: '#leftbar_catalog',
											subItemBoxClass: "index-subItem-box",
											itemClass: "index-item",
											linkClass: "index-link",
											offset: 80,
										});
									})
								</script>
								<?php if (get_option('lyrargon_show_headindex_number') == 'true') {?>
									<style>
										#leftbar_catalog ul {
											counter-reset: blog_catalog_number;
										}
										#leftbar_catalog li.index-item > a:before {
											content: counters(blog_catalog_number, '.') " ";
											counter-increment: blog_catalog_number;
										}
									</style>
								<?php }?>
							</div>
						<?php } ?>
						<div class="tab-pane fade text-center<?php if ($nowActiveTab == 1) { echo ' active show'; }?>" id="leftbar_tab_overview" role="tabpanel" aria-labelledby="leftbar_tab_overview_btn">
							<?php
								$author_mode = get_option('lyrargon_sidebar_author_mode', 'single');
								$default_avatar = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCAxMDAgMTAwIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cmVjdCBmaWxsPSIjNUU3MkU0MjIiIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIi8+PGc+PGcgb3BhY2l0eT0iMC4zIj48cGF0aCBmaWxsPSIjNUU3MkU0IiBkPSJNNzQuMzksMzIuODZjLTAuOTgtMS43LTMuMzktMy4wOS01LjM1LTMuMDlINDUuNjJjLTEuOTYsMC00LjM3LDEuMzktNS4zNSwzLjA5TDI4LjU3LDUzLjE1Yy0wLjk4LDEuNy0wLjk4LDQuNDgsMCw2LjE3bDExLjcxLDIwLjI5YzAuOTgsMS43LDMuMzksMy4wOSw1LjM1LDMuMDloMjMuNDNjMS45NiwwLDQuMzctMS4zOSw1LjM1LTMuMDlMODYuMSw1OS4zMmMwLjk4LTEuNywwLjk4LTQuNDgsMCw2LjE3TDc0LjM5LDMyLjg2eiIvPjwvZz48ZyBvcGFjaXR5PSIwLjgiPjxwYXRoIGZpbGw9IiM1RTcyRTQiIGQ9Ik02Mi4wNCwyMC4zOWMtMC45OC0xLjctMy4zOS0zLjA5LTUuMzUtMy4wOUgzMS43M2MtMS45NiwwLTQuMzcsMS4zOS01LjM1LDMuMDlMMTMuOSw0Mi4wMWMtMC45OCwxLjctMC45OCw0LjQ4LDAsNi4xN2wxMi40OSwyMS42MmMwLjk4LDEuNywzLjM5LDMuMDksNS4zNSwzLjA5aDI0Ljk3YzEuOTYsMCw0LjM3LTEuMzksNS4zNS0zLjA5bDEyLjQ5LTIxLjYyYzAuOTgtMS43LDAuOTgtNC40OCwwLTYuMTdMNjIuMDQsMjAuMzl6Ii8+PC9nPjwvZz48L3N2Zz4=';
								$author1_name = get_option('lyrargon_sidebar_auther_name') == '' ? get_bloginfo('name') : get_option('lyrargon_sidebar_auther_name');
								$author1_image = get_option('lyrargon_sidebar_auther_image') == '' ? $default_avatar : get_option('lyrargon_sidebar_auther_image');
								$author1_desc = get_option('lyrargon_sidebar_author_description', '');

								$author2_name = get_option('lyrargon_sidebar_author2_name', '');
								$author2_image = get_option('lyrargon_sidebar_author2_image', '') == '' ? $default_avatar : get_option('lyrargon_sidebar_author2_image');
								$author2_desc = get_option('lyrargon_sidebar_author2_description', '');

								// 50% 概率随机选择默认首发展示的作者
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
								/*侧栏作者链接*/
								if (!class_exists('leftbarAuthorLinksWalker')) {
									class leftbarAuthorLinksWalker extends Walker_Nav_Menu{
										public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
											if ($depth == 0){
												$output .= "\n
												<div class='site-author-links-item'>
													<a href='" . $object -> url . "' rel='noopener' target='_blank'>". $object -> title . "</a>";
											}
										}
										public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
											if ($depth == 0){
												$output .= "</div>";
											}
										}
									}
								}

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
							<?php
								/*侧栏友情链接*/
								class leftbarFriendLinksWalker extends Walker_Nav_Menu{
									public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
										if ($depth == 0){
											$output .= "\n
											<li class='site-friend-links-item'>
												<a href='" . $object -> url . "' rel='noopener' target='_blank'>". $object -> title . "</a>";
										}
									}
									public function end_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {
										if ($depth == 0){
											$output .= "</li>";
										}
									}
								}

								if ( has_nav_menu('leftbar_friend_links') ){
									echo "<div class='site-friend-links'>
											<div class='site-friend-links-title'><i class='fa fa-fw fa-link'></i> Links</div>
											<ul class='site-friend-links-ul'>";
									wp_nav_menu( array(
										'container'  => '',
									    'theme_location'  => 'leftbar_friend_links',
										'items_wrap'  => '%3$s',
									    'depth' => 0,
										'walker' => new leftbarFriendLinksWalker()
									) );
									echo "</ul></div>";
								}else{
									echo "<div style='height: 20px;'></div>";
								}
							?>
							<?php if ( is_active_sidebar( 'leftbar-siteinfo-extra-tools' ) ){?>
								<div id="leftbar_siteinfo_extra_tools">
									<?php dynamic_sidebar( 'leftbar-siteinfo-extra-tools' ); ?>
								</div>
							<?php }?>
						</div>
						<?php if ( is_active_sidebar( 'leftbar-tools' ) ){?>
							<div class="tab-pane fade<?php if ($nowActiveTab == 2) { echo ' active show'; }?>" id="leftbar_tab_tools" role="tabpanel" aria-labelledby="leftbar_tab_tools_btn">
								<?php dynamic_sidebar( 'leftbar-tools' ); ?>
							</div>
						<?php }?>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(function($) {
				let $tabContent = $('#leftbar_part2 .tab-content');
				$tabContent.css({
					'transition': 'height 0.4s cubic-bezier(0.25, 1, 0.5, 1)',
					'overflow': 'hidden'
				});

				$('#leftbar_part2 a[data-toggle="tab"]').on('show.bs.tab', function (e) {
					let $newTabPane = $($(e.target).attr('href'));
					
					let currentHeight = $tabContent.height();
					$tabContent.css('height', currentHeight + 'px');
					
					// Force reflow
					$tabContent[0].offsetHeight;
					
					$newTabPane.css({
						display: 'block',
						position: 'absolute',
						visibility: 'hidden',
						width: $tabContent.width()
					});
					let newHeight = $newTabPane.outerHeight();
					$newTabPane.css({
						display: '',
						position: '',
						visibility: '',
						width: ''
					});
					
					$tabContent.css('height', newHeight + 'px');
				});
				
				$('#leftbar_part2 a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
					$tabContent.css('height', 'auto');
				});
			});
		</script>
</aside>
<div class="modal fade" id="blog_categories" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?php _e('分类', 'lyrargon');?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<?php
					$categories = get_categories(array(
						'child_of' => 0,
						'orderby' => 'name',
						'order' => 'ASC',
						'hide_empty' => 0,
						'hierarchical' => 0,
						'taxonomy' => 'category',
						'pad_counts' => false
					));
					foreach($categories as $category) {
						echo "<a href=" . get_category_link( $category -> term_id ) . " class='badge badge-secondary tag'>" . $category->name . " <span class='tag-num'>" . $category -> count . "</span></a>";
					}
				?>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="blog_tags" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title"><?php _e('标签', 'lyrargon');?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<?php
					$categories = get_categories(array(
						'child_of' => 0,
						'orderby' => 'name',
						'order' => 'ASC',
						'hide_empty' => 0,
						'hierarchical' => 0,
						'taxonomy' => 'post_tag',
						'pad_counts' => false
					));
					foreach($categories as $category) {
						echo "<a href=" . get_category_link( $category -> term_id ) . " class='badge badge-secondary tag'>" . $category->name . " <span class='tag-num'>" . $category -> count . "</span></a>";
					}
				?>
			</div>
		</div>
	</div>
</div>
<?php
	if (get_option('lyrargon_page_layout') == 'triple'){
		echo '<aside id="rightbar" class="rightbar widget-area" role="complementary">';
		dynamic_sidebar( 'rightbar-tools' );
		echo '</aside>';
	}
?>

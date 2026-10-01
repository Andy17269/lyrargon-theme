<?php
	$thumbnail_display = (is_single() && argon_has_post_thumbnail()) ? argon_get_post_thumbnail_display_type() : 'none';
	$has_banner_thumbnail = ($thumbnail_display === 'banner' && argon_has_post_thumbnail());
	$has_card_thumbnail = ($thumbnail_display === 'normal' && argon_has_post_thumbnail());
?>
<article class="post post-full card bg-white shadow-sm border-0" id="post-<?php the_ID(); ?>" <?php post_class(); ?><?php if ($has_banner_thumbnail) { echo ' data-banner-thumbnail="' . esc_url(argon_get_post_thumbnail()) . '"'; } ?>>
	<header class="post-header text-center<?php if ($has_card_thumbnail) { echo ' post-header-with-thumbnail'; } ?>">
		<?php
			if ($has_card_thumbnail){
				$thumbnail_url = argon_get_post_thumbnail();
				echo "<img class='post-thumbnail' src='" . esc_url($thumbnail_url) . "' crossorigin='anonymous'></img>";
			}
			if ($has_banner_thumbnail){
				$thumbnail_url = argon_get_post_thumbnail();
				echo "
				<style>
					body section.banner {
						background-image: url(" . esc_url($thumbnail_url) . ") !important;
					}
				</style>";
			}
		?>
		<div class="post-header-text-container">
			<a class="post-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			<div class="post-meta">
				<?php
					$metaList = explode('|', get_option('lyrargon_article_meta', 'time|views|comments|categories'));
					if (is_sticky() && is_home() && ! is_paged()){
						array_unshift($metaList, "sticky");
					}
					if (post_password_required()){
						array_unshift($metaList, "needpassword");
					}
					if (is_meta_simple()){
						array_remove($metaList, "time");
						array_remove($metaList, "edittime");
						array_remove($metaList, "categories");
						array_remove($metaList, "author");
					}
					if (count(get_the_category()) == 0){
						array_remove($metaList, "categories");
					}
					for ($i = 0; $i < count($metaList); $i++){
						if ($i > 0){
							echo ' <div class="post-meta-devide">|</div> ';
						}
						echo get_article_meta($metaList[$i]);
					}
				?>
			</div>
		</div>
	</header>

	<div class="post-content" id="post_content">
		<?php if (post_password_required()){ ?>
			<div class="text-center container">
				<form action="/wp-login.php?action=postpass" class="post-password-form" method="post">
					<div class="post-password-form-text"><?php _e('这是一篇受密码保护的文章，您需要提供访问密码', 'lyrargon');?></div>
					<div class="row">
						<div class="form-group col-lg-6 col-md-8 col-sm-10 col-xs-12 post-password-form-input">
							<div class="input-group input-group-alternative">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fa fa-key"></i></span>
								</div>
								<input name="post_password" class="form-control" placeholder="<?php _e('密码', 'lyrargon');?>" type="password">
							</div>
						</div>
					</div>
					<input class="btn btn-primary" type="submit" name="Submit" value="<?php _e('确认', 'lyrargon');?>">
				</form>
			</div>
		<?php
			}else{
				$show_month = get_option('lyrargon_archives_timeline_show_month', 'true');
				$POST = $GLOBALS['post'];
				echo "<div class='argon-timeline archive-timeline'>";
				$last_year = 0;
				$last_month = 0;
				$posts = get_posts('numberposts=-1&orderby=post_date&order=DESC');
				foreach ($posts as $post){
					setup_postdata($post);
					$year = mysql2date('Y', $post -> post_date);
					$month = mysql2date('M', $post -> post_date);
					if ($year != $last_year){
						echo "<div class='argon-timeline-node'>
								<h2 class='argon-timeline-time archive-timeline-year'><a href='" . get_year_link($year) . "'>" . $year . "</a></h2>
								<div class='argon-timeline-card card bg-gradient-secondary archive-timeline-title'></div>
							</div>";
							$last_year = $year;
							$last_month = 0;
					}
					if ($month != $last_month && $show_month == 'true'){
						echo "<div class='argon-timeline-node'>
								<h3 class='argon-timeline-time archive-timeline-month" . ($last_month == 0 ? " first-month-of-year" : "") . "'><a href='" . get_month_link($year, $month) . "'>" . $month . "</a></h3>
								<div class='argon-timeline-card card bg-gradient-secondary archive-timeline-title'></div>
							</div>";
							$last_month = $month;
					} ?>
					<div class='argon-timeline-node'>
						<div class='argon-timeline-time'><?php echo mysql2date('m-d', $post -> post_date); ?></div>
						<div class='argon-timeline-card card bg-gradient-secondary archive-timeline-title'>
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<small class="text-muted ml-2"><i class="fa fa-user" aria-hidden="true"></i> <?php echo get_the_author_meta('display_name', $post->post_author); ?></small>
						</div>
					</div>
					<?php
				}
				echo '</div>';
				$GLOBALS['post'] = $POST;
			}
		?>
	</div>

	<?php if (has_tag()) { ?>
		<div class="post-tags">
			<i class="fa fa-tags" aria-hidden="true"></i>
			<?php
				$tags = get_the_tags();
				foreach ($tags as $tag) {
					echo "<a href='" . get_tag_link($tag -> term_id) . "' target='_blank' class='tag badge badge-secondary post-meta-detail-tag'>" . $tag -> name . "</a>";
				}
			?>
		</div>
	<?php } ?>
</article>
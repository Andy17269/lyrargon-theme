<?php 
/*
Template Name: 服务状态 (Status)
*/
?>
<?php get_header(); ?>

<div class="page-information-card-container"></div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		while ( have_posts() ) :
			the_post();
		?>
		<article class="post post-full card bg-white shadow-sm border-0 status-page-article" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="post-header text-center">
				<div class="post-header-text-container">
					<a class="post-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</div>
			</header>
			<div class="post-content">
				<?php 
				$page_content = get_the_content();
				if ( ! empty( trim( $page_content ) ) ) {
					echo '<div class="status-page-custom-desc mb-4">' . apply_filters( 'the_content', $page_content ) . '</div>';
				}
				?>

				<?php if ( get_option( 'lyrargon_status_enabled' ) !== 'true' ) { ?>
					<div class="status-disabled-card card shadow-sm">
						<div class="status-disabled-inner text-center">
							<i class="fa fa-exclamation-circle status-disabled-icon"></i>
							<h3 class="status-disabled-title"><?php _e('服务状态监控功能未开启', 'lyrargon'); ?></h3>
							<p class="status-disabled-desc"><?php _e('站点管理员可前往 WordPress 管理后台 ->「Lyrargon 选项」->「服务状态监控 (Status)」启用并配置 UptimeRobot API Key。', 'lyrargon'); ?></p>
						</div>
					</div>
				<?php } else { ?>
					<?php 
					$cached_status = function_exists( 'lyrargon_get_uptime_cached_data' ) ? lyrargon_get_uptime_cached_data() : null;
					if ( ! empty( $cached_status ) ) {
					?>
						<script id="lyrargon_uptime_preload" type="application/json"><?php echo wp_json_encode( $cached_status ); ?></script>
					<?php } ?>

					<!-- 状态监控应用根容器 -->
					<div id="uptime_status_app" class="uptime-status-container" 
						data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
						data-days="<?php echo esc_attr( get_option( 'lyrargon_status_days', 90 ) ); ?>"
						data-show-link="<?php echo ( get_option( 'lyrargon_status_show_link' ) === 'true' ) ? '1' : '0'; ?>">
						
						<!-- 骨架屏加载状态 (High-Fidelity Skeleton Screen) -->
						<div class="uptime-loading-wrapper" id="uptime_skeleton_wrapper">
							<!-- 概览卡片骨架 -->
							<div class="uptime-skeleton-overview card shadow-sm">
								<div class="uptime-skeleton-banner">
									<div class="skeleton-shimmer skeleton-circle"></div>
									<div class="uptime-skeleton-banner-texts">
										<div class="skeleton-shimmer skeleton-title"></div>
									</div>
									<div class="skeleton-shimmer skeleton-refresh-btn"></div>
								</div>
							</div>
							<!-- 监控项目列表骨架 -->
							<div class="uptime-skeleton-monitors">
								<?php for ( $i = 0; $i < 3; $i++ ) : ?>
								<div class="uptime-skeleton-card card shadow-sm">
									<div class="uptime-skeleton-monitor-head">
										<div class="skeleton-shimmer skeleton-monitor-name"></div>
										<div class="uptime-skeleton-monitor-badges">
											<div class="skeleton-shimmer skeleton-pill"></div>
											<div class="skeleton-shimmer skeleton-badge"></div>
										</div>
									</div>
									<div class="skeleton-shimmer skeleton-timeline-bar"></div>
									<div class="uptime-skeleton-monitor-foot">
										<div class="skeleton-shimmer skeleton-foot-text"></div>
										<div class="skeleton-shimmer skeleton-foot-summary"></div>
										<div class="skeleton-shimmer skeleton-foot-text"></div>
									</div>
								</div>
								<?php endfor; ?>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
		</article>

		<?php
			if ( get_option( 'lyrargon_show_sharebtn' ) != 'false' ) {
				get_template_part( 'template-parts/share' );
			}

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>

<?php get_footer(); ?>

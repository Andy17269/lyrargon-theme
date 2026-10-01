<?php get_header(); ?>

<div class="page-information-card-container">
	<div class="page-information-card card shadow-sm border-0">
		<div class="card-body">
			<h2 class="page-information-title"><?php the_archive_title();?></h2>
			<?php if (the_archive_description() != ''){ ?>
				<p class="page-information-desc mt-2 mb-0">
					<?php the_archive_description(); ?>
				</p>
			<?php } ?>
			<p class="page-information-count mt-2 mb-0">
				<i class="fa fa-file-o mr-1"></i>
				<?php echo $wp_query -> found_posts; ?> <?php _e('篇文章', 'lyrargon');?>
			</p>
		</div>
	</div>
</div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main article-list" role="main">
	<?php if ( have_posts() ) : ?>
		<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content-preview', lyrargon_get_article_list_layout() );
			endwhile;
		?>
		<?php
			echo get_argon_formatted_paginate_links_for_all_platforms();
		?>
		<?php
	else :
		get_template_part( 'template-parts/preview/content', 'none-tag' );
	endif;
	?>

<?php get_footer(); ?>

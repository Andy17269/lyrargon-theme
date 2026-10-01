<?php 
/*
Template Name: 说说
*/
query_posts("post_type=shuoshuo&post_status=publish&posts_per_page=-1");
?>

<?php get_header(); ?>

<div class="page-information-card-container">
	<div class="page-information-card card shadow-sm border-0">
		<div class="card-body">
			<h2 class="page-information-title"><?php _e('说说', 'lyrargon');?></h2>
			<?php if (the_archive_description() != ''){ ?>
				<p class="page-information-desc mt-3">
					<?php the_archive_description(); ?>
				</p>
			<?php } ?>
			<p class="page-information-count mt-3 mb-0">
				<i class="fa fa-quote-left mr-1"></i>
				<?php echo wp_count_posts('shuoshuo','') -> publish; ?> <?php _e('条说说', 'lyrargon');?>
			</p>
		</div>
	</div>
</div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
	<?php if ( have_posts() ) : ?>
		<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'shuoshuo' );
			endwhile;
		?>
		<?php
			echo get_argon_formatted_paginate_links_for_all_platforms();
		?>
		<?php
	else :
		get_template_part( 'template-parts/content', 'none-tag' );
	endif;
	?>

<?php get_footer(); ?>

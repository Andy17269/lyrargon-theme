<?php get_header(); ?>

<div class="page-information-card-container">
	<div class="page-information-card card shadow-sm border-0" <?php if (isset($_GET['post_type'])){echo 'style="animation: none;"';}?>>
		<div class="card-body">
			<h2 class="page-information-title mr-2 d-inline-block"><?php echo get_search_query();?></h2>
			<p class="lead text-black mt-0 d-inline-block">
				<?php _e('的搜索结果', 'lyrargon');?>
			</p>
			<?php 
				if (get_option('lyrargon_enable_search_filters', 'true') == 'true'){ ?>
					<div class="search-filters">
						<?php
							$all_post_types= get_post_types(array(
								'public'   => true,
							), 'objects');
							$search_filters_type = explode(',', get_option('lyrargon_search_filters_type', '*post,*page,shuoshuo'));
							$current_filters_type = argon_get_search_post_type_array();
							foreach ($search_filters_type as $filter_type) {
								if ($filter_type[0] == '*'){ $filter_type = substr($filter_type, 1); }
								$checked = in_array($filter_type, $current_filters_type);
								if (isset($all_post_types[$filter_type])){
									$filter_name = $all_post_types[$filter_type] -> labels -> name;
								?>
									<div class="custom-control custom-checkbox search-filter-wrapper">
										<input class="custom-control-input search-filter" name="<?php echo $filter_type; ?>" id="search_filter_<?php echo $filter_type; ?>" type="checkbox" <?php echo $checked ? 'checked="true"' : ''; ?>>
										<label class="custom-control-label" for="search_filter_<?php echo $filter_type; ?>"><?php echo $filter_name; ?></label>
									</div>
								<?php
								}
							}
						?>
					</div>
					<script>
						$(".search-filter").prop("checked", false);
						$("<?php echo "#search_filter_" . implode(",#search_filter_", $current_filters_type); ?>").prop("checked", true);
					</script>
			<?php } ?>
			<p class="page-information-count mt-3 mb-0">
				<i class="fa fa-file-o mr-1"></i>
				<?php global $wp_query; echo $wp_query -> found_posts; ?> <?php _e('个结果', 'lyrargon');?>
			</p>
		</div>
	</div>
</div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main article-list search-result" role="main">
	<?php if ( have_posts() ) : ?>
		<?php
			while ( have_posts() ) :
				the_post();
				if (get_post_type() == 'shuoshuo'){
					get_template_part( 'template-parts/content-shuoshuo-preview' );
				}else{
					get_template_part( 'template-parts/content-preview', lyrargon_get_article_list_layout() );
				}
			endwhile;
		?>
		<?php
			echo get_argon_formatted_paginate_links_for_all_platforms();
		?>
		<?php
	else :
		get_template_part( 'template-parts/content', 'none-search' );
	endif;
	?>

<?php get_footer(); ?>

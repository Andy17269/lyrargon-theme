					<footer id="footer" class="site-footer card shadow-sm border-0">
						<?php
							echo get_option('lyrargon_footer_html');
						?>
						<div>
							Theme <a href="https://github.com/Andy17269/lyrargon" target="_blank"><strong>Lyrargon</strong></a><?php if (get_option('lyrargon_hide_footer_author') != 'true') {echo " By solstice23 & AndyWen"; }?></div>
					</footer>
				</main>
			</div>
		</div>
		<?php if (get_option('lyrargon_math_render') == 'mathjax3') { /*Mathjax V3*/?>
			<script>
				window.MathJax = {
					tex: {
						inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],
						displayMath: [['$$','$$']],
						processEscapes: true,
						packages: {'[+]': ['noerrors']}
					},
					options: {
						skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
						ignoreHtmlClass: 'tex2jax_ignore',
						processHtmlClass: 'tex2jax_process'
					},
					loader: {
						load: ['[tex]/noerrors']
					}
				};
			</script>
			<script src="<?php echo get_option('lyrargon_mathjax_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js' : get_option('lyrargon_mathjax_cdn_url'); ?>" id="MathJax-script" async></script>
		<?php }?>
		<?php if (get_option('lyrargon_math_render') == 'mathjax2') { /*Mathjax V2*/?>
			<script type="text/x-mathjax-config" id="mathjax_v2_script">
				MathJax.Hub.Config({
					messageStyle: "none",
					tex2jax: {
						inlineMath: [["$", "$"], ["\\\\(", "\\\\)"]],
						displayMath: [['$$','$$']],
						processEscapes: true,
						skipTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code']
					},
					menuSettings: {
						zoom: "Hover",
						zscale: "200%"
					},
					"HTML-CSS": {
						showMathMenu: "false"
					}
				});
			</script>
			<script src="<?php echo get_option('lyrargon_mathjax_v2_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/mathjax@2.7.5/MathJax.js?config=TeX-AMS_HTML' : get_option('lyrargon_mathjax_v2_cdn_url'); ?>"></script>
		<?php }?>
		<?php if (get_option('lyrargon_math_render') == 'katex') { /*Katex*/?>
			<link rel="stylesheet" href="<?php echo get_option('lyrargon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('lyrargon_katex_cdn_url'); ?>katex.min.css">
			<script src="<?php echo get_option('lyrargon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('lyrargon_katex_cdn_url'); ?>katex.min.js"></script>
			<script src="<?php echo get_option('lyrargon_katex_cdn_url') == '' ? '//cdn.jsdelivr.net/npm/katex@0.11.1/dist/' : get_option('lyrargon_katex_cdn_url'); ?>contrib/auto-render.min.js"></script>
			<script>
				document.addEventListener("DOMContentLoaded", function() {
					renderMathInElement(document.body,{
						delimiters: [
							{left: "$$", right: "$$", display: true},
							{left: "$", right: "$", display: false},
							{left: "\\(", right: "\\)", display: false}
						]
					});
				});
			</script>
		<?php }?>

		<?php 
			if (get_option('lyrargon_enable_code_highlight') == 'true') { /*Highlight.js (仅在页面含有代码块时服务端预输出，其他页面由客户端动态按需补齐)*/
				$should_load_code_css = false;
				if (is_singular()) {
					global $post;
					if ($post && (stripos($post->post_content, '<pre') !== false || stripos($post->post_content, '```') !== false || stripos($post->post_content, '<code') !== false)) {
						$should_load_code_css = true;
					}
				}
				if ($should_load_code_css) {
		?>
			<link id="argon_highlight_css" rel="stylesheet" href="<?php echo lyrargon_assets_path(); ?>/assets/vendor/highlight/styles/<?php echo get_option('lyrargon_code_theme') == '' ? 'vs2015' : get_option('lyrargon_code_theme'); ?>.css">
		<?php 
				}
			}
		?>

	</div>
</div>
<?php wp_footer(); ?>
</body>

<?php echo get_option('lyrargon_custom_html_foot'); ?>

</html>

<?php
/**
 * Lyrargon 主题核心引导文件
 *
 * 原 functions.php（3224 行）已按职责拆分到 inc/ 目录下的多个文件。
 * 本文件仅作为加载器，按原有执行顺序依次引入各模块，确保行为与拆分前完全一致。
 *
 * 模块划分：
 *   1. core.php          版本检查、数据库迁移、主题支持、全局变量、地区、更新检查、分析、时区
 *   2. widgets.php       小工具与后台配色
 *   3. post-media.php    文章媒体/缩略图、分页、访问量、字数/阅读时间、SEO、Token/Session
 *   4. comments.php      评论核心：UA、私聊、RSS 过滤、评论格式化、点赞
 *   5. comments-ajax.php 评论验证码、Ajax 评论、邮件通知、编辑/置顶、排序
 *   6. content-filters.php 内容过滤器：Lazyload、Fancybox、Gravatar CDN
 *   7. helpers.php       说说点赞与颜色计算工具函数
 *   8. metabox.php       文章 Meta 框、首页说说/隐藏分类、过时信息
 *   9. gutenberg.php     Gutenberg 区块注册与分类
 *  10. shortcodes.php    短代码
 *  11. tinymce.php       TinyMCE 按钮
 *  12. features.php      导航菜单、说说 CPT、搜索过滤、登录样式、自定义表情注入
 */

require_once get_template_directory() . '/inc/core.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/widgets.php';
require_once get_template_directory() . '/inc/post-media.php';
require_once get_template_directory() . '/inc/comments.php';
require_once get_template_directory() . '/inc/comments-ajax.php';
require_once get_template_directory() . '/inc/content-filters.php';
require_once get_template_directory() . '/inc/metabox.php';
require_once get_template_directory() . '/inc/gutenberg.php';
require_once get_template_directory() . '/inc/shortcodes.php';
require_once get_template_directory() . '/inc/tinymce.php';
require_once get_template_directory() . '/inc/uptime-status.php';

/**
 * 主题选项菜单
 * 注意：此函数保留在 functions.php 中，以维持 basename(__FILE__) 作为菜单 slug 的原有行为。
 */
function themeoptions_admin_menu(){
	/*后台管理面板侧栏添加选项*/
	add_menu_page(__("Lyrargon", 'lyrargon'), __("Lyrargon 选项", 'lyrargon'), 'edit_theme_options', basename(__FILE__), 'themeoptions_page');
	add_submenu_page(basename(__FILE__), __("Lyrargon 表情管理", 'lyrargon'), __("表情管理", 'lyrargon'), 'edit_theme_options', 'lyrargon_emoji_manager', 'argon_emoji_manager_page');
	add_submenu_page(basename(__FILE__), __("从 Argon 迁移", 'lyrargon'), __("从 Argon 迁移", 'lyrargon'), 'edit_theme_options', 'lyrargon_migration', 'lyrargon_migration_page');
}
include_once(get_template_directory() . '/settings.php');
include_once(get_template_directory() . '/settings-emojis.php');
include_once(get_template_directory() . '/settings-migrate.php');

// 导航菜单、说说 CPT、搜索过滤、登录样式、自定义表情注入（原 functions.php 3117-3224）
require_once get_template_directory() . '/inc/features.php';

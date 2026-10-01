<?php
if (!defined('ABSPATH')) exit;

function argon_emoji_manager_page() {
    if (!current_user_can('edit_theme_options')) {
        wp_die(__('您没有权限访问此页面。'));
    }

    // 确保 Media Library 脚本已加载
    wp_enqueue_media();

    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/wrgon-emojis';
    $base_url = $upload_dir['baseurl'] . '/wrgon-emojis';

    if (!file_exists($base_dir)) {
        wp_mkdir_p($base_dir);
    }

    $messages = array();

    // 处理删除操作
    if (isset($_POST['delete_emoji_pack']) && isset($_POST['emoji_pack_index'])) {
        check_admin_referer('lyrargon_delete_emoji', 'lyrargon_emoji_nonce');
        $custom_emojis = get_option('lyrargon_custom_emojis', array());
        $index = intval($_POST['emoji_pack_index']);
        if (isset($custom_emojis[$index])) {
            $folder_name = $custom_emojis[$index]['folder'];
            
            // 删除物理文件
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
            global $wp_filesystem;
            $pack_dir = $base_dir . '/' . $folder_name;
            if ($wp_filesystem->exists($pack_dir)) {
                $wp_filesystem->delete($pack_dir, true);
            }
            
            // 从配置中删除
            unset($custom_emojis[$index]);
            $custom_emojis = array_values($custom_emojis);
            update_option('lyrargon_custom_emojis', $custom_emojis);
            $messages[] = '<div class="updated"><p>表情包已删除。</p></div>';
        }
    }

    // 处理上传操作
    if (isset($_POST['upload_emoji_pack']) && isset($_FILES['emoji_zip'])) {
        check_admin_referer('lyrargon_upload_emoji', 'lyrargon_emoji_nonce');
        
        $file = $_FILES['emoji_zip'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'zip') {
                $messages[] = '<div class="error"><p>上传失败：仅支持 .zip 格式的表情包！</p></div>';
            } else {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
                global $wp_filesystem;
                
                $upload_file_path = $file['tmp_name'];
                
                // 解压到一个临时文件夹
                $temp_dir = $base_dir . '/temp_' . time() . '_' . wp_generate_password(6, false);
                $unzip_result = unzip_file($upload_file_path, $temp_dir);
                
                if (is_wp_error($unzip_result)) {
                    $messages[] = '<div class="error"><p>解压失败：' . $unzip_result->get_error_message() . '</p></div>';
                } else {
                    // 检查 config.json
                    $config_path = $temp_dir . '/config.json';
                    // 为了兼容可能被打包进一层文件夹的情况，查找一层
                    if (!$wp_filesystem->exists($config_path)) {
                        $files = $wp_filesystem->dirlist($temp_dir);
                        foreach ($files as $name => $details) {
                            if ($details['type'] == 'd') {
                                if ($wp_filesystem->exists($temp_dir . '/' . $name . '/config.json')) {
                                    $temp_dir = $temp_dir . '/' . $name;
                                    $config_path = $temp_dir . '/config.json';
                                    break;
                                }
                            }
                        }
                    }

                    if (!$wp_filesystem->exists($config_path)) {
                        $messages[] = '<div class="error"><p>包内未找到 config.json，请确认表情包是否正确！</p></div>';
                    } else {
                        $config_content = $wp_filesystem->get_contents($config_path);
                        $config = json_decode($config_content, true);
                        if (!$config || !isset($config['groupname']) || !isset($config['list'])) {
                            $messages[] = '<div class="error"><p>config.json 格式错误！</p></div>';
                        } elseif (!is_string($config['groupname']) || empty(trim($config['groupname']))) {
                            $messages[] = '<div class="error"><p>config.json 中 groupname 无效！</p></div>';
                        } elseif (!is_array($config['list'])) {
                            $messages[] = '<div class="error"><p>config.json 中 list 格式无效！</p></div>';
                        } else {
                            // --- 安全检查：检测 src 中的路径穿越（../） ---
                            $traversal_found = false;
                            foreach ($config['list'] as $item) {
                                if ($item['type'] === 'sticker' && isset($item['src']) && is_string($item['src'])) {
                                    // 检查是否包含 ../ 或绝对路径
                                    if (preg_match('#\.\./|\.\.\\\\|^/#', $item['src'])) {
                                        $traversal_found = true;
                                        break;
                                    }
                                    // 检查是否包含空字节或危险字符
                                    if (strpos($item['src'], "\0") !== false || preg_match('#[<>"\']#', $item['src'])) {
                                        $traversal_found = true;
                                        break;
                                    }
                                }
                            }
                            if ($traversal_found) {
                                $messages[] = '<div class="error"><p>config.json 中存在不安全的 src 路径（包含 ../ 或绝对路径），已拒绝安装！</p></div>';
                            } else {
                                // --- 安全检查：检测同包内是否有重复 code ---
                                $codes = array();
                                $duplicate_code_found = false;
                                foreach ($config['list'] as $item) {
                                    if ($item['type'] === 'sticker' && isset($item['code']) && is_string($item['code'])) {
                                        $code = trim($item['code']);
                                        if (!empty($code)) {
                                            if (isset($codes[$code])) {
                                                $duplicate_code_found = true;
                                                break;
                                            }
                                            $codes[$code] = true;
                                        }
                                    }
                                }
                                if ($duplicate_code_found) {
                                    $messages[] = '<div class="error"><p>config.json 中存在重复的 sticker code，已拒绝安装！</p></div>';
                                } else {
                                    // --- 安全检查：检测是否已有同名表情包 ---
                                    $existing_emojis = get_option('lyrargon_custom_emojis', array());
                                    $duplicate_groupname = false;
                                    foreach ($existing_emojis as $existing_pack) {
                                        if (isset($existing_pack['groupname']) && $existing_pack['groupname'] === $config['groupname']) {
                                            $duplicate_groupname = true;
                                            break;
                                        }
                                    }
                                    if ($duplicate_groupname) {
                                        $messages[] = '<div class="error"><p>同名表情包“' . esc_html($config['groupname']) . '”已存在，请先删除旧版本再安装！</p></div>';
                                    } else {
                                        // 确定最终文件夹名
                                        if (isset($config['foldername']) && !empty($config['foldername'])) {
                                            $folder_name = sanitize_title($config['foldername']);
                                            // 防止同名文件夹冲突
                                            if ($wp_filesystem->exists($base_dir . '/' . $folder_name)) {
                                                $folder_name .= '_' . wp_generate_password(4, false);
                                            }
                                        } else {
                                            $folder_name = sanitize_title($config['groupname']) . '_' . wp_generate_password(4, false);
                                        }
                                        
                                        $final_dir = $base_dir . '/' . $folder_name;
                                        
                                        // 移动文件
                                        $wp_filesystem->move($temp_dir, $final_dir);
                                        
                                        // 修正 src 路径
                                        $pack_url = $base_url . '/' . $folder_name;
                                        foreach ($config['list'] as &$item) {
                                            if ($item['type'] === 'sticker' && isset($item['src']) && !preg_match('/^http/', $item['src'])) {
                                                $item['src'] = $pack_url . '/' . ltrim($item['src'], '/');
                                            }
                                        }
                                        
                                        // 存入数据库
                                        $config['folder'] = $folder_name;
                                        $custom_emojis = get_option('lyrargon_custom_emojis', array());
                                        $custom_emojis[] = $config;
                                        update_option('lyrargon_custom_emojis', $custom_emojis);
                                        
                                        $messages[] = '<div class="updated"><p>表情包“' . esc_html($config['groupname']) . '”已安装。</p></div>';
                                    }
                                }
                            }
                        }
                    }
                }
                // 清理可能遗留的临时目录
                $temp_base = $base_dir . '/temp_';
                if ($wp_filesystem->exists($temp_dir) && strpos($temp_dir, $temp_base) !== false) {
                    $wp_filesystem->delete($temp_dir, true);
                }
            }
        } else {
            $messages[] = '<div class="error"><p>上传出错，错误代码' . $file['error'] . '</p></div>';
        }
    }

    // 处理编辑表情包（名称 + 表情图片，一次保存）
    if (isset($_POST['edit_emoji_pack']) && isset($_POST['emoji_pack_index'])) {
        $edit_index = intval($_POST['emoji_pack_index']);
        check_admin_referer('lyrargon_edit_emoji_' . $edit_index, 'lyrargon_emoji_edit_nonce');
        $custom_emojis_temp = get_option('lyrargon_custom_emojis', array());
        $new_name = isset($_POST['emoji_groupname']) ? trim(wp_unslash($_POST['emoji_groupname'])) : '';
        if (isset($custom_emojis_temp[$edit_index])) {
            if (empty($new_name)) {
                $messages[] = '<div class="error"><p>名称不能为空！</p></div>';
            } else {
                $custom_emojis_temp[$edit_index]['groupname'] = $new_name;
                if (isset($_POST['sticker']) && is_array($_POST['sticker'])) {
                    foreach ($_POST['sticker'] as $sidx => $update) {
                        $sidx_int = intval($sidx);
                        if (isset($custom_emojis_temp[$edit_index]['list'][$sidx_int])) {
                            if (isset($update['src']) && !empty($update['src'])) {
                                $custom_emojis_temp[$edit_index]['list'][$sidx_int]['src'] = esc_url_raw(trim($update['src']));
                            }
                            if (isset($update['title']) && !empty($update['title'])) {
                                $custom_emojis_temp[$edit_index]['list'][$sidx_int]['title'] = sanitize_text_field(trim($update['title']));
                            }
                        }
                    }
                }
                update_option('lyrargon_custom_emojis', $custom_emojis_temp);
                $messages[] = '<div class="updated"><p>表情包已保存。</p></div>';
            }
        }
    }

    $custom_emojis = get_option('lyrargon_custom_emojis', array());
    ?>
    <style>
        .argon-emojis-page {
            margin-top: 24px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
            border: 1px solid #dbe3ea;
            border-radius: 18px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            animation: lyrargon-fade-in 0.2s ease;
        }
        @keyframes lyrargon-fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .argon-emojis-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }
        .argon-emojis-title {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 300;
            letter-spacing: -0.03em;
        }
        .argon-emojis-title a {
            margin-left: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            text-decoration: none;
        }
        .argon-emojis-title a:hover {
            color: #0f172a;
        }
        .argon-emojis-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }
        .argon-emojis-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.95fr);
            gap: 18px;
            align-items: start;
        }
        .argon-emojis-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .argon-emojis-card-header {
            padding: 16px 18px 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .argon-emojis-card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
        }
        .argon-emojis-card-header p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }
        .argon-emojis-notices {
            margin-bottom: 18px;
            display: grid;
            gap: 10px;
        }
        .argon-emojis-notice {
            border-radius: 14px;
            padding: 12px 14px;
            border: 1px solid;
            font-size: 13px;
            line-height: 1.7;
            background: #fff;
        }
        .argon-emojis-notice.updated {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }
        .argon-emojis-notice.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }
        .argon-emojis-table-wrap {
            overflow-x: auto;
        }
        .argon-emojis-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .argon-emojis-table thead th {
            text-align: left;
            padding: 14px 18px;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .argon-emojis-table tbody td {
            padding: 16px 18px;
            border-bottom: 1px solid #eef2f7;
            color: #0f172a;
            vertical-align: top;
            font-size: 14px;
        }
        .argon-emojis-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .argon-emojis-empty {
            text-align: center;
            color: #64748b;
            padding: 22px 18px;
        }
        .argon-emojis-delete-form {
            margin: 0;
        }
        .argon-emojis-delete-form .button-link-delete {
            padding: 0;
            color: #dc2626 !important;
            text-decoration: none;
            font-weight: 600;
            border: none;
            background: none;
            cursor: pointer;
        }
        .argon-emojis-link-edit {
            color: #2271b1;
            text-decoration: none;
            font-weight: 600;
        }
        .argon-emojis-link-edit:hover {
            color: #135e96;
        }
        .argon-emojis-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .argon-emojis-upload {
            padding: 18px;
        }
        .argon-emojis-upload input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #fff;
        }
        .argon-emojis-upload .button-primary {
            background: linear-gradient(180deg, #2f9af3 0%, #1686ea 100%);
            border-color: #1686ea;
            box-shadow: 0 10px 24px rgba(33, 150, 243, 0.16);
        }
        .argon-emojis-upload .button-primary:hover,
        .argon-emojis-upload .button-primary:focus {
            background: linear-gradient(180deg, #3aa2fb 0%, #1686ea 100%);
            border-color: #1686ea;
        }
        @media (max-width: 960px) {
            .argon-emojis-page {
                padding: 18px;
            }
            .argon-emojis-grid {
                grid-template-columns: 1fr;
            }
            .argon-emojis-header {
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .argon-emoji-edit-form-wrap {
                padding: 16px;
            }
            .argon-emoji-edit-form-wrap .edit-field {
                flex-direction: column;
                align-items: stretch;
                gap: 6px;
            }
            .argon-emoji-edit-form-wrap .edit-field label {
                min-width: 0;
            }
            .argon-emoji-edit-sticker {
                flex-wrap: wrap;
            }
            .argon-emoji-edit-sticker .sticker-src-field {
                flex-basis: 100%;
            }
        }

        /* --- 编辑页面样式 --- */
        .argon-emoji-edit-page {
            max-width: 100%;
            margin: 0;
        }
        .argon-emoji-edit-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            background: #fff;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.15s ease;
        }
        .argon-emoji-edit-back:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .argon-emoji-edit-form-wrap {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            padding: 24px;
        }
        .argon-emoji-edit-form-wrap .edit-field {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .argon-emoji-edit-form-wrap .edit-field label {
            min-width: 100px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
            flex-shrink: 0;
        }
        .argon-emoji-edit-form-wrap .edit-field input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }
        .argon-emoji-edit-form-wrap .edit-field input[type="text"]:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: none;
        }
        .argon-emoji-edit-form-wrap .edit-field-desc label {
            color: #64748b;
        }
        .argon-emoji-desc-text {
            color: #475569;
            font-size: 14px;
            line-height: 1.6;
        }
        .argon-emoji-edit-stickers {
            display: grid;
            gap: 12px;
        }
        .argon-emoji-edit-sticker {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .argon-emoji-edit-sticker .sticker-preview {
            width: 48px;
            height: 48px;
            object-fit: contain;
            flex-shrink: 0;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        .argon-emoji-edit-sticker .sticker-code {
            font-family: monospace;
            font-size: 13px;
            color: #64748b;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 3px 10px;
            border-radius: 5px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .argon-emoji-edit-sticker .sticker-src-field {
            flex: 1;
            min-width: 0;
            display: flex;
            gap: 6px;
        }
        .argon-emoji-edit-sticker .sticker-src-field input[type="url"] {
            flex: 1;
            min-width: 0;
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12px;
            font-family: monospace;
        }
        .argon-emoji-edit-sticker .sticker-src-field input[type="url"]:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: none;
        }
        .argon-emoji-edit-sticker .media-select-btn {
            flex-shrink: 0;
            padding: 5px 14px;
            font-size: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            color: #475569;
            white-space: nowrap;
        }
        .argon-emoji-edit-sticker .media-select-btn:hover {
            background: #f1f5f9;
        }
        .argon-emoji-edit-stickers-title {
            font-weight: 600;
            color: #334155;
            font-size: 14px;
            margin: 0 0 4px;
            padding: 0;
        }
        .argon-emoji-edit-submit-wrap {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .argon-emoji-edit-submit-wrap .button-primary {
            background: linear-gradient(180deg, #2f9af3 0%, #1686ea 100%);
            border-color: #1686ea;
            box-shadow: 0 10px 24px rgba(33, 150, 243, 0.16);
        }
        .argon-emoji-edit-submit-wrap .button-primary:hover,
        .argon-emoji-edit-submit-wrap .button-primary:focus {
            background: linear-gradient(180deg, #3aa2fb 0%, #1686ea 100%);
            border-color: #1686ea;
        }
    </style>

    <?php
    // 检查是否进入编辑模式
    $edit_mode = isset($_GET['edit_pack']) && is_numeric($_GET['edit_pack']);
    $edit_index = $edit_mode ? intval($_GET['edit_pack']) : -1;
    $edit_pack = $edit_mode && isset($custom_emojis[$edit_index]) ? $custom_emojis[$edit_index] : null;
    ?>

    <div class="wrap argon-emojis-page">
        <div class="argon-emojis-header">
            <div>
                <h1 class="argon-emojis-title">
                    Lyrargon 表情包管理
                    <?php if (!$edit_mode): ?>
                    <a href="https://lyrargon.wenlei.top/stickers" target="_blank" rel="noopener noreferrer">获取表情包 →</a>
                    <?php endif; ?>
                </h1>
            </div>
        </div>

        <?php if (!empty($messages)): ?>
            <div class="argon-emojis-notices">
                <?php foreach ($messages as $msg) {
                    $notice_class = (strpos($msg, 'updated') !== false) ? 'updated' : 'error';
                    echo '<div class="argon-emojis-notice ' . esc_attr($notice_class) . '">' . wp_kses_post($msg) . '</div>';
                } ?>
            </div>
        <?php endif; ?>

        <?php if ($edit_mode && $edit_pack): ?>

        <!-- ===== 编辑模式 ===== -->
        <div class="argon-emoji-edit-page">
            <a href="?page=lyrargon_emoji_manager" class="argon-emoji-edit-back">&larr; 返回表情包列表</a>

            <div class="argon-emoji-edit-form-wrap">
                <form method="post" action="?page=lyrargon_emoji_manager&edit_pack=<?php echo $edit_index; ?>">
                    <?php wp_nonce_field('lyrargon_edit_emoji_' . $edit_index, 'lyrargon_emoji_edit_nonce'); ?>
                    <input type="hidden" name="emoji_pack_index" value="<?php echo $edit_index; ?>" />

                    <div class="edit-field">
                        <label for="emoji_groupname">表情包名称</label>
                        <input type="text" id="emoji_groupname" name="emoji_groupname" value="<?php echo esc_attr($edit_pack['groupname']); ?>" required />
                    </div>
                    <?php if (isset($edit_pack['description']) && !empty($edit_pack['description'])): ?>
                    <div class="edit-field edit-field-desc">
                        <label>表情包描述</label>
                        <span class="argon-emoji-desc-text"><?php echo esc_html($edit_pack['description']); ?></span>
                    </div>
                    <?php endif; ?>

                    <p class="argon-emoji-edit-stickers-title">表情图片（共 <?php echo count($edit_pack['list']); ?> 个）</p>
                    <div class="argon-emoji-edit-stickers">
                        <?php foreach ($edit_pack['list'] as $sidx => $sticker): ?>
                        <div class="argon-emoji-edit-sticker">
                            <img class="sticker-preview" src="<?php echo esc_url($sticker['src'] ?? ''); ?>" alt="" onerror="this.style.display='none'" />
                            <span class="sticker-code">:<?php echo esc_html($sticker['code'] ?? ''); ?>:</span>
                            <div class="sticker-src-field">
                                <input type="url" name="sticker[<?php echo $sidx; ?>][src]" value="<?php echo esc_url($sticker['src'] ?? ''); ?>" placeholder="图片 URL" />
                                <button type="button" class="media-select-btn" data-target="sticker[<?php echo $sidx; ?>][src]">媒体库</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="argon-emoji-edit-submit-wrap">
                        <button type="submit" name="edit_emoji_pack" class="button button-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>

        <!-- ===== 列表模式 ===== -->
        <div class="argon-emojis-grid">
            <section class="argon-emojis-card">
                <div class="argon-emojis-card-header">
                    <h2>已安装的表情包</h2>
                </div>
                <div class="argon-emojis-table-wrap">
                    <table class="argon-emojis-table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 25%;">名称</th>
                                <th scope="col" style="width: 40%;">描述</th>
                                <th scope="col" style="width: 15%;">数量</th>
                                <th scope="col" style="width: 20%;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($custom_emojis)): ?>
                                <tr><td colspan="4" class="argon-emojis-empty">尚未安装自定义表情包。</td></tr>
                            <?php else: ?>
                                <?php foreach ($custom_emojis as $index => $pack): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($pack['groupname']); ?></strong></td>
                                        <td><?php echo esc_html(isset($pack['description']) ? $pack['description'] : '-'); ?></td>
                                        <td><?php echo count($pack['list']); ?> 个</td>
                                        <td>
                                            <div class="argon-emojis-actions">
                                                <a href="?page=lyrargon_emoji_manager&edit_pack=<?php echo $index; ?>" class="argon-emojis-link-edit">编辑</a>
                                                <form class="argon-emojis-delete-form" method="post" action="" style="display:inline;" onsubmit="return confirm('确定要删除此表情包及相关文件吗？');">
                                                    <?php wp_nonce_field('lyrargon_delete_emoji', 'lyrargon_emoji_nonce'); ?>
                                                    <input type="hidden" name="emoji_pack_index" value="<?php echo $index; ?>" />
                                                    <button type="submit" name="delete_emoji_pack" class="button-link-delete">删除</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="argon-emojis-card">
                <div class="argon-emojis-card-header">
                    <h2>添加新表情包</h2>
                </div>
                <form class="argon-emojis-upload" method="post" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('lyrargon_upload_emoji', 'lyrargon_emoji_nonce'); ?>
                    <p>
                        <input type="file" name="emoji_zip" accept=".zip" required />
                    </p>
                    <p style="margin: 14px 0 0; display: flex; justify-content: flex-end;">
                        <button type="submit" name="upload_emoji_pack" class="button button-primary">安装</button>
                    </p>
                </form>
            </section>
        </div>

        <?php endif; ?>
    </div>

    <script type="text/javascript">
    (function($){
        // Media Library 选择器（编辑页面使用）
        var fileFrame = null;
        $(document).on('click', '.media-select-btn', function(e){
            e.preventDefault();
            var btn = $(this);
            var targetName = btn.data('target');
            var input = $('input[name="' + targetName + '"]');

            if (fileFrame) {
                fileFrame.open();
                return;
            }

            fileFrame = wp.media({
                title: '选择表情图片',
                button: { text: '使用此图片' },
                multiple: false,
                library: { type: 'image' }
            });

            fileFrame.on('select', function(){
                var attachment = fileFrame.state().get('selection').first().toJSON();
                if (input.length) {
                    input.val(attachment.url);
                    var preview = btn.closest('.argon-emoji-edit-sticker').find('.sticker-preview');
                    if (preview.length) {
                        preview.attr('src', attachment.url).show();
                    }
                }
            });

            fileFrame.open();
        });
    })(jQuery);
    </script>

    <?php
}
?>

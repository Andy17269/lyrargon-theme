<?php
if (!defined('ABSPATH')) exit;

	function lyrargon_migration_page() {
		if (!current_user_can('edit_theme_options')) {
			wp_die(__('您没有权限访问此页面。'));
		}
		global $wpdb;

		$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'db_migrate';
	$messages = array();

	// ====== JSON 导入处理（文件 / 粘贴） ======
	if (isset($_POST['import_argon_json']) && check_admin_referer('lyrargon_import_json', 'lyrargon_import_nonce')) {
		$json_content = null;

		if (!empty($_POST['json_paste'])) {
			$json_content = stripslashes($_POST['json_paste']);
		} elseif (isset($_FILES['argon_json_file']) && $_FILES['argon_json_file']['error'] === UPLOAD_ERR_OK) {
			$json_content = file_get_contents($_FILES['argon_json_file']['tmp_name']);
		} else {
			$upload_error = $_FILES['argon_json_file']['error'] ?? -1;
			if ($upload_error !== UPLOAD_ERR_NO_FILE && $upload_error !== UPLOAD_ERR_OK) {
				$messages[] = array('type' => 'error', 'text' => __('文件上传失败，请重试。', 'lyrargon'));
			} else {
				$messages[] = array('type' => 'error', 'text' => __('请上传 JSON 文件或粘贴 JSON 内容。', 'lyrargon'));
			}
		}

		if ($json_content !== null) {
			$data = json_decode($json_content, true);
			if ($data === null) {
				$messages[] = array('type' => 'error', 'text' => __('JSON 格式错误，请检查。', 'lyrargon'));
			} else {
				$import_count = 0;
				$mapped_count = 0;
				foreach ($data as $key => $value) {
					$mapped_key = preg_replace('/^argon_/', 'lyrargon_', $key);
					if ($mapped_key !== $key) {
						if (get_option($mapped_key) === false) {
							update_option($mapped_key, $value);
							$import_count++;
						}
						$mapped_count++;
					}
				}
				$messages[] = array(
					'type' => 'success',
					'text' => sprintf(
						__('成功处理 %d 项设置，其中 %d 项已导入到 Lyrargon（未覆盖已存在的设置）。', 'lyrargon'),
						$mapped_count, $import_count
					)
				);
			}
		}
	}

	// ====== 手动触发 DB 迁移 ======
	if (isset($_POST['run_db_migration']) && check_admin_referer('lyrargon_run_migration', 'lyrargon_migrate_nonce')) {
		delete_option('lyrargon_db_version');
		lyrargon_migrate_old_options();
		$messages[] = array(
			'type' => 'success',
			'text' => __('旧主题设置已经成功迁移到 Lyrargon。', 'lyrargon')
		);
	}

	// ====== 删除残留 argon_* 旧选项 ======
	if (isset($_POST['delete_old_options']) && check_admin_referer('lyrargon_delete_old', 'lyrargon_delete_nonce')) {
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'argon\_%'"
		);
		$deleted = ($deleted === false) ? 0 : (int) $deleted;
		$messages[] = array(
			'type' => 'success',
			'text' => sprintf(__('数据库已清理完成，删除 %d 项旧主题设置。', 'lyrargon'), $deleted)
		);
	}

	// ====== 数据库状态查询 ======
	$db_migrated = get_option('lyrargon_db_version');
	global $wpdb;
	$old_options_count = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'argon\_%'"
	);
	$new_options_count = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'lyrargon\_%'"
	);

	?>
	<div class="wrap lyrargon-migrate-page">
		<div class="lyrargon-migrate-header">
			<h1 class="lyrargon-migrate-title"><?php _e('从 Argon 迁移到 Lyrargon', 'lyrargon'); ?></h1>
			<p class="lyrargon-migrate-subtitle"><?php _e('如果之前使用的是 Argon-Theme 主题，可以通过此页面将 Argon 原有设置迁移到 Lyrargon。', 'lyrargon'); ?></p>
			<p class="lyrargon-migrate-subtitle"><?php _e('在从 Argon 迁移到 Lyrargon 之前，请先将 Argon-Theme 升级至最新版本。', 'lyrargon'); ?></p>
		</div>

		<?php if (!empty($messages)): ?>
			<div class="lyrargon-migrate-notices">
				<?php foreach ($messages as $msg): ?>
					<div class="lyrargon-migrate-notice <?php echo $msg['type'] === 'success' ? 'updated' : 'error'; ?>">
						<?php echo esc_html($msg['text']); ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="lyrargon-migrate-tabs">
			<a href="#" data-tab="db_migrate" class="lyrargon-migrate-tab <?php echo $active_tab == 'db_migrate' ? 'active' : ''; ?>">
				<?php _e('通过数据库迁移', 'lyrargon'); ?>
			</a>
			<a href="#" data-tab="json_import" class="lyrargon-migrate-tab <?php echo $active_tab == 'json_import' ? 'active' : ''; ?>">
				<?php _e('通过 JSON 导入', 'lyrargon'); ?>
			</a>
			<a href="#" data-tab="report" class="lyrargon-migrate-tab <?php echo $active_tab == 'report' ? 'active' : ''; ?>">
				<?php _e('迁移日志', 'lyrargon'); ?>
			</a>
		</div>

		<div class="lyrargon-migrate-content">
		<div class="lyrargon-migrate-tab-panel<?php echo $active_tab == 'db_migrate' ? '' : ' lyrargon-migrate-hidden'; ?>" data-tab="db_migrate">
		<section class="lyrargon-migrate-card">
			<div class="lyrargon-migrate-card-header">
				<h2><?php _e('设置迁移', 'lyrargon'); ?></h2>
			</div>
			<div class="lyrargon-migrate-card-body">
			<?php if (!empty($db_migrated)): ?>
				<div class="lyrargon-migrate-notice success">
					<?php _e('你的 Argon 主题设置已经迁移到 Lyrargon。', 'lyrargon'); ?>
				</div>
			<?php endif; ?>

			<?php
			$has_error = false;
			foreach ($messages as $m) { if ($m['type'] === 'error') { $has_error = true; break; } }
			?>
			<table class="lyrargon-migrate-table">
				<tbody>
					<tr>
						<td><?php _e('原 Argon-Theme 设置选项', 'lyrargon'); ?></td>
						<td>
							<?php if ($old_options_count == 0): ?>
								<span class="lyrargon-status-badge success"><?php _e('已清理', 'lyrargon'); ?></span>
							<?php elseif (!empty($db_migrated)): ?>
								<span class="lyrargon-status-badge success"><?php _e('已迁移', 'lyrargon'); ?></span>
							<?php else: ?>
								<span class="lyrargon-status-badge warning"><?php _e('待处理', 'lyrargon'); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ($has_error): ?>
			<details class="lyrargon-migrate-details" open>
				<summary><?php _e('显示详情', 'lyrargon'); ?></summary>
				<table class="lyrargon-migrate-table">
					<tbody>
						<tr><td><?php _e('旧设置数量', 'lyrargon'); ?></td><td><?php echo $old_options_count; ?></td></tr>
						<tr><td><?php _e('当前设置数量', 'lyrargon'); ?></td><td><?php echo $new_options_count; ?></td></tr>
					</tbody>
				</table>
			</details>
			<?php endif; ?>

			<p class="lyrargon-migrate-desc"><?php _e('在正常情况下，Lyrargon 会尝试自动导入 Argon 的设置选项。但若显示「待处理」，可尝试进行手动迁移。', 'lyrargon'); ?></p>

			<?php if ($old_options_count > 0 && empty($db_migrated)): ?>
			<form method="post" action="">
				<?php wp_nonce_field('lyrargon_run_migration', 'lyrargon_migrate_nonce'); ?>
				<p class="lyrargon-migrate-actions">
					<button type="submit" name="run_db_migration" class="lyrargon-btn lyrargon-btn-primary">
						<?php _e('开始迁移', 'lyrargon'); ?>
					</button>
				</p>
			</form>
			<?php endif; ?>

			<?php if ($old_options_count > 0): ?>
				<hr class="lyrargon-migrate-divider" />
				<p class="lyrargon-migrate-desc"><?php _e('如果确定不再使用 Argon 主题，可以把这些旧设置彻底删除：', 'lyrargon'); ?></p>
				<form method="post" action="" onsubmit="return confirm('<?php echo esc_js(__('确定要删除旧主题设置吗？此操作不可撤销。', 'lyrargon')); ?>');">
					<?php wp_nonce_field('lyrargon_delete_old', 'lyrargon_delete_nonce'); ?>
					<p class="lyrargon-migrate-actions">
						<button type="submit" name="delete_old_options" class="lyrargon-btn lyrargon-btn-danger">
							<?php _e('删除旧主题设置', 'lyrargon'); ?>
						</button>
					</p>
				</form>
			<?php endif; ?>
			</div>
		</section>
		</div>

		<div class="lyrargon-migrate-tab-panel<?php echo $active_tab == 'json_import' ? '' : ' lyrargon-migrate-hidden'; ?>" data-tab="json_import">
		<section class="lyrargon-migrate-card">
			<div class="lyrargon-migrate-card-header">
				<h2><?php _e('从 Argon 备份 JSON 导入', 'lyrargon'); ?></h2>
			</div>
			<div class="lyrargon-migrate-card-body">
				<p class="lyrargon-migrate-desc"><?php _e('如果您之前从 Argon 导出了设置备份（JSON 文件），可以在这里上传，系统会自动转成 Lyrargon 的格式。', 'lyrargon'); ?></p>

				<div class="lyrargon-migrate-notice info">
					<?php _e('仅导入尚未存在的设置项，不会覆盖您当前在 Lyrargon 中已配置的值。', 'lyrargon'); ?>
				</div>

				<form method="post" action="" enctype="multipart/form-data" class="lyrargon-migrate-upload">
					<?php wp_nonce_field('lyrargon_import_json', 'lyrargon_import_nonce'); ?>
					<div class="lyrargon-migrate-field">
						<input type="file" name="argon_json_file" id="argon_json_file" accept=".json" />
					</div>
					<p class="lyrargon-migrate-actions">
						<button type="submit" name="import_argon_json" class="lyrargon-btn lyrargon-btn-primary">
							<?php _e('导入并映射到 Lyrargon', 'lyrargon'); ?>
						</button>
					</p>
				</form>

			<hr class="lyrargon-migrate-divider" />

			<h3 class="lyrargon-migrate-heading"><?php _e('或者直接粘贴 JSON', 'lyrargon'); ?></h3>
			<form method="post" action="">
				<?php wp_nonce_field('lyrargon_import_json', 'lyrargon_import_nonce'); ?>
				<div class="lyrargon-migrate-field">
					<textarea name="json_paste" rows="8" class="lyrargon-migrate-textarea" placeholder="<?php echo esc_attr(__('在此粘贴 JSON 内容…', 'lyrargon')); ?>"></textarea>
				</div>
				<p class="lyrargon-migrate-actions">
					<button type="submit" name="import_argon_json" class="lyrargon-btn lyrargon-btn-primary">
						<?php _e('导入并映射到 Lyrargon', 'lyrargon'); ?>
					</button>
				</p>
			</form>
			</div>
		</section>
		</div>

		<div class="lyrargon-migrate-tab-panel<?php echo $active_tab == 'report' ? '' : ' lyrargon-migrate-hidden'; ?>" data-tab="report">
		<section class="lyrargon-migrate-card">
			<div class="lyrargon-migrate-card-header">
				<h2><?php _e('迁移结果报告', 'lyrargon'); ?></h2>
			</div>
			<div class="lyrargon-migrate-card-body">
				<?php
				$old_rows = $wpdb->get_results(
					"SELECT option_name, option_value FROM {$wpdb->options}
					 WHERE option_name LIKE 'argon\_%'"
				);
				$new_rows = $wpdb->get_results(
					"SELECT option_name, option_value FROM {$wpdb->options}
					 WHERE option_name LIKE 'lyrargon\_%'"
				);
				?>

				<?php
				$has_error = false;
				foreach ($messages as $m) { if ($m['type'] === 'error') { $has_error = true; break; } }
				?>

				<?php if ($old_options_count == 0): ?>
					<div class="lyrargon-migrate-notice success">
						<?php _e('✅ 没有需要处理的旧设置，一切就绪。', 'lyrargon'); ?>
					</div>
				<?php else: ?>
					<p class="lyrargon-migrate-desc">
						<?php
						if (!empty($db_migrated)) {
							printf(__('已把 %d 项旧设置迁移到 Lyrargon。', 'lyrargon'), $old_options_count + count($new_rows));
						} else {
							printf(__('还有 %d 项旧设置待处理。', 'lyrargon'), $old_options_count);
						}
						?>
					</p>
				<?php endif; ?>

				<?php if ($old_options_count > 0): ?>
					<form method="post" action="" onsubmit="return confirm('<?php echo esc_js(__('确定要删除旧主题设置吗？此操作不可撤销。', 'lyrargon')); ?>');">
						<?php wp_nonce_field('lyrargon_delete_old', 'lyrargon_delete_nonce'); ?>
						<p class="lyrargon-migrate-actions">
							<button type="submit" name="delete_old_options" class="lyrargon-btn lyrargon-btn-danger">
								<?php _e('删除旧主题设置', 'lyrargon'); ?>
							</button>
						</p>
					</form>
				<?php endif; ?>

				<?php if ($has_error): ?>
				<details class="lyrargon-migrate-details" open>
					<summary><?php _e('显示详情', 'lyrargon'); ?></summary>
					<h3 class="lyrargon-migrate-heading"><?php _e('概览', 'lyrargon'); ?></h3>
					<table class="lyrargon-migrate-table">
						<tbody>
							<tr><td><?php _e('旧设置数量', 'lyrargon'); ?></td><td><?php echo count($old_rows); ?></td></tr>
							<tr><td><?php _e('当前设置数量', 'lyrargon'); ?></td><td><?php echo count($new_rows); ?></td></tr>
						</tbody>
					</table>

					<?php if (!empty($old_rows)): ?>
						<h3 class="lyrargon-migrate-heading"><?php _e('残留的旧设置', 'lyrargon'); ?></h3>
						<div class="lyrargon-migrate-scroll">
							<table class="lyrargon-migrate-table">
								<tbody>
									<?php foreach ($old_rows as $row): ?>
										<tr><td><code><?php echo esc_html($row->option_name); ?></code></td></tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>

					<?php if (!empty($new_rows)): ?>
						<h3 class="lyrargon-migrate-heading"><?php _e('当前设置预览', 'lyrargon'); ?></h3>
						<div class="lyrargon-migrate-scroll">
							<table class="lyrargon-migrate-table">
								<tbody>
									<?php foreach (array_slice($new_rows, 0, 50) as $row): ?>
										<tr>
											<td><code><?php echo esc_html($row->option_name); ?></code></td>
											<td><code style="font-size: 11px; word-break: break-all;"><?php echo esc_html(substr($row->option_value, 0, 120)); ?></code></td>
										</tr>
									<?php endforeach; ?>
									<?php if (count($new_rows) > 50): ?>
										<tr><td colspan="2"><em><?php _e('… 仅显示前 50 项', 'lyrargon'); ?></em></td></tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</details>
				<?php endif; ?>
			</div>
		</section>
		</div>

		</div>
	</div>

	<style>
		/* === 页面主体 === */
		.lyrargon-migrate-page {
			margin-top: 24px;
			background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
			border: 1px solid #dbe3ea;
			border-radius: 18px;
			box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
			padding: 24px;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
		}

		/* === 头部 === */
		.lyrargon-migrate-header {
			margin-bottom: 18px;
		}
		.lyrargon-migrate-title {
			margin: 0;
			color: #0f172a;
			font-size: 28px;
			font-weight: 300;
			letter-spacing: -0.03em;
		}
		.lyrargon-migrate-subtitle {
			margin: 6px 0 0;
			color: #64748b;
			font-size: 13px;
			line-height: 1.7;
		}

		/* === Tab 切换 === */
		.lyrargon-migrate-tabs {
			display: flex;
			gap: 4px;
			margin-bottom: 18px;
			background: #f1f5f9;
			border-radius: 14px;
			padding: 4px;
		}
		.lyrargon-migrate-tab {
			flex: 1;
			text-align: center;
			padding: 10px 16px;
			border-radius: 12px;
			font-size: 13px;
			font-weight: 600;
			color: #64748b;
			text-decoration: none;
			transition: all 0.2s;
		}
		.lyrargon-migrate-tab:hover {
			color: #0f172a;
			background: rgba(255,255,255,0.6);
		}
		.lyrargon-migrate-tab.active {
			background: #fff;
			color: #0f172a;
			box-shadow: 0 1px 3px rgba(15,23,42,0.08);
		}

		/* === 通知消息 === */
		.lyrargon-migrate-notices {
			margin-bottom: 18px;
			display: grid;
			gap: 10px;
		}
		.lyrargon-migrate-notice {
			border-radius: 14px;
			padding: 12px 14px;
			border: 1px solid;
			font-size: 13px;
			line-height: 1.7;
			background: #fff;
		}
		.lyrargon-migrate-notice.updated,
		.lyrargon-migrate-notice.success {
			border-color: #bbf7d0;
			background: #f0fdf4;
			color: #166534;
		}
		.lyrargon-migrate-notice.error {
			border-color: #fecaca;
			background: #fef2f2;
			color: #991b1b;
		}
		.lyrargon-migrate-notice.info {
			border-color: #bfdbfe;
			background: #eff6ff;
			color: #1e40af;
		}

		/* === 卡片 === */
		.lyrargon-migrate-card {
			background: #fff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
			overflow: hidden;
		}
		.lyrargon-migrate-card-header {
			padding: 16px 18px 14px;
			border-bottom: 1px solid #e2e8f0;
		}
		.lyrargon-migrate-card-header h2 {
			margin: 0;
			font-size: 18px;
			font-weight: 600;
			color: #0f172a;
		}
		.lyrargon-migrate-card-body {
			padding: 18px;
		}

		/* === 表格 === */
		.lyrargon-migrate-table {
			width: 100%;
			border-collapse: separate;
			border-spacing: 0;
			margin-bottom: 14px;
		}
		.lyrargon-migrate-table thead th {
			text-align: left;
			padding: 14px 18px;
			color: #475569;
			font-size: 13px;
			font-weight: 600;
			background: #f8fafc;
			border-bottom: 1px solid #e2e8f0;
		}
		.lyrargon-migrate-table tbody td {
			padding: 16px 18px;
			border-bottom: 1px solid #eef2f7;
			color: #0f172a;
			font-size: 14px;
		}
		.lyrargon-migrate-table tbody tr:last-child td {
			border-bottom: 0;
		}
		.lyrargon-migrate-scroll {
			max-height: 300px;
			overflow-y: auto;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
		}

		/* === 状态徽章 === */
		.lyrargon-status-badge {
			display: inline-block;
			padding: 2px 10px;
			border-radius: 999px;
			font-size: 12px;
			font-weight: 600;
		}
		.lyrargon-status-badge.success {
			background: #f0fdf4;
			color: #166534;
		}
		.lyrargon-status-badge.warning {
			background: #fffbeb;
			color: #92400e;
		}

		/* === 按钮 === */
		.lyrargon-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			padding: 9px 18px;
			border-radius: 999px;
			font-size: 13px;
			font-weight: 600;
			border: 1px solid transparent;
			cursor: pointer;
			text-decoration: none;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			line-height: 1.4;
			box-sizing: border-box;
		}
		.lyrargon-btn:active {
			transform: scale(0.98);
		}
		.lyrargon-btn-primary {
			background: linear-gradient(180deg, #2f9af3 0%, #1686ea 100%);
			border-color: #1686ea;
			color: #fff;
			box-shadow: 0 4px 14px rgba(33, 150, 243, 0.25);
		}
		.lyrargon-btn-primary:hover {
			background: linear-gradient(180deg, #3aa2fb 0%, #1686ea 100%);
			color: #fff;
			box-shadow: 0 6px 20px rgba(33, 150, 243, 0.35);
			transform: translateY(-1px);
		}
		.lyrargon-btn-primary:disabled {
			opacity: 0.5;
			cursor: not-allowed;
			box-shadow: none;
			transform: none;
		}
		.lyrargon-btn-secondary {
			background: #fff;
			border-color: #cbd5e1;
			color: #334155;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
		}
		.lyrargon-btn-secondary:hover {
			background: #f8fafc;
			border-color: #2196f3;
			color: #2196f3;
			transform: translateY(-1px);
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
		}
		.lyrargon-btn-danger {
			background: linear-gradient(180deg, #f87171 0%, #ef4444 100%);
			border-color: #ef4444;
			color: #fff;
			box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
		}
		.lyrargon-btn-danger:hover {
			background: linear-gradient(180deg, #fa7d7d 0%, #ef4444 100%);
			color: #fff;
			box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
			transform: translateY(-1px);
		}
		.lyrargon-migrate-actions {
			margin: 14px 0 0;
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.lyrargon-migrate-details {
			margin: 16px 0 0;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			background: #f8fafc;
			padding: 10px 14px;
		}
		.lyrargon-migrate-details summary {
			cursor: pointer;
			font-size: 13px;
			font-weight: 600;
			color: #475569;
		}
		.lyrargon-migrate-details[open] summary {
			margin-bottom: 10px;
		}

		/* === 其他 === */
		.lyrargon-migrate-desc {
			color: #64748b;
			font-size: 13px;
			line-height: 1.7;
			margin: 8px 0;
		}
		.lyrargon-migrate-heading {
			margin: 1.5em 0 0.5em;
			font-size: 15px;
			font-weight: 600;
			color: #0f172a;
		}
		.lyrargon-migrate-divider {
			margin: 22px 0;
			border: none;
			border-top: 1px solid #e2e8f0;
		}
		.lyrargon-migrate-upload {
			margin-top: 16px;
		}
		.lyrargon-migrate-upload input[type="file"] {
			width: 100%;
			padding: 10px 12px;
			border: 1px solid #cbd5e1;
			border-radius: 12px;
			background: #fff;
		}
		.lyrargon-migrate-field {
			margin-bottom: 12px;
		}
		.lyrargon-migrate-textarea {
			width: 100%;
			padding: 12px 14px;
			border: 1px solid #cbd5e1;
			border-radius: 12px;
			background: #fff;
			color: #0f172a;
			font-size: 13px;
			font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
			line-height: 1.6;
			resize: vertical;
			box-sizing: border-box;
		}
		.lyrargon-migrate-textarea:focus {
			border-color: #94a3b8;
			outline: none;
			box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.15);
		}
		.lyrargon-migrate-steps {
			padding-left: 20px;
			color: #475569;
			font-size: 13px;
			line-height: 2;
		}

		/* === Tab 面板显隐 === */
		.lyrargon-migrate-hidden {
			display: none;
		}
		.lyrargon-migrate-tab-panel {
			transition: opacity 0.15s ease;
		}
		.lyrargon-migrate-tab-panel.lyrargon-migrate-loading {
			opacity: 0.35;
			pointer-events: none;
		}

		@media (max-width: 960px) {
			.lyrargon-migrate-page {
				padding: 18px;
			}
		}
	</style>
	<script>
		(function() {
			var tabs = document.querySelectorAll('.lyrargon-migrate-tab');
			var panels = document.querySelectorAll('.lyrargon-migrate-tab-panel');
			var currentTab = '<?php echo esc_js($active_tab); ?>';

			tabs.forEach(function(tab) {
				tab.addEventListener('click', function(e) {
					e.preventDefault();
					var tabName = this.getAttribute('data-tab');
					if (tabName === currentTab) return;

					// 切换 active 样式
					tabs.forEach(function(t) { t.classList.remove('active'); });
					this.classList.add('active');

					// 切换面板并加入淡入淡出
					panels.forEach(function(p) { p.classList.add('lyrargon-migrate-hidden'); });
					setTimeout(function() {
						var target = document.querySelector('.lyrargon-migrate-tab-panel[data-tab="' + tabName + '"]');
						if (target) {
							target.classList.remove('lyrargon-migrate-hidden');
							target.classList.add('lyrargon-migrate-loading');
							requestAnimationFrame(function() {
								target.classList.remove('lyrargon-migrate-loading');
							});
						}
						currentTab = tabName;
					}, 150);
				});
			});
		})();
	</script>
	<?php
}

/**
 * AI Agent - 前端交互逻辑
 *
 * 依赖：jQuery（主题已自带）
 * 提供：AI 助手浮动聊天窗口的交互、REST API 调用、消息渲染
 */
(function($) {
	'use strict';

	var AiAgent = {
		// DOM 缓存
		$window: null,
		$messages: null,
		$input: null,
		$send: null,
		$status: null,
		$btn: null,
		$header: null,
		$minimize: null,
		$close: null,

		// 状态
		isOpen: false,
		isMinimized: false,
		isLoading: false,
		history: [],          // 消息历史 [{role, content}]
		maxHistory: 20,       // 最大保留历史轮数

		// REST API 路径
		restBase: window.aiAgentConfig ? window.aiAgentConfig.restBase : '/wp-json/lyrargon/v1/agent',

		/**
		 * 初始化
		 */
		init: function() {
			this.$window   = $('#ai_agent_window');
			this.$messages = $('#ai_agent_messages');
			this.$input    = $('#ai_agent_input');
			this.$send     = $('#ai_agent_send');
			this.$status   = $('#ai_agent_status');
			this.$btn      = $('#fabtn_ai_agent');
			this.$header   = $('#ai_agent_header');
			this.$minimize = $('#ai_agent_minimize');
			this.$close    = $('#ai_agent_close');

			if (!this.$window.length) return;

			this.bindEvents();
		},

		/**
		 * 绑定事件
		 */
		bindEvents: function() {
			var self = this;

			// 按钮点击切换窗口
			this.$btn.on('click', function(e) {
				e.stopPropagation();
				self.toggle();
			});

			// 关闭按钮
			this.$close.on('click', function(e) {
				e.stopPropagation();
				self.close();
			});

			// 最小化按钮
			this.$minimize.on('click', function(e) {
				e.stopPropagation();
				self.toggleMinimize();
			});

			// 发送按钮
			this.$send.on('click', function() {
				self.sendMessage();
			});

			// 回车发送
			this.$input.on('keydown', function(e) {
				if (e.keyCode === 13 && !e.shiftKey) {
					e.preventDefault();
					self.sendMessage();
				}
			});

			// 窗口拖拽
			this.makeDraggable();

			// 点击窗口外部不关闭
			this.$window.on('click', function(e) {
				e.stopPropagation();
			});
		},

		/**
		 * 切换窗口显示
		 */
		toggle: function() {
			if (this.isOpen) {
				this.close();
			} else {
				this.open();
			}
		},

		/**
		 * 打开窗口
		 */
		open: function() {
			this.isOpen = true;
			// 先以「关闭态」渲染（保留 flex 布局），强制重排后再移除，
			// 这样 opacity/transform 过渡才会真正生效（进场动画）
			this.$window.css('display', 'flex').addClass('ai-agent-closed');
			this.$window[0].offsetHeight; // 触发重排
			this.$window.removeClass('ai-agent-closed');
			this.$input.focus();
		},

		/**
		 * 关闭窗口
		 */
		close: function() {
			this.isOpen = false;
			this.$window.addClass('ai-agent-closed');
			var self = this;
			setTimeout(function() {
				if (!self.isOpen) {
					self.$window.css('display', 'none');
				}
			}, 200);
		},

		/**
		 * 切换最小化
		 */
		toggleMinimize: function() {
			this.isMinimized = !this.isMinimized;
			this.$window.toggleClass('ai-agent-minimized', this.isMinimized);
			if (!this.isMinimized) {
				this.$input.focus();
			}
		},

		/**
		 * 窗口拖拽
		 */
		makeDraggable: function() {
			var self = this;
			var isDragging = false;
			var startX, startY, origX, origY;

			this.$header.on('mousedown', function(e) {
				if ($(e.target).closest('button').length) return;
				isDragging = true;
				var rect = self.$window[0].getBoundingClientRect();
				origX = rect.left;
				origY = rect.top;
				startX = e.clientX;
				startY = e.clientY;
				self.$window.css('left', origX + 'px');
				self.$window.css('bottom', 'auto');
				self.$window.css('top', origY + 'px');
				e.preventDefault();
			});

			$(document).on('mousemove', function(e) {
				if (!isDragging) return;
				var dx = e.clientX - startX;
				var dy = e.clientY - startY;
				self.$window.css('left', (origX + dx) + 'px');
				self.$window.css('top', (origY + dy) + 'px');
			});

			$(document).on('mouseup', function() {
				isDragging = false;
			});
		},

		/**
		 * 发送消息
		 */
		sendMessage: function() {
			var text = this.$input.val().trim();
			if (!text) return;
			if (this.isLoading) return;

			this.$input.val('');
			this.addMessage('user', text);
			this.showTyping();
			this.setLoading(true);

			// 构建请求
			var payload = {
				message: text,
				history: this.history.slice(-this.maxHistory)
			};

			var self = this;
			$.ajax({
				url: this.restBase + '/chat',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify(payload),
				success: function(res) {
					self.hideTyping();
					self.setLoading(false);
					if (res.reply) {
						self.addMessage('agent', res.reply, res.qos);
						// 更新历史
						self.history.push({ role: 'user', content: text });
						self.history.push({ role: 'assistant', content: res.reply });
						// 裁剪历史
						if (self.history.length > self.maxHistory * 2) {
							self.history = self.history.slice(-self.maxHistory * 2);
						}
					} else {
						self.addErrorMessage(res.message || 'AI 返回为空');
					}
				},
				error: function(jqXHR) {
					self.hideTyping();
					self.setLoading(false);
					var msg = '请求失败';
					try {
						var body = JSON.parse(jqXHR.responseText);
						if (body.message) msg = body.message;
					} catch(e) {}
					self.addErrorMessage(msg);
				}
			});
		},

		/**
		 * 添加消息气泡
		 */
		addMessage: function(role, content, qos) {
			var isAgent = (role === 'agent');
			var $msg = $('<div class="ai-message ai-message-' + (isAgent ? 'agent' : 'user') + '">');
			var avatarHtml = '<div class="ai-message-avatar"><i class="fa fa-' + (isAgent ? 'commenting-o' : 'user') + '"></i></div>';
			var contentHtml = '<div class="ai-message-content">' + this.renderContent(content) + '</div>';
			$msg.append(avatarHtml + contentHtml);
			this.$messages.append($msg);
			this.scrollToBottom();

			// 更新状态栏（如果包含 QoS 信息）
			if (qos) {
				this.$status.text('今日剩余 ' + (qos.daily_limit - qos.daily_current + 1) + '/' + qos.daily_limit + ' 次');
				this.$status.show();
			}
		},

		/**
		 * 添加错误消息
		 */
		addErrorMessage: function(text) {
			var $msg = $('<div class="ai-message ai-message-error">');
			$msg.append('<div class="ai-message-avatar"><i class="fa fa-exclamation-triangle"></i></div>');
			$msg.append('<div class="ai-message-content">' + this.escapeHtml(text) + '</div>');
			this.$messages.append($msg);
			this.scrollToBottom();
		},

		/**
		 * 渲染消息内容（Markdown 转 HTML + 文章卡片）
		 */
		renderContent: function(text) {
			if (!text) return '';
			var html = this.markdownToHtml(text);
			// 转换文章链接为卡片
			html = this.convertArticleLinks(html);
			return html;
		},

		/**
		 * 简单的 Markdown 转 HTML
		 */
		markdownToHtml: function(text) {
			var html = this.escapeHtml(text);

			// 代码块 (```)
			html = html.replace(/```(\w*)\n([\s\S]*?)```/g, function(match, lang, code) {
				var langClass = lang ? ' class="language-' + lang + '"' : '';
				return '<pre><code' + langClass + '>' + code.trim() + '</code></pre>';
			});

			// 行内代码 (`)
			html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

			// 加粗 (**text**)
			html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

			// 斜体 (*text*)
			html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

			// 图片 ![alt](url)
			html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" style="max-width:100%;border-radius:4px;">');

			// 链接 [text](url) - 标记特殊属性以便后续转换为卡片
			html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" class="ai-link" data-title="$1">$1</a>');

			// 标题 (## text)
			html = html.replace(/^### (.+)$/gm, '<h4>$1</h4>');
			html = html.replace(/^## (.+)$/gm, '<h3>$1</h3>');
			html = html.replace(/^# (.+)$/gm, '<h2>$1</h2>');

			// 无序列表 (- item)
			html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
			html = html.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');

			// 有序列表 (1. item)
			html = html.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');
			// 避免重复包裹
			html = html.replace(/(<ul>)?(<li>.*<\/li>\n?)+(<\/ul>)?/g, function(m) {
				if (m.indexOf('<ul>') === -1) return '<ul>' + m + '</ul>';
				return m;
			});

			// 段落（连续的文本行）
			html = html.replace(/\n\n/g, '</p><p>');
			html = '<p>' + html + '</p>';

			// 清理空段落和多余的包裹
			html = html.replace(/<p><\/p>/g, '');
			html = html.replace(/<p>\n?<\/p>/g, '');
			html = html.replace(/<p><\/li>/g, '</li>');
			html = html.replace(/<li><\/p>/g, '<li>');
			html = html.replace(/<\/ul><p>/g, '</ul>');
			html = html.replace(/<p><ul>/g, '<ul>');

			// 换行转 <br>
			html = html.replace(/\n/g, '<br>');

			return html;
		},

		/**
		 * 将文章链接转换为卡片样式
		 */
		convertArticleLinks: function(html) {
			var self = this;
			return html.replace(/<a[^>]+class="ai-link"[^>]*>.*?<\/a>/g, function(match) {
				var url = match.match(/href="([^"]+)"/);
				var title = match.match(/data-title="([^"]+)"/);
				if (!url) return match;
				url = url[1];
				title = title ? title[1] : url;
				// 检查是否为本博客链接（包含文章路径）
				var isLocal = url.indexOf(window.location.hostname) > -1 || url.indexOf('/') === 0;
				if (isLocal) {
					return '<a href="' + url + '" target="_blank" class="ai-article-card">'
						+ '<span class="ai-article-card-title">' + self.escapeHtml(title) + '</span>'
						+ '<span class="ai-article-card-excerpt">' + self.escapeHtml(title) + '</span>'
						+ '<span class="ai-article-card-meta"><i class="fa fa-external-link"></i> ' + self.escapeHtml(url) + '</span>'
						+ '</a>';
				}
				return match;
			});
		},

		/**
		 * 显示打字指示器
		 */
		showTyping: function() {
			var $indicator = $(
				'<div class="ai-message ai-message-agent ai-message-typing">'
				+ '<div class="ai-message-avatar"><i class="fa fa-commenting-o"></i></div>'
				+ '<div class="ai-message-content"><div class="ai-typing-indicator">'
				+ '<span></span><span></span><span></span>'
				+ '</div></div></div>'
			);
			this.$messages.append($indicator);
			this.scrollToBottom();
		},

		/**
		 * 隐藏打字指示器
		 */
		hideTyping: function() {
			this.$messages.find('.ai-message-typing').remove();
		},

		/**
		 * 设置加载状态
		 */
		setLoading: function(loading) {
			this.isLoading = loading;
			this.$send.prop('disabled', loading);
			this.$input.prop('disabled', loading);
		},

		/**
		 * 滚动到底部
		 */
		scrollToBottom: function() {
			this.$messages.scrollTop(this.$messages[0].scrollHeight);
		},

		/**
		 * HTML 转义
		 */
		escapeHtml: function(text) {
			return $('<span>').text(text).html();
		}
	};

	// 页面加载完成后初始化
	$(document).ready(function() {
		AiAgent.init();
	});

})(jQuery);

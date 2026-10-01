/**
 * Lyrargon 服务状态监控 (Uptime Status) 原生前端逻辑
 *
 * 完整复用 UpTimeStatus-Pro 的数据统计、时间切片与状态计算逻辑，
 * 采用原生 JavaScript/jQuery 与主题原生 Argon UI 组件渲染。
 * 支持「服务端预载 + LocalStorage 浏览器即时缓存 + 后台静默刷新」三重零延迟极速呈现体系。
 */

(function($) {
	'use strict';

	const LOCAL_CACHE_KEY = 'lyrargon_uptime_local_cache_v2';
	const REFRESH_INTERVAL_SECONDS = 60;
	let countdownInterval = null;
	let remainingSeconds = REFRESH_INTERVAL_SECONDS;
	let isFetching = false;

	function formatNumber(value) {
		let num = parseFloat(value);
		if (isNaN(num)) return '0.00';
		return (Math.floor(num * 100) / 100).toFixed(2);
	}

	function formatDuration(seconds) {
		let s = parseInt(seconds, 10);
		if (isNaN(s) || s <= 0) return '0 秒';
		let m = 0;
		let h = 0;
		let d = 0;
		if (s >= 60) {
			m = Math.floor(s / 60);
			s = s % 60;
			if (m >= 60) {
				h = Math.floor(m / 60);
				m = m % 60;
				if (h >= 24) {
					d = Math.floor(h / 24);
					h = h % 24;
				}
			}
		}
		let text = '';
		if (d > 0) text += d + ' 天 ';
		if (h > 0) text += h + ' 小时 ';
		if (m > 0) text += m + ' 分 ';
		if (s > 0 || text === '') text += s + ' 秒';
		return text.trim();
	}

	function formatDateTime(timestamp) {
		let date = new Date(timestamp * 1000);
		let y = date.getFullYear();
		let m = String(date.getMonth() + 1).padStart(2, '0');
		let d = String(date.getDate()).padStart(2, '0');
		let hh = String(date.getHours()).padStart(2, '0');
		let mm = String(date.getMinutes()).padStart(2, '0');
		let ss = String(date.getSeconds()).padStart(2, '0');
		return y + '-' + m + '-' + d + ' ' + hh + ':' + mm + ':' + ss;
	}

	function parseMonitorData(monitor, days) {
		let now = new Date();
		now.setHours(0, 0, 0, 0);
		let dates = [];
		let dateMap = {};

		for (let i = 0; i < days; i++) {
			let d = new Date(now.getTime() - (i * 86400000));
			let y = d.getFullYear();
			let m = String(d.getMonth() + 1).padStart(2, '0');
			let day = String(d.getDate()).padStart(2, '0');
			let dateStr = y + '-' + m + '-' + day;
			let dateKey = y + m + day;
			dates.push(dateStr);
			dateMap[dateKey] = i;
		}

		let rawRanges = monitor.custom_uptime_ranges ? String(monitor.custom_uptime_ranges) : '';
		let ranges = rawRanges ? rawRanges.split('-') : [];
		let average = ranges.length > 0 ? formatNumber(ranges.pop() || 0) : '100.00';

		let daily = [];
		for (let i = 0; i < days; i++) {
			let uptimeVal = ranges[i] !== undefined && ranges[i] !== '' ? formatNumber(ranges[i]) : '0.00';
			daily.push({
				date: dates[i],
				uptime: uptimeVal,
				down: { times: 0, duration: 0 }
			});
		}

		// 解析故障日志
		let total = { times: 0, duration: 0 };
		if (Array.isArray(monitor.logs)) {
			monitor.logs.forEach(function(log) {
				if (parseInt(log.type, 10) === 1) { // 1 = Down 故障事件
					let logTime = parseInt(log.datetime, 10) || 0;
					let logDate = new Date(logTime * 1000);
					let ly = logDate.getFullYear();
					let lm = String(logDate.getMonth() + 1).padStart(2, '0');
					let ld = String(logDate.getDate()).padStart(2, '0');
					let key = ly + lm + ld;
					let dailyIdx = dateMap[key];
					let duration = parseInt(log.duration, 10) || 0;

					total.duration += duration;
					total.times += 1;

					if (dailyIdx !== undefined && daily[dailyIdx]) {
						daily[dailyIdx].down.duration += duration;
						daily[dailyIdx].down.times += 1;
					}
				}
			});
		}

		// 判定当前监控项状态: 2 = 正常, 9 = 停机, 其他 = 未知/暂停
		let status = 'unknown';
		if (parseInt(monitor.status, 10) === 2) status = 'ok';
		else if (parseInt(monitor.status, 10) === 9) status = 'down';

		return {
			id: monitor.id,
			name: monitor.friendly_name || '未命名站点',
			url: monitor.url || '',
			average: average,
			daily: daily, // index 0 为今天, index (days-1) 为最早
			total: total,
			status: status
		};
	}

	function generateSparklineSvg(monitors, days) {
		if (!monitors || monitors.length === 0 || days <= 0) return '';

		try {
			let dailyAverages = [];
			for (let i = days - 1; i >= 0; i--) {
				let daySum = 0;
				let validCount = 0;
				let dateStr = '';
				monitors.forEach(function(m) {
					if (m.daily && m.daily[i]) {
						daySum += parseFloat(m.daily[i].uptime) || 0;
						validCount++;
						if (!dateStr) dateStr = m.daily[i].date;
					}
				});
				let avg = validCount > 0 ? (daySum / validCount) : 100;
				dailyAverages.push({
					date: dateStr,
					uptime: avg
				});
			}

			let width = 360;
			let height = 48;
			let padTop = 6;
			let padBottom = 6;
			let plotHeight = height - padTop - padBottom;
			let n = dailyAverages.length;
			if (n < 2) return '';

			let minVal = 100;
			let maxVal = 100;
			dailyAverages.forEach(function(d) {
				if (d.uptime < minVal) minVal = d.uptime;
				if (d.uptime > maxVal) maxVal = d.uptime;
			});

			let yMin = Math.max(0, Math.floor(minVal - (minVal < 98 ? 6 : 2)));
			let yMax = 100;
			let range = yMax - yMin;
			if (range <= 0) range = 10;

			let points = [];
			for (let idx = 0; idx < n; idx++) {
				let x = (idx / (n - 1)) * width;
				let normalized = (dailyAverages[idx].uptime - yMin) / range;
				let y = padTop + (1 - normalized) * plotHeight;
				points.push({
					x: Number(x.toFixed(2)),
					y: Number(y.toFixed(2)),
					date: dailyAverages[idx].date,
					uptime: dailyAverages[idx].uptime.toFixed(2)
				});
			}

			let linePath = 'M ' + points.map(function(p) { return p.x + ' ' + p.y; }).join(' L ');
			let areaPath = linePath + ' L ' + width + ' ' + height + ' L 0 ' + height + ' Z';

			let isAllOk = minVal >= 99.5;
			let strokeColor = isAllOk ? '#10b981' : (minVal >= 85 ? '#f59e0b' : '#ef4444');
			let gradId = 'uptime_grad_' + Math.random().toString(36).substring(2, 8);

			let svg = '<svg class="uptime-sparkline-svg" viewBox="0 0 ' + width + ' ' + height + '" preserveAspectRatio="none">';
			svg += '<defs>';
			svg += '  <linearGradient id="' + gradId + '" x1="0%" y1="0%" x2="0%" y2="100%">';
			svg += '    <stop offset="0%" stop-color="' + strokeColor + '" stop-opacity="0.35" />';
			svg += '    <stop offset="100%" stop-color="' + strokeColor + '" stop-opacity="0.0" />';
			svg += '  </linearGradient>';
			svg += '</defs>';
			svg += '<path class="uptime-sparkline-area" d="' + areaPath + '" fill="url(#' + gradId + ')" />';
			svg += '<path class="uptime-sparkline-line" d="' + linePath + '" fill="none" stroke="' + strokeColor + '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />';
			svg += '</svg>';

			return svg;
		} catch (e) {
			console.warn('Failed to render sparkline SVG:', e);
			return '';
		}
	}

	function collectIncidentHistory(rawMonitors, days) {
		let incidents = [];
		if (!Array.isArray(rawMonitors)) return incidents;

		try {
			let now = Date.now() / 1000;
			let earliestTime = now - (days * 86400);

			rawMonitors.forEach(function(m) {
				let siteName = m.friendly_name || '未命名站点';
				let siteUrl = m.url || '';
				if (Array.isArray(m.logs)) {
					m.logs.forEach(function(log) {
						if (parseInt(log.type, 10) === 1) { // Down 故障事件
							let logTime = parseInt(log.datetime, 10) || 0;
							if (logTime >= earliestTime) {
								let duration = parseInt(log.duration, 10) || 0;
								let reason = log.reason ? (log.reason.detail || log.reason.code || '') : '';
								incidents.push({
									siteName: siteName,
									siteUrl: siteUrl,
									timestamp: logTime,
									dateFormatted: formatDateTime(logTime),
									duration: duration,
									durationFormatted: formatDuration(duration),
									reason: reason
								});
							}
						}
					});
				}
			});

			incidents.sort(function(a, b) {
				return b.timestamp - a.timestamp;
			});
		} catch (e) {
			console.warn('Failed to collect incident history:', e);
		}

		return incidents;
	}

	function renderIncidentHistorySection(incidents, days) {
		let html = '<div class="uptime-incidents-section card shadow-sm">';
		html += '  <div class="uptime-section-header">';
		html += '    <div class="uptime-section-title-wrap">';
		html += '      <i class="fa fa-history uptime-section-icon"></i>';
		html += '      <h3 class="uptime-section-title">故障与事件记录 (近 ' + days + ' 天)</h3>';
		html += '    </div>';
		html += '    <span class="uptime-incident-count-badge ' + (incidents.length > 0 ? 'has-incidents' : 'no-incidents') + '">';
		html += '      ' + (incidents.length > 0 ? incidents.length + ' 起故障记录' : '<i class="fa fa-check-circle mr-1"></i>运行极其稳定');
		html += '    </span>';
		html += '  </div>';

		if (incidents.length === 0) {
			html += '  <div class="uptime-no-incidents">';
			html += '    <div class="uptime-no-incidents-icon"><i class="fa fa-shield"></i></div>';
			html += '    <div class="uptime-no-incidents-text">';
			html += '      <h4 class="uptime-no-incidents-title">所有服务节点运行极佳</h4>';
			html += '      <p class="uptime-no-incidents-desc">在过去 ' + days + ' 天内未检测到任何停机中断或故障报警事件。</p>';
			html += '    </div>';
			html += '  </div>';
		} else {
			html += '  <div class="uptime-incident-timeline">';
			incidents.forEach(function(item) {
				html += '    <div class="uptime-incident-item">';
				html += '      <div class="uptime-incident-indicator"><i class="fa fa-times-circle"></i></div>';
				html += '      <div class="uptime-incident-body">';
				html += '        <div class="uptime-incident-meta">';
				html += '          <span class="uptime-incident-site">' + $('<div>').text(item.siteName).html() + '</span>';
				html += '          <span class="uptime-incident-badge">停机中断 ' + item.durationFormatted + '</span>';
				html += '        </div>';
				html += '        <div class="uptime-incident-desc">';
				html += '          <span class="uptime-incident-time"><i class="fa fa-clock-o mr-1"></i>' + item.dateFormatted + '</span>';
				if (item.reason) {
					html += '          <span class="uptime-incident-reason">(' + $('<div>').text(item.reason).html() + ')</span>';
				}
				html += '        </div>';
				html += '      </div>';
				html += '    </div>';
			});
			html += '  </div>';
		}

		html += '</div>';
		return html;
	}

	function stopAutoRefresh() {
		if (countdownInterval) {
			clearInterval(countdownInterval);
			countdownInterval = null;
		}
	}

	function resetAutoRefreshCountdown() {
		stopAutoRefresh();
		remainingSeconds = REFRESH_INTERVAL_SECONDS;
		updateCountdownUI();

		countdownInterval = setInterval(function() {
			if (document.visibilityState === 'hidden') {
				return;
			}
			remainingSeconds--;
			if (remainingSeconds <= 0) {
				stopAutoRefresh();
				loadUptimeData(false, true);
			} else {
				updateCountdownUI();
			}
		}, 1000);
	}

	function updateCountdownUI() {
		let $cd = $('#uptime_countdown_text');
		if ($cd.length > 0) {
			$cd.text(remainingSeconds + 's');
		}
	}

	function renderStatusApp(data) {
		let $app = $('#uptime_status_app');
		if ($app.length === 0 || !data) return;

		let days = parseInt(data.count_days, 10) || 90;
		let showLink = data.show_link;
		let notice = data.notice || '';
		let rawMonitors = Array.isArray(data.monitors) ? data.monitors : [];

		if (rawMonitors.length === 0) {
			renderErrorState('未能获取到监控列表数据');
			return;
		}

		let monitors = rawMonitors.map(function(m) {
			return parseMonitorData(m, days);
		});

		// 计算全局健康状态与统计
		let totalCount = monitors.length;
		let okCount = 0;
		let downCount = 0;
		let sumAverage = 0;

		monitors.forEach(function(m) {
			if (m.status === 'ok') okCount++;
			else if (m.status === 'down') downCount++;
			sumAverage += parseFloat(m.average) || 0;
		});

		let overallAverage = totalCount > 0 ? (sumAverage / totalCount).toFixed(2) : '100.00';
		let globalStatus = 'ok';
		let globalTitle = '全部服务正常';
		let globalSub = '';

		if (downCount > 0 && downCount < totalCount) {
			globalStatus = 'partial';
			globalTitle = '部分服务异常';
			globalSub = '检测到 ' + downCount + ' 个服务发生停机中断';
		} else if (downCount > 0 && downCount === totalCount) {
			globalStatus = 'down';
			globalTitle = '全部服务中断';
			globalSub = '所有监测节点当前不可用';
		}

		// 1. 构建总览卡片 (Overview Banner)
		let html = '';
		html += '<div class="uptime-overview-card card shadow-sm status-' + globalStatus + '">';
		html += '  <div class="uptime-overview-header">';
		html += '    <div class="uptime-pulse-icon status-' + globalStatus + '">';
		html += '      <i class="fa ' + (globalStatus === 'ok' ? 'fa-check-circle' : (globalStatus === 'partial' ? 'fa-exclamation-triangle' : 'fa-times-circle')) + '"></i>';
		html += '    </div>';
		html += '    <div class="uptime-overview-info">';
		html += '      <h2 class="uptime-overview-title">' + globalTitle + '</h2>';
		html += '      <p class="uptime-overview-subtitle">' + (globalSub || ('近 ' + days + ' 天综合可用率 ' + overallAverage + '% · 实时监测中')) + '</p>';
		html += '    </div>';
		html += '    <div class="uptime-overview-actions">';
		html += '      <button type="button" class="uptime-header-refresh-btn" id="uptime_refresh_btn" aria-label="刷新状态">';
		html += '        <i class="fa fa-refresh"></i>';
		html += '        <span class="uptime-refresh-countdown" id="uptime_countdown_text">' + remainingSeconds + 's</span>';
		html += '      </button>';
		html += '    </div>';
		html += '  </div>';

		// 概览趋势图 (Sparkline Trendline)
		let sparklineSvg = generateSparklineSvg(monitors, days);
		if (sparklineSvg) {
			html += '  <div class="uptime-overview-sparkline-wrap">';
			html += '    <div class="uptime-sparkline-meta">';
			html += '      <span class="uptime-sparkline-label"><i class="fa fa-line-chart mr-1"></i>近 ' + days + ' 天整体可用率趋势</span>';
			html += '      <span class="uptime-sparkline-val">' + overallAverage + '%</span>';
			html += '    </div>';
			html += '    <div class="uptime-sparkline-chart">' + sparklineSvg + '</div>';
			html += '  </div>';
		}

		html += '</div>';

		// 2. 状态公告 (Notice Card，若有配置)
		if (notice && notice.trim() !== '') {
			html += '<div class="uptime-notice-card card shadow-sm">';
			html += '  <i class="fa fa-bullhorn uptime-notice-icon"></i>';
			html += '  <div class="uptime-notice-content">' + $('<div>').text(notice).html() + '</div>';
			html += '</div>';
		}

		// 3. 监控列表 (Monitor Cards)
		html += '<div class="uptime-monitors-list">';
		monitors.forEach(function(site) {
			let statusLabel = site.status === 'ok' ? '正常运行' : (site.status === 'down' ? '发生故障' : '状态未知');
			let statusClass = site.status;

			html += '<div class="uptime-monitor-card card shadow-sm">';
			html += '  <div class="uptime-monitor-header">';
			html += '    <div class="uptime-monitor-name-wrap">';
			html += '      <span class="uptime-monitor-name">' + $('<div>').text(site.name).html() + '</span>';
			if (showLink && site.url) {
				html += '      <a href="' + site.url + '" target="_blank" rel="noopener noreferrer" class="uptime-monitor-link" title="访问站点"><i class="fa fa-external-link"></i></a>';
			}
			html += '    </div>';
			html += '    <div class="uptime-monitor-badges">';
			html += '      <span class="uptime-average-pill" title="最近 ' + days + ' 天平均可用率">' + site.average + '% 可用率</span>';
			html += '      <span class="uptime-badge status-' + statusClass + '"><i class="uptime-dot"></i> ' + statusLabel + '</span>';
			html += '    </div>';
			html += '  </div>';

			// 时间轴微柱状图 (从最早展示到今天: reverse daily array)
			html += '  <div class="uptime-timeline-wrap">';
			html += '    <div class="uptime-timeline">';
			let reversedDaily = site.daily.slice().reverse();
			reversedDaily.forEach(function(day) {
				let upVal = parseFloat(day.uptime);
				let barStatus = 'ok';
				if (upVal >= 99.9) {
					barStatus = 'ok';
				} else if (upVal > 0 || (day.down && day.down.times > 0)) {
					barStatus = (day.down.times > 0 && upVal < 80) ? 'down' : 'partial';
				} else if (upVal <= 0 && day.down.times === 0) {
					barStatus = 'none'; // 无数据
				} else {
					barStatus = 'down';
				}

				let tooltipText = '<strong>' + day.date + '</strong><br/>可用率：' + day.uptime + '%';
				if (day.down.times > 0) {
					tooltipText += '<br/><span style="color:#ef4444;">故障 ' + day.down.times + ' 次 (共 ' + formatDuration(day.down.duration) + ')</span>';
				}

				html += '<div class="uptime-bar status-' + barStatus + '" data-tippy-content="' + tooltipText.replace(/"/g, '&quot;') + '"></div>';
			});
			html += '    </div>';
			html += '  </div>';

			// 底部概要统计
			html += '  <div class="uptime-monitor-footer">';
			html += '    <span class="uptime-footer-start">' + (reversedDaily[0] ? reversedDaily[0].date : (days + ' 天前')) + '</span>';
			html += '    <span class="uptime-footer-summary">';
			if (site.total.times > 0) {
				html += '近 ' + days + ' 天故障 ' + site.total.times + ' 次，停机 ' + formatDuration(site.total.duration);
			} else {
				html += '近 ' + days + ' 天无故障记录，运行稳定';
			}
			html += '    </span>';
			html += '    <span class="uptime-footer-end">今天</span>';
			html += '  </div>';
			html += '</div>';
		});
		html += '</div>';

		// 4. 故障记录时间线 (Incident History)
		let incidents = collectIncidentHistory(rawMonitors, days);
		html += renderIncidentHistorySection(incidents, days);

		$app.html(html);

		// 启动自动刷新倒计时
		resetAutoRefreshCountdown();

		// 更新刷新按钮悬浮提示
		let nowTime = new Date();
		let timeStr = String(nowTime.getHours()).padStart(2, '0') + ':' + String(nowTime.getMinutes()).padStart(2, '0') + ':' + String(nowTime.getSeconds()).padStart(2, '0');
		
		let refreshTooltip = '点击刷新实时数据 · 自动倒计时中 (' + timeStr + ')';
		if (data.cooling) {
			refreshTooltip = (data.notice_msg || '数据已是最新') + ' (' + timeStr + ')';
		} else if (data.cached) {
			refreshTooltip = '点击强制刷新 · 当前为本地缓存 (' + timeStr + ')';
		} else {
			refreshTooltip = '点击刷新 · 刚刚同步实时数据 (' + timeStr + ')';
		}

		// 绑定 Tippy.js 浮层
		if (typeof window.tippy === 'function') {
			try {
				window.tippy('.uptime-bar', {
					allowHTML: true,
					theme: 'light',
					animation: 'shift-away',
					placement: 'top',
					duration: [150, 100],
					arrow: true
				});
				window.tippy('#uptime_refresh_btn', {
					content: refreshTooltip,
					placement: 'left',
					arrow: true
				});
			} catch (e) {
				// tippy fallback
			}
		}
	}

	function renderSkeletonScreen($app) {
		let html = '<div class="uptime-loading-wrapper" id="uptime_skeleton_wrapper">' +
			'<div class="uptime-skeleton-overview card shadow-sm">' +
				'<div class="uptime-skeleton-banner">' +
					'<div class="skeleton-shimmer skeleton-circle"></div>' +
					'<div class="uptime-skeleton-banner-texts">' +
						'<div class="skeleton-shimmer skeleton-title"></div>' +
					'</div>' +
					'<div class="skeleton-shimmer skeleton-refresh-btn"></div>' +
				'</div>' +
			'</div>' +
			'<div class="uptime-skeleton-monitors">';
		for (let i = 0; i < 3; i++) {
			html += '<div class="uptime-skeleton-card card shadow-sm">' +
				'<div class="uptime-skeleton-monitor-head">' +
					'<div class="skeleton-shimmer skeleton-monitor-name"></div>' +
					'<div class="uptime-skeleton-monitor-badges">' +
						'<div class="skeleton-shimmer skeleton-pill"></div>' +
						'<div class="skeleton-shimmer skeleton-badge"></div>' +
					'</div>' +
				'</div>' +
				'<div class="skeleton-shimmer skeleton-timeline-bar"></div>' +
				'<div class="uptime-skeleton-monitor-foot">' +
					'<div class="skeleton-shimmer skeleton-foot-text"></div>' +
					'<div class="skeleton-shimmer skeleton-foot-summary"></div>' +
					'<div class="skeleton-shimmer skeleton-foot-text"></div>' +
				'</div>' +
			'</div>';
		}
		html += '</div></div>';
		$app.html(html);
	}

	function renderErrorState(errMsg) {
		let $app = $('#uptime_status_app');
		if ($app.length === 0) return;

		let html = '';
		html += '<div class="uptime-error-card card shadow-sm text-center">';
		html += '  <div class="uptime-error-inner">';
		html += '    <i class="fa fa-wifi uptime-error-icon"></i>';
		html += '    <h3 class="uptime-error-title">暂未获取到监控数据</h3>';
		html += '    <p class="uptime-error-msg">' + $('<div>').text(errMsg || '网络请求超时或 API Key 配置有误').html() + '</p>';
		html += '    <button type="button" class="btn btn-primary btn-sm uptime-retry-btn" id="uptime_retry_btn"><i class="fa fa-refresh mr-1"></i> 重新载入</button>';
		html += '  </div>';
		html += '</div>';

		$app.html(html);
		resetAutoRefreshCountdown();
	}

	function loadUptimeData(refresh, isBackground) {
		let $app = $('#uptime_status_app');
		if ($app.length === 0) return;

		if (isFetching) return;
		isFetching = true;

		let $refreshBtn = $('#uptime_refresh_btn');
		$refreshBtn.addClass('is-refreshing');

		// 仅在当前屏幕没有任何内容且不是后台静默刷新时才显示骨架屏
		let hasContent = $app.find('.uptime-monitors-list').length > 0;
		if (!hasContent && !isBackground && $app.find('.uptime-loading-wrapper').length === 0) {
			renderSkeletonScreen($app);
		}

		let ajaxUrl = $app.data('ajax-url') || (window.argonConfig ? window.argonConfig.wp_path + 'wp-admin/admin-ajax.php' : '/wp-admin/admin-ajax.php');
		let reqUrl = ajaxUrl + '?action=lyrargon_get_status' + (refresh ? '&refresh=1' : '') + '&_t=' + Date.now();

		$.ajax({
			url: reqUrl,
			type: 'GET',
			dataType: 'json',
			timeout: 12000
		}).done(function(response) {
			isFetching = false;
			$refreshBtn.removeClass('is-refreshing');
			if (response && response.success && response.data) {
				// 成功获取数据：写入本地持久化缓存
				try {
					localStorage.setItem(LOCAL_CACHE_KEY, JSON.stringify(response.data));
				} catch (e) {
					// localstorage quota exception fallback
				}
				renderStatusApp(response.data);
				if (response.data.cooling && typeof window.iziToast !== 'undefined') {
					iziToast.info({
						title: '提示',
						message: response.data.notice_msg || '数据已是最新，请稍后再试',
						position: 'topRight'
					});
				}
			} else {
				let msg = response && response.data && response.data.message ? response.data.message : '接口请求失败';
				if ($app.find('.uptime-monitors-list').length > 0) {
					if (typeof window.iziToast !== 'undefined') {
						iziToast.warning({ title: '提示', message: msg, position: 'topRight' });
					}
				} else {
					renderErrorState(msg);
				}
			}
		}).fail(function(xhr, status, error) {
			isFetching = false;
			$refreshBtn.removeClass('is-refreshing');
			let msg = '网络连接超时，未能同步实时数据';
			if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				msg = xhr.responseJSON.data.message;
			}
			if ($app.find('.uptime-monitors-list').length > 0) {
				// 已有缓存渲染，仅轻量提示
				if (typeof window.iziToast !== 'undefined') {
					iziToast.warning({ title: '提示', message: msg, position: 'topRight' });
				}
			} else {
				renderErrorState(msg);
			}
		});
	}

	// 重新载入与刷新事件绑定
	$(document).on('click', '#uptime_retry_btn, #uptime_refresh_btn', function() {
		loadUptimeData(true, false);
	});

	// 初始化入口（三重零延迟架构）
	function initUptimeStatus() {
		let $app = $('#uptime_status_app');
		if ($app.length === 0) return;

		// 1. 第一优先级：服务端直出的预载缓存数据（0ms 瞬间渲染）
		let $preload = $('#lyrargon_uptime_preload');
		if ($preload.length > 0) {
			try {
				let preloadedData = JSON.parse($preload.text());
				if (preloadedData && preloadedData.monitors && preloadedData.monitors.length > 0) {
					try {
						localStorage.setItem(LOCAL_CACHE_KEY, JSON.stringify(preloadedData));
					} catch (e) {}
					renderStatusApp(preloadedData);
					return;
				}
			} catch (e) {
				console.error('Failed to parse preloaded uptime status data:', e);
			}
		}

		// 2. 第二优先级：浏览器 localStorage 本地瞬时缓存（0ms 直出）
		try {
			let localCacheRaw = localStorage.getItem(LOCAL_CACHE_KEY);
			if (localCacheRaw) {
				let localData = JSON.parse(localCacheRaw);
				if (localData && localData.monitors && localData.monitors.length > 0) {
					renderStatusApp(localData);
					// 后台静默刷新最新数据，不展示骨架屏
					loadUptimeData(false, true);
					return;
				}
			}
		} catch (e) {
			console.warn('Failed to read local uptime cache:', e);
		}

		// 3. 第三优先级（首次冷启动）：展示骨架屏并异步请求
		loadUptimeData(false, false);
	}

	$(document).ready(function() {
		initUptimeStatus();
	});

	$(document).on('pjax:complete', function() {
		initUptimeStatus();
	});

	$(document).on('pjax:send', function() {
		stopAutoRefresh();
	});

	window.addEventListener('beforeunload', function() {
		stopAutoRefresh();
	});

})(jQuery);

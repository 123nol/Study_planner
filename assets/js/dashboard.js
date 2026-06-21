/* ============================================
   DASHBOARD — Data loading and rendering
   ============================================ */

(function () {
	const state = {
		overview: null,
		weeklyTaskCounts: [],
		todaySchedule: [],
		generatedAt: null,
	};

	const elements = {};

	function getElement(id) {
		return document.getElementById(id);
	}

	function formatTime(value) {
		if (!value) {
			return '--:--';
		}

		try {
			return new Intl.DateTimeFormat([], {
				hour: 'numeric',
				minute: '2-digit',
			}).format(new Date(`1970-01-01T${value}`));
		} catch (error) {
			return value;
		}
	}

	function formatUpdatedAt(value) {
		if (!value) {
			return 'Last updated just now';
		}

		const timestamp = new Date(value);
		if (Number.isNaN(timestamp.getTime())) {
			return 'Last updated just now';
		}

		return `Last updated ${new Intl.DateTimeFormat([], {
			month: 'short',
			day: 'numeric',
			hour: 'numeric',
			minute: '2-digit',
		}).format(timestamp)}`;
	}

	function formatMinutes(totalMinutes) {
		const minutes = Math.max(0, Number(totalMinutes) || 0);
		const hours = Math.floor(minutes / 60);
		const remainingMinutes = minutes % 60;

		if (hours === 0) {
			return `${remainingMinutes}m`;
		}

		if (remainingMinutes === 0) {
			return `${hours}h`;
		}

		return `${hours}h ${remainingMinutes}m`;
	}

	function colorForIndex(index) {
		const palette = ['#6366f1', '#8b5cf6', '#ec4899', '#14b8a6', '#f59e0b', '#38bdf8', '#f97316'];
		return palette[index % palette.length];
	}

	function renderSummary(overview) {
		if (!overview) {
			return;
		}

		elements.completionRate.textContent = `${Number(overview.completion_rate || 0).toFixed(1)}%`;
		elements.totalTasks.textContent = String(overview.total_tasks ?? 0);
		elements.weeklyTasks.textContent = String(state.weeklyTaskCounts.reduce((sum, entry) => sum + (entry.count || 0), 0));
		elements.todayBlocks.textContent = String(overview.today_schedule_count ?? 0);
		elements.lastUpdated.textContent = formatUpdatedAt(state.generatedAt);
	}

	function renderTodaySchedule(scheduleItems) {
		const list = elements.todayScheduleList;
		list.innerHTML = '';

		if (!scheduleItems.length) {
			list.innerHTML = `
				<div class="dashboard-empty-state">
					<p class="dashboard-empty-state__title">No study blocks planned</p>
					<p class="text-tertiary">Add or generate schedule blocks to populate today’s plan.</p>
				</div>
			`;
			return;
		}

		scheduleItems.forEach((item, index) => {
			const itemRow = document.createElement('article');
			itemRow.className = 'dashboard-schedule-item';

			const color = item.course_color || colorForIndex(index);
			const durationLabel = formatMinutes(item.duration_minutes);
			const recurringLabel = item.is_recurring ? 'Recurring' : 'Specific date';

			itemRow.innerHTML = `
				<div class="dashboard-schedule-item__time">${formatTime(item.start_time)}</div>
				<div class="dashboard-schedule-item__body">
					<strong class="dashboard-schedule-item__title">${escapeHtml(item.title || 'Study block')}</strong>
					<div class="dashboard-schedule-item__meta">
						<span class="dashboard-dot" style="background: ${escapeHtml(color)}"></span>
						<span>${escapeHtml(item.course_name || 'General study')}</span>
						<span>•</span>
						<span>${escapeHtml(recurringLabel)}</span>
					</div>
				</div>
				<div class="dashboard-schedule-item__duration">${escapeHtml(durationLabel)}</div>
			`;

			list.appendChild(itemRow);
		});
	}

	function renderCharts() {
		const ringCanvas = elements.progressRingCanvas;
		const barCanvas = elements.weeklyBarCanvas;
		const overview = state.overview || { completion_rate: 0 };

		if (window.DashboardCharts) {
			window.DashboardCharts.drawProgressRing(ringCanvas, Number(overview.completion_rate || 0), {
				label: 'Completion',
				subLabel: `${overview.completed_tasks || 0} of ${overview.total_tasks || 0} tasks`,
			});

			const labels = state.weeklyTaskCounts.map((entry) => entry.label);
			const values = state.weeklyTaskCounts.map((entry) => entry.count);

			window.DashboardCharts.drawBarChart(barCanvas, labels, values, {
				emptyLabel: 'No tasks logged this week',
			});
		}
	}

	function escapeHtml(value) {
		return String(value)
			.replaceAll('&', '&amp;')
			.replaceAll('<', '&lt;')
			.replaceAll('>', '&gt;')
			.replaceAll('"', '&quot;')
			.replaceAll("'", '&#039;');
	}

	async function loadDashboard() {
		elements.refreshButton.disabled = true;
		elements.refreshButton.textContent = 'Refreshing...';

		try {
			const response = window.API
				? await window.API.request('progress.php')
				: await fetch('/api/progress.php', { headers: { Accept: 'application/json' } }).then(async (result) => {
					const payload = await result.json();
					if (!result.ok || !payload.success) {
						throw new Error(payload.error || 'Unable to load dashboard data');
					}
					return payload.data;
				});

			state.overview = response.overview || null;
			state.weeklyTaskCounts = Array.isArray(response.weekly_task_counts) ? response.weekly_task_counts : [];
			state.todaySchedule = Array.isArray(response.today_schedule) ? response.today_schedule : [];
			state.generatedAt = response.generated_at || null;

			renderSummary(state.overview);
			renderTodaySchedule(state.todaySchedule);
			renderCharts();
		} catch (error) {
			console.error('Dashboard load failed:', error);

			elements.lastUpdated.textContent = 'Unable to sync dashboard';
			elements.todayScheduleList.innerHTML = `
				<div class="dashboard-empty-state">
					<p class="dashboard-empty-state__title">Dashboard data unavailable</p>
					<p class="text-tertiary">Refresh the page or check the progress endpoint for errors.</p>
				</div>
			`;

			if (window.showToast) {
				window.showToast('Unable to load dashboard data.', 'error');
			}
		} finally {
			elements.refreshButton.disabled = false;
			elements.refreshButton.textContent = 'Refresh data';
		}
	}

	function cacheElements() {
		elements.refreshButton = getElement('btn-dashboard-refresh');
		elements.completionRate = getElement('stat-completion-rate');
		elements.totalTasks = getElement('stat-total-tasks');
		elements.weeklyTasks = getElement('stat-weekly-tasks');
		elements.todayBlocks = getElement('stat-today-blocks');
		elements.lastUpdated = getElement('dashboard-last-updated');
		elements.progressRingCanvas = getElement('progress-ring-canvas');
		elements.weeklyBarCanvas = getElement('weekly-bar-canvas');
		elements.todayScheduleList = getElement('today-schedule-list');
	}

	function init() {
		cacheElements();

		if (!elements.refreshButton || !elements.progressRingCanvas || !elements.weeklyBarCanvas || !elements.todayScheduleList) {
			return;
		}

		elements.refreshButton.addEventListener('click', loadDashboard);

		let resizeTimer = null;
		window.addEventListener('resize', () => {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(() => {
				renderCharts();
			}, 120);
		});

		loadDashboard();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

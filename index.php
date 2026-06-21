<?php
$pageCss = 'dashboard.css';
$pageScripts = ['charts.js', 'dashboard.js'];

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
?>

<header class="page-header dashboard-header">
	<div class="dashboard-header__copy">
		<p class="dashboard-kicker">Dashboard</p>
		<h1 class="page-header__title">Welcome back, <?= htmlspecialchars($currentUser['username'] ?? 'Student', ENT_QUOTES, 'UTF-8') ?></h1>
		<p class="text-tertiary dashboard-header__description">
			Track progress, review today’s plan, and jump straight into the next study block.
		</p>
	</div>

	<div class="page-header__actions dashboard-header__actions">
		<button type="button" class="btn btn-secondary" id="btn-dashboard-refresh">Refresh data</button>
		<a class="btn btn-primary" href="pages/tasks.php">Add Task</a>
	</div>
</header>

<div class="page-body dashboard-page">
	<section class="dashboard-hero card">
		<div class="dashboard-hero__copy">
			<span class="dashboard-badge">Live overview</span>
			<h2 class="dashboard-hero__title">Your study command center</h2>
			<p class="dashboard-hero__text">
				The charts summarize completion momentum and this week’s activity. The schedule panel shows what is coming up today.
			</p>
			<div class="dashboard-hero__meta">
				<span class="dashboard-chip">Timezone: <?= htmlspecialchars($currentUser['timezone'] ?? 'Africa/Addis_Ababa', ENT_QUOTES, 'UTF-8') ?></span>
				<span class="dashboard-chip" id="dashboard-last-updated">Syncing data...</span>
			</div>
		</div>

		<div class="dashboard-hero__stats" aria-label="Dashboard summary metrics">
			<article class="dashboard-stat">
				<span class="dashboard-stat__label">Completion Rate</span>
				<strong class="dashboard-stat__value" id="stat-completion-rate">--%</strong>
				<span class="dashboard-stat__hint">Across all tasks</span>
			</article>
			<article class="dashboard-stat">
				<span class="dashboard-stat__label">Total Tasks</span>
				<strong class="dashboard-stat__value" id="stat-total-tasks">--</strong>
				<span class="dashboard-stat__hint">Active workload</span>
			</article>
			<article class="dashboard-stat">
				<span class="dashboard-stat__label">This Week</span>
				<strong class="dashboard-stat__value" id="stat-weekly-tasks">--</strong>
				<span class="dashboard-stat__hint">Tasks created</span>
			</article>
			<article class="dashboard-stat">
				<span class="dashboard-stat__label">Today’s Plan</span>
				<strong class="dashboard-stat__value" id="stat-today-blocks">--</strong>
				<span class="dashboard-stat__hint">Scheduled blocks</span>
			</article>
		</div>
	</section>

	<section class="dashboard-grid" aria-label="Dashboard panels">
		<article class="card dashboard-card dashboard-card--ring">
			<div class="dashboard-card__header">
				<div>
					<h3 class="section__title">Progress Ring</h3>
					<p class="text-tertiary">Completed tasks against your total workload.</p>
				</div>
				<span class="dashboard-card__tag">Canvas</span>
			</div>
			<div class="dashboard-chart-shell dashboard-chart-shell--ring">
				<canvas id="progress-ring-canvas" class="dashboard-canvas" width="360" height="360"></canvas>
			</div>
		</article>

		<article class="card dashboard-card dashboard-card--bars">
			<div class="dashboard-card__header">
				<div>
					<h3 class="section__title">Weekly Task Activity</h3>
					<p class="text-tertiary">Tasks created over the last seven days.</p>
				</div>
				<span class="dashboard-card__tag">Canvas</span>
			</div>
			<div class="dashboard-chart-shell dashboard-chart-shell--bars">
				<canvas id="weekly-bar-canvas" class="dashboard-canvas" width="860" height="320"></canvas>
			</div>
		</article>

		<article class="card dashboard-card dashboard-card--schedule">
			<div class="dashboard-card__header">
				<div>
					<h3 class="section__title">Today’s Schedule</h3>
					<p class="text-tertiary">A focused preview of what is coming next.</p>
				</div>
				<a class="dashboard-card__link" href="pages/schedule.php">Open schedule</a>
			</div>
			<div class="dashboard-schedule" id="today-schedule-list" aria-live="polite">
				<div class="dashboard-empty-state">
					<p class="dashboard-empty-state__title">Loading today’s plan</p>
					<p class="text-tertiary">Your schedule will appear here once the dashboard syncs.</p>
				</div>
			</div>
		</article>

		<article class="card dashboard-card dashboard-card--actions">
			<div class="dashboard-card__header">
				<div>
					<h3 class="section__title">Quick Actions</h3>
					<p class="text-tertiary">Common next steps without leaving the dashboard.</p>
				</div>
			</div>

			<div class="dashboard-actions">
				<a class="dashboard-action" href="pages/tasks.php">
					<span class="dashboard-action__icon">+</span>
					<span>
						<strong>Add Task</strong>
						<small>Create the next study item.</small>
					</span>
				</a>
				<a class="dashboard-action" href="pages/schedule.php">
					<span class="dashboard-action__icon">S</span>
					<span>
						<strong>Generate Schedule</strong>
						<small>Plan your next study block.</small>
					</span>
				</a>
				<a class="dashboard-action" href="pages/reminders.php">
					<span class="dashboard-action__icon">R</span>
					<span>
						<strong>Reminders</strong>
						<small>Check upcoming notifications.</small>
					</span>
				</a>
				<a class="dashboard-action" href="pages/profile.php">
					<span class="dashboard-action__icon">P</span>
					<span>
						<strong>Profile</strong>
						<small>Update timezone and settings.</small>
					</span>
				</a>
			</div>
		</article>
	</section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

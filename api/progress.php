<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

if (!isset($_SESSION['user_id'])) {
	jsonError('Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	jsonError('Method not allowed', 405);
}

$userId = (int) $_SESSION['user_id'];

try {
	$db = getDBConnection();
} catch (PDOException $e) {
	jsonError('Database connection failed', 500);
}

$today = new DateTimeImmutable('today');
$weekStart = $today->modify('-6 days');
$weekEndExclusive = $today->modify('+1 day');

$summarizeDuration = static function (string $startTime, string $endTime): int {
	$startTimestamp = strtotime($startTime);
	$endTimestamp = strtotime($endTime);

	if ($startTimestamp === false || $endTimestamp === false) {
		return 0;
	}

	$minutes = (int) round(($endTimestamp - $startTimestamp) / 60);
	if ($minutes < 0) {
		$minutes += 24 * 60;
	}

	return max(0, $minutes);
};

$overviewStmt = $db->prepare(
	'SELECT
		COUNT(*) AS total_tasks,
		COALESCE(SUM(CASE WHEN status = "done" THEN 1 ELSE 0 END), 0) AS completed_tasks,
		COALESCE(SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END), 0) AS in_progress_tasks,
		COALESCE(SUM(CASE WHEN status = "todo" THEN 1 ELSE 0 END), 0) AS todo_tasks
	 FROM tasks
	 WHERE user_id = ?'
);
$overviewStmt->execute([$userId]);
$overviewRow = $overviewStmt->fetch() ?: [];

$totalTasks = (int) ($overviewRow['total_tasks'] ?? 0);
$completedTasks = (int) ($overviewRow['completed_tasks'] ?? 0);
$inProgressTasks = (int) ($overviewRow['in_progress_tasks'] ?? 0);
$todoTasks = (int) ($overviewRow['todo_tasks'] ?? 0);
$completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0.0;

$overdueStmt = $db->prepare(
	'SELECT COUNT(*) AS overdue_tasks
	 FROM tasks
	 WHERE user_id = ?
	   AND status <> "done"
	   AND deadline IS NOT NULL
	   AND deadline < NOW()'
);
$overdueStmt->execute([$userId]);
$overdueTasks = (int) ($overdueStmt->fetch()['overdue_tasks'] ?? 0);

$weeklyCountsStmt = $db->prepare(
	'SELECT DATE(created_at) AS task_date, COUNT(*) AS task_count
	 FROM tasks
	 WHERE user_id = ?
	   AND created_at >= ?
	   AND created_at < ?
	 GROUP BY DATE(created_at)'
);
$weeklyCountsStmt->execute([
	$userId,
	$weekStart->format('Y-m-d 00:00:00'),
	$weekEndExclusive->format('Y-m-d 00:00:00'),
]);

$countsByDate = [];
foreach ($weeklyCountsStmt->fetchAll() as $row) {
	$countsByDate[$row['task_date']] = (int) $row['task_count'];
}

$weeklyTaskCounts = [];
for ($cursor = $weekStart; $cursor <= $today; $cursor = $cursor->modify('+1 day')) {
	$dateKey = $cursor->format('Y-m-d');
	$weeklyTaskCounts[] = [
		'date' => $dateKey,
		'label' => $cursor->format('D'),
		'count' => $countsByDate[$dateKey] ?? 0,
	];
}

$todayScheduleStmt = $db->prepare(
	'SELECT
		sb.id,
		sb.label,
		sb.start_time,
		sb.end_time,
		sb.day_of_week,
		sb.specific_date,
		sb.is_recurring,
		c.name AS course_name,
		c.color AS course_color,
		COALESCE(t.title, sb.label, c.name) AS item_title
	 FROM schedule_blocks sb
	 INNER JOIN courses c ON sb.course_id = c.id
	 LEFT JOIN tasks t ON sb.task_id = t.id
	 WHERE sb.user_id = ?
	   AND (
			sb.specific_date = ?
			OR (sb.specific_date IS NULL AND sb.day_of_week = ?)
	   )
	 ORDER BY sb.start_time ASC'
);
$todayScheduleStmt->execute([
	$userId,
	$today->format('Y-m-d'),
	(int) $today->format('w'),
]);

$todaySchedule = [];
foreach ($todayScheduleStmt->fetchAll() as $row) {
	$todaySchedule[] = [
		'id' => (int) $row['id'],
		'title' => $row['item_title'] ?: 'Study block',
		'label' => $row['label'] ?: '',
		'course_name' => $row['course_name'],
		'course_color' => $row['course_color'],
		'start_time' => substr((string) $row['start_time'], 0, 5),
		'end_time' => substr((string) $row['end_time'], 0, 5),
		'is_recurring' => (int) $row['is_recurring'] === 1,
		'duration_minutes' => $summarizeDuration((string) $row['start_time'], (string) $row['end_time']),
		'specific_date' => $row['specific_date'],
	];
}

$scheduledMinutesToday = array_sum(array_column($todaySchedule, 'duration_minutes'));

jsonSuccess([
	'overview' => [
		'total_tasks' => $totalTasks,
		'completed_tasks' => $completedTasks,
		'in_progress_tasks' => $inProgressTasks,
		'todo_tasks' => $todoTasks,
		'overdue_tasks' => $overdueTasks,
		'completion_rate' => $completionRate,
		'today_schedule_count' => count($todaySchedule),
		'scheduled_minutes_today' => $scheduledMinutesToday,
	],
	'weekly_task_counts' => $weeklyTaskCounts,
	'today_schedule' => $todaySchedule,
	'generated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
]);

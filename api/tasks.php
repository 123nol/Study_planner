<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validation.php';

if (!isset($_SESSION['user_id'])) {
    jsonError('Unauthorized', 401);
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();
} catch (PDOException $e) {
    jsonError('Database connection failed: ' . $e->getMessage(), 500);
}

if ($method === 'GET') {
    $query = 'SELECT t.id, t.title, t.description, t.status, t.priority, t.deadline, t.estimated_minutes, t.actual_minutes, t.completed_at, t.created_at,
                     c.id AS course_id, c.name AS course_name, c.color AS course_color
              FROM tasks t
              LEFT JOIN courses c ON t.course_id = c.id
              WHERE t.user_id = ?';
    $params = [$userId];

    if (isset($_GET['status']) && in_array($_GET['status'], ['todo', 'in_progress', 'done'], true)) {
        $query .= ' AND t.status = ?';
        $params[] = $_GET['status'];
    }

    if (isset($_GET['course_id']) && intval($_GET['course_id']) > 0) {
        $query .= ' AND t.course_id = ?';
        $params[] = intval($_GET['course_id']);
    }

    if (isset($_GET['priority']) && validateIntRange($_GET['priority'], 1, 3)) {
        $query .= ' AND t.priority = ?';
        $params[] = sanitizeInt($_GET['priority']);
    }

    $query .= ' ORDER BY FIELD(t.status, "todo", "in_progress", "done"), t.deadline IS NULL, t.deadline ASC';

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    jsonSuccess(['tasks' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getJsonBody();
    $action = $data['action'] ?? '';

    if ($action === 'create') {
        $missing = validateRequired($data, ['title']);
        if (!empty($missing)) {
            jsonError('Required task fields are missing', 400, ['missing' => $missing]);
        }

        $title = sanitizeString($data['title']);
        $description = sanitizeString($data['description'] ?? '');
        $status = in_array($data['status'] ?? 'todo', ['todo', 'in_progress', 'done'], true) ? $data['status'] : 'todo';
        $priority = validateIntRange($data['priority'] ?? 2, 1, 3) ? sanitizeInt($data['priority']) : 2;
        $estimatedMinutes = sanitizeInt($data['estimated_minutes'] ?? 60);
        $courseId = sanitizeInt($data['course_id'] ?? 0);
        $deadline = sanitizeString($data['deadline'] ?? '');

        if ($estimatedMinutes <= 0) {
            jsonError('Estimated minutes must be a positive number');
        }

        if ($deadline !== '' && !validateDateTime($deadline)) {
            jsonError('Invalid deadline format. Use YYYY-MM-DD HH:MM');
        }

        if ($courseId > 0) {
            $check = $db->prepare('SELECT id FROM courses WHERE id = ? AND user_id = ?');
            $check->execute([$courseId, $userId]);
            if (!$check->fetch()) {
                jsonError('Selected course was not found', 404);
            }
        } else {
            $courseId = null;
        }

        $completedAt = $status === 'done' ? (new DateTime())->format('Y-m-d H:i:s') : null;

        $stmt = $db->prepare(
            'INSERT INTO tasks (user_id, course_id, title, description, status, priority, deadline, estimated_minutes, actual_minutes, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)'
        );
        $stmt->execute([$userId, $courseId, $title, $description, $status, $priority, $deadline ?: null, $estimatedMinutes, $completedAt]);

        $taskId = (int) $db->lastInsertId();
        jsonSuccess(['task' => [
            'id' => $taskId,
            'course_id' => $courseId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'deadline' => $deadline,
            'estimated_minutes' => $estimatedMinutes,
            'completed_at' => $completedAt,
        ]], 201);
    }

    if ($action === 'update') {
        $taskId = sanitizeInt($data['id'] ?? 0);
        if ($taskId <= 0) {
            jsonError('Task ID is required for update', 400);
        }

        $updateFields = [];
        $params = [];

        if (isset($data['title'])) {
            $title = sanitizeString($data['title']);
            if ($title === '') {
                jsonError('Task title cannot be empty');
            }
            $updateFields[] = 'title';
            $params[] = $title;
        }

        if (isset($data['description'])) {
            $updateFields[] = 'description';
            $params[] = sanitizeString($data['description']);
        }

        if (isset($data['status'])) {
            $status = in_array($data['status'], ['todo', 'in_progress', 'done'], true) ? $data['status'] : 'todo';
            $updateFields[] = 'status';
            $params[] = $status;
            if ($status === 'done') {
                $updateFields[] = 'completed_at';
                $params[] = (new DateTime())->format('Y-m-d H:i:s');
            } else {
                $updateFields[] = 'completed_at';
                $params[] = null;
            }
        }

        if (isset($data['priority'])) {
            $priority = validateIntRange($data['priority'], 1, 3) ? sanitizeInt($data['priority']) : 2;
            $updateFields[] = 'priority';
            $params[] = $priority;
        }

        if (array_key_exists('estimated_minutes', $data)) {
            $estimatedMinutes = sanitizeInt($data['estimated_minutes']);
            if ($estimatedMinutes <= 0) {
                jsonError('Estimated minutes must be a positive number');
            }
            $updateFields[] = 'estimated_minutes';
            $params[] = $estimatedMinutes;
        }

        if (isset($data['deadline'])) {
            $deadline = sanitizeString($data['deadline']);
            if ($deadline !== '' && !validateDateTime($deadline)) {
                jsonError('Invalid deadline format. Use YYYY-MM-DD HH:MM');
            }
            $updateFields[] = 'deadline';
            $params[] = $deadline ?: null;
        }

        if (array_key_exists('course_id', $data)) {
            $courseId = sanitizeInt($data['course_id']);
            if ($courseId > 0) {
                $check = $db->prepare('SELECT id FROM courses WHERE id = ? AND user_id = ?');
                $check->execute([$courseId, $userId]);
                if (!$check->fetch()) {
                    jsonError('Selected course was not found', 404);
                }
                $updateFields[] = 'course_id';
                $params[] = $courseId;
            } else {
                $updateFields[] = 'course_id';
                $params[] = null;
            }
        }

        if (empty($updateFields)) {
            jsonError('No task fields provided for update', 400);
        }

        $setSql = implode(', ', array_map(fn($field) => "$field = ?", $updateFields));
        $params[] = $taskId;
        $params[] = $userId;

        $stmt = $db->prepare("UPDATE tasks SET $setSql WHERE id = ? AND user_id = ?");
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            jsonError('Task not found or no changes made', 404);
        }

        $stmt = $db->prepare(
            'SELECT t.id, t.title, t.description, t.status, t.priority, t.deadline, t.estimated_minutes, t.actual_minutes, t.completed_at, t.created_at,
                    c.id AS course_id, c.name AS course_name, c.color AS course_color
             FROM tasks t
             LEFT JOIN courses c ON t.course_id = c.id
             WHERE t.id = ? AND t.user_id = ?'
        );
        $stmt->execute([$taskId, $userId]);
        $task = $stmt->fetch();
        if (!$task) {
            jsonError('Task not found after update', 404);
        }

        jsonSuccess(['task' => $task]);
    }

    if ($action === 'delete') {
        $taskId = sanitizeInt($data['id'] ?? 0);
        if ($taskId <= 0) {
            jsonError('Task ID is required for delete', 400);
        }

        $stmt = $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$taskId, $userId]);

        if ($stmt->rowCount() === 0) {
            jsonError('Task not found or could not be deleted', 404);
        }

        jsonSuccess(['message' => 'Task deleted successfully']);
    }

    jsonError('Invalid action specified', 400);
}

jsonError('Method not allowed', 405);

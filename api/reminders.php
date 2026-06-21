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

function validateReminderType(string $type): bool {
    return in_array($type, ['countdown', 'alert', 'notification'], true);
}

if ($method === 'GET') {
    $stmt = $db->prepare(
        'SELECT r.id, r.task_id, r.message, r.remind_at, r.type, r.is_active,
                t.title AS task_title,
                c.name AS task_course_name,
                c.color AS task_course_color
         FROM reminders r
         LEFT JOIN tasks t ON r.task_id = t.id
         LEFT JOIN courses c ON t.course_id = c.id
         WHERE r.user_id = ? AND r.is_active = 1
         ORDER BY r.remind_at ASC'
    );
    $stmt->execute([$userId]);
    jsonSuccess(['reminders' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getJsonBody();
    $action = $data['action'] ?? '';

    if ($action === 'create') {
        $missing = validateRequired($data, ['message', 'remind_at']);
        if (!empty($missing)) {
            jsonError('Required reminder fields are missing', 400, ['missing' => $missing]);
        }

        $message = sanitizeString($data['message']);
        $remindAt = sanitizeString($data['remind_at']);
        $type = sanitizeString($data['type'] ?? 'alert');
        $taskId = sanitizeInt($data['task_id'] ?? 0);

        if ($message === '') {
            jsonError('Reminder message cannot be empty', 400);
        }

        if (!validateDateTime($remindAt)) {
            jsonError('Invalid remind_at format. Use YYYY-MM-DD HH:MM');
        }

        if (!validateReminderType($type)) {
            jsonError('Invalid reminder type', 400);
        }

        if ($taskId > 0) {
            $stmt = $db->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
            $stmt->execute([$taskId, $userId]);
            if (!$stmt->fetch()) {
                jsonError('Selected task not found', 404);
            }
        } else {
            $taskId = null;
        }

        $stmt = $db->prepare(
            'INSERT INTO reminders (user_id, task_id, message, remind_at, type, is_active)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$userId, $taskId, $message, $remindAt, $type]);

        $reminderId = (int) $db->lastInsertId();
        jsonSuccess(['reminder' => [
            'id' => $reminderId,
            'task_id' => $taskId,
            'message' => $message,
            'remind_at' => $remindAt,
            'type' => $type,
            'is_active' => 1,
        ]], 201);
    }

    if ($action === 'dismiss') {
        $id = sanitizeInt($data['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Reminder ID is required for dismiss', 400);
        }

        $stmt = $db->prepare('UPDATE reminders SET is_active = 0 WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        if ($stmt->rowCount() === 0) {
            jsonError('Reminder not found or already dismissed', 404);
        }

        jsonSuccess(['message' => 'Reminder dismissed successfully']);
    }

    jsonError('Invalid action specified', 400);
}

jsonError('Method not allowed', 405);

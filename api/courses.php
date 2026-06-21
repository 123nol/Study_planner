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
    $stmt = $db->prepare(
        'SELECT id, name, color, priority, weekly_hours_goal, IFNULL(end_date, "") AS end_date
         FROM courses
         WHERE user_id = ?
         ORDER BY priority DESC, name ASC'
    );
    $stmt->execute([$userId]);
    jsonSuccess(['courses' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = getJsonBody();
    $action = $data['action'] ?? '';

    if ($action === 'create') {
        $missing = validateRequired($data, ['name', 'weekly_hours_goal']);
        if (!empty($missing)) {
            jsonError('Required course fields are missing', 400, ['missing' => $missing]);
        }

        $name = sanitizeString($data['name']);
        $color = sanitizeString($data['color'] ?? '#6366f1');
        $priority = validateIntRange($data['priority'] ?? 2, 1, 3) ? sanitizeInt($data['priority']) : 2;
        $weeklyHours = sanitizeInt($data['weekly_hours_goal']);
        $endDate = sanitizeString($data['end_date'] ?? '');

        if ($weeklyHours <= 0) {
            jsonError('Weekly hours goal must be a positive number');
        }

        if ($endDate !== '' && !validateDate($endDate)) {
            jsonError('Invalid end date format. Use YYYY-MM-DD.');
        }

        $color = validateHexColor($color) ? $color : '#6366f1';

        $stmt = $db->prepare(
            'INSERT INTO courses (user_id, name, color, priority, weekly_hours_goal, end_date)
             VALUES (?, ?, ?, ?, ?, ?)' 
        );
        $stmt->execute([$userId, $name, $color, $priority, $weeklyHours, $endDate ?: null]);

        $courseId = (int) $db->lastInsertId();
        jsonSuccess(['course' => [
            'id' => $courseId,
            'name' => $name,
            'color' => $color,
            'priority' => $priority,
            'weekly_hours_goal' => $weeklyHours,
            'end_date' => $endDate,
        ]], 201);
    }

    if ($action === 'update') {
        $courseId = sanitizeInt($data['id'] ?? 0);
        if ($courseId <= 0) {
            jsonError('Course ID is required for update', 400);
        }

        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $name = sanitizeString($data['name']);
            if ($name === '') {
                jsonError('Course name cannot be empty');
            }
            $fields[] = 'name';
            $params[] = $name;
        }

        if (isset($data['color'])) {
            $color = sanitizeString($data['color']);
            $fields[] = 'color';
            $params[] = validateHexColor($color) ? $color : '#6366f1';
        }

        if (isset($data['priority'])) {
            $priority = validateIntRange($data['priority'], 1, 3) ? sanitizeInt($data['priority']) : 2;
            $fields[] = 'priority';
            $params[] = $priority;
        }

        if (isset($data['weekly_hours_goal'])) {
            $weeklyHours = sanitizeInt($data['weekly_hours_goal']);
            if ($weeklyHours <= 0) {
                jsonError('Weekly hours goal must be a positive number');
            }
            $fields[] = 'weekly_hours_goal';
            $params[] = $weeklyHours;
        }

        if (array_key_exists('end_date', $data)) {
            $endDate = sanitizeString($data['end_date'] ?? '');
            if ($endDate !== '' && !validateDate($endDate)) {
                jsonError('Invalid end date format. Use YYYY-MM-DD.');
            }
            $fields[] = 'end_date';
            $params[] = $endDate ?: null;
        }

        if (empty($fields)) {
            jsonError('No course fields provided for update', 400);
        }

        $setSql = implode(', ', array_map(fn($field) => "$field = ?", $fields));
        $params[] = $courseId;
        $params[] = $userId;

        $stmt = $db->prepare("UPDATE courses SET $setSql WHERE id = ? AND user_id = ?");
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            jsonError('Course not found or no changes made', 404);
        }

        $stmt = $db->prepare('SELECT id, name, color, priority, weekly_hours_goal, IFNULL(end_date, "") AS end_date FROM courses WHERE id = ? AND user_id = ?');
        $stmt->execute([$courseId, $userId]);
        $course = $stmt->fetch();
        if (!$course) {
            jsonError('Course not found after update', 404);
        }

        jsonSuccess(['course' => $course]);
    }

    if ($action === 'delete') {
        $courseId = sanitizeInt($data['id'] ?? 0);
        if ($courseId <= 0) {
            jsonError('Course ID is required for delete', 400);
        }

        $stmt = $db->prepare('DELETE FROM courses WHERE id = ? AND user_id = ?');
        $stmt->execute([$courseId, $userId]);

        if ($stmt->rowCount() === 0) {
            jsonError('Course not found or could not be deleted', 404);
        }

        jsonSuccess(['message' => 'Course deleted successfully']);
    }

    jsonError('Invalid action specified', 400);
}

jsonError('Method not allowed', 405);

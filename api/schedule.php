<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/scheduler.php';

// Check if request is a POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Ensure the request has a JSON body
$data = getJsonBody();

if (empty($data)) {
    // If no body, assume it's just a test request and generate some mock data
    // Usually this data would come from the database based on the authenticated user
    $courses = [
        ['id' => 1, 'name' => 'Web Development', 'color' => '#6366f1', 'priority' => 3, 'weekly_hours_goal' => 10, 'end_date' => '2026-06-15'],
        ['id' => 2, 'name' => 'Data Structures', 'color' => '#ec4899', 'priority' => 3, 'weekly_hours_goal' => 8, 'end_date' => '2026-06-20'],
        ['id' => 3, 'name' => 'Database Systems', 'color' => '#14b8a6', 'priority' => 2, 'weekly_hours_goal' => 6, 'end_date' => '2026-06-10'],
        ['id' => 4, 'name' => 'Machine Learning', 'color' => '#f59e0b', 'priority' => 1, 'weekly_hours_goal' => 4, 'end_date' => '2026-07-01']
    ];

    // Mock availability (9am to 5pm on weekdays)
    $availability = [];
    for ($day = 0; $day < 7; $day++) {
        $availability[$day] = [];
        for ($hour = 0; $hour < 24; $hour++) {
            // Weekdays 9-17
            if ($day >= 1 && $day <= 5 && $hour >= 9 && $hour < 17) {
                $availability[$day][$hour] = true;
            } else {
                $availability[$day][$hour] = false;
            }
        }
    }

    $preferences = [
        'max_block_minutes' => 120,
        'min_block_minutes' => 45,
        'break_minutes' => 15
    ];

    $schedule = generateSchedule($courses, $availability, $preferences);
    jsonSuccess(['blocks' => $schedule]);
}

// Real generation flow based on provided input
$action = $data['action'] ?? '';

if ($action === 'generate') {
    $courses = $data['courses'] ?? [];
    $availability = $data['availability'] ?? [];
    $preferences = $data['preferences'] ?? [];

    if (empty($courses)) {
        jsonError('No courses provided');
    }

    $schedule = generateSchedule($courses, $availability, $preferences);
    
    // In a real app, we would save these to the database here
    // ... DB save logic ...

    jsonSuccess(['blocks' => $schedule]);
}

if ($action === 'save') {
    $blocks = $data['blocks'] ?? [];
    if (empty($blocks)) {
        jsonError('No blocks provided to save');
    }

    require_once __DIR__ . '/../config/database.php';
    
    try {
        $db = getDBConnection();
        // For now, hardcode user_id = 1 since auth isn't built yet
        $userId = 1;

        // Verify if user exists, if not, create a mock user so foreign keys don't fail
        $stmt = $db->query('SELECT id FROM users WHERE id = 1');
        if (!$stmt->fetch()) {
            $db->exec("INSERT INTO users (id, username, email, password_hash) VALUES (1, 'demo_user', 'demo@example.com', 'hash')");
        }
        
        $db->beginTransaction();
        
        // Clear existing schedule for this user
        $stmt = $db->prepare('DELETE FROM schedule_blocks WHERE user_id = ?');
        $stmt->execute([$userId]);
        
        // Insert new blocks
        $stmt = $db->prepare('
            INSERT INTO schedule_blocks 
            (user_id, course_id, day_of_week, start_time, end_time, label) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        foreach ($blocks as $block) {
            // Check if course exists in DB, if not, insert it (since we are using mock courses in JS)
            $courseId = $block['course_id'];
            $cStmt = $db->prepare('SELECT id FROM courses WHERE id = ?');
            $cStmt->execute([$courseId]);
            if (!$cStmt->fetch()) {
                $insCourse = $db->prepare('INSERT INTO courses (id, user_id, name, color) VALUES (?, ?, ?, ?)');
                $insCourse->execute([$courseId, $userId, $block['course_name'] ?? 'Mock Course', $block['color'] ?? '#6366f1']);
            }

            $stmt->execute([
                $userId,
                $courseId,
                $block['day'],
                $block['start_time'] . ':00',
                $block['end_time'] . ':00',
                $block['label']
            ]);
        }
        
        $db->commit();
        jsonSuccess(['message' => 'Schedule saved successfully to the database!']);
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        // Check if it's a database missing error
        if ($e->getCode() == 1049) {
            jsonError('Database does not exist. Please create "smart_study_planner" in phpMyAdmin and import schema.sql first.');
        }
        jsonError('Database error: ' . $e->getMessage());
    }
}

jsonError('Invalid action');

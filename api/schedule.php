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

jsonError('Invalid action');

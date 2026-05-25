<?php
/* ============================================
   SCHEDULING ALGORITHM
   Generates a balanced weekly study schedule
   based on courses, availability, and preferences.
   ============================================ */

/**
 * Generate a study schedule.
 *
 * @param array $courses     Array of course objects with id, name, priority (1-3), weekly_hours_goal, end_date
 * @param array $availability 2D array: availability[day_of_week (0-6)][hour (0-23)] = true/false
 * @param array $preferences  max_block_minutes, min_block_minutes, break_minutes
 * @return array              Array of schedule block objects
 */
function generateSchedule(array $courses, array $availability, array $preferences): array {
    if (empty($courses)) {
        return [];
    }

    $maxBlock  = $preferences['max_block_minutes'] ?? 90;
    $minBlock  = $preferences['min_block_minutes'] ?? 30;
    $breakMins = $preferences['break_minutes'] ?? 15;

    // Step 1: Calculate urgency scores
    $now = new DateTime();
    $coursesWithUrgency = [];

    foreach ($courses as $course) {
        $endDate = new DateTime($course['end_date'] ?? '+30 days');
        $daysUntil = max((int) $now->diff($endDate)->days, 1);
        $weeksUntil = max($daysUntil / 7, 0.14); // avoid division by zero

        $priorityWeight = ($course['priority'] ?? 2);
        $weeklyGoal = ($course['weekly_hours_goal'] ?? 5) * 60; // convert to minutes

        $urgency = $priorityWeight * ($weeklyGoal / $weeksUntil);

        $coursesWithUrgency[] = [
            'id'           => $course['id'],
            'name'         => $course['name'],
            'color'        => $course['color'] ?? '#6366f1',
            'priority'     => $priorityWeight,
            'weekly_goal'  => $weeklyGoal,
            'urgency'      => $urgency,
            'allocated'    => 0,
            'remaining'    => 0,
        ];
    }

    // Step 2: Compute total available minutes
    $availableSlots = buildAvailableSlots($availability);
    $totalAvailableMinutes = count($availableSlots) * 60;

    if ($totalAvailableMinutes === 0) {
        return [];
    }

    // Step 3: Proportional allocation by urgency
    $totalUrgency = array_sum(array_column($coursesWithUrgency, 'urgency'));

    foreach ($coursesWithUrgency as &$c) {
        $proportion = ($totalUrgency > 0) ? ($c['urgency'] / $totalUrgency) : (1 / count($coursesWithUrgency));
        $allocated = min($proportion * $totalAvailableMinutes, $c['weekly_goal']);
        $allocated = max($allocated, $minBlock); // at least one block
        $c['allocated'] = (int) $allocated;
        $c['remaining'] = (int) $allocated;
    }
    unset($c);

    // Step 4: Sort by urgency (highest first)
    usort($coursesWithUrgency, function ($a, $b) {
        return $b['urgency'] <=> $a['urgency'];
    });

    // Step 5: Fill slots with course blocks
    $scheduleBlocks = [];
    $usedSlots = []; // Track which slots are used
    $slotIndex = 0;
    $courseIndex = 0;
    $lastCourseId = null;

    while ($slotIndex < count($availableSlots)) {
        // Find next course that needs time
        $coursesNeedingTime = array_filter($coursesWithUrgency, fn($c) => $c['remaining'] > 0);
        if (empty($coursesNeedingTime)) {
            break;
        }

        // Round-robin through courses, preferring alternation
        $selectedCourse = null;
        $attempts = 0;
        $tempIndex = $courseIndex;

        while ($attempts < count($coursesWithUrgency)) {
            $idx = $tempIndex % count($coursesWithUrgency);
            if ($coursesWithUrgency[$idx]['remaining'] > 0) {
                // Prefer a different course than the last one
                if ($coursesWithUrgency[$idx]['id'] !== $lastCourseId || $attempts >= count($coursesWithUrgency) - 1) {
                    $selectedCourse = &$coursesWithUrgency[$idx];
                    $courseIndex = $idx + 1;
                    break;
                }
            }
            $tempIndex++;
            $attempts++;
        }

        if ($selectedCourse === null) {
            break;
        }

        // Determine block duration
        $blockMinutes = min($maxBlock, $selectedCourse['remaining']);
        $blockMinutes = max($blockMinutes, $minBlock);
        $slotsNeeded = (int) ceil($blockMinutes / 60);

        // Check we have enough consecutive slots
        $consecutiveSlots = getConsecutiveSlots($availableSlots, $slotIndex, $slotsNeeded, $usedSlots);

        if (empty($consecutiveSlots)) {
            $slotIndex++;
            continue;
        }

        // Create schedule block
        $firstSlot = $consecutiveSlots[0];
        $lastSlot = $consecutiveSlots[count($consecutiveSlots) - 1];

        $block = [
            'course_id'  => $selectedCourse['id'],
            'course_name'=> $selectedCourse['name'],
            'color'      => $selectedCourse['color'],
            'day'        => $firstSlot['day'],
            'start_time' => sprintf('%02d:00', $firstSlot['hour']),
            'end_time'   => sprintf('%02d:%02d', $firstSlot['hour'], $blockMinutes % 60 === 0 ? 0 : $blockMinutes),
            'label'      => $selectedCourse['name'],
            'duration'   => $blockMinutes,
        ];

        // Recalculate proper end time
        $startMinutes = $firstSlot['hour'] * 60;
        $endMinutes = $startMinutes + $blockMinutes;
        $block['start_time'] = sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60);
        $block['end_time'] = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);

        $scheduleBlocks[] = $block;

        // Mark slots as used
        foreach ($consecutiveSlots as $slot) {
            $usedSlots[$slot['day'] . '-' . $slot['hour']] = true;
        }

        $selectedCourse['remaining'] -= $blockMinutes;
        $lastCourseId = $selectedCourse['id'];

        // Skip past the used slots + break time
        $slotIndex += count($consecutiveSlots);
        if ($breakMins >= 30) {
            $slotIndex++; // Skip one hour for break
        }

        unset($selectedCourse);
    }

    // Sort blocks by day then start time
    usort($scheduleBlocks, function ($a, $b) {
        if ($a['day'] !== $b['day']) return $a['day'] - $b['day'];
        return strcmp($a['start_time'], $b['start_time']);
    });

    // Add sequential IDs
    foreach ($scheduleBlocks as $i => &$block) {
        $block['id'] = $i + 1;
    }

    return $scheduleBlocks;
}

/**
 * Build a flat list of available time slots from the availability grid.
 */
function buildAvailableSlots(array $availability): array {
    $slots = [];
    for ($day = 0; $day < 7; $day++) {
        for ($hour = 6; $hour < 22; $hour++) {
            if (!empty($availability[$day][$hour])) {
                $slots[] = ['day' => $day, 'hour' => $hour];
            }
        }
    }
    return $slots;
}

/**
 * Get consecutive available slots starting from a given index.
 */
function getConsecutiveSlots(array $allSlots, int $startIndex, int $needed, array $usedSlots): array {
    $consecutive = [];

    for ($i = $startIndex; $i < count($allSlots) && count($consecutive) < $needed; $i++) {
        $slot = $allSlots[$i];
        $key = $slot['day'] . '-' . $slot['hour'];

        if (isset($usedSlots[$key])) {
            continue;
        }

        // Check if consecutive with previous slot (same day, next hour)
        if (!empty($consecutive)) {
            $prev = $consecutive[count($consecutive) - 1];
            if ($slot['day'] !== $prev['day'] || $slot['hour'] !== $prev['hour'] + 1) {
                // Not consecutive, start over from this slot
                $consecutive = [$slot];
                continue;
            }
        }

        $consecutive[] = $slot;
    }

    return count($consecutive) >= 1 ? $consecutive : [];
}

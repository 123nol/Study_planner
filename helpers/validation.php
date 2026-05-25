<?php
/* ============================================
   INPUT VALIDATION HELPERS
   ============================================ */

/**
 * Sanitize a string input.
 */
function sanitizeString(mixed $value): string {
    return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields are present in data array.
 * Returns array of missing field names.
 */
function validateRequired(array $data, array $fields): array {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Validate an integer is within a range.
 */
function validateIntRange(mixed $value, int $min, int $max): bool {
    $val = filter_var($value, FILTER_VALIDATE_INT);
    return $val !== false && $val >= $min && $val <= $max;
}

/**
 * Validate a date string (Y-m-d format).
 */
function validateDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate a time string (H:i format).
 */
function validateTime(string $time): bool {
    $t = DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
}

/**
 * Validate a hex color string (#RRGGBB).
 */
function validateHexColor(string $color): bool {
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
}

/**
 * Validate email format.
 */
function validateEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize and cast to int.
 */
function sanitizeInt(mixed $value): int {
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

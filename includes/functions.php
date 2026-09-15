<?php
/**
 * Small shared helper functions used across the app.
 */

/**
 * jsonResponse() - sends a consistent JSON envelope and stops execution.
 * Every API endpoint in this system uses this so the frontend can rely on
 * always receiving { success, message, data } shaped JSON.
 */
function jsonResponse(bool $success, string $message = '', array $data = [], int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ]);
    exit;
}

/**
 * clean() - trims a string and escapes HTML special characters for safe
 * output. Use this any time user-supplied text is echoed into HTML.
 */
function clean(?string $value): string
{
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * readJsonBody() - reads and decodes a JSON request body sent via fetch().
 * Returns an empty array if the body is missing or invalid.
 */
function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * gradeStatus() - turns a numeric grade into a Passed / Failed / Pending label.
 * Passing grade for this system is >= 75.
 */
function gradeStatus($grade): string
{
    if ($grade === null || $grade === '') {
        return 'Pending';
    }
    return ((float) $grade >= 75) ? 'Passed' : 'Failed';
}

/**
 * gradeStatusClass() - CSS class to color-code the status badge.
 */
function gradeStatusClass($grade): string
{
    $status = gradeStatus($grade);
    return match ($status) {
        'Passed'  => 'badge-passed',
        'Failed'  => 'badge-failed',
        default   => 'badge-pending',
    };
}

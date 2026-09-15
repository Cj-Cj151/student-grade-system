<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// Only teachers may encode/update grades through this endpoint.
requireApiRole('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();
$gradeId = isset($body['grade_id']) ? (int) $body['grade_id'] : 0;
$gradeValue = array_key_exists('grade', $body) ? $body['grade'] : null;
$remarks = trim((string) ($body['remarks'] ?? ''));

$teacherId = $_SESSION['teacher_id'] ?? null;

if (!$gradeId || !$teacherId) {
    jsonResponse(false, 'Missing or invalid grade record.', [], 422);
}

// --- Server-side validation (never trust the client) --------------------
if ($gradeValue !== null && $gradeValue !== '') {
    if (!is_numeric($gradeValue)) {
        jsonResponse(false, 'Grade must be a number.', [], 422);
    }
    $gradeValue = (float) $gradeValue;
    if ($gradeValue < 0 || $gradeValue > 100) {
        jsonResponse(false, 'Grade must be between 0 and 100.', [], 422);
    }
} else {
    $gradeValue = null; // allow clearing a grade back to "pending"
}

if (strlen($remarks) > 255) {
    jsonResponse(false, 'Remarks must be 255 characters or fewer.', [], 422);
}

$pdo = getDbConnection();

// Make sure this grade record actually belongs to a subject/student this
// teacher is responsible for, so a teacher can never edit another
// teacher's grade record by guessing an ID.
$stmt = $pdo->prepare('SELECT grade_id FROM grades WHERE grade_id = :gid AND teacher_id = :tid');
$stmt->execute(['gid' => $gradeId, 'tid' => $teacherId]);

if (!$stmt->fetch()) {
    jsonResponse(false, 'You are not authorized to update this grade record.', [], 403);
}

try {
    $stmt = $pdo->prepare('
        UPDATE grades
        SET grade = :grade, remarks = :remarks, updated_at = NOW()
        WHERE grade_id = :gid AND teacher_id = :tid
    ');
    $stmt->execute([
        'grade'   => $gradeValue,
        'remarks' => $remarks !== '' ? $remarks : null,
        'gid'     => $gradeId,
        'tid'     => $teacherId,
    ]);
} catch (PDOException $e) {
    error_log('Grade save failed: ' . $e->getMessage());
    jsonResponse(false, 'Unable to save grade. Please try again.', [], 500);
}

jsonResponse(true, 'Grade successfully saved.', [
    'status'       => gradeStatus($gradeValue),
    'status_class' => gradeStatusClass($gradeValue),
]);

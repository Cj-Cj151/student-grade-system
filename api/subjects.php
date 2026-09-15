<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();

$subjectId   = !empty($body['subject_id']) ? (int) $body['subject_id'] : null;
$subjectCode = trim((string) ($body['subject_code'] ?? ''));
$subjectName = trim((string) ($body['subject_name'] ?? ''));
$units       = isset($body['units']) ? (float) $body['units'] : 0;

if ($subjectCode === '' || $subjectName === '') {
    jsonResponse(false, 'Please fill in all required fields.', [], 422);
}
if ($units <= 0 || $units > 10) {
    jsonResponse(false, 'Units must be a number greater than 0 (up to 10).', [], 422);
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare('SELECT subject_id FROM subjects WHERE subject_code = :code AND subject_id != :sid');
    $stmt->execute(['code' => $subjectCode, 'sid' => $subjectId ?? 0]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'That subject code is already in use.', [], 409);
    }

    if ($subjectId) {
        $stmt = $pdo->prepare('
            UPDATE subjects SET subject_code = :code, subject_name = :name, units = :units
            WHERE subject_id = :sid
        ');
        $stmt->execute(['code' => $subjectCode, 'name' => $subjectName, 'units' => $units, 'sid' => $subjectId]);
        $message = 'Subject updated successfully.';
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO subjects (subject_code, subject_name, units) VALUES (:code, :name, :units)
        ');
        $stmt->execute(['code' => $subjectCode, 'name' => $subjectName, 'units' => $units]);
        $message = 'Subject created successfully.';
    }
} catch (PDOException $e) {
    error_log('Subject save failed: ' . $e->getMessage());
    jsonResponse(false, 'Unable to save subject. Please try again.', [], 500);
}

jsonResponse(true, $message);

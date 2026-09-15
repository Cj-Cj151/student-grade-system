<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();
$pdo = getDbConnection();

// --- Set an existing term as the active one -----------------------------
if (($body['action'] ?? '') === 'set_active') {
    $termId = !empty($body['term_id']) ? (int) $body['term_id'] : 0;
    if (!$termId) {
        jsonResponse(false, 'Missing term.', [], 422);
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec("UPDATE academic_terms SET status = 'inactive' WHERE status = 'active'");
        $stmt = $pdo->prepare("UPDATE academic_terms SET status = 'active' WHERE term_id = :tid");
        $stmt->execute(['tid' => $termId]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Set active term failed: ' . $e->getMessage());
        jsonResponse(false, 'Unable to update the active term.', [], 500);
    }

    jsonResponse(true, 'Active term updated.');
}

// --- Add / edit a term ----------------------------------------------------
$termId = !empty($body['term_id']) ? (int) $body['term_id'] : null;
$schoolYear = trim((string) ($body['school_year'] ?? ''));
$semester = trim((string) ($body['semester'] ?? ''));
$makeActive = !empty($body['make_active']);

if ($schoolYear === '' || $semester === '') {
    jsonResponse(false, 'Please fill in all required fields.', [], 422);
}
if (!preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
    jsonResponse(false, 'School year must be in the format YYYY-YYYY (e.g. 2026-2027).', [], 422);
}

try {
    $stmt = $pdo->prepare('
        SELECT term_id FROM academic_terms
        WHERE school_year = :sy AND semester = :sem AND term_id != :tid
    ');
    $stmt->execute(['sy' => $schoolYear, 'sem' => $semester, 'tid' => $termId ?? 0]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'That school year and semester combination already exists.', [], 409);
    }

    $pdo->beginTransaction();

    if ($makeActive) {
        $pdo->exec("UPDATE academic_terms SET status = 'inactive' WHERE status = 'active'");
    }
    $status = $makeActive ? 'active' : 'inactive';

    if ($termId) {
        $stmt = $pdo->prepare('
            UPDATE academic_terms SET school_year = :sy, semester = :sem, status = :status
            WHERE term_id = :tid
        ');
        $stmt->execute(['sy' => $schoolYear, 'sem' => $semester, 'status' => $status, 'tid' => $termId]);
        $message = 'Academic term updated successfully.';
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO academic_terms (school_year, semester, status) VALUES (:sy, :sem, :status)
        ');
        $stmt->execute(['sy' => $schoolYear, 'sem' => $semester, 'status' => $status]);
        $message = 'Academic term created successfully.';
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Term save failed: ' . $e->getMessage());
    jsonResponse(false, 'Unable to save academic term. Please try again.', [], 500);
}

jsonResponse(true, $message);

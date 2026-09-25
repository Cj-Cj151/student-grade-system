<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();

$gradeId = isset($body['grade_id'])
    ? (int) $body['grade_id']
    : 0;

$gradeValue = array_key_exists('grade', $body)
    ? $body['grade']
    : null;

$remarks = trim(
    (string) ($body['remarks'] ?? '')
);

if (!$gradeId) {
    jsonResponse(
        false,
        'Missing grade record.',
        [],
        422
    );
}

if ($gradeValue !== null && $gradeValue !== '') {

    if (!is_numeric($gradeValue)) {
        jsonResponse(
            false,
            'Grade must be a number.',
            [],
            422
        );
    }

    $gradeValue = (float) $gradeValue;

    if ($gradeValue < 0 || $gradeValue > 100) {
        jsonResponse(
            false,
            'Grade must be between 0 and 100.',
            [],
            422
        );
    }

} else {

    $gradeValue = null;

}

if (strlen($remarks) > 255) {
    jsonResponse(
        false,
        'Remarks must be 255 characters or fewer.',
        [],
        422
    );
}

$pdo = getDbConnection();

try {

    $stmt = $pdo->prepare('
        SELECT grade_id
        FROM grades
        WHERE grade_id = :gid
        LIMIT 1
    ');

    $stmt->execute([
        'gid' => $gradeId
    ]);

    if (!$stmt->fetchColumn()) {
        jsonResponse(
            false,
            'Grade record not found.',
            [],
            404
        );
    }

    $stmt = $pdo->prepare('
        UPDATE grades
        SET
            grade = :grade,
            remarks = :remarks,
            updated_at = NOW()
        WHERE grade_id = :gid
    ');

    $stmt->execute([
        'grade' => $gradeValue,
        'remarks' => $remarks !== ''
            ? $remarks
            : null,
        'gid' => $gradeId
    ]);

} catch (PDOException $e) {

    error_log(
        'Admin grade save failed: '
        . $e->getMessage()
    );

    jsonResponse(
        false,
        'Unable to save grade. Please try again.',
        [],
        500
    );
}

jsonResponse(
    true,
    'Grade updated successfully.'
);
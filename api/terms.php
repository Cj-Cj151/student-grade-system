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

$action = strtolower(
    trim((string) ($body['action'] ?? ''))
);

if ($action === 'delete') {

    $termId = !empty($body['term_id'])
        ? (int) $body['term_id']
        : 0;

    if (!$termId) {
        jsonResponse(
            false,
            'Missing academic term.',
            [],
            422
        );
    }

    try {

        $stmt = $pdo->prepare('
            SELECT term_id
            FROM academic_terms
            WHERE term_id = :tid
            LIMIT 1
        ');

        $stmt->execute([
            'tid' => $termId
        ]);

        if (!$stmt->fetch()) {
            jsonResponse(
                false,
                'Academic term not found.',
                [],
                404
            );
        }

        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM grades
            WHERE term_id = :tid
        ');

        $stmt->execute([
            'tid' => $termId
        ]);

        $gradeCount = (int) $stmt->fetchColumn();

        if ($gradeCount > 0) {
            jsonResponse(
                false,
                'This academic term cannot be deleted because grade records already exist for it.',
                [
                    'grade_count' => $gradeCount
                ],
                409
            );
        }

        $stmt = $pdo->prepare('
            DELETE FROM academic_terms
            WHERE term_id = :tid
        ');

        $stmt->execute([
            'tid' => $termId
        ]);

    } catch (PDOException $e) {

        error_log(
            'Academic term delete failed: '
            . $e->getMessage()
        );

        jsonResponse(
            false,
            'Unable to delete academic term. Please try again.',
            [],
            500
        );
    }

    jsonResponse(
        true,
        'Academic term deleted successfully.'
    );
}

if ($action === 'set_active') {

    $termId = !empty($body['term_id'])
        ? (int) $body['term_id']
        : 0;

    if (!$termId) {
        jsonResponse(
            false,
            'Missing term.',
            [],
            422
        );
    }

    try {

        $pdo->beginTransaction();

        $stmt = $pdo->prepare('
            SELECT term_id
            FROM academic_terms
            WHERE term_id = :tid
            LIMIT 1
        ');

        $stmt->execute([
            'tid' => $termId
        ]);

        if (!$stmt->fetch()) {

            $pdo->rollBack();

            jsonResponse(
                false,
                'Academic term not found.',
                [],
                404
            );
        }

        $pdo->exec("
            UPDATE academic_terms
            SET status = 'inactive'
            WHERE status = 'active'
        ");

        $stmt = $pdo->prepare('
            UPDATE academic_terms
            SET status = :status
            WHERE term_id = :tid
        ');

        $stmt->execute([
            'status' => 'active',
            'tid' => $termId
        ]);

        $pdo->commit();

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Set active term failed: '
            . $e->getMessage()
        );

        jsonResponse(
            false,
            'Unable to update the active term.',
            [],
            500
        );
    }

    jsonResponse(
        true,
        'Active term updated successfully.'
    );
}

$termId = !empty($body['term_id'])
    ? (int) $body['term_id']
    : null;

$schoolYear = trim(
    (string) ($body['school_year'] ?? '')
);

$semester = trim(
    (string) ($body['semester'] ?? '')
);

$gradingPeriod = trim(
    (string) ($body['grading_period'] ?? '')
);

$makeActive = !empty($body['make_active']);

if (
    $schoolYear === '' ||
    $semester === '' ||
    $gradingPeriod === ''
) {
    jsonResponse(
        false,
        'Please fill in all required fields.',
        [],
        422
    );
}

if (!preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
    jsonResponse(
        false,
        'School year must be in the format YYYY-YYYY (e.g. 2026-2027).',
        [],
        422
    );
}

$allowedSemesters = [
    '1st Semester',
    '2nd Semester',
    'Summer'
];

if (!in_array($semester, $allowedSemesters, true)) {
    jsonResponse(
        false,
        'Invalid semester.',
        [],
        422
    );
}

$allowedGradingPeriods = [
    'Prelim',
    'Midterm',
    'Semi-Final',
    'Final'
];

if (!in_array($gradingPeriod, $allowedGradingPeriods, true)) {
    jsonResponse(
        false,
        'Invalid grading period.',
        [],
        422
    );
}

try {

    $stmt = $pdo->prepare('
        SELECT term_id
        FROM academic_terms
        WHERE school_year = :sy
          AND semester = :sem
          AND grading_period = :gp
          AND term_id != :tid
        LIMIT 1
    ');

    $stmt->execute([
        'sy' => $schoolYear,
        'sem' => $semester,
        'gp' => $gradingPeriod,
        'tid' => $termId ?? 0
    ]);

    if ($stmt->fetch()) {
        jsonResponse(
            false,
            'That school year, semester, and grading period combination already exists.',
            [],
            409
        );
    }

    if ($termId) {

        $stmt = $pdo->prepare('
            SELECT term_id
            FROM academic_terms
            WHERE term_id = :tid
            LIMIT 1
        ');

        $stmt->execute([
            'tid' => $termId
        ]);

        if (!$stmt->fetch()) {
            jsonResponse(
                false,
                'Academic term not found.',
                [],
                404
            );
        }
    }

    $pdo->beginTransaction();

    if ($makeActive) {

        $pdo->exec("
            UPDATE academic_terms
            SET status = 'inactive'
            WHERE status = 'active'
        ");
    }

    $status = $makeActive
        ? 'active'
        : 'inactive';

    if ($termId) {

        $stmt = $pdo->prepare('
            UPDATE academic_terms
            SET
                school_year = :sy,
                semester = :sem,
                grading_period = :gp,
                status = :status
            WHERE term_id = :tid
        ');

        $stmt->execute([
            'sy' => $schoolYear,
            'sem' => $semester,
            'gp' => $gradingPeriod,
            'status' => $status,
            'tid' => $termId
        ]);

        $message = 'Academic term updated successfully.';

    } else {

        $stmt = $pdo->prepare('
            INSERT INTO academic_terms (
                school_year,
                semester,
                grading_period,
                status
            )
            VALUES (
                :sy,
                :sem,
                :gp,
                :status
            )
        ');

        $stmt->execute([
            'sy' => $schoolYear,
            'sem' => $semester,
            'gp' => $gradingPeriod,
            'status' => $status
        ]);

        $message = 'Academic term created successfully.';
    }

    $pdo->commit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Term save failed: '
        . $e->getMessage()
    );

    jsonResponse(
        false,
        'Unable to save academic term. Please try again.',
        [],
        500
    );
}

jsonResponse(
    true,
    $message
);
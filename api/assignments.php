<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$teacherId = isset($data['teacher_id'])
    ? (int) $data['teacher_id']
    : 0;

$subjectId = isset($data['subject_id'])
    ? (int) $data['subject_id']
    : 0;

$sectionId = isset($data['section_id'])
    ? (int) $data['section_id']
    : 0;

$termId = isset($data['term_id'])
    ? (int) $data['term_id']
    : 0;

if (
    $teacherId === 0 ||
    $subjectId === 0 ||
    $sectionId === 0 ||
    $termId === 0
) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'All assignment fields are required.'
    ]);

    exit;
}

try {

    $pdo = getDbConnection();

    $teacherStmt = $pdo->prepare("
        SELECT t.teacher_id
        FROM teachers t
        JOIN users u
            ON u.user_id = t.user_id
        WHERE t.teacher_id = :teacher_id
          AND u.is_active = TRUE
        LIMIT 1
    ");

    $teacherStmt->execute([
        'teacher_id' => $teacherId
    ]);

    if (!$teacherStmt->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected teacher is not available.'
        ]);

        exit;
    }

    $subjectStmt = $pdo->prepare("
        SELECT subject_id
        FROM subjects
        WHERE subject_id = :subject_id
        LIMIT 1
    ");

    $subjectStmt->execute([
        'subject_id' => $subjectId
    ]);

    if (!$subjectStmt->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected subject does not exist.'
        ]);

        exit;
    }

    $sectionStmt = $pdo->prepare("
        SELECT
            section_id,
            course,
            year_level
        FROM sections
        WHERE section_id = :section_id
          AND is_active = TRUE
        LIMIT 1
    ");

    $sectionStmt->execute([
        'section_id' => $sectionId
    ]);

    if (!$sectionStmt->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected section is not available.'
        ]);

        exit;
    }

    $termStmt = $pdo->prepare("
        SELECT term_id
        FROM academic_terms
        WHERE term_id = :term_id
        LIMIT 1
    ");

    $termStmt->execute([
        'term_id' => $termId
    ]);

    if (!$termStmt->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected academic term does not exist.'
        ]);

        exit;
    }

    $duplicateStmt = $pdo->prepare("
        SELECT assignment_id
        FROM subject_assignments
        WHERE subject_id = :subject_id
          AND section_id = :section_id
          AND term_id = :term_id
          AND is_active = TRUE
        LIMIT 1
    ");

    $duplicateStmt->execute([
        'subject_id' => $subjectId,
        'section_id' => $sectionId,
        'term_id' => $termId
    ]);

    if ($duplicateStmt->fetch()) {

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'This subject is already assigned to this section for the selected academic term.'
        ]);

        exit;
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO subject_assignments (
            subject_id,
            teacher_id,
            section_id,
            term_id,
            is_active
        )
        VALUES (
            :subject_id,
            :teacher_id,
            :section_id,
            :term_id,
            TRUE
        )
    ");

    $insertStmt->execute([
        'subject_id' => $subjectId,
        'teacher_id' => $teacherId,
        'section_id' => $sectionId,
        'term_id' => $termId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Subject assignment saved successfully.'
    ]);

} catch (PDOException $e) {

    error_log('Subject assignment API failed: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to save subject assignment.'
    ]);
}
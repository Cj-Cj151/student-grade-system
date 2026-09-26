<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$assignmentId = (int) ($data['assignment_id'] ?? 0);
$teacherId = (int) ($data['teacher_id'] ?? 0);
$subjectId = (int) ($data['subject_id'] ?? 0);
$sectionId = (int) ($data['section_id'] ?? 0);
$termId = (int) ($data['term_id'] ?? 0);

if (
    $assignmentId < 1 ||
    $teacherId < 1 ||
    $subjectId < 1 ||
    $sectionId < 1 ||
    $termId < 1
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

    $assignmentCheck = $pdo->prepare("
        SELECT assignment_id
        FROM subject_assignments
        WHERE assignment_id = :assignment_id
    ");

    $assignmentCheck->execute([
        'assignment_id' => $assignmentId
    ]);

    if (!$assignmentCheck->fetch()) {

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Assignment not found.'
        ]);

        exit;
    }

    $teacherCheck = $pdo->prepare("
        SELECT teacher_id
        FROM teachers t
        JOIN users u
            ON u.user_id = t.user_id
        WHERE t.teacher_id = :teacher_id
          AND u.is_active = TRUE
    ");

    $teacherCheck->execute([
        'teacher_id' => $teacherId
    ]);

    if (!$teacherCheck->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected teacher is not available.'
        ]);

        exit;
    }

    $subjectCheck = $pdo->prepare("
        SELECT subject_id
        FROM subjects
        WHERE subject_id = :subject_id
    ");

    $subjectCheck->execute([
        'subject_id' => $subjectId
    ]);

    if (!$subjectCheck->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected subject does not exist.'
        ]);

        exit;
    }

    $sectionCheck = $pdo->prepare("
        SELECT section_id
        FROM sections
        WHERE section_id = :section_id
          AND is_active = TRUE
    ");

    $sectionCheck->execute([
        'section_id' => $sectionId
    ]);

    if (!$sectionCheck->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected section is not available.'
        ]);

        exit;
    }

    $termCheck = $pdo->prepare("
        SELECT term_id
        FROM academic_terms
        WHERE term_id = :term_id
    ");

    $termCheck->execute([
        'term_id' => $termId
    ]);

    if (!$termCheck->fetch()) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Selected academic term does not exist.'
        ]);

        exit;
    }

    $duplicateCheck = $pdo->prepare("
        SELECT assignment_id
        FROM subject_assignments
        WHERE subject_id = :subject_id
          AND section_id = :section_id
          AND term_id = :term_id
          AND assignment_id <> :assignment_id
          AND is_active = TRUE
        LIMIT 1
    ");

    $duplicateCheck->execute([
        'subject_id' => $subjectId,
        'section_id' => $sectionId,
        'term_id' => $termId,
        'assignment_id' => $assignmentId
    ]);

    if ($duplicateCheck->fetch()) {

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'This subject is already assigned to this section for the selected academic term.'
        ]);

        exit;
    }

    $update = $pdo->prepare("
        UPDATE subject_assignments
        SET
            teacher_id = :teacher_id,
            subject_id = :subject_id,
            section_id = :section_id,
            term_id = :term_id
        WHERE assignment_id = :assignment_id
    ");

    $update->execute([
        'teacher_id' => $teacherId,
        'subject_id' => $subjectId,
        'section_id' => $sectionId,
        'term_id' => $termId,
        'assignment_id' => $assignmentId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Subject assignment updated successfully.'
    ]);

} catch (PDOException $e) {

    error_log('Update assignment failed: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to update subject assignment.'
    ]);
}
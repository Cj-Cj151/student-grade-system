<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();

$action = strtolower(trim((string) ($body['action'] ?? '')));
$teacherId = $_SESSION['teacher_id'] ?? null;

if (!$teacherId) {
    jsonResponse(false, 'Teacher account not found. Please log in again.', [], 401);
}

if (!in_array($action, ['add', 'update', 'delete'], true)) {
    jsonResponse(false, 'Invalid grade action.', [], 422);
}

$pdo = getDbConnection();

if ($action === 'add') {

    $studentId = isset($body['student_id'])
        ? (int) $body['student_id']
        : 0;

    $subjectId = isset($body['subject_id'])
        ? (int) $body['subject_id']
        : 0;

    $termId = isset($body['term_id'])
        ? (int) $body['term_id']
        : 0;

    $gradeValue = array_key_exists('grade', $body)
        ? $body['grade']
        : null;

    $remarks = trim((string) ($body['remarks'] ?? ''));

    if (!$studentId || !$subjectId || !$termId) {
        jsonResponse(
            false,
            'Student, subject, and academic term are required.',
            [],
            422
        );
    }

    if ($gradeValue === null || $gradeValue === '') {
        jsonResponse(
            false,
            'Grade is required.',
            [],
            422
        );
    }

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

    if (strlen($remarks) > 255) {
        jsonResponse(
            false,
            'Remarks must be 255 characters or fewer.',
            [],
            422
        );
    }

    $stmt = $pdo->prepare('
        SELECT subject_id
        FROM subjects
        WHERE subject_id = :sid
        LIMIT 1
    ');

    $stmt->execute([
        'sid' => $subjectId
    ]);

    if (!$stmt->fetchColumn()) {
        jsonResponse(
            false,
            'Selected subject was not found.',
            [],
            404
        );
    }

    $stmt = $pdo->prepare('
        SELECT term_id
        FROM academic_terms
        WHERE term_id = :tid
        LIMIT 1
    ');

    $stmt->execute([
        'tid' => $termId
    ]);

    if (!$stmt->fetchColumn()) {
        jsonResponse(
            false,
            'Selected academic term was not found.',
            [],
            404
        );
    }

    $stmt = $pdo->prepare('
        SELECT st.student_id
        FROM students st
        JOIN users u ON u.user_id = st.user_id
        WHERE st.student_id = :sid
          AND u.is_active = TRUE
        LIMIT 1
    ');

    $stmt->execute([
        'sid' => $studentId
    ]);

    if (!$stmt->fetchColumn()) {
        jsonResponse(
            false,
            'Student account was not found or is inactive.',
            [],
            422
        );
    }

    $stmt = $pdo->prepare('
        SELECT grade_id
        FROM grades
        WHERE student_id = :student_id
          AND subject_id = :subject_id
          AND term_id = :term_id
        LIMIT 1
    ');

    $stmt->execute([
        'student_id' => $studentId,
        'subject_id' => $subjectId,
        'term_id' => $termId
    ]);

    if ($stmt->fetch()) {
        jsonResponse(
            false,
            'This student already has a grade for the selected subject and term.',
            [],
            409
        );
    }

    try {

        $stmt = $pdo->prepare('
            INSERT INTO grades (
                student_id,
                subject_id,
                teacher_id,
                term_id,
                grade,
                remarks,
                updated_at
            )
            VALUES (
                :student_id,
                :subject_id,
                :teacher_id,
                :term_id,
                :grade,
                :remarks,
                NOW()
            )
            RETURNING grade_id
        ');

        $stmt->execute([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'term_id' => $termId,
            'grade' => $gradeValue,
            'remarks' => $remarks !== '' ? $remarks : null
        ]);

        $newGradeId = $stmt->fetchColumn();

    } catch (PDOException $e) {

        error_log('Grade insert failed: ' . $e->getMessage());

        jsonResponse(
            false,
            'Unable to add grade. Please try again.',
            [],
            500
        );
    }

    jsonResponse(
        true,
        'Grade successfully added.',
        [
            'grade_id' => (int) $newGradeId,
            'status' => gradeStatus($gradeValue),
            'status_class' => gradeStatusClass($gradeValue)
        ]
    );
}


if ($action === 'update') {

    $gradeId = isset($body['grade_id'])
        ? (int) $body['grade_id']
        : 0;

    $gradeValue = array_key_exists('grade', $body)
        ? $body['grade']
        : null;

    $remarks = trim((string) ($body['remarks'] ?? ''));

    if (!$gradeId) {
        jsonResponse(
            false,
            'Missing or invalid grade record.',
            [],
            422
        );
    }

    if ($gradeValue === null || $gradeValue === '') {
        jsonResponse(
            false,
            'Grade is required.',
            [],
            422
        );
    }

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

    if (strlen($remarks) > 255) {
        jsonResponse(
            false,
            'Remarks must be 255 characters or fewer.',
            [],
            422
        );
    }

    $stmt = $pdo->prepare('
        SELECT grade_id
        FROM grades
        WHERE grade_id = :gid
          AND teacher_id = :tid
        LIMIT 1
    ');

    $stmt->execute([
        'gid' => $gradeId,
        'tid' => $teacherId
    ]);

    if (!$stmt->fetchColumn()) {
        jsonResponse(
            false,
            'You are not authorized to update this grade record.',
            [],
            403
        );
    }

    try {

        $stmt = $pdo->prepare('
            UPDATE grades
            SET
                grade = :grade,
                remarks = :remarks,
                updated_at = NOW()
            WHERE grade_id = :gid
              AND teacher_id = :tid
        ');

        $stmt->execute([
            'grade' => $gradeValue,
            'remarks' => $remarks !== '' ? $remarks : null,
            'gid' => $gradeId,
            'tid' => $teacherId
        ]);

    } catch (PDOException $e) {

        error_log('Grade update failed: ' . $e->getMessage());

        jsonResponse(
            false,
            'Unable to update grade. Please try again.',
            [],
            500
        );
    }

    jsonResponse(
        true,
        'Grade successfully updated.',
        [
            'status' => gradeStatus($gradeValue),
            'status_class' => gradeStatusClass($gradeValue)
        ]
    );
}


if ($action === 'delete') {

    $gradeId = isset($body['grade_id'])
        ? (int) $body['grade_id']
        : 0;

    if (!$gradeId) {
        jsonResponse(
            false,
            'Missing or invalid grade record.',
            [],
            422
        );
    }

    $stmt = $pdo->prepare('
        SELECT grade_id
        FROM grades
        WHERE grade_id = :gid
          AND teacher_id = :tid
        LIMIT 1
    ');

    $stmt->execute([
        'gid' => $gradeId,
        'tid' => $teacherId
    ]);

    if (!$stmt->fetchColumn()) {
        jsonResponse(
            false,
            'You are not authorized to delete this grade record.',
            [],
            403
        );
    }

    try {

        $stmt = $pdo->prepare('
            DELETE FROM grades
            WHERE grade_id = :gid
              AND teacher_id = :tid
        ');

        $stmt->execute([
            'gid' => $gradeId,
            'tid' => $teacherId
        ]);

    } catch (PDOException $e) {

        error_log('Grade delete failed: ' . $e->getMessage());

        jsonResponse(
            false,
            'Unable to delete grade. Please try again.',
            [],
            500
        );
    }

    jsonResponse(
        true,
        'Grade successfully deleted.',
        []
    );
}
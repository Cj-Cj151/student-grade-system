<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();

$studentId  = !empty($body['student_id']) ? (int) $body['student_id'] : null;
$userId     = !empty($body['user_id']) ? (int) $body['user_id'] : null;
$firstName  = trim((string) ($body['first_name'] ?? ''));
$lastName   = trim((string) ($body['last_name'] ?? ''));
$email      = trim((string) ($body['email'] ?? ''));
$course     = trim((string) ($body['course'] ?? ''));
$yearLevel  = (int) ($body['year_level'] ?? 0);
$sectionId  = (int) ($body['section_id'] ?? 0);
$loginId    = trim((string) ($body['login_id'] ?? ''));
$password   = (string) ($body['password'] ?? '');

if (
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $course === '' ||
    $loginId === '' ||
    $sectionId < 1
) {
    jsonResponse(false, 'Please fill in all required fields.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Please enter a valid email address.', [], 422);
}

if ($yearLevel < 1 || $yearLevel > 4) {
    jsonResponse(false, 'Year level must be between 1 and 4.', [], 422);
}

$isNew = !$studentId || !$userId;

if ($isNew && strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters.', [], 422);
}

if (!$isNew && $password !== '' && strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters.', [], 422);
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare('
        SELECT section_id
        FROM sections
        WHERE section_id = :section_id
          AND course = :course
          AND year_level = :year_level
          AND is_active = TRUE
    ');

    $stmt->execute([
        'section_id' => $sectionId,
        'course' => $course,
        'year_level' => $yearLevel
    ]);

    if (!$stmt->fetch()) {
        jsonResponse(false, 'Selected section does not match the course and year level.', [], 422);
    }

    $stmt = $pdo->prepare('
        SELECT user_id
        FROM users
        WHERE login_id = :login_id
          AND user_id != :uid
    ');

    $stmt->execute([
        'login_id' => $loginId,
        'uid' => $userId ?? 0
    ]);

    if ($stmt->fetch()) {
        jsonResponse(false, 'That Student ID is already in use.', [], 409);
    }

    $stmt = $pdo->prepare('
        SELECT student_id
        FROM students
        WHERE email = :email
          AND student_id != :sid
    ');

    $stmt->execute([
        'email' => $email,
        'sid' => $studentId ?? 0
    ]);

    if ($stmt->fetch()) {
        jsonResponse(false, 'That email is already in use by another student.', [], 409);
    }

    $pdo->beginTransaction();

    if ($isNew) {
        $stmt = $pdo->prepare('
            INSERT INTO users (
                login_id,
                password_hash,
                role,
                is_active
            )
            VALUES (
                :login_id,
                :password_hash,
                \'student\',
                TRUE
            )
            RETURNING user_id
        ');

        $stmt->execute([
            'login_id' => $loginId,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT)
        ]);

        $userId = $stmt->fetchColumn();

        $stmt = $pdo->prepare('
            INSERT INTO students (
                user_id,
                first_name,
                last_name,
                email,
                course,
                year_level,
                section_id
            )
            VALUES (
                :user_id,
                :first_name,
                :last_name,
                :email,
                :course,
                :year_level,
                :section_id
            )
        ');

        $stmt->execute([
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'course' => $course,
            'year_level' => $yearLevel,
            'section_id' => $sectionId
        ]);

        $message = 'Student created successfully.';
    } else {
        if ($password !== '') {
            $stmt = $pdo->prepare('
                UPDATE users
                SET
                    login_id = :login_id,
                    password_hash = :password_hash
                WHERE user_id = :uid
            ');

            $stmt->execute([
                'login_id' => $loginId,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'uid' => $userId
            ]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE users
                SET login_id = :login_id
                WHERE user_id = :uid
            ');

            $stmt->execute([
                'login_id' => $loginId,
                'uid' => $userId
            ]);
        }

        $stmt = $pdo->prepare('
            UPDATE students
            SET
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                course = :course,
                year_level = :year_level,
                section_id = :section_id
            WHERE student_id = :sid
              AND user_id = :uid
        ');

        $stmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'course' => $course,
            'year_level' => $yearLevel,
            'section_id' => $sectionId,
            'sid' => $studentId,
            'uid' => $userId
        ]);

        $message = 'Student updated successfully.';
    }

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Student save failed: ' . $e->getMessage());

    jsonResponse(
        false,
        'Unable to save student. Please try again.',
        [],
        500
    );
}

jsonResponse(true, $message);
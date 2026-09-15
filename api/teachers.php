<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();

$teacherId  = !empty($body['teacher_id']) ? (int) $body['teacher_id'] : null;
$userId     = !empty($body['user_id']) ? (int) $body['user_id'] : null;
$firstName  = trim((string) ($body['first_name'] ?? ''));
$lastName   = trim((string) ($body['last_name'] ?? ''));
$email      = trim((string) ($body['email'] ?? ''));
$department = trim((string) ($body['department'] ?? ''));
$loginId    = trim((string) ($body['login_id'] ?? ''));
$password   = (string) ($body['password'] ?? '');

if ($firstName === '' || $lastName === '' || $email === '' || $department === '' || $loginId === '') {
    jsonResponse(false, 'Please fill in all required fields.', [], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Please enter a valid email address.', [], 422);
}

$isNew = !$teacherId || !$userId;
if ($isNew && strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters.', [], 422);
}
if (!$isNew && $password !== '' && strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters.', [], 422);
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE login_id = :login_id AND user_id != :uid');
    $stmt->execute(['login_id' => $loginId, 'uid' => $userId ?? 0]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'That Teacher ID is already in use.', [], 409);
    }

    $stmt = $pdo->prepare('SELECT teacher_id FROM teachers WHERE email = :email AND teacher_id != :tid');
    $stmt->execute(['email' => $email, 'tid' => $teacherId ?? 0]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'That email is already in use by another teacher.', [], 409);
    }

    $pdo->beginTransaction();

    if ($isNew) {
        $stmt = $pdo->prepare('
            INSERT INTO users (login_id, password_hash, role, is_active)
            VALUES (:login_id, :password_hash, \'teacher\', TRUE)
            RETURNING user_id
        ');
        $stmt->execute([
            'login_id'      => $loginId,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
        $userId = $stmt->fetchColumn();

        $stmt = $pdo->prepare('
            INSERT INTO teachers (user_id, first_name, last_name, email, department)
            VALUES (:user_id, :first_name, :last_name, :email, :department)
        ');
        $stmt->execute([
            'user_id'    => $userId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'department' => $department,
        ]);

        $message = 'Teacher created successfully.';
    } else {
        if ($password !== '') {
            $stmt = $pdo->prepare('UPDATE users SET login_id = :login_id, password_hash = :password_hash WHERE user_id = :uid');
            $stmt->execute([
                'login_id'      => $loginId,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'uid'           => $userId,
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET login_id = :login_id WHERE user_id = :uid');
            $stmt->execute(['login_id' => $loginId, 'uid' => $userId]);
        }

        $stmt = $pdo->prepare('
            UPDATE teachers
            SET first_name = :first_name, last_name = :last_name, email = :email, department = :department
            WHERE teacher_id = :tid AND user_id = :uid
        ');
        $stmt->execute([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'department' => $department,
            'tid'        => $teacherId,
            'uid'        => $userId,
        ]);

        $message = 'Teacher updated successfully.';
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Teacher save failed: ' . $e->getMessage());
    jsonResponse(false, 'Unable to save teacher. Please try again.', [], 500);
}

jsonResponse(true, $message);

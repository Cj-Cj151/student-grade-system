<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();
$loginId = trim($body['login_id'] ?? '');
$password = (string) ($body['password'] ?? '');

if ($loginId === '' || $password === '') {
    jsonResponse(false, 'Please enter both your login ID and password.', [], 422);
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare('SELECT user_id, login_id, password_hash, role, is_active FROM users WHERE login_id = :login_id');
    $stmt->execute(['login_id' => $loginId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Login query failed: ' . $e->getMessage());
    jsonResponse(false, 'A system error occurred. Please try again later.', [], 500);
}

// Same generic message whether the ID doesn't exist or the password is wrong,
// so we don't reveal which logins are valid.
$genericError = 'Invalid login ID or password.';

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(false, $genericError, [], 401);
}

if (!$user['is_active']) {
    jsonResponse(false, 'This account has been deactivated. Please contact the administrator.', [], 403);
}

// Regenerate the session ID on login to prevent session fixation.
session_regenerate_id(true);

$_SESSION['user_id']  = $user['user_id'];
$_SESSION['login_id'] = $user['login_id'];
$_SESSION['role']     = $user['role'];

// Load a friendly display name for the sidebar, depending on role.
$displayName = $user['login_id'];
try {
    if ($user['role'] === 'student') {
        $stmt = $pdo->prepare('SELECT student_id, first_name, last_name FROM students WHERE user_id = :uid');
        $stmt->execute(['uid' => $user['user_id']]);
        if ($row = $stmt->fetch()) {
            $displayName = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['student_id'] = $row['student_id'];
        }
    } elseif ($user['role'] === 'teacher') {
        $stmt = $pdo->prepare('SELECT teacher_id, first_name, last_name FROM teachers WHERE user_id = :uid');
        $stmt->execute(['uid' => $user['user_id']]);
        if ($row = $stmt->fetch()) {
            $displayName = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['teacher_id'] = $row['teacher_id'];
        }
    } else {
        $displayName = 'Administrator';
    }
} catch (PDOException $e) {
    error_log('Login profile lookup failed: ' . $e->getMessage());
    // Not fatal - we can still log the user in with a fallback display name.
}

$_SESSION['display_name'] = $displayName;

jsonResponse(true, 'Login successful.', [
    'redirect' => dashboardUrlForRole($user['role']),
]);

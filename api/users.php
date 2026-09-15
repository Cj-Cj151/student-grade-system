<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();
$action = $body['action'] ?? '';
$userId = !empty($body['user_id']) ? (int) $body['user_id'] : 0;

if (!$userId) {
    jsonResponse(false, 'Missing user account.', [], 422);
}

$pdo = getDbConnection();

// Prevent an admin from deactivating their own account by mistake and
// getting locked out of the system.
if ($userId === (int) $_SESSION['user_id'] && $action === 'toggle_status') {
    jsonResponse(false, 'You cannot change the status of your own account.', [], 403);
}

if ($action === 'toggle_status') {
    $isActive = !empty($body['is_active']);

    try {
        $stmt = $pdo->prepare('UPDATE users SET is_active = :active WHERE user_id = :uid');
        $stmt->execute(['active' => $isActive, 'uid' => $userId]);
    } catch (PDOException $e) {
        error_log('User status toggle failed: ' . $e->getMessage());
        jsonResponse(false, 'Unable to update account status.', [], 500);
    }

    jsonResponse(true, $isActive ? 'Account activated.' : 'Account deactivated.');
}

if ($action === 'reset_password') {
    $password = (string) ($body['password'] ?? '');
    if (strlen($password) < 6) {
        jsonResponse(false, 'Password must be at least 6 characters.', [], 422);
    }

    try {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :uid');
        $stmt->execute(['hash' => password_hash($password, PASSWORD_BCRYPT), 'uid' => $userId]);
    } catch (PDOException $e) {
        error_log('Password reset failed: ' . $e->getMessage());
        jsonResponse(false, 'Unable to reset password.', [], 500);
    }

    jsonResponse(true, 'Password reset successfully.');
}

jsonResponse(false, 'Unknown action.', [], 400);

<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApiRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$body = readJsonBody();

$action = trim((string) ($body['action'] ?? ''));
$userId = isset($body['user_id'])
    ? (int) $body['user_id']
    : 0;

if (!$userId) {
    jsonResponse(false, 'Missing user account.', [], 422);
}

$pdo = getDbConnection();

if (
    $action === 'toggle_status' &&
    $userId === (int) ($_SESSION['user_id'] ?? 0)
) {
    jsonResponse(
        false,
        'You cannot change the status of your own account.',
        [],
        403
    );
}

if ($action === 'toggle_status') {

    $isActive = filter_var(
        $body['is_active'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

    try {

        $stmt = $pdo->prepare('
            UPDATE users
            SET is_active = :active
            WHERE user_id = :uid
        ');

        $stmt->bindValue(
            ':active',
            $isActive,
            PDO::PARAM_BOOL
        );

        $stmt->bindValue(
            ':uid',
            $userId,
            PDO::PARAM_INT
        );

        $stmt->execute();

    } catch (PDOException $e) {

        error_log(
            'User status toggle failed: ' .
            $e->getMessage()
        );

        jsonResponse(
            false,
            'Unable to update account status.',
            [],
            500
        );
    }

    jsonResponse(
        true,
        $isActive
            ? 'Account activated.'
            : 'Account deactivated.'
    );
}

if ($action === 'reset_password') {

    $password = (string) (
        $body['password'] ?? ''
    );

    if (strlen($password) < 6) {
        jsonResponse(
            false,
            'Password must be at least 6 characters.',
            [],
            422
        );
    }

    try {

        $stmt = $pdo->prepare('
            UPDATE users
            SET password_hash = :hash
            WHERE user_id = :uid
        ');

        $stmt->execute([
            'hash' => password_hash(
                $password,
                PASSWORD_BCRYPT
            ),
            'uid' => $userId
        ]);

    } catch (PDOException $e) {

        error_log(
            'Password reset failed: ' .
            $e->getMessage()
        );

        jsonResponse(
            false,
            'Unable to reset password.',
            [],
            500
        );
    }

    jsonResponse(
        true,
        'Password reset successfully.'
    );
}

jsonResponse(
    false,
    'Unknown action.',
    [],
    400
);
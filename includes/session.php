<?php
/**
 * Session bootstrap + role-based access control helpers.
 * Include this at the top of every protected page or API endpoint.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie a little.
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * isLoggedIn() - true if a user has an active session.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

/**
 * currentRole() - returns the logged-in user's role, or null.
 */
function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/**
 * requireLogin() - kicks the visitor back to the login page if not logged in.
 * Use on normal HTML pages (not API endpoints).
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /public/login.php');
        exit;
    }
}

/**
 * requireRole() - requireLogin() + must match one of the given roles.
 * Sends the visitor to their own dashboard (not an error page) if the
 * role does not match, so a student clicking an admin link just lands
 * somewhere safe instead of seeing a raw permissions error.
 *
 * @param string|array $roles one role or an array of allowed roles
 */
function requireRole($roles): void
{
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];

    if (!in_array(currentRole(), $roles, true)) {
        header('Location: ' . dashboardUrlForRole(currentRole()));
        exit;
    }
}

/**
 * requireApiRole() - same idea as requireRole() but for JSON API endpoints.
 * Returns a JSON error and stops instead of redirecting.
 */
function requireApiRole($roles): void
{
    $roles = is_array($roles) ? $roles : [$roles];

    if (!isLoggedIn()) {
        jsonResponse(false, 'You must be logged in to do that.', [], 401);
    }
    if (!in_array(currentRole(), $roles, true)) {
        jsonResponse(false, 'You are not authorized to perform this action.', [], 403);
    }
}

/**
 * dashboardUrlForRole() - where each role's home dashboard lives.
 */
function dashboardUrlForRole(?string $role): string
{
    switch ($role) {
        case 'student':
            return '/student/dashboard.php';
        case 'teacher':
            return '/teacher/dashboard.php';
        case 'admin':
            return '/admin/dashboard.php';
        default:
            return '/public/login.php';
    }
}

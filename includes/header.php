<?php
/**
 * Shared header/sidebar partial.
 *
 * Expects these variables to already be set by the including page:
 *   $pageTitle    (string)  - shown in <title> and the topbar
 *   $pageSubtitle (string)  - optional small text under the title
 *   $activeNav    (string)  - key of the current nav link, for highlighting
 *
 * Relies on includes/session.php already being loaded (for $_SESSION data).
 */

$role = currentRole();
$displayName = $_SESSION['display_name'] ?? $_SESSION['login_id'] ?? 'User';
$initials = strtoupper(substr($displayName, 0, 1));

// Nav items per role. Each item: [key, label, href, icon]
$navByRole = [
    'student' => [
        ['dashboard', 'Dashboard', '/student/dashboard.php', '&#128193;'],
        ['grades',    'My Grades', '/student/grades.php',    '&#128220;'],
        ['profile',   'Profile',   '/student/profile.php',   '&#128100;'],
    ],
    'teacher' => [
        ['dashboard', 'Dashboard',       '/teacher/dashboard.php', '&#128193;'],
        ['subjects',  'My Subjects',     '/teacher/subjects.php',  '&#128218;'],
        ['grades',    'Grade Management','/teacher/grades.php',    '&#9997;'],
    ],
    'admin' => [
        ['dashboard', 'Dashboard',       '/admin/dashboard.php', '&#128202;'],
        ['students',  'Students',        '/admin/students.php',  '&#127891;'],
        ['teachers',  'Teachers',        '/admin/teachers.php',  '&#128104;'],
        ['subjects',  'Subjects',        '/admin/subjects.php',  '&#128218;'],
        ['terms',     'Academic Terms',  '/admin/terms.php',     '&#128197;'],
        ['users',     'User Accounts',   '/admin/users.php',     '&#128274;'],
        ['grades',    'Grade Records',   '/admin/grades.php',    '&#128220;'],
    ],
];

$navItems = $navByRole[$role] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle) ?> - Rosemont Grade System</title>
<link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

<div class="app-shell">

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">RS</div>
            <span>Rosemont Grades</span>
        </div>

        <div class="sidebar-section-label">Menu</div>
        <?php foreach ($navItems as [$key, $label, $href, $icon]): ?>
            <a href="<?= $href ?>" class="nav-link <?= $activeNav === $key ? 'active' : '' ?>">
                <span><?= $icon ?></span> <?= clean($label) ?>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar"><?= clean($initials) ?></div>
                <div>
                    <div class="sidebar-user-name"><?= clean($displayName) ?></div>
                    <div class="sidebar-user-role"><?= clean($role) ?></div>
                </div>
            </div>
            <a href="/public/logout.php" class="btn btn-secondary btn-block btn-sm" id="logoutLink">Log Out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="d-flex gap-8" style="align-items:center;">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">&#9776;</button>
                <div>
                    <h1 class="page-title"><?= clean($pageTitle) ?></h1>
                    <?php if (!empty($pageSubtitle)): ?>
                        <div class="page-subtitle"><?= clean($pageSubtitle) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

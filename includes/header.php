<?php

$role = currentRole();
$displayName = $_SESSION['display_name'] ?? $_SESSION['login_id'] ?? 'User';
$initials = strtoupper(substr($displayName, 0, 1));

$navByRole = [
    'student' => [
        ['dashboard', 'Dashboard', '/student/dashboard.php'],
        ['grades',    'My Grades', '/student/grades.php'],
        ['profile',   'Profile',   '/student/profile.php'],
    ],
    'teacher' => [
        ['dashboard', 'Dashboard',        '/teacher/dashboard.php'],
        ['subjects',  'My Subjects',      '/teacher/subjects.php'],
        ['grades',    'Grade Management', '/teacher/grades.php'],
    ],
    'admin' => [
        ['dashboard',    'Dashboard',           '/admin/dashboard.php'],
        ['students',     'Students',            '/admin/students.php'],
        ['teachers',     'Teachers',            '/admin/teachers.php'],
        ['subjects',     'Subjects',            '/admin/subjects.php'],
        ['assignments',  'Subject Assignments', '/admin/assignments.php'],
        ['terms',        'Academic Terms',      '/admin/terms.php'],
        ['users',        'User Accounts',       '/admin/users.php'],
        ['grades',       'Grade Records',       '/admin/grades.php'],
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

        <div class="sidebar-menu-title">MENU</div>

        <?php foreach ($navItems as [$key, $label, $href]): ?>
            <a
                href="<?= $href ?>"
                class="nav-link <?= $activeNav === $key ? 'active' : '' ?>"
            >
                <?= clean($label) ?>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-footer">

            <div class="sidebar-user">
                <div class="avatar"><?= clean($initials) ?></div>

                <div>
                    <div class="sidebar-user-name">
                        <?= clean($displayName) ?>
                    </div>

                    <div class="sidebar-user-role">
                        <?= clean($role) ?>
                    </div>
                </div>
            </div>

            <a
                href="/public/logout.php"
                class="btn btn-secondary btn-block btn-sm"
                id="logoutLink"
            >
                Log Out
            </a>

        </div>

    </aside>

    <main class="main-content">

        <div class="topbar">

            <div class="d-flex gap-8" style="align-items:center;">

                <button
                    class="menu-toggle"
                    id="menuToggle"
                    aria-label="Toggle menu"
                >
                    Menu
                </button>

                <div>

                    <h1 class="page-title">
                        <?= clean($pageTitle) ?>
                    </h1>

                    <?php if (!empty($pageSubtitle)): ?>

                        <div class="page-subtitle">
                            <?= clean($pageSubtitle) ?>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>
<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$pdo = getDbConnection();

$totalStudents = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalTeachers = (int) $pdo->query('SELECT COUNT(*) FROM teachers')->fetchColumn();
$totalSubjects = (int) $pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalGrades = (int) $pdo->query('SELECT COUNT(*) FROM grades')->fetchColumn();

$activeTerm = $pdo->query("SELECT school_year, semester FROM academic_terms WHERE status = 'active' LIMIT 1")->fetch();

// A quick look at recently created users, for the dashboard.
$recentUsers = $pdo->query('
    SELECT login_id, role, is_active, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 6
')->fetchAll();

$pageTitle = 'Admin Dashboard';
$pageSubtitle = 'System overview and quick stats';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
    <div class="glass-card stat-card">
        <div class="stat-icon">&#127891;</div>
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-label">Total Students</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128104;</div>
        <div class="stat-value"><?= $totalTeachers ?></div>
        <div class="stat-label">Total Teachers</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128218;</div>
        <div class="stat-value"><?= $totalSubjects ?></div>
        <div class="stat-label">Total Subjects</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128274;</div>
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128197;</div>
        <div class="stat-value" style="font-size:16px;">
            <?= $activeTerm ? clean($activeTerm['school_year'] . ' - ' . $activeTerm['semester']) : 'None set' ?>
        </div>
        <div class="stat-label">Active Academic Term</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128220;</div>
        <div class="stat-value"><?= $totalGrades ?></div>
        <div class="stat-label">Total Grade Records</div>
    </div>
</div>

<div class="glass-card panel">
    <div class="panel-header">
        <h2 class="panel-title">Recently Created Accounts</h2>
        <a href="/admin/users.php" class="btn btn-secondary btn-sm">Manage Users</a>
    </div>

    <?php if (empty($recentUsers)): ?>
        <div class="empty-state"><p>No user accounts yet.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Login ID</th><th>Role</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?= clean($u['login_id']) ?></td>
                            <td style="text-transform:capitalize;"><?= clean($u['role']) ?></td>
                            <td><span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td class="cell-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

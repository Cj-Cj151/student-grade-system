<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('teacher');

$pdo = getDbConnection();
$teacherId = $_SESSION['teacher_id'] ?? null;

if (!$teacherId) {
    $stmt = $pdo->prepare('SELECT teacher_id FROM teachers WHERE user_id = :uid');
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $teacherId = $stmt->fetchColumn();
    $_SESSION['teacher_id'] = $teacherId;
}

$stmt = $pdo->prepare('SELECT first_name, last_name, department FROM teachers WHERE teacher_id = :tid');
$stmt->execute(['tid' => $teacherId]);
$teacher = $stmt->fetch();

// Distinct subjects this teacher currently has grade records for.
$stmt = $pdo->prepare('
    SELECT DISTINCT s.subject_id, s.subject_code, s.subject_name
    FROM grades g
    JOIN subjects s ON s.subject_id = g.subject_id
    WHERE g.teacher_id = :tid
    ORDER BY s.subject_code
');
$stmt->execute(['tid' => $teacherId]);
$subjects = $stmt->fetchAll();

// Total grade records managed by this teacher.
$stmt = $pdo->prepare('SELECT COUNT(*) FROM grades WHERE teacher_id = :tid');
$stmt->execute(['tid' => $teacherId]);
$totalRecords = (int) $stmt->fetchColumn();

// Distinct students under this teacher.
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT student_id) FROM grades WHERE teacher_id = :tid');
$stmt->execute(['tid' => $teacherId]);
$studentCount = (int) $stmt->fetchColumn();

// Pending (ungraded) records.
$stmt = $pdo->prepare('SELECT COUNT(*) FROM grades WHERE teacher_id = :tid AND grade IS NULL');
$stmt->execute(['tid' => $teacherId]);
$pendingCount = (int) $stmt->fetchColumn();

// Recent grade activity (most recently updated records with a grade).
$stmt = $pdo->prepare('
    SELECT st.first_name, st.last_name, sub.subject_code, g.grade, g.updated_at
    FROM grades g
    JOIN students st ON st.student_id = g.student_id
    JOIN subjects sub ON sub.subject_id = g.subject_id
    WHERE g.teacher_id = :tid AND g.grade IS NOT NULL
    ORDER BY g.updated_at DESC
    LIMIT 5
');
$stmt->execute(['tid' => $teacherId]);
$recentActivity = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . $teacher['first_name'] . '!';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128218;</div>
        <div class="stat-value"><?= count($subjects) ?></div>
        <div class="stat-label">Subjects Handled</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128118;</div>
        <div class="stat-value"><?= $studentCount ?></div>
        <div class="stat-label">Students Managed</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128220;</div>
        <div class="stat-value"><?= $totalRecords ?></div>
        <div class="stat-label">Grade Records</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#8987;</div>
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Pending Grades</div>
    </div>
</div>

<div class="grid-2">
    <div class="glass-card panel">
        <div class="panel-header">
            <h2 class="panel-title">My Subjects</h2>
            <a href="/teacher/subjects.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <?php if (empty($subjects)): ?>
            <div class="empty-state">
                <div class="empty-icon">&#128218;</div>
                <p>No subjects assigned yet.</p>
            </div>
        <?php else: ?>
            <ul class="profile-list">
                <?php foreach ($subjects as $s): ?>
                    <li><span class="label"><?= clean($s['subject_code']) ?></span><span><?= clean($s['subject_name']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="glass-card panel">
        <div class="panel-header">
            <h2 class="panel-title">Recent Grade Activity</h2>
            <a href="/teacher/grades.php" class="btn btn-secondary btn-sm">Manage Grades</a>
        </div>
        <?php if (empty($recentActivity)): ?>
            <div class="empty-state">
                <div class="empty-icon">&#9997;</div>
                <p>No grades encoded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Student</th><th>Subject</th><th>Grade</th><th>Updated</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $a): ?>
                            <tr>
                                <td><?= clean($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                <td><?= clean($a['subject_code']) ?></td>
                                <td><?= number_format((float) $a['grade'], 2) ?></td>
                                <td class="cell-muted"><?= date('M j, Y', strtotime($a['updated_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

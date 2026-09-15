<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('student');

$pdo = getDbConnection();
$studentId = $_SESSION['student_id'] ?? null;

// Fallback: look up student_id if it wasn't stored in session for some reason.
if (!$studentId) {
    $stmt = $pdo->prepare('SELECT student_id FROM students WHERE user_id = :uid');
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $studentId = $stmt->fetchColumn();
    $_SESSION['student_id'] = $studentId;
}

// Student profile info
$stmt = $pdo->prepare('SELECT first_name, last_name, course, year_level FROM students WHERE student_id = :sid');
$stmt->execute(['sid' => $studentId]);
$student = $stmt->fetch();

// Active academic term
$stmt = $pdo->query("SELECT term_id, school_year, semester FROM academic_terms WHERE status = 'active' LIMIT 1");
$activeTerm = $stmt->fetch();

// Number of subjects with grades (any term)
$stmt = $pdo->prepare('SELECT COUNT(*) FROM grades WHERE student_id = :sid');
$stmt->execute(['sid' => $studentId]);
$subjectCount = (int) $stmt->fetchColumn();

// Grade summary: passed / failed / pending counts
$stmt = $pdo->prepare('SELECT grade FROM grades WHERE student_id = :sid');
$stmt->execute(['sid' => $studentId]);
$allGrades = $stmt->fetchAll();

$passed = $failed = $pending = 0;
foreach ($allGrades as $g) {
    $status = gradeStatus($g['grade']);
    if ($status === 'Passed') $passed++;
    elseif ($status === 'Failed') $failed++;
    else $pending++;
}

// Recent grades (latest 5 by updated_at)
$stmt = $pdo->prepare('
    SELECT s.subject_code, s.subject_name, g.grade, g.remarks, g.updated_at,
           t.first_name AS teacher_first, t.last_name AS teacher_last
    FROM grades g
    JOIN subjects s ON s.subject_id = g.subject_id
    JOIN teachers t ON t.teacher_id = g.teacher_id
    WHERE g.student_id = :sid
    ORDER BY g.updated_at DESC
    LIMIT 5
');
$stmt->execute(['sid' => $studentId]);
$recentGrades = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . $student['first_name'] . '!';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="stat-grid">
    <div class="glass-card stat-card">
        <div class="stat-icon">&#128218;</div>
        <div class="stat-value"><?= $subjectCount ?></div>
        <div class="stat-label">Subjects With Grades</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#9989;</div>
        <div class="stat-value"><?= $passed ?></div>
        <div class="stat-label">Passed</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#10060;</div>
        <div class="stat-value"><?= $failed ?></div>
        <div class="stat-label">Failed</div>
    </div>
    <div class="glass-card stat-card">
        <div class="stat-icon">&#8987;</div>
        <div class="stat-value"><?= $pending ?></div>
        <div class="stat-label">Pending</div>
    </div>
</div>

<div class="grid-2">
    <div class="glass-card panel">
        <div class="panel-header">
            <h2 class="panel-title">My Profile Summary</h2>
        </div>
        <ul class="profile-list">
            <li><span class="label">Student Name</span><span><?= clean($student['first_name'] . ' ' . $student['last_name']) ?></span></li>
            <li><span class="label">Student ID</span><span><?= clean($_SESSION['login_id']) ?></span></li>
            <li><span class="label">Course</span><span><?= clean($student['course']) ?></span></li>
            <li><span class="label">Year Level</span><span>Year <?= (int) $student['year_level'] ?></span></li>
            <li><span class="label">Current Term</span>
                <span><?= $activeTerm ? clean($activeTerm['school_year'] . ' - ' . $activeTerm['semester']) : 'No active term set' ?></span>
            </li>
        </ul>
    </div>

    <div class="glass-card panel">
        <div class="panel-header">
            <h2 class="panel-title">Recent Grades</h2>
            <a href="/student/grades.php" class="btn btn-secondary btn-sm">View All</a>
        </div>

        <?php if (empty($recentGrades)): ?>
            <div class="empty-state">
                <div class="empty-icon">&#128220;</div>
                <p>No grades have been recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentGrades as $g): ?>
                            <tr>
                                <td>
                                    <strong><?= clean($g['subject_code']) ?></strong><br>
                                    <span class="cell-muted"><?= clean($g['subject_name']) ?></span>
                                </td>
                                <td><?= clean($g['teacher_first'] . ' ' . $g['teacher_last']) ?></td>
                                <td><?= $g['grade'] !== null ? number_format((float) $g['grade'], 2) : '&mdash;' ?></td>
                                <td><span class="badge <?= gradeStatusClass($g['grade']) ?>"><?= gradeStatus($g['grade']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

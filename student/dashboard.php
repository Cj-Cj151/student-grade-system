<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('student');

$pdo = getDbConnection();
$studentId = $_SESSION['student_id'] ?? null;

if (!$studentId) {
    $stmt = $pdo->prepare('
        SELECT student_id
        FROM students
        WHERE user_id = :uid
    ');

    $stmt->execute([
        'uid' => $_SESSION['user_id']
    ]);

    $studentId = $stmt->fetchColumn();
    $_SESSION['student_id'] = $studentId;
}

$stmt = $pdo->prepare('
    SELECT
        first_name,
        last_name,
        course,
        year_level
    FROM students
    WHERE student_id = :sid
');

$stmt->execute([
    'sid' => $studentId
]);

$student = $stmt->fetch();

$stmt = $pdo->query("
    SELECT
        term_id,
        school_year,
        semester,
        grading_period
    FROM academic_terms
    WHERE status = 'active'
    ORDER BY term_id DESC
    LIMIT 1
");

$activeTerm = $stmt->fetch();

$stmt = $pdo->prepare('
    SELECT COUNT(*)
    FROM grades
    WHERE student_id = :sid
');

$stmt->execute([
    'sid' => $studentId
]);

$subjectCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('
    SELECT grade
    FROM grades
    WHERE student_id = :sid
');

$stmt->execute([
    'sid' => $studentId
]);

$allGrades = $stmt->fetchAll();

$passed = 0;
$failed = 0;
$pending = 0;

foreach ($allGrades as $g) {

    $status = gradeStatus($g['grade']);

    if ($status === 'Passed') {
        $passed++;
    } elseif ($status === 'Failed') {
        $failed++;
    } else {
        $pending++;
    }
}

$stmt = $pdo->prepare('
    SELECT
        s.subject_code,
        s.subject_name,
        g.grade,
        g.remarks,
        g.updated_at,
        t.first_name AS teacher_first,
        t.last_name AS teacher_last,
        at.school_year,
        at.semester,
        at.grading_period
    FROM grades g
    JOIN subjects s
        ON s.subject_id = g.subject_id
    JOIN teachers t
        ON t.teacher_id = g.teacher_id
    JOIN academic_terms at
        ON at.term_id = g.term_id
    WHERE g.student_id = :sid
    ORDER BY g.updated_at DESC
    LIMIT 5
');

$stmt->execute([
    'sid' => $studentId
]);

$recentGrades = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . $student['first_name'] . '!';
$activeNav = 'dashboard';

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-welcome">

    <h1>
        Welcome back,
        <?= clean(
            $student['first_name']
            . ' '
            . $student['last_name']
        ) ?>!
    </h1>

    <?php if ($activeTerm): ?>

        <p class="dashboard-term">

            Current Academic Term:
            <strong>
                <?= clean(
                    $activeTerm['school_year']
                    . ' - '
                    . $activeTerm['semester']
                ) ?>
            </strong>

            <span class="badge badge-info">
                <?= clean($activeTerm['grading_period']) ?>
            </span>

        </p>

    <?php else: ?>

        <p class="dashboard-term">
            No active academic term has been set.
        </p>

    <?php endif; ?>

</div>

<div class="stat-grid">

    <div class="glass-card stat-card">

        <div class="stat-value">
            <?= $subjectCount ?>
        </div>

        <div class="stat-label">
            Subjects With Grades
        </div>

    </div>

    <div class="glass-card stat-card">

        <div class="stat-value">
            <?= $passed ?>
        </div>

        <div class="stat-label">
            Passed
        </div>

    </div>

    <div class="glass-card stat-card">

        <div class="stat-value">
            <?= $failed ?>
        </div>

        <div class="stat-label">
            Failed
        </div>

    </div>

    <div class="glass-card stat-card">

        <div class="stat-value">
            <?= $pending ?>
        </div>

        <div class="stat-label">
            Pending
        </div>

    </div>

</div>

<div class="grid-2">

    <div class="glass-card panel">

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    My Profile Summary
                </h2>

                <p class="panel-subtitle">
                    Quick view of your student information
                </p>

            </div>

            <a
                href="/student/profile.php"
                class="btn btn-secondary btn-sm"
            >
                View Profile
            </a>

        </div>

        <ul class="profile-list">

            <li>

                <span class="label">
                    Student Name
                </span>

                <span>
                    <?= clean(
                        $student['first_name']
                        . ' '
                        . $student['last_name']
                    ) ?>
                </span>

            </li>

            <li>

                <span class="label">
                    Student ID
                </span>

                <span>
                    <?= clean($_SESSION['login_id']) ?>
                </span>

            </li>

            <li>

                <span class="label">
                    Course
                </span>

                <span>
                    <?= clean($student['course']) ?>
                </span>

            </li>

            <li>

                <span class="label">
                    Year Level
                </span>

                <span>
                    Year <?= (int) $student['year_level'] ?>
                </span>

            </li>

            <li>

                <span class="label">
                    Current Grading Period
                </span>

                <span>

                    <?php if ($activeTerm): ?>

                        <?= clean(
                            $activeTerm['school_year']
                            . ' - '
                            . $activeTerm['semester']
                        ) ?>

                        <span class="badge badge-info">
                            <?= clean(
                                $activeTerm['grading_period']
                            ) ?>
                        </span>

                    <?php else: ?>

                        No active term set

                    <?php endif; ?>

                </span>

            </li>

        </ul>

    </div>

    <div class="glass-card panel">

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Recent Grades
                </h2>

                <p class="panel-subtitle">
                    Your latest recorded grades
                </p>

            </div>

            <a
                href="/student/grades.php"
                class="btn btn-secondary btn-sm"
            >
                View All
            </a>

        </div>

        <?php if (empty($recentGrades)): ?>

            <div class="empty-state">

                <h3>
                    No Grades Yet
                </h3>

                <p>
                    No grades have been recorded yet.
                </p>

            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table class="data-table">

                    <thead>

                        <tr>
                            <th>Subject</th>
                            <th>Academic Term</th>
                            <th>Grading Period</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($recentGrades as $g): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= clean(
                                            $g['subject_code']
                                        ) ?>
                                    </strong>

                                    <br>

                                    <span class="cell-muted">
                                        <?= clean(
                                            $g['subject_name']
                                        ) ?>
                                    </span>

                                </td>

                                <td class="cell-muted">

                                    <?= clean(
                                        $g['school_year']
                                        . ' - '
                                        . $g['semester']
                                    ) ?>

                                </td>

                                <td>

                                    <span class="badge badge-info">
                                        <?= clean(
                                            $g['grading_period']
                                        ) ?>
                                    </span>

                                </td>

                                <td>

                                    <?= $g['grade'] !== null
                                        ? number_format(
                                            (float) $g['grade'],
                                            2
                                        )
                                        : '&mdash;' ?>

                                </td>

                                <td>

                                    <span
                                        class="badge <?= gradeStatusClass(
                                            $g['grade']
                                        ) ?>"
                                    >
                                        <?= gradeStatus(
                                            $g['grade']
                                        ) ?>
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
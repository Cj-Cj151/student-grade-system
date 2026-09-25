<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('teacher');

$pdo = getDbConnection();
$teacherId = $_SESSION['teacher_id'];

$stmt = $pdo->prepare('
    SELECT
        s.subject_id,
        s.subject_code,
        s.subject_name,
        s.units,
        COUNT(DISTINCT g.student_id) AS student_count,
        COUNT(g.grade_id) FILTER (WHERE g.grade IS NULL) AS pending_count
    FROM subjects s
    JOIN grades g
        ON g.subject_id = s.subject_id
    WHERE g.teacher_id = :tid
    GROUP BY
        s.subject_id,
        s.subject_code,
        s.subject_name,
        s.units
    ORDER BY s.subject_code
');

$stmt->execute([
    'tid' => $teacherId
]);

$subjects = $stmt->fetchAll();

$pageTitle = 'My Subjects';
$pageSubtitle = 'Subjects you are currently handling';
$activeNav = 'subjects';

include __DIR__ . '/../includes/header.php';
?>

<div class="glass-card panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">
                Assigned Subjects
            </h2>

            <p class="panel-subtitle">
                View your subjects and manage their grade records
            </p>
        </div>

    </div>

    <?php if (empty($subjects)): ?>

        <div class="empty-state">

            <h3>No Subjects Yet</h3>

            <p>
                You are not handling any subjects yet.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                        <th>Students</th>
                        <th>Pending Grades</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($subjects as $s): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= clean($s['subject_code']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= clean($s['subject_name']) ?>
                            </td>

                            <td>
                                <?= rtrim(
                                    rtrim(
                                        number_format(
                                            (float) $s['units'],
                                            1
                                        ),
                                        '0'
                                    ),
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $s['student_count'] ?>
                            </td>

                            <td>

                                <?php if ((int) $s['pending_count'] > 0): ?>

                                    <span class="badge badge-pending">
                                        <?= (int) $s['pending_count'] ?>
                                        pending
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-passed">
                                        All encoded
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="row-actions">

                                <a
                                    href="/teacher/grades.php?subject_id=<?= (int) $s['subject_id'] ?>"
                                    class="btn btn-primary btn-sm"
                                >
                                    Manage Grades
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
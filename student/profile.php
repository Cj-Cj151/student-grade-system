<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('student');

$pdo = getDbConnection();
$studentId = $_SESSION['student_id'];

$stmt = $pdo->prepare('
    SELECT
        s.first_name,
        s.last_name,
        s.email,
        s.course,
        s.year_level,
        u.login_id,
        u.created_at,
        u.is_active
    FROM students s
    JOIN users u
        ON u.user_id = s.user_id
    WHERE s.student_id = :sid
');

$stmt->execute([
    'sid' => $studentId
]);

$student = $stmt->fetch();

$pageTitle = 'My Profile';
$pageSubtitle = 'Your personal and academic information';
$activeNav = 'profile';

include __DIR__ . '/../includes/header.php';
?>

<div class="grid-2">

    <div class="glass-card panel">

        <div class="panel-header">

            <div>
                <h2 class="panel-title">
                    Personal Information
                </h2>

                <p class="panel-subtitle">
                    Your registered personal details
                </p>
            </div>

        </div>

        <ul class="profile-list">

            <li>

                <span class="label">
                    Full Name
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
                    Email
                </span>

                <span>
                    <?= clean($student['email']) ?>
                </span>

            </li>

            <li>

                <span class="label">
                    Student ID
                </span>

                <span>
                    <?= clean($student['login_id']) ?>
                </span>

            </li>

        </ul>

    </div>

    <div class="glass-card panel">

        <div class="panel-header">

            <div>
                <h2 class="panel-title">
                    Academic Information
                </h2>

                <p class="panel-subtitle">
                    Your current academic details
                </p>
            </div>

        </div>

        <ul class="profile-list">

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
                    Account Status
                </span>

                <span>

                    <span
                        class="badge <?= $student['is_active']
                            ? 'badge-active'
                            : 'badge-inactive' ?>"
                    >
                        <?= $student['is_active']
                            ? 'Active'
                            : 'Inactive' ?>
                    </span>

                </span>

            </li>

            <li>

                <span class="label">
                    Account Created
                </span>

                <span>
                    <?= date(
                        'F j, Y',
                        strtotime($student['created_at'])
                    ) ?>
                </span>

            </li>

        </ul>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
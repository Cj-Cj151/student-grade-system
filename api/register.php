<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

$data = json_decode(
    file_get_contents('php://input'),
    true
);

$accountType = trim(
    (string) ($data['account_type'] ?? '')
);

$firstName = trim(
    (string) ($data['first_name'] ?? '')
);

$lastName = trim(
    (string) ($data['last_name'] ?? '')
);

$email = trim(
    (string) ($data['email'] ?? '')
);

$studentId = trim(
    (string) ($data['student_id'] ?? '')
);

$course = trim(
    (string) ($data['course'] ?? '')
);

$yearLevel = isset($data['year_level'])
    ? (int) $data['year_level']
    : 0;

$sectionId = isset($data['section_id'])
    ? (int) $data['section_id']
    : 0;

$teacherId = trim(
    (string) ($data['teacher_id'] ?? '')
);

$department = trim(
    (string) ($data['department'] ?? '')
);

$password = (string) (
    $data['password'] ?? ''
);

$confirmPassword = (string) (
    $data['confirm_password'] ?? ''
);

if (
    !in_array(
        $accountType,
        ['student', 'teacher'],
        true
    )
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Please select a valid account type.'
    ]);

    exit;
}

if (
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $password === '' ||
    $confirmPassword === ''
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Please complete all required fields.'
    ]);

    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);

    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters.'
    ]);

    exit;
}

if ($password !== $confirmPassword) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match.'
    ]);

    exit;
}

if ($accountType === 'student') {

    if (
        $studentId === '' ||
        $course === '' ||
        $yearLevel === 0 ||
        $sectionId === 0
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Please complete all student information.'
        ]);

        exit;
    }

    if ($yearLevel < 1 || $yearLevel > 4) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid year level.'
        ]);

        exit;
    }

    $loginId = $studentId;

} else {

    if (
        $teacherId === '' ||
        $department === ''
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Please complete all teacher information.'
        ]);

        exit;
    }

    $loginId = $teacherId;
}

$pdo = null;

try {

    $pdo = getDbConnection();

    $pdo->beginTransaction();

    if ($accountType === 'student') {

        $checkSection = $pdo->prepare('
            SELECT section_id
            FROM sections
            WHERE section_id = :section_id
              AND course = :course
              AND year_level = :year_level
              AND is_active = TRUE
            LIMIT 1
        ');

        $checkSection->execute([
            'section_id' => $sectionId,
            'course' => $course,
            'year_level' => $yearLevel
        ]);

        if (!$checkSection->fetch()) {

            $pdo->rollBack();

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Selected section does not match the course and year level.'
            ]);

            exit;
        }
    }

    $checkUser = $pdo->prepare('
        SELECT user_id
        FROM users
        WHERE login_id = :login_id
        LIMIT 1
    ');

    $checkUser->execute([
        'login_id' => $loginId
    ]);

    if ($checkUser->fetch()) {

        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => $accountType === 'student'
                ? 'Student ID is already registered.'
                : 'Teacher ID is already registered.'
        ]);

        exit;
    }

    $checkStudentEmail = $pdo->prepare('
        SELECT student_id
        FROM students
        WHERE email = :email
        LIMIT 1
    ');

    $checkStudentEmail->execute([
        'email' => $email
    ]);

    if ($checkStudentEmail->fetch()) {

        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Email address is already registered.'
        ]);

        exit;
    }

    $checkTeacherEmail = $pdo->prepare('
        SELECT teacher_id
        FROM teachers
        WHERE email = :email
        LIMIT 1
    ');

    $checkTeacherEmail->execute([
        'email' => $email
    ]);

    if ($checkTeacherEmail->fetch()) {

        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Email address is already registered.'
        ]);

        exit;
    }

    $passwordHash = password_hash(
        $password,
        PASSWORD_BCRYPT
    );

    $userInsert = $pdo->prepare('
        INSERT INTO users (
            login_id,
            password_hash,
            role,
            is_active
        )
        VALUES (
            :login_id,
            :password_hash,
            :role,
            TRUE
        )
        RETURNING user_id
    ');

    $userInsert->execute([
        'login_id' => $loginId,
        'password_hash' => $passwordHash,
        'role' => $accountType
    ]);

    $user = $userInsert->fetch();

    if (!$user) {
        throw new Exception(
            'Unable to create user account.'
        );
    }

    $userId = (int) $user['user_id'];

    if ($accountType === 'student') {

        $studentInsert = $pdo->prepare('
            INSERT INTO students (
                user_id,
                first_name,
                last_name,
                email,
                course,
                year_level,
                section_id
            )
            VALUES (
                :user_id,
                :first_name,
                :last_name,
                :email,
                :course,
                :year_level,
                :section_id
            )
        ');

        $studentInsert->execute([
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'course' => $course,
            'year_level' => $yearLevel,
            'section_id' => $sectionId
        ]);

    } else {

        $teacherInsert = $pdo->prepare('
            INSERT INTO teachers (
                user_id,
                first_name,
                last_name,
                email,
                department
            )
            VALUES (
                :user_id,
                :first_name,
                :last_name,
                :email,
                :department
            )
        ');

        $teacherInsert->execute([
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'department' => $department
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully.'
    ]);

} catch (PDOException $e) {

    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Registration database error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to create account. Please try again later.'
    ]);

} catch (Exception $e) {

    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Registration error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to create account. Please try again later.'
    ]);
}
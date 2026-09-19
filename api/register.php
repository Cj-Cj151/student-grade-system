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

$data = json_decode(file_get_contents('php://input'), true);

$studentId = trim($data['student_id'] ?? '');
$firstName = trim($data['first_name'] ?? '');
$lastName = trim($data['last_name'] ?? '');
$email = trim($data['email'] ?? '');
$course = trim($data['course'] ?? '');
$yearLevel = (int) ($data['year_level'] ?? 0);
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

/*
|--------------------------------------------------------------------------
| Validate required fields
|--------------------------------------------------------------------------
*/

if (
    $studentId === '' ||
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $course === '' ||
    $yearLevel === 0 ||
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

/*
|--------------------------------------------------------------------------
| Validate email
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate year level
|--------------------------------------------------------------------------
*/

if ($yearLevel < 1 || $yearLevel > 6) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid year level.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Validate password
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Create account
|--------------------------------------------------------------------------
*/

try {
    $pdo = getDbConnection();

    $pdo->beginTransaction();

    /*
     * Check duplicate Student ID
     */
    $checkUser = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE login_id = :login_id
        LIMIT 1
    ");

    $checkUser->execute([
        'login_id' => $studentId
    ]);

    if ($checkUser->fetch()) {
        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Student ID is already registered.'
        ]);

        exit;
    }

    /*
     * Check duplicate email
     */
    $checkEmail = $pdo->prepare("
        SELECT student_id
        FROM students
        WHERE email = :email
        LIMIT 1
    ");

    $checkEmail->execute([
        'email' => $email
    ]);

    if ($checkEmail->fetch()) {
        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Email address is already registered.'
        ]);

        exit;
    }

    /*
     * Hash password securely
     */
    $passwordHash = password_hash(
        $password,
        PASSWORD_BCRYPT
    );

    /*
     * Create user account
     *
     * IMPORTANT:
     * Public registration can only create
     * student accounts.
     */
    $userInsert = $pdo->prepare("
        INSERT INTO users (
            login_id,
            password_hash,
            role,
            is_active
        )
        VALUES (
            :login_id,
            :password_hash,
            'student',
            TRUE
        )
        RETURNING user_id
    ");

    $userInsert->execute([
        'login_id' => $studentId,
        'password_hash' => $passwordHash
    ]);

    $user = $userInsert->fetch();

    if (!$user) {
        throw new Exception('Unable to create user account.');
    }

    $userId = $user['user_id'];

    /*
     * Create student profile
     */
    $studentInsert = $pdo->prepare("
        INSERT INTO students (
            user_id,
            first_name,
            last_name,
            email,
            course,
            year_level
        )
        VALUES (
            :user_id,
            :first_name,
            :last_name,
            :email,
            :course,
            :year_level
        )
    ");

    $studentInsert->execute([
        'user_id' => $userId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'course' => $course,
        'year_level' => $yearLevel
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully.'
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Registration database error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to create account. Please try again later.'
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Registration error: ' . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to create account. Please try again later.'
    ]);
}
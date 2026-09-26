<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$course = trim($_GET['course'] ?? '');
$yearLevel = isset($_GET['year_level']) ? (int) $_GET['year_level'] : 0;

if ($course === '' || $yearLevel < 1 || $yearLevel > 4) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Course and year level are required.'
    ]);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('
        SELECT
            section_id,
            section_name
        FROM sections
        WHERE course = :course
          AND year_level = :year_level
          AND is_active = TRUE
        ORDER BY section_name
    ');

    $stmt->execute([
        'course' => $course,
        'year_level' => $yearLevel
    ]);

    echo json_encode([
        'success' => true,
        'sections' => $stmt->fetchAll()
    ]);

} catch (PDOException $e) {
    error_log('Section API failed: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load sections.'
    ]);
}
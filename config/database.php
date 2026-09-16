<?php
/**
 * Database configuration and connection.
 *
 * Local database credentials are stored in database.local.php
 * and should never be committed to version control.
 */

$localConfig = __DIR__ . '/database.local.php';

if (file_exists($localConfig)) {
    $dbConfig = require $localConfig;
} else {
    $dbConfig = [
        'host' => 'localhost',
        'port' => '5432',
        'name' => 'grade_system',
        'user' => 'postgres',
        'password' => ''
    ];
}

define('DB_HOST', $dbConfig['host']);
define('DB_PORT', $dbConfig['port']);
define('DB_NAME', $dbConfig['name']);
define('DB_USER', $dbConfig['user']);
define('DB_PASSWORD', $dbConfig['password']);

/**
 * getDbConnection()
 * Returns a shared PDO connection to PostgreSQL.
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('A system error occurred. Please try again later or contact the administrator.');
    }
}
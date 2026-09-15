<?php
/**
 * Database configuration and connection.
 *
 * CHANGE THESE VALUES to match your local PostgreSQL setup.
 * Never commit real production credentials to version control.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'grade_system');
define('DB_USER', 'postgres');
define('DB_PASSWORD', '200515'); // <-- change this

/**
 * getDbConnection()
 * Returns a shared PDO connection to PostgreSQL.
 * Any database error is logged on the server and never shown to the user.
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
        // Never leak raw database errors to the browser.
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('A system error occurred. Please try again later or contact the administrator.');
    }
}

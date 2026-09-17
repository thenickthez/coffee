<?php
// Copy this file to database.php and enter the database credentials.
// database.php is excluded from Git.

define('DB_HOST', 'localhost');
define('DB_NAME', 'CHANGE_DATABASE_NAME');
define('DB_USER', 'CHANGE_DATABASE_USER');
define('DB_PASS', 'CHANGE_DATABASE_PASSWORD');

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    return $pdo;
}

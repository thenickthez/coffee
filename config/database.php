<?php
// Copy this file to database.php and enter the database credentials.
// database.php is excluded from Git.

define('DB_HOST', 'localhost');
define('DB_NAME', 'ngdev_coffee2');
define('DB_USER', 'ngdev_coffee_usr2');
define('DB_PASS', '.;RH_ytm&3uVkJWY');

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

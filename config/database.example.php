<?php
declare(strict_types=1);

// Copy this file to database.php and enter your local MySQL connection details.
require_once __DIR__ . '/../includes/helpers.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = '127.0.0.1';
    $database = 'digital_notice_board';
    $username = 'your_mysql_username';
    $password = 'your_mysql_password';
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+05:30'");
        return $pdo;
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        throw new RuntimeException('The notice board is temporarily unavailable.');
    }
}
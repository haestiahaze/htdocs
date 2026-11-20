<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('glassalu_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // set to true in HTTPS production
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    throw new RuntimeException(
        'Missing config.php. Copy config.sample.php to config.php and set your DB credentials.'
    );
}

$config = require $configPath;

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $config['db_host'],
    $config['db_name'],
    $config['db_charset']
);

try {
    $pdo = new PDO(
        $dsn,
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    throw $e;
}
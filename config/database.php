<?php
declare(strict_types=1);

/**
 * Подключение к базе данных (Docker версия)
 */

$host     = getenv('DB_HOST')     ?: 'db';           // важно: 'db' — имя сервиса в docker-compose
$dbname   = getenv('DB_DATABASE') ?: 'itfreelance';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

return $pdo;

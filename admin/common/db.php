<?php

declare(strict_types=1);

$DB_DSN  = 'mysql:host=localhost;dbname=prospine;charset=utf8mb4';
$DB_USER = 'root';
$DB_PASS = '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database connection error: ' . $e->getMessage());
}

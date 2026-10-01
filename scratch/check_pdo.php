<?php
$envFile = __DIR__ . '/../.env';
$env = parse_ini_file($envFile);

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db   = $env['DB_DATABASE'] ?? 'zecotacao';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully to MySQL DB $db\n";

    $stmt = $pdo->query("SHOW COLUMNS FROM chat_mensagens LIKE 'mensagem'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($col);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

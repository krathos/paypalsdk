<?php

require 'vendor/autoload.php';

$host = 'localhost'; // Cambia esto según sea necesario
$db = 'paypal_integration';
$user = 'root'; // Cambia esto según sea necesario
$pass = ''; // Cambia esto según sea necesario

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderID = $input['orderID'];
    $amount = $input['amount'];

    try {
        $stmt = $pdo->prepare('INSERT INTO orders (order_id, amount, status) VALUES (?, ?, ?)');
        $stmt->execute([$orderID, $amount, 'pending']);
        $result = json_encode(['status' => 'success']);
    } catch (\PDOException $e) {
        $result = json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    echo $result;
}
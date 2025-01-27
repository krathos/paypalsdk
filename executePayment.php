<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$host = 'localhost'; // Cambia esto según sea necesario
$db = 'paypal_integration';
$user = 'root'; // Cambia esto según sea necesario
$pass = ''; // Cambia esto según sea necesario

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$client = new Client();

$clientID = '****';
$secret = '***';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderID = $input['orderID'];

    try {
        // Obtener el estado de la orden
        $response = $client->request('GET', 'https://api.sandbox.paypal.com/v2/checkout/orders/' . $orderID, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($clientID . ':' . $secret),
            ]
        ]);

        $order = json_decode($response->getBody()->getContents(), true);

        if ($order['status'] === 'COMPLETED') {
            echo json_encode(['status' => 'error', 'message' => 'Order already captured']);
            exit;
        }

        // Capturar la orden
        try {
            $response = $client->request('POST', 'https://api.sandbox.paypal.com/v2/checkout/orders/' . $orderID, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($clientID . ':' . $secret),
                ]
            ]);

            if ($response->getStatusCode() == 201) {
                $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
                $stmt->execute(['completed', $orderID]);

                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to capture order']);
            }
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                echo json_encode(['status' => 'error', 'message' => 'Request Exception: ' . $responseBody]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Request Exception: ' . $e->getMessage()]);
            }
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            echo json_encode(['status' => 'error', 'message' => 'Request Exception: ' . $responseBody]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Request Exception: ' . $e->getMessage()]);
        }
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
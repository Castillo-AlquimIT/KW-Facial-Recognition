<?php
header('Content-Type: application/json');
require_once 'db.php'; // provides $con

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['image'])) {
    echo json_encode(["success" => false, "message" => "Missing image"]);
    exit;
}

$config = require __DIR__ . '/config.php';
$apiUrl = rtrim($config['face_api_url'], '/') . '/recognize';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(["success" => false, "message" => "Could not reach face API: $curlError"]);
    exit;
}

$result = json_decode($response, true);

if (!empty($result['success'])) {
    $stmt = $con->prepare("INSERT INTO attendance (name, timestamp) VALUES (?, NOW())");
    $stmt->bind_param("s", $result['name']);
    $stmt->execute();
    $stmt->close();
    $con->close();
}

echo $response;

<?php
header('Content-Type: application/json');
require_once 'db.php'; // provides $con

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['image']) || empty($input['name'])) {
    echo json_encode(["success" => false, "message" => "Missing image or name"]);
    exit;
}

$name = trim($input['name']);

$config = require __DIR__ . '/config.php';
$apiUrl = rtrim($config['face_api_url'], '/') . '/register';

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
    // Only insert into users if this name isn't already registered
    $stmt = $con->prepare("SELECT id FROM users WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $insert = $con->prepare("INSERT INTO users (name) VALUES (?)");
        $insert->bind_param("s", $name);
        $insert->execute();
        $insert->close();
    } else {
        $stmt->close();
    }

    $con->close();
}

echo $response;

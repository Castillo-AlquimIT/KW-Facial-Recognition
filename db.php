<?php
// Loads config.php (create it by copying config.php.example) and opens
// a shared mysqli connection as $con for register.php/recognize.php/dashboard.php.

$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    die(json_encode([
        "success" => false,
        "message" => "Missing config.php — copy config.php.example to config.php and fill in your database details."
    ]));
}

$config = require $configPath;

$con = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_password'],
    $config['db_name'],
    $config['db_port']
);

if ($con->connect_error) {
    http_response_code(500);
    die(json_encode([
        "success" => false,
        "message" => "Could not connect to the database: " . $con->connect_error
    ]));
}

<?php
// Database connection file
require_once __DIR__ . '/config.php';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error)
    die('DB Error');
// Create user table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(32) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    type ENUM('user','admin') DEFAULT 'user'
)");

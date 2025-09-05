<?php
// View image by hexcode
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'bdimghost';
$upload_dir = __DIR__ . '/uploads';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error)
    die('DB Error');
$hex = $_GET['img'] ?? '';
if (!preg_match('/^[a-f0-9]{8}$/', $hex)) die('Invalid image code');
$stmt = $conn->prepare('SELECT stored_name, adult FROM images WHERE hexcode = ?');
$stmt->bind_param('s', $hex);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) die('Image not found');
$stmt->bind_result($stored_name, $adult);
$stmt->fetch();
$stmt->close();
if ($adult) die('Adult image detected and deleted.');
$file = "$upload_dir/$stored_name";
if (!file_exists($file)) die('File missing');
$ext = pathinfo($file, PATHINFO_EXTENSION);
$type = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : ($ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'application/octet-stream'));
header('Content-Type: ' . $type);
readfile($file);

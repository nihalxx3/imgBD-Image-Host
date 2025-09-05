<?php
session_start();
require_once __DIR__ . '/connection.php';
$msg = '';
if (isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare('SELECT id, password, type FROM user WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hash, $type);
        $stmt->fetch();
        if (password_verify($password, $hash) && $type === 'admin') {
            $_SESSION['admin'] = $id;
            header('Location: /');
            exit;
        } else {
            $msg = 'Invalid credentials or not admin.';
        }
    } else {
        $msg = 'Invalid credentials.';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4 text-center">Admin Login</h2>
        <form method="POST" class="card p-4 mx-auto" style="max-width:400px;">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" required placeholder="Username">
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <?php if ($msg): ?>
            <div class="alert alert-danger mt-3 text-center"> <?= htmlspecialchars($msg) ?> </div>
        <?php endif; ?>
    </div>
</body>
</html>

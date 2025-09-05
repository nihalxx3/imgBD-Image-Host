<?php
require_once __DIR__ . '/connection.php';
$msg = '';
if (isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
        $msg = 'Invalid username.';
    } elseif (strlen($password) < 6) {
        $msg = 'Password too short.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $type = 'user';
        $stmt = $conn->prepare('INSERT INTO user (username, password, type) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $username, $hash, $type);
        try {
            $stmt->execute();
            $msg = 'Signup successful!';
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $msg = 'Username already exists.';
            } else {
                $msg = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4 text-center">Sign Up</h2>
        <form method="POST" class="card p-4 mx-auto" style="max-width:400px;">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" required placeholder="Username">
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign Up</button>
        </form>
        <?php if ($msg): ?>
            <div class="alert alert-info mt-3 text-center"> <?= htmlspecialchars($msg) ?> </div>
        <?php endif; ?>
    </div>
</body>
</html>

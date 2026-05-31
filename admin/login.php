<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND role='admin'");
    $user   = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | Admin Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <h1><a href="../index.php" style="color:inherit;text-decoration:none;">MyMarket<span>-ZA</span></a> &mdash; Admin</h1>
    </div>
</header>

<div class="container">
    <div class="form-wrapper" style="margin-top:30px;">
        <h2 class="page-title">Admin Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Admin Email</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn btn-green btn-full">Log In</button>
        </form>

        <p class="form-footer"><a href="../index.php">&larr; Back to site</a></p>
    </div>
</div>

<footer>&copy; <?= date('Y') ?> <?= SITE_NAME ?></footer>
<script src="../js/script.js"></script>
</body>
</html>
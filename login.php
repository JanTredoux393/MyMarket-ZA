<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user   = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // prevents session hijacking
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        // Redirect admins to their dashboard
        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Incorrect email or password. Please try again.";
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <h2 class="page-title">Log In</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn btn-green btn-full">Log In</button>
        </form>

        <p class="form-footer">No account yet? <a href="register.php">Register for free</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
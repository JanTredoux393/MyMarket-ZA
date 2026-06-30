<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = in_array($_POST['role'], ['buyer', 'seller']) ? $_POST['role'] : 'buyer';

    if (!$username || !$email || !$password) {
        $error = "Please fill in all fields.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' OR username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "That username or email is already in use.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "
                INSERT INTO users (username, email, password, role)
                VALUES ('$username', '$email', '$hashed', '$role')
            ");
            $success = "Account created! You can now log in.";
        }
    }
}

$page_title = 'Register';
include 'includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <h2 class="page-title">Create an Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-auto-hide"><?= $success ?> <a href="login.php">Log in here</a>.</div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="register-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required maxlength="50"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="role">I want to:</label>
            <select id="role" name="role">
                <option value="buyer">Buy items only</option>
                <option value="seller">Buy and sell items</option>
            </select>

            <button type="submit" class="btn btn-green btn-full"
                    onclick="return validateForm('register-form')">Create Account</button>
        </form>

        <p class="form-footer">Already have an account? <a href="login.php">Log in here</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
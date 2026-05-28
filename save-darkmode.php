<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn() && isset($_POST['dark_mode'])) {
    $dark = (int)$_POST['dark_mode'];
    $uid  = currentUserId();
    mysqli_query($conn, "UPDATE users SET dark_mode=$dark WHERE id=$uid");
}
?>
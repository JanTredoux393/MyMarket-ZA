<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: browse.php");
    exit();
}

$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));

if (!$product) {
    header("Location: browse.php");
    exit();
}

if (currentUserId() !== $product['user_id'] && !isAdmin()) {
    header("Location: browse.php");
    exit();
}

mysqli_query($conn, "DELETE FROM products WHERE id=$id");

if (isAdmin() && strpos($_SERVER['HTTP_REFERER'] ?? '', 'admin') !== false) {
    header("Location: admin/products.php?deleted=1");
} else {
    header("Location: my-listing.php?deleted=1");
}
exit();
?>
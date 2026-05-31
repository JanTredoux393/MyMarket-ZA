<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) { header("Location: browse.php"); exit(); }

$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));

// Only the seller can mark their own item as sold
if (!$product || $product['user_id'] !== currentUserId()) {
    header("Location: browse.php");
    exit();
}

// Set stock to 0 and mark as sold
mysqli_query($conn, "UPDATE products SET is_sold=1, stock=0 WHERE id=$id");

// Redirect back to wherever they came from
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'my-listing.php';
header("Location: " . $redirect);
exit();
?>
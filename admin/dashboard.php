<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin();

// Dashboard counts
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users"))['n'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM products"))['n'];
$total_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM messages"))['n'];
$total_sellers  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users WHERE role='seller'"))['n'];

// Recent listings
$recent = mysqli_query($conn, "
    SELECT p.id, p.title, p.price, p.created_at, u.username
    FROM products p JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC LIMIT 5
");

$page_title = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<header>
    <h1><a href="../index.php" style="color:inherit;text-decoration:none;">MyMarket<span>-ZA</span></a> &mdash; Admin</h1>
    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php">Listings</a>
        <a href="../browse.php">View Site</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h2 class="page-title">Dashboard</h2>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $total_users ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $total_sellers ?></div>
            <div class="stat-label">Sellers</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $total_products ?></div>
            <div class="stat-label">Listings</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $total_messages ?></div>
            <div class="stat-label">Messages Sent</div>
        </div>
    </div>

    <!-- Recent listings -->
    <h3 style="margin-bottom:12px;color:#1a7a4a;">Recent Listings</h3>
    <table>
        <tr><th>Title</th><th>Seller</th><th>Price</th><th>Date</th><th>Action</th></tr>
        <?php while ($r = mysqli_fetch_assoc($recent)): ?>
        <tr>
            <td><a href="../product-details.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
            <td><?= htmlspecialchars($r['username']) ?></td>
            <td>R <?= number_format($r['price'], 2) ?></td>
            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td>
                <a href="../delete-listing.php?id=<?= $r['id'] ?>"
                   class="btn btn-red" style="padding:4px 8px;font-size:12px;"
                   onclick="return confirmDelete('Delete this listing?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<footer>
    &copy; <?= date('Y') ?> <?= SITE_NAME ?> | Admin Panel
</footer>
<script src="../script.js"></script>
</body>
</html>
<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin();

$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users"))['n'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM products"))['n'];
$total_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM messages"))['n'];
$total_sellers  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users WHERE role='seller'"))['n'];

$recent = mysqli_query($conn, "
    SELECT p.id, p.title, p.price, p.created_at, u.username
    FROM products p JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<style>
    .admin-topbar { background:linear-gradient(135deg,#052e16 0%,#14532d 50%,#166534 100%);color:white;padding:0 24px;box-shadow:0 4px 20px rgba(0,0,0,0.2);position:sticky;top:0;z-index:100; }
    .admin-topbar-inner { max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:68px;gap:16px; }
    .admin-topbar-brand { font-size:20px;font-weight:800;letter-spacing:-0.5px;white-space:nowrap;color:white;text-decoration:none; }
    .admin-topbar-brand span { color:var(--gold); }
    .admin-topbar nav { display:flex;align-items:center;gap:2px; }
    .admin-topbar nav a { color:rgba(255,255,255,0.8);font-size:13px;font-weight:500;padding:7px 14px;border-radius:999px;text-decoration:none;white-space:nowrap;transition:background 0.15s,color 0.15s; }
    .admin-topbar nav a:hover { background:rgba(255,255,255,0.15);color:white; }
    .admin-topbar nav a.active { background:var(--gold);color:#14532d;font-weight:700; }
    .admin-topbar nav a.nav-viewsite { border:1px solid rgba(255,255,255,0.35); }
</style>

<div class="admin-topbar">
    <div class="admin-topbar-inner">
        <a class="admin-topbar-brand" href="../index.php">MyMarket<span>-ZA</span> &mdash; Admin</a>
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="products.php">Listings</a>
            <a href="../browse.php" class="nav-viewsite" target="_blank">View Site ↗</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>
</div>

<div class="container">
    <h2 class="page-title">Dashboard</h2>

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

    <h3 style="margin-bottom:12px;color:var(--green);">Recent Listings</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Seller</th>
            <th>Price</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
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

<footer>&copy; <?= date('Y') ?> <?= SITE_NAME ?> | Admin Panel</footer>
<script src="../js/script.js"></script>
</body>
</html>
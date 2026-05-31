<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin();

$success = '';

if (isset($_GET['deleted'])) {
    $success = "Listing deleted successfully.";
}

// Search/filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where  = $search ? "WHERE p.title LIKE '%$search%' OR u.username LIKE '%$search%'" : "";

$products = mysqli_query($conn, "
    SELECT p.*, u.username, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    $where
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | Manage Listings</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <h1><a href="../index.php" style="color:inherit;text-decoration:none;">MyMarket<span>-ZA</span></a> &mdash; Admin</h1>
    </div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php" class="active">Listings</a>
        <a href="../browse.php">View Site</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h2 class="page-title">Manage Listings</h2>

    <?php if ($success): ?>
        <div class="alert alert-success alert-auto-hide"><?= $success ?></div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" action="products.php" style="margin-bottom:16px;">
        <div class="search-bar">
            <input type="text" name="search" placeholder="Search by title or seller..."
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-green">Search</button>
            <?php if ($search): ?>
                <a href="products.php" class="btn btn-gray">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <table>
        <tr><th>ID</th><th>Title</th><th>Seller</th><th>Category</th><th>Price</th><th>Date</th><th>Actions</th></tr>
        <?php if (mysqli_num_rows($products) === 0): ?>
            <tr><td colspan="7" style="text-align:center;color:#888;">No listings found.</td></tr>
        <?php endif; ?>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><a href="../product-details.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a></td>
            <td><?= htmlspecialchars($p['username']) ?></td>
            <td><?= htmlspecialchars($p['category_name'] ?? 'None') ?></td>
            <td>R <?= number_format($p['price'], 2) ?></td>
            <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            <td class="flex-row" style="gap:4px;">
                <a href="../edit-listing.php?id=<?= $p['id'] ?>"
                   class="btn btn-yellow" style="padding:4px 8px;font-size:12px;">Edit</a>
                <a href="../delete-listing.php?id=<?= $p['id'] ?>"
                   class="btn btn-red" style="padding:4px 8px;font-size:12px;"
                   onclick="return confirmDelete('Delete this listing?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<footer>&copy; <?= date('Y') ?> <?= SITE_NAME ?> | Admin Panel</footer>
<script src="../script.js"></script>
</body>
</html>
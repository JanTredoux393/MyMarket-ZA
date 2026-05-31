<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$search     = isset($_GET['search'])   ? mysqli_real_escape_string($conn, trim($_GET['search']))  : '';
$cat_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$where = "WHERE p.is_sold = 0";
if ($search !== '')  $where .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%' OR p.location LIKE '%$search%')";
if ($cat_filter > 0) $where .= " AND p.category_id = $cat_filter";

$products   = mysqli_query($conn, "
    SELECT p.*, u.username, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    $where
    ORDER BY p.created_at DESC
");
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

$page_title = 'Browse Listings';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Browse Listings</h2>

    <!-- Search and filter -->
    <form method="GET" action="browse.php">
        <div class="search-bar">
            <input type="text" name="search" placeholder="Search products or location..."
                   value="<?= htmlspecialchars($search) ?>">
            <select name="category">
                <option value="0">All Categories</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-green">Search</button>
            <?php if ($search || $cat_filter): ?>
                <a href="browse.php" class="btn btn-gray">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Results count -->
    <?php $count = mysqli_num_rows($products); ?>
    <p style="font-size:14px;color:#888;margin-bottom:12px;">
        <?= $count ?> listing<?= $count !== 1 ? 's' : '' ?> found
        <?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
    </p>

    <!-- Product grid -->
    <?php if ($count === 0): ?>
        <div class="no-results">
            <p>No listings found.
            <?php if (!$search && !$cat_filter): ?>
                <a href="register.php">Register</a> to be the first to post!
            <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php while ($p = mysqli_fetch_assoc($products)): ?>
            <div class="product-card">
                <div>

                <?php if (!empty($p['image'])): ?>
    <img src="<?= htmlspecialchars($p['image']) ?>"
         alt="<?= htmlspecialchars($p['title']) ?>"
         style="width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:10px;">
<?php endif; ?>

                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <div class="price">R <?= number_format($p['price'], 2) ?></div>
                    <?php if ($p['category_name']): ?>
                        <div class="category"><?= htmlspecialchars($p['category_name']) ?></div>
                    <?php endif; ?>
                    <?php if ($p['location']): ?>
                        <div class="location">📍 <?= htmlspecialchars($p['location']) ?></div>
                    <?php endif; ?>
                    <div class="location" style="margin-top:4px;">By: <?= htmlspecialchars($p['username']) ?></div>
                </div>
                <div class="card-footer">
                    <a href="product-details.php?id=<?= $p['id'] ?>" class="btn btn-green">View Item</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
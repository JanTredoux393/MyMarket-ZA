<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$search     = isset($_GET['search'])   ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$cat_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$area       = isset($_GET['area'])     ? mysqli_real_escape_string($conn, trim($_GET['area'])) : '';
$sort       = isset($_GET['sort'])     ? $_GET['sort'] : 'newest';
$page       = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;
$per_page   = 12;
$offset     = ($page - 1) * $per_page;

$where = "WHERE p.is_sold = 0";
if ($search !== '') $where .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($cat_filter > 0) $where .= " AND p.category_id = $cat_filter";
if ($area !== '')    $where .= " AND p.location LIKE '%$area%'";

$order = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    default      => 'p.created_at DESC',
};

$total_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM products p $where"
))['n'];
$total_pages = ceil($total_count / $per_page);

$products   = mysqli_query($conn, "
    SELECT p.*, u.username, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    $where
    ORDER BY $order
    LIMIT $per_page OFFSET $offset
");
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

$page_title = 'Browse Listings';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Browse Listings</h2>

    <form method="GET" action="browse.php">
        <div class="search-bar" style="flex-wrap:wrap;gap:8px;">
            <input type="text" name="search"
                   placeholder="Search listings..."
                   value="<?= htmlspecialchars($search) ?>"
                   style="flex:2;min-width:160px;">
            <input type="text" name="area"
                   placeholder="Area / town..."
                   value="<?= htmlspecialchars($area) ?>"
                   style="flex:1;min-width:130px;">
            <select name="category" style="min-width:150px;">
                <option value="0">All Categories</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="sort" style="min-width:170px;">
                <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest First</option>
                <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High to Low</option>
            </select>
            <button type="submit" class="btn btn-green" style="padding:10px 24px;">Search</button>
            <?php if ($search || $cat_filter || $area || $sort !== 'newest'): ?>
                <a href="browse.php" class="btn btn-gray">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php $count = mysqli_num_rows($products); ?>
    <p style="font-size:14px;color:#888;margin-bottom:12px;">
        <?= $count ?> listing<?= $count !== 1 ? 's' : '' ?> found
        <?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
    </p>

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
                             style="width:100%;height:140px;object-fit:cover;border-radius:6px;margin-bottom:10px;">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <div class="price">R <?= number_format($p['price'], 2) ?></div>
                    <?php if ($p['category_name']): ?>
                        <div class="category"><?= htmlspecialchars($p['category_name']) ?></div>
                    <?php endif; ?>
                    <?php if ($p['location']): ?>
                        <div class="location"><?= htmlspecialchars($p['location']) ?></div>
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

<?php if ($total_pages > 1): ?>
<div class="flex-row" style="justify-content:center;margin-top:32px;gap:8px;">
    <?php if ($page > 1): ?>
        <a href="browse.php?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= $cat_filter ?>"
           class="btn btn-gray">&larr; Previous</a>
    <?php endif; ?>

    <span style="padding:9px 16px;font-size:14px;color:var(--gray-500);font-weight:600;">
        Page <?= $page ?> of <?= $total_pages ?>
    </span>

    <?php if ($page < $total_pages): ?>
        <a href="browse.php?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= $cat_filter ?>"
           class="btn btn-green">Next &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

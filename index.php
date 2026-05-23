<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Fetch latest 8 products for the homepage
$featured = mysqli_query($conn, "
    SELECT p.*, u.username, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 8
");

// Fetch categories for search dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

$page_title = 'Buy & Sell Near You';
include 'includes/header.php';
?>

<style>
/* ---- INDEX PAGE ONLY ---- */

.index-hero {
    background: linear-gradient(135deg, #14532d 0%, #166534 60%, #15803d 100%);
    padding: 36px 20px 40px;
    text-align: center;
    margin-bottom: 0;
}

.index-hero h2 {
    color: white;
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 6px;
    letter-spacing: -0.5px;
}

.index-hero p {
    color: rgba(255,255,255,0.78);
    font-size: 14px;
    margin-bottom: 20px;
}

/* Search bar on hero */
.hero-search {
    max-width: 680px;
    margin: 0 auto;
    display: flex;
    gap: 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.hero-search select {
    width: 160px;
    border: none;
    border-right: 1px solid #e5e7eb;
    border-radius: 0;
    margin-bottom: 0;
    font-size: 14px;
    padding: 12px 10px;
    background: #f9fafb;
    color: #374151;
    cursor: pointer;
    flex-shrink: 0;
}

.hero-search select:focus {
    outline: none;
    box-shadow: none;
    border-color: #e5e7eb;
}

.hero-search input {
    flex: 1;
    border: none;
    border-radius: 0;
    margin-bottom: 0;
    font-size: 15px;
    padding: 12px 16px;
}

.hero-search input:focus {
    outline: none;
    box-shadow: none;
    border-color: transparent;
}

.hero-search button {
    border-radius: 0 8px 8px 0;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 600;
    background: #f59e0b;
    color: #1a1a1a;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.15s;
}

.hero-search button:hover {
    background: #d97706;
}

/* Trust badges row */
.trust-bar {
    background: #f0fdf4;
    border-bottom: 1px solid #dcfce7;
    padding: 10px 20px;
}

.trust-bar-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    gap: 32px;
    flex-wrap: wrap;
}

.trust-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #15803d;
}

<?php if (isLoggedIn()): ?>
<div class="flex-row mb-2" style="margin-top:8px;">
    <a href="cart.php" class="btn btn-green btn-lg">🛒 My Cart
        <?php
        $uid = currentUserId();
        $cq  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) AS total FROM cart WHERE user_id=$uid"));
        $cc  = (int)($cq['total'] ?? 0);
        if ($cc > 0): ?>
            <span class="cart-badge"><?= $cc ?></span>
        <?php endif; ?>
    </a>
    <a href="checkout.php" class="btn btn-yellow btn-lg">✅ Checkout</a>
</div>
<?php endif; ?>

/* Category pills */
.category-strip {
    max-width: 1100px;
    margin: 24px auto 0;
    padding: 0 20px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    transition: all 0.15s;
    white-space: nowrap;
}

.cat-pill:hover {
    background: #f0fdf4;
    border-color: #16a34a;
    color: #16a34a;
    text-decoration: none;
}

/* Section headings */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.section-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
}

.section-header a {
    font-size: 13px;
    font-weight: 500;
    color: #16a34a;
}

/* Mini feature strip */
.mini-features {
    display: flex;
    gap: 12px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.mini-feature {
    flex: 1;
    min-width: 140px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #4b5563;
    font-weight: 500;
}

.mini-feature span.icon {
    font-size: 20px;
    flex-shrink: 0;
}

/* CTA banner */
.cta-banner {
    background: linear-gradient(135deg, #14532d, #166534);
    border-radius: 12px;
    padding: 28px 24px;
    text-align: center;
    margin-top: 40px;
    color: white;
}

.cta-banner h3 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
}

.cta-banner p {
    font-size: 14px;
    color: rgba(255,255,255,0.78);
    margin-bottom: 16px;
}

.cta-banner .flex-row {
    justify-content: center;
}
</style>

<!-- Hero with search -->
<div class="index-hero">
    <h2>South Africa's Community Marketplace</h2>
    <p>Buy and sell anything — from clothing to electronics, furniture to services</p>

    <form method="GET" action="browse.php">
        <div class="hero-search">
            <select name="category">
                <option value="0">All Categories</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="search" placeholder="Search for anything...">
            <button type="submit">🔍 Search</button>
        </div>
    </form>
</div>

<!-- Trust bar -->
<div class="trust-bar">
    <div class="trust-bar-inner">
        <div class="trust-item">✅ Free to use</div>
        <div class="trust-item">🛡️ Verified sellers</div>
        <div class="trust-item">📍 South Africa only</div>
        <div class="trust-item">🤝 Direct contact</div>
    </div>
</div>

<div class="container">

    <!-- Category pills -->
    <div class="category-strip" style="padding:0;margin-top:20px;margin-bottom:24px;">
        <a href="browse.php?category=0" class="cat-pill">🏷️ All</a>
        <a href="browse.php?category=1" class="cat-pill">👕 Clothing</a>
        <a href="browse.php?category=2" class="cat-pill">📱 Electronics</a>
        <a href="browse.php?category=3" class="cat-pill">🛒 Food</a>
        <a href="browse.php?category=4" class="cat-pill">🪑 Furniture</a>
        <a href="browse.php?category=5" class="cat-pill">🔧 Tools</a>
        <a href="browse.php?category=6" class="cat-pill">🚗 Vehicles</a>
        <a href="browse.php?category=7" class="cat-pill">💼 Services</a>
    </div>

    <!-- Latest listings -->
    <div class="section-header">
        <h3>Latest Listings</h3>
        <a href="browse.php">View all &rarr;</a>
    </div>

    <?php if (mysqli_num_rows($featured) === 0): ?>
        <div style="background:white;border:1px solid #e5e7eb;border-radius:12px;padding:48px;text-align:center;color:#9ca3af;margin-bottom:32px;">
            <div style="font-size:40px;margin-bottom:12px;">🛍️</div>
            <p style="font-size:16px;font-weight:600;color:#374151;margin-bottom:6px;">No listings yet</div>
            <p style="font-size:14px;">Be the first to post something for sale!</p>
            <?php if (isLoggedIn() && isSeller()): ?>
                <a href="create-listing.php" class="btn btn-green" style="margin-top:16px;">Post a Listing</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-green" style="margin-top:16px;">Register to Sell</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="product-grid" style="margin-bottom:32px;">
            <?php while ($p = mysqli_fetch_assoc($featured)): ?>
            <div class="product-card" onclick="window.location='product-details.php?id=<?= $p['id'] ?>'">
                <div>
                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <div class="price">R <?= number_format($p['price'], 2) ?></div>
                    <?php if ($p['category_name']): ?>
                        <div class="category"><?= htmlspecialchars($p['category_name']) ?></div>
                    <?php endif; ?>
                    <?php if ($p['location']): ?>
                        <div class="location">📍 <?= htmlspecialchars($p['location']) ?></div>
                    <?php endif; ?>
                    <div class="location">By: <?= htmlspecialchars($p['username']) ?></div>
                </div>
                <div class="card-footer">
                    <a href="product-details.php?id=<?= $p['id'] ?>" class="btn btn-green">View Item</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- Mini features -->
    <div class="mini-features">
        <div class="mini-feature">
            <span class="icon">🛒</span> Browse without an account
        </div>
        <div class="mini-feature">
            <span class="icon">📦</span> Post items in minutes
        </div>
        <div class="mini-feature">
            <span class="icon">🤝</span> Contact sellers directly
        </div>
        <div class="mini-feature">
            <span class="icon">📍</span> South Africa focused
        </div>
    </div>

    <!-- CTA for guests -->
    <?php if (!isLoggedIn()): ?>
    <div class="cta-banner">
        <h3>Ready to start selling?</h3>
        <p>Join thousands of South African sellers. Register for free and post your first listing in minutes.</p>
        <div class="flex-row">
            <a href="register.php" class="btn btn-yellow btn-lg">Register Free</a>
            <a href="login.php" class="btn btn-outline" style="border-color:rgba(255,255,255,0.5);color:white;">Log In</a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
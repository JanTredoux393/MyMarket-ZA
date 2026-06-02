<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$featured   = mysqli_query($conn, "
    SELECT p.*, u.username, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_sold = 0
    ORDER BY p.created_at DESC
    LIMIT 8
");
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

$page_title = 'Buy & Sell Near You';
include 'includes/header.php';
?>

<style>

/* ---- HERO ---- */
.index-hero {
    background: linear-gradient(160deg, #052e16 0%, #14532d 60%, #166534 100%);
    padding: 56px 20px 48px;
    text-align: center;

    /* Subtle dot-grid texture to break up the flat green */
    background-image:
        radial-gradient(circle, rgba(255,255,255,0.06) 1px, transparent 1px),
        linear-gradient(160deg, #052e16 0%, #14532d 60%, #166534 100%);
    background-size: 28px 28px, cover;
}

.index-hero h2 {
    color: white;
    font-size: 38px;
    font-weight: 800;
    margin-bottom: 10px;
    letter-spacing: -1.5px;
    line-height: 1.15;
}

.index-hero h2 span {
    color: var(--gold);
}

.index-hero p {
    color: rgba(255,255,255,0.60);
    font-size: 15px;
    margin-bottom: 28px;
}

/* ---- HERO SEARCH BAR ---- */
.hero-search {
    max-width: 620px;
    margin: 0 auto;
    display: flex;
    gap: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}

.hero-search select {
    width: 150px;
    border: none;
    border-right: 1px solid #e5e7eb;
    border-radius: 0;
    margin-bottom: 0;
    font-size: 13px;
    padding: 14px 10px;
    background: #f9fafb;
    color: #374151;
    flex-shrink: 0;
    font-weight: 600;
}

.hero-search select:focus { outline: none; box-shadow: none; }

.hero-search input {
    flex: 1;
    border: none;
    border-radius: 0;
    margin-bottom: 0;
    font-size: 15px;
    padding: 14px 16px;
    color: #111;
}

.hero-search input:focus { outline: none; box-shadow: none; }

.hero-search button {
    border-radius: 0 12px 12px 0;
    padding: 14px 26px;
    font-size: 14px;
    font-weight: 700;
    background: var(--gold);
    color: #1a1a1a;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.15s;
    letter-spacing: 0.02em;
}

.hero-search button:hover { background: var(--gold-dark); }

/* ---- TRUST BAR — sits inside the hero now, no gap ---- */
.trust-bar {
    margin-top: 24px;
}

.trust-bar-inner {
    max-width: 700px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    gap: 28px;
    flex-wrap: wrap;
}

.trust-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,0.55);
    letter-spacing: 0.02em;
}

/* ---- CATEGORY PILLS ---- */
.category-strip {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 32px;
    margin-top: 32px;
}

.cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    background: white;
    border: 1.5px solid var(--gray-200);
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-600);
    text-decoration: none;
    transition: all 0.15s;
    white-space: nowrap;
    box-shadow: var(--shadow-sm);
}

.cat-pill:hover {
    background: var(--green-xlight);
    border-color: var(--green);
    color: var(--green);
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* ---- SECTION HEADER ---- */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.section-header h3 {
    font-size: 20px;
    font-weight: 800;
    color: var(--gray-900);
    letter-spacing: -0.3px;
}

.section-header a {
    font-size: 13px;
    font-weight: 600;
    color: var(--green);
}

/* ---- PRODUCT CARD — image fills the top ---- */
.product-card .card-image {
    width: calc(100% + 36px);   /* stretch past the card's 18px padding on each side */
    margin: -18px -18px 12px -18px;
    height: 160px;
    object-fit: cover;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    display: block;
    background: var(--gray-100); /* placeholder colour while loading */
}

/* Placeholder shown when there's no image */
.card-no-image {
    width: calc(100% + 36px);
    margin: -18px -18px 12px -18px;
    height: 100px;
    background: linear-gradient(135deg, var(--green-xlight), var(--gray-100));
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

/* ---- CTA BANNER ---- */
.cta-banner {
    background: linear-gradient(135deg, #052e16, #14532d);
    border-radius: 16px;
    padding: 40px 28px;
    text-align: center;
    margin-top: 48px;
    color: white;
    position: relative;
    overflow: hidden;
}

.cta-banner::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 180px; height: 180px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}

.cta-banner h3 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
    position: relative;
}

.cta-banner p {
    font-size: 14px;
    color: rgba(255,255,255,0.65);
    margin-bottom: 20px;
    position: relative;
}

.cta-banner .flex-row { justify-content: center; position: relative; }

/* ---- STATS STRIP ---- */
.stats-strip {
    display: flex;
    gap: 0;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    margin-bottom: 32px;
}

.stats-strip-item {
    flex: 1;
    text-align: center;
    padding: 20px 12px;
    border-right: 1px solid var(--gray-100);
}

.stats-strip-item:last-child { border-right: none; }

.stats-strip-number {
    font-size: 26px;
    font-weight: 800;
    color: var(--green);
    letter-spacing: -1px;
    line-height: 1;
}

.stats-strip-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 4px;
}

</style>

<!-- Hero — no gap between this and the content below -->
<div class="index-hero">
    <h2>Buy &amp; Sell Anything in <span>South Africa</span></h2>
    <p>The community marketplace for everyday South Africans</p>

    <form method="GET" action="browse.php">
        <div class="hero-search">
            <select name="category">
                <option value="0">All Categories</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="search" placeholder="What are you looking for?">
            <button type="submit">🔍 Search</button>
        </div>
    </form>

    <div class="trust-bar">
        <div class="trust-bar-inner">
            <div class="trust-item">✓ Free to use</div>
            <div class="trust-item">✓ South Africa only</div>
            <div class="trust-item">✓ Direct contact</div>
            <div class="trust-item">✓ No middlemen</div>
        </div>
    </div>
</div>

<div class="container">

    <?php
    // Count live stats to show on the homepage — gives it a sense of activity
    $stat_listings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM products WHERE is_sold=0"))['n'];
    $stat_sellers  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users WHERE role IN ('seller','admin')"))['n'];
    $stat_cats     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM categories"))['n'];
    ?>

    <!-- Live stats strip — makes the page feel alive -->
    <div class="stats-strip">
        <div class="stats-strip-item">
            <div class="stats-strip-number"><?= $stat_listings ?></div>
            <div class="stats-strip-label">Active Listings</div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-number"><?= $stat_sellers ?></div>
            <div class="stats-strip-label">Sellers</div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-number"><?= $stat_cats ?></div>
            <div class="stats-strip-label">Categories</div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-number">0</div>
            <div class="stats-strip-label">Delivery Areas</div>
        </div>
    </div>

    <!-- Category pills -->
    <div class="category-strip">
        <a href="browse.php" class="cat-pill">🏷️ All</a>
        <?php
        $cat_icons = [
            'Clothing'         => '👕',
            'Electronics'      => '📱',
            'Food & Groceries' => '🛒',
            'Furniture'        => '🪑',
            'Tools'            => '🔧',
            'Vehicles'         => '🚗',
            'Services'         => '💼',
            'Other'            => '📦',
        ];
        $pills = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
        while ($pill = mysqli_fetch_assoc($pills)):
            $icon = $cat_icons[$pill['name']] ?? '📦';
        ?>
            <a href="browse.php?category=<?= $pill['id'] ?>" class="cat-pill">
                <?= $icon ?> <?= htmlspecialchars($pill['name']) ?>
            </a>
        <?php endwhile; ?>
    </div>

    <!-- Latest listings -->
    <div class="section-header">
        <h3>🛍️ Latest Listings</h3>
        <a href="browse.php">View all &rarr;</a>
    </div>

    <?php if (mysqli_num_rows($featured) === 0): ?>
        <div style="background:white;border:1px solid var(--gray-200);border-radius:16px;padding:64px 24px;text-align:center;color:var(--gray-400);margin-bottom:32px;box-shadow:var(--shadow-sm);">
            <div style="font-size:48px;margin-bottom:16px;">🛍️</div>
            <p style="font-size:17px;font-weight:700;color:var(--gray-700);margin-bottom:6px;">No listings yet</p>
            <p style="font-size:14px;margin-bottom:20px;">Be the first to post something for sale!</p>
            <?php if (isLoggedIn() && isSeller()): ?>
                <a href="create-listing.php" class="btn btn-green">Post a Listing</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-green">Register to Sell</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="product-grid" style="margin-bottom:40px;">
            <?php while ($p = mysqli_fetch_assoc($featured)): ?>
            <div class="product-card" onclick="window.location='product-details.php?id=<?= $p['id'] ?>'">
                <div>
                    <?php if (!empty($p['image'])): ?>
                        <!-- Product image fills the top of the card edge to edge -->
                        <img class="card-image"
                             src="<?= htmlspecialchars($p['image']) ?>"
                             alt="<?= htmlspecialchars($p['title']) ?>">
                    <?php else: ?>
                        <!-- No image — show a coloured placeholder with a category emoji -->
                        <div class="card-no-image">
                            <?= $cat_icons[$p['category_name']] ?? '🛍️' ?>
                        </div>
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <div class="price">R <?= number_format($p['price'], 2) ?></div>
                    <?php if ($p['category_name']): ?>
                        <div class="category"><?= htmlspecialchars($p['category_name']) ?></div>
                    <?php endif; ?>
                    <?php if ($p['location']): ?>
                        <div class="location">📍 <?= htmlspecialchars($p['location']) ?></div>
                    <?php endif; ?>
                    <!-- Shows the seller's actual username, not "buyer" -->
                    <div class="location">By <?= htmlspecialchars($p['username']) ?></div>
                </div>
                <div class="card-footer">
                    <a href="product-details.php?id=<?= $p['id'] ?>" class="btn btn-green">View Item</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- CTA — only shown to guests who are not logged in -->
    <?php if (!isLoggedIn()): ?>
    <div class="cta-banner">
        <h3>Start selling today</h3>
        <p>Register free and reach buyers across South Africa in minutes.</p>
        <div class="flex-row">
            <a href="register.php" class="btn btn-yellow btn-lg">Register Free</a>
            <a href="login.php" style="color:rgba(255,255,255,0.7);font-size:14px;font-weight:600;">Already have an account? Log in →</a>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
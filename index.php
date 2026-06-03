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
$categories     = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
$categories_nav = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

$stat_listings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM products WHERE is_sold=0"))['n'];
$stat_sellers  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM users WHERE role IN ('seller','admin')"))['n'];

$page_title = 'Buy & Sell Near You';
include 'includes/header.php';
?>

<style>

/* ---- HERO ---- */
.mz-hero {
    background: linear-gradient(135deg, #14532d 0%, #166534 60%, #15803d 100%);
    overflow: hidden;
    position: relative;
    border-bottom: 3px solid var(--gold);
}

.mz-hero-inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 52px 20px 48px;
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 40px;
    align-items: center;
}

.mz-hero-bg-text {
    position: absolute;
    right: -10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 200px;
    font-weight: 800;
    color: rgba(255,255,255,0.04);
    line-height: 1;
    pointer-events: none;
    user-select: none;
    white-space: nowrap;
    letter-spacing: -8px;
}

.mz-hero-left h1 {
    font-size: 46px;
    font-weight: 800;
    color: white;
    line-height: 1.08;
    letter-spacing: -2px;
    margin-bottom: 14px;
}

.mz-hero-left h1 em {
    font-style: normal;
    color: var(--gold);
}

.mz-hero-left p {
    color: rgba(255,255,255,0.6);
    font-size: 15px;
    margin-bottom: 24px;
    max-width: 380px;
    line-height: 1.65;
}

.mz-hero-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.mz-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 999px;
    padding: 5px 13px;
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.75);
}

.mz-hero-pill strong { color: white; font-weight: 700; }

/* Search card */
.mz-hero-search {
    background: white;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.25);
}

.mz-hero-search h3 {
    font-size: 14px;
    font-weight: 700;
    color: #14532d;
    margin-bottom: 12px;
}

.mz-hero-search input,
.mz-hero-search select {
    width: 100%;
    padding: 10px 13px;
    border: 1.5px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    margin-bottom: 8px;
    background: var(--gray-50);
    color: var(--gray-800);
    transition: border-color 0.15s;
}

.mz-hero-search input:focus,
.mz-hero-search select:focus {
    outline: none;
    border-color: var(--green);
    background: white;
    box-shadow: none;
}

.mz-hero-search button {
    width: 100%;
    padding: 12px;
    background: var(--green);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.15s;
}

.mz-hero-search button:hover { background: var(--green-dark); }

/* ---- PAGE BODY ---- */
.mz-body {
    max-width: 1100px;
    margin: 0 auto;
    padding: 36px 20px;
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 32px;
    align-items: start;
}

/* ---- SIDEBAR ---- */
.mz-sidebar h4 {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--gray-400);
    margin-bottom: 8px;
    padding-left: 4px;
}

.mz-sidebar-cats {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
}

.mz-sidebar-cats li a {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-600);
    text-decoration: none;
    transition: background 0.12s, color 0.12s;
}

.mz-sidebar-cats li a:hover {
    background: var(--green-xlight);
    color: var(--green-dark);
    text-decoration: none;
}

.mz-sidebar-cats li a .cat-icon {
    width: 26px;
    height: 26px;
    background: var(--gray-100);
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

.mz-sidebar-sell {
    background: linear-gradient(135deg, #14532d, #166534);
    color: white;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    margin-bottom: 24px;
}

.mz-sidebar-sell strong {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: white;
    margin-bottom: 5px;
}

.mz-sidebar-sell p {
    font-size: 12px;
    color: rgba(255,255,255,0.6);
    margin-bottom: 10px;
    line-height: 1.5;
}

/* ---- LISTINGS ---- */
.mz-listings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.mz-listings-header h2 {
    font-size: 19px;
    font-weight: 700;
    color: var(--gray-900);
    letter-spacing: -0.3px;
}

.mz-listings-header a {
    font-size: 13px;
    font-weight: 600;
    color: var(--green);
}

/* ---- CARDS ---- */
.mz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 14px;
}

.mz-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.18s, box-shadow 0.18s;
    cursor: pointer;
    display: flex;
    flex-direction: column;
}

.mz-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.08);
    border-color: var(--gray-300);
}

.mz-card-img {
    width: 100%;
    height: 130px;
    object-fit: cover;
    display: block;
}

.mz-card-no-img {
    width: 100%;
    height: 80px;
    background: var(--green-xlight);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}

.mz-card-body {
    padding: 11px 13px 13px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.mz-card-body h3 {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 5px;
    line-height: 1.35;
}

.mz-card-price {
    font-size: 17px;
    font-weight: 700;
    color: var(--green);
    letter-spacing: -0.3px;
    margin-bottom: 5px;
}

.mz-card-meta {
    font-size: 11px;
    color: var(--gray-400);
    margin-top: auto;
    padding-top: 7px;
    border-top: 1px solid var(--gray-100);
    display: flex;
    justify-content: space-between;
}

/* ---- CTA ---- */
.mz-cta {
    background: linear-gradient(135deg, #14532d, #166534);
    border-radius: 14px;
    padding: 32px 24px;
    text-align: center;
    margin-top: 28px;
    color: white;
}

.mz-cta h3 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 7px;
    letter-spacing: -0.3px;
}

.mz-cta p {
    font-size: 13px;
    color: rgba(255,255,255,0.6);
    margin-bottom: 18px;
}

.mz-cta .flex-row { justify-content: center; }

/* ---- RESPONSIVE ---- */
@media (max-width: 800px) {
    .mz-hero-inner { grid-template-columns: 1fr; }
    .mz-hero-left h1 { font-size: 32px; }
    .mz-hero-bg-text { display: none; }
    .mz-body { grid-template-columns: 1fr; }
    .mz-sidebar { display: none; }
}

</style>

<div class="mz-hero">
    <div class="mz-hero-bg-text">ZA</div>
    <div class="mz-hero-inner">

        <div class="mz-hero-left">
            <h1>Buy &amp; sell<br>anything in<br><em>South Africa.</em></h1>
            <p>A community marketplace for everyday South Africans. No fees, no middlemen — just direct deals.</p>
            <div class="mz-hero-pills">
                <span class="mz-hero-pill"><strong><?= $stat_listings ?></strong> live listings</span>
                <span class="mz-hero-pill"><strong><?= $stat_sellers ?></strong> sellers</span>
                <span class="mz-hero-pill">✓ Free to use</span>
            </div>
        </div>

        <div class="mz-hero-search">
            <h3>Find something today</h3>
            <form method="GET" action="browse.php">
                <input type="text" name="search" placeholder="e.g. iPhone, couch, haircut...">
                <input type="text" name="area"   placeholder="📍 Area / town (optional)">
                <select name="category">
                    <option value="0">All Categories</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">🔍 Search Listings</button>
            </form>
        </div>

    </div>
</div>

<div class="mz-body">

    <aside class="mz-sidebar">

        <?php if (isLoggedIn() && isSeller()): ?>
        <div class="mz-sidebar-sell">
            <strong>Got something to sell?</strong>
            <p>Post a listing for free and reach buyers near you.</p>
            <a href="create-listing.php" class="btn btn-green"
               style="width:100%;justify-content:center;font-size:13px;">+ Post a Listing</a>
        </div>
        <?php endif; ?>

        <h3>Categories</h3>
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
        ?>
        <ul class="mz-sidebar-cats">
            <li>
                <a href="browse.php">
                    <span class="cat-icon">🏷️</span> All Listings
                </a>
            </li>
            <?php while ($pill = mysqli_fetch_assoc($categories_nav)):
                $icon = $cat_icons[$pill['name']] ?? '📦';
            ?>
            <li>
                <a href="browse.php?category=<?= $pill['id'] ?>">
                    <span class="cat-icon"><?= $icon ?></span>
                    <?= htmlspecialchars($pill['name']) ?>
                </a>
            </li>
            <?php endwhile; ?>
        </ul>

    </aside>

    <div class="mz-listings">

        <div class="mz-listings-header">
            <h2>Latest Listings</h2>
            <a href="browse.php">View all &rarr;</a>
        </div>

        <?php if (mysqli_num_rows($featured) === 0): ?>
            <div style="background:white;border:1px solid var(--gray-200);border-radius:12px;
                        padding:48px 24px;text-align:center;color:var(--gray-400);">
                <div style="font-size:36px;margin-bottom:10px;">🛍️</div>
                <p style="font-size:15px;font-weight:700;color:var(--gray-700);margin-bottom:5px;">No listings yet</p>
                <p style="font-size:13px;margin-bottom:14px;">Be the first to post something for sale!</p>
                <?php if (isLoggedIn() && isSeller()): ?>
                    <a href="create-listing.php" class="btn btn-green">Post a Listing</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-green">Register to Sell</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="mz-grid">
                <?php while ($p = mysqli_fetch_assoc($featured)): ?>
                <div class="mz-card" onclick="window.location='product-details.php?id=<?= $p['id'] ?>'">
                    <?php if (!empty($p['image'])): ?>
                        <img class="mz-card-img"
                             src="<?= htmlspecialchars($p['image']) ?>"
                             alt="<?= htmlspecialchars($p['title']) ?>">
                    <?php else: ?>
                        <div class="mz-card-no-img">
                            <?= $cat_icons[$p['category_name']] ?? '🛍️' ?>
                        </div>
                    <?php endif; ?>
                    <div class="mz-card-body">
                        <h3><?= htmlspecialchars($p['title']) ?></h3>
                        <div class="mz-card-price">R <?= number_format($p['price'], 2) ?></div>
                        <div class="mz-card-meta">
                            <span>📍 <?= htmlspecialchars($p['location'] ?: 'SA') ?></span>
                            <span><?= htmlspecialchars($p['username']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if (!isLoggedIn()): ?>
        <div class="mz-cta">
            <h3>Ready to start?</h3>
            <p>Register free and reach buyers across South Africa in minutes.</p>
            <div class="flex-row" style="justify-content:center;">
                <a href="register.php" class="btn btn-yellow btn-lg">Register Free</a>
                <a href="login.php"
                   style="color:rgba(255,255,255,0.6);font-size:13px;font-weight:600;">Log in →</a>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<?php include 'includes/footer.php'; ?>

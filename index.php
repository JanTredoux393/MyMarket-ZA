<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$featured = mysqli_query($conn, "
    SELECT p.*, u.username, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_sold = 0
    ORDER BY p.created_at DESC
    LIMIT 8
");
$categories_nav = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

$page_title = 'Buy & Sell Near You';
include 'includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&display=swap');

.mz-hero {
    background: #14532d;
    border-bottom: 4px solid var(--gold);
    padding: 44px 20px 40px;
}

.mz-hero-inner {
    max-width: 1100px;
    margin: 0 auto;
}

.mz-hero h1 {
    font-family: 'Barlow Condensed', 'Inter', sans-serif;
    font-size: 72px;
    font-weight: 800;
    color: white;
    line-height: 0.95;
    letter-spacing: -1px;
    margin-bottom: 14px;
    text-transform: uppercase;
}

.mz-hero h1 em {
    font-style: italic;
    color: var(--gold);
}

.mz-hero p {
    color: rgba(255,255,255,0.55);
    font-size: 15px;
    margin-bottom: 24px;
    max-width: 420px;
    line-height: 1.6;
}

.mz-hero-searchbar {
    display: flex;
    max-width: 480px;
    border-radius: 5px;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,0.2);
}

.mz-hero-searchbar input {
    flex: 1;
    padding: 12px 15px;
    border: none;
    font-size: 15px;
    font-family: inherit;
    outline: none;
    color: var(--gray-800);
    background: white;
    margin-bottom: 0;
}

.mz-hero-searchbar button {
    padding: 12px 18px;
    background: var(--gold);
    color: #1a1a1a;
    border: none;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.15s;
}

.mz-hero-searchbar button:hover { background: var(--gold-dark); }

/* Body layout */
.mz-body {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 20px;
    display: grid;
    grid-template-columns: 175px 1fr;
    gap: 32px;
    align-items: start;
}

/* Sidebar */
.mz-sidebar h4 {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--gray-400);
    margin-bottom: 8px;
}

.mz-sidebar-cats {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
}

.mz-sidebar-cats li a {
    display: block;
    padding: 6px 0;
    font-size: 14px;
    color: var(--gray-600);
    text-decoration: none;
    border-bottom: 2px solid rgba(245, 158, 11, 0.8);
    transition: color 0.12s;
}

.mz-sidebar-cats li a:hover {
    color: var(--green);
    text-decoration: none;
}

.mz-sidebar-sell {
    background: #d1ffcf;
    border: 2px solid var(--gold);
    border-radius: 6px;
    padding: 14px;
    margin-bottom: 20px;
}

.mz-sidebar-sell strong {
    color: var(--gold);
}

.mz-sidebar-sell p {
    color: rgba(255,255,255,0.85);
}

.mz-sidebar-sell strong {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--green-dark);
    margin-bottom: 6px;
}

.mz-sidebar-sell p {
    font-size: 12px;
    color: var(--gray-500);
    margin-bottom: 10px;
    line-height: 1.5;
}

/* Listings header */
.mz-listings-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 16px;
    border-bottom: 2px solid var(--gray-900);
    padding-bottom: 8px;
}

.mz-listings-header h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
}

.mz-listings-header a {
    font-size: 13px;
    color: var(--green);
    font-weight: 600;
}

.mz-listings-header a:hover { color: var(--green-dark); text-decoration: underline; }

/* Cards */
.mz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 12px;
}

.mz-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 5px;
    overflow: hidden;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}

.mz-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.09);
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
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: var(--gray-400);
}

.mz-card-body {
    padding: 10px 11px 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.mz-card-body h3 {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 4px;
    line-height: 1.3;
}

.mz-card-price {
    font-size: 16px;
    font-weight: 700;
    color: var(--green);
    margin-bottom: 6px;
}

.mz-card-meta {
    font-size: 11px;
    color: var(--gray-400);
    margin-top: auto;
    display: flex;
    justify-content: space-between;
}

/* CTA */
.mz-cta {
    margin-top: 28px;
    padding: 24px 20px;
    background: #14532d;
    border: 2px solid var(--gold);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.mz-cta-text h3 {
    font-size: 17px;
    font-weight: 700;
    color: white;
    margin-bottom: 4px;
}

.mz-cta-text p {
    font-size: 13px;
    color: rgba(255,255,255,0.45);
}

.mz-cta-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.mz-cta-actions a.secondary {
    font-size: 13px;
    color: rgba(255,255,255,0.75);
    font-weight: 600;
}

/* Responsive */
@media (max-width: 800px) {
    .mz-hero h1 { font-size: 48px; }
    .mz-hero-searchbar { max-width: 100%; }
    .mz-body { grid-template-columns: 1fr; }
    .mz-sidebar { display: none; }
    .mz-cta { flex-direction: column; align-items: flex-start; }
}

</style>

<div class="mz-hero">
    <div class="mz-hero-inner">
        <h1>YOUR market<br><em>Anything, Anywhere!</em></h1>
        <p>A community marketplace for South Africans. No fees, no middlemen — direct deals between real people.</p>
        <form method="GET" action="browse.php" style="margin:0;">
            <div class="mz-hero-searchbar">
                <input type="text" name="search" placeholder="What are you looking for?">
                <button type="submit">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="mz-body">

    <aside class="mz-sidebar">

        <?php if (isLoggedIn() && isSeller()): ?>
        <div class="mz-sidebar-sell">
            <strong>Got something to sell?</strong>
            <p>Post a listing for free.</p>
            <a href="create-listing.php" class="btn btn-green" style="width:100%;font-size:13px;">+ Post a Listing</a>
        </div>
        <?php endif; ?>

        <h3>Categories</h3>
        <ul class="mz-sidebar-cats">
            <li><a href="browse.php">All Listings</a></li>
            <?php while ($cat = mysqli_fetch_assoc($categories_nav)): ?>
            <li>
                <a href="browse.php?category=<?= $cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
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
            <div style="background:white;border:1px solid var(--gray-200);border-radius:5px;
                        padding:48px 24px;text-align:center;color:var(--gray-400);">
                <p style="font-size:15px;font-weight:600;color:var(--gray-700);margin-bottom:6px;">No listings yet</p>
                <p style="font-size:13px;margin-bottom:14px;">Be the first to post something for sale.</p>
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
                        <div class="mz-card-no-img">No image</div>
                    <?php endif; ?>
                    <div class="mz-card-body">
                        <h3><?= htmlspecialchars($p['title']) ?></h3>
                        <div class="mz-card-price">R <?= number_format($p['price'], 2) ?></div>
                        <div class="mz-card-meta">
                            <span><?= htmlspecialchars($p['location'] ?: 'SA') ?></span>
                            <span><?= htmlspecialchars($p['username']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if (!isLoggedIn()): ?>
        <div class="mz-cta">
            <div class="mz-cta-text">
                <h3>Start selling today</h3>
                <p>Register free and reach buyers across South Africa.</p>
            </div>
            <div class="mz-cta-actions">
                <a href="register.php" class="btn btn-yellow btn-lg">Register Free</a>
                <a href="login.php" class="secondary">Log in &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<?php include 'includes/footer.php'; ?>

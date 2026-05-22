<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// View own profile or another user's public profile
$view_id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? currentUserId() : 0);

if ($view_id === 0) {
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id, username, email, role, created_at FROM users WHERE id=$view_id
"));

if (!$user) {
    header("Location: browse.php");
    exit();
}

$is_own = isLoggedIn() && currentUserId() === $view_id;

// Count their listings
$listing_count = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM products WHERE user_id=$view_id
"));

$page_title = $user['username'] . "'s Profile";
include 'includes/header.php';
?>

<div class="container">

    <?php if (isset($_GET['error']) && $_GET['error'] === 'notseller'): ?>
        <div class="alert alert-error">You need a seller account to post listings. Please contact the admin to upgrade your account.</div>
    <?php endif; ?>

    <!-- Profile card -->
    <div class="profile-box">
        <h2>
            <?= htmlspecialchars($user['username']) ?>
            <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
        </h2>
        <?php if ($is_own): ?>
            <p>Email: <?= htmlspecialchars($user['email']) ?></p>
        <?php endif; ?>
        <p>Member since: <?= date('d F Y', strtotime($user['created_at'])) ?></p>
        <p>Active listings: <?= $listing_count['total'] ?></p>
    </div>

    <!-- Quick links for own profile -->
    <?php if ($is_own): ?>
        <div class="flex-row mb-2">
            <a href="my-listings.php" class="btn btn-green">My Listings</a>
            <?php if (isSeller()): ?>
                <a href="create-listing.php" class="btn btn-yellow">+ New Listing</a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" class="btn btn-gray">Admin Panel</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Their public listings -->
    <h2 class="page-title">
        <?= $is_own ? 'My Listings' : htmlspecialchars($user['username']) . "'s Listings" ?>
    </h2>

    <?php
    $listings = mysqli_query($conn, "
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = $view_id
        ORDER BY p.created_at DESC
    ");
    ?>

    <?php if (mysqli_num_rows($listings) === 0): ?>
        <p class="no-results">No listings posted yet.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php while ($p = mysqli_fetch_assoc($listings)): ?>
            <div class="product-card">
                <div>
                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <div class="price">R <?= number_format($p['price'], 2) ?></div>
                    <?php if ($p['location']): ?>
                        <div class="location">📍 <?= htmlspecialchars($p['location']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="product-details.php?id=<?= $p['id'] ?>" class="btn btn-green">View</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
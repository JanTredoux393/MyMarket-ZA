<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id  = currentUserId();
$listings = mysqli_query($conn, "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = $user_id
    ORDER BY p.created_at DESC
");

$page_title = 'My Listings';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">My Listings</h2>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-auto-hide">Listing deleted successfully.</div>
    <?php endif; ?>

    <?php if (isSeller()): ?>
        <div class="mb-2">
            <a href="create-listing.php" class="btn btn-green">+ Post New Listing</a>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Your account is set to <strong>buyer</strong> only. <a href="profile.php">Contact support</a> to become a seller.
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($listings) === 0): ?>
        <p class="no-results">You have not posted any listings yet.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php while ($p = mysqli_fetch_assoc($listings)): ?>
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
                    <div class="location">Posted: <?= date('d M Y', strtotime($p['created_at'])) ?></div>
                </div>
                <div class="card-footer flex-row">
                    <a href="product-details.php?id=<?= $p['id'] ?>" class="btn btn-green">View</a>
                    <a href="edit-listing.php?id=<?= $p['id'] ?>" class="btn btn-yellow">Edit</a>
                    <a href="delete-listing.php?id=<?= $p['id'] ?>"
                       class="btn btn-red"
                       onclick="return confirmDelete('Delete this listing?')">Delete</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
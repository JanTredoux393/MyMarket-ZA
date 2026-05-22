<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: browse.php");
    exit();
}

$product = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, u.username, u.id AS seller_id, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $id
"));

if (!$product) {
    header("Location: browse.php");
    exit();
}

$success = '';
$error   = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $sender_name  = trim(mysqli_real_escape_string($conn, $_POST['sender_name']));
    $sender_email = trim(mysqli_real_escape_string($conn, $_POST['sender_email']));
    $message      = trim(mysqli_real_escape_string($conn, $_POST['message']));

    if (!$sender_name || !$sender_email || !$message) {
        $error = "Please fill in all contact fields.";
    } else {
        mysqli_query($conn, "
            INSERT INTO messages (product_id, sender_name, sender_email, message)
            VALUES ($id, '$sender_name', '$sender_email', '$message')
        ");
        $success = "Your message has been sent to the seller!";
    }
}

$page_title = htmlspecialchars($product['title']);
include 'includes/header.php';
?>

<div class="container">

    <?php if ($success): ?>
        <div class="alert alert-success alert-auto-hide"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Product detail block -->
    <div class="product-detail">
        <h2><?= htmlspecialchars($product['title']) ?></h2>
        <div class="price">R <?= number_format($product['price'], 2) ?></div>

        <div class="meta">
            <?php if ($product['category_name']): ?>
                <span>Category: <?= htmlspecialchars($product['category_name']) ?></span> &bull;
            <?php endif; ?>
            <?php if ($product['location']): ?>
                <span>📍 <?= htmlspecialchars($product['location']) ?></span> &bull;
            <?php endif; ?>
            <span>Seller: <a href="profile.php?id=<?= $product['seller_id'] ?>"><?= htmlspecialchars($product['username']) ?></a></span> &bull;
            <span>Posted: <?= date('d M Y', strtotime($product['created_at'])) ?></span>
        </div>

        <?php if ($product['description']): ?>
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <?php endif; ?>

        <!-- Owner or admin actions -->
        <?php if (isLoggedIn() && currentUserId() === $product['user_id']): ?>
            <div class="flex-row" style="margin-top:16px;">
                <a href="edit-listing.php?id=<?= $product['id'] ?>" class="btn btn-yellow">Edit Listing</a>
                <a href="delete-listing.php?id=<?= $product['id'] ?>"
                   class="btn btn-red"
                   onclick="return confirmDelete('Are you sure you want to delete this listing?')">Delete Listing</a>
            </div>
        <?php elseif (isAdmin()): ?>
            <div style="margin-top:16px;">
                <a href="delete-listing.php?id=<?= $product['id'] ?>"
                   class="btn btn-red"
                   onclick="return confirmDelete('Delete this listing as admin?')">Admin: Delete</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contact seller form -->
    <?php if (!isLoggedIn() || currentUserId() !== $product['user_id']): ?>
    <div class="profile-box">
        <h3 style="margin-bottom:14px;">Contact the Seller</h3>
        <form method="POST" action="product-details.php?id=<?= $id ?>">
            <label for="sender_name">Your Name</label>
            <input type="text" id="sender_name" name="sender_name" required
                   value="<?= htmlspecialchars($_POST['sender_name'] ?? '') ?>">

            <label for="sender_email">Your Email</label>
            <input type="email" id="sender_email" name="sender_email" required
                   value="<?= htmlspecialchars($_POST['sender_email'] ?? '') ?>">

            <label for="message">Message</label>
            <textarea id="message" name="message" placeholder="Hi, I am interested in this item..." required></textarea>

            <button type="submit" name="send_message" class="btn btn-green">Send Message</button>
        </form>
    </div>
    <?php endif; ?>

    <div style="margin-top:16px;">
        <a href="browse.php" class="btn btn-gray">&larr; Back to Listings</a>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) { header("Location: browse.php"); exit(); }

$product = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, u.username, u.id AS seller_id, c.name AS category_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $id
"));

if (!$product) { header("Location: browse.php"); exit(); }

$success = '';
$error   = '';

// Contact form
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
    <?php if (isset($_GET['carterror'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['carterror']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['cartsuccess'])): ?>
        <div class="alert alert-success alert-auto-hide">Item added to your cart! <a href="cart.php">View cart</a></div>
    <?php endif; ?>

    <div class="product-detail">

<?php if (!empty($product['image'])): ?>
    <img src="<?= htmlspecialchars($product['image']) ?>"
         alt="<?= htmlspecialchars($product['title']) ?>"
         style="width:100%;max-height:380px;object-fit:cover;border-radius:10px;margin-bottom:20px;">
<?php endif; ?>

        <h2><?= htmlspecialchars($product['title']) ?></h2>
        <div class="price">R <?= number_format($product['price'], 2) ?></div>

        <div class="meta">
            <?php if ($product['category_name']): ?>
                <span>📁 <?= htmlspecialchars($product['category_name']) ?></span>
            <?php endif; ?>
            <?php if ($product['location']): ?>
                <span>📍 <?= htmlspecialchars($product['location']) ?></span>
            <?php endif; ?>
            <span>👤 Seller: <a href="profile.php?id=<?= $product['seller_id'] ?>"><?= htmlspecialchars($product['username']) ?></a></span>
            <span>🗓️ <?= date('d M Y', strtotime($product['created_at'])) ?></span>
            <span>📦 <?= $product['stock'] ?> available</span>
        </div>

        <?php if ($product['description']): ?>
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <?php endif; ?>

        <!-- Add to cart -->
        <?php if (isLoggedIn() && currentUserId() !== $product['user_id'] && $product['stock'] > 0): ?>
            <div class="add-to-cart-box">
                <form method="POST" action="cart.php">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="from" value="product-details.php?id=<?= $product['id'] ?>">
                    <input type="hidden" name="add_to_cart" value="1">
                    <div class="flex-row">
                        <div style="display:flex;align-items:center;gap:0;">
                            <label style="margin:0;text-transform:none;font-size:14px;font-weight:600;color:var(--gray-700);margin-right:10px;">Qty:</label>
                            <select name="quantity" style="width:80px;margin-bottom:0;">
                                <?php for ($i = 1; $i <= min($product['stock'], 10); $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-green btn-lg">🛒 Add to Cart</button>
                        <a href="cart.php" class="btn btn-gray">View Cart</a>
                    </div>
                </form>
            </div>
        <?php elseif ($product['stock'] <= 0): ?>
            <div class="alert alert-error" style="margin-top:16px;">❌ Out of stock</div>
        <?php elseif (!isLoggedIn()): ?>
            <div class="alert alert-info" style="margin-top:16px;">
                <a href="login.php">Log in</a> to add this item to your cart.
            </div>
        <?php endif; ?>

        <!-- Owner/admin actions -->
        <?php if (isLoggedIn() && currentUserId() === $product['user_id']): ?>
            <div class="flex-row" style="margin-top:16px;">
                <a href="edit-listing.php?id=<?= $product['id'] ?>" class="btn btn-yellow">Edit Listing</a>
                <a href="delete-listing.php?id=<?= $product['id'] ?>"
                   class="btn btn-red"
                   onclick="return confirmDelete('Are you sure you want to delete this listing?')">Delete Listing</a>
            </div>
        <?php elseif (isAdmin()): ?>
            <a href="delete-listing.php?id=<?= $product['id'] ?>"
               class="btn btn-red" style="margin-top:16px;"
               onclick="return confirmDelete('Delete this listing as admin?')">Admin: Delete</a>
        <?php endif; ?>
    </div>

    <!-- Contact form -->
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
            <textarea id="message" name="message"
                      placeholder="Hi, I am interested in this item..." required></textarea>
            <button type="submit" name="send_message" class="btn btn-green">Send Message</button>
        </form>
    </div>
    <?php endif; ?>

    <a href="browse.php" class="btn btn-gray">&larr; Back to Listings</a>

</div>

<?php include 'includes/footer.php'; ?>
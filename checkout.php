<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = currentUserId();

// Load cart
$cart_items = mysqli_query($conn, "
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.title,
           p.price, p.stock, p.location, p.user_id AS seller_id, u.username AS seller, u.email AS seller_email
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE c.user_id = $user_id
    ORDER BY c.added_at DESC
");

$cart_rows = [];
$total     = 0;
while ($row = mysqli_fetch_assoc($cart_items)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cart_rows[] = $row;
}

// Redirect if cart is empty
if (count($cart_rows) === 0) {
    header("Location: cart.php");
    exit();
}

$success = false;

// Confirm order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $name    = trim(mysqli_real_escape_string($conn, $_POST['full_name']));
    $phone   = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $address = trim(mysqli_real_escape_string($conn, $_POST['address']));

    if (!$name || !$phone || !$address) {
        $error = "Please fill in all fields.";
    } else {
        // Send a message to each seller for each item
        foreach ($cart_rows as $item) {
            $msg = "ORDER ENQUIRY from $name (phone: $phone, address: $address): "
                 . "I would like to buy {$item['quantity']} x {$item['title']} "
                 . "at R" . number_format($item['price'], 2) . " each "
                 . "(subtotal: R" . number_format($item['subtotal'], 2) . ").";
            $msg_escaped = mysqli_real_escape_string($conn, $msg);
            $email_escaped = mysqli_real_escape_string($conn, $_SESSION['username'] . '@mymarket.co.za');

            mysqli_query($conn, "
                INSERT INTO messages (product_id, sender_name, sender_email, message)
                VALUES ({$item['product_id']}, '$name', '$email_escaped', '$msg_escaped')
            ");
        }

        // Clear the cart
        mysqli_query($conn, "DELETE FROM cart WHERE user_id=$user_id");
        $success = true;
        $cart_rows = [];
        $total = 0;
    }
}

$page_title = 'Checkout';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Checkout</h2>

    <?php if ($success): ?>
        <div class="checkout-success">
            <div style="font-size:52px;margin-bottom:16px;">✅</div>
            <h3>Order Sent!</h3>
            <p>Your order enquiry has been sent to the sellers. They will contact you to arrange payment and delivery.</p>
            <p style="font-size:13px;color:var(--gray-400);margin-top:8px;">Check your messages on each product listing for seller replies.</p>
            <div class="flex-row" style="justify-content:center;margin-top:20px;">
                <a href="browse.php" class="btn btn-green">Continue Shopping</a>
                <a href="profile.php" class="btn btn-gray">My Profile</a>
            </div>
        </div>

    <?php else: ?>

        <div class="cart-layout">

            <!-- Checkout form -->
            <div class="cart-items">
                <div class="profile-box">
                    <h3 style="margin-bottom:16px;">Your Details</h3>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="checkout.php">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required
                               placeholder="Your full name"
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">

                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required
                               placeholder="e.g. 082 123 4567"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

                        <label for="address">Delivery Address or Area</label>
                        <input type="text" id="address" name="address" required
                               placeholder="e.g. 12 Main Street, Soweto"
                               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">

                        <div class="alert alert-info" style="margin-top:4px;">
                            ℹ️ This is a C2C marketplace. After submitting, sellers will contact you directly to arrange payment and delivery.
                        </div>

                        <button type="submit" name="confirm_order"
                                class="btn btn-green btn-full" style="padding:14px;margin-top:8px;">
                            ✅ Confirm Order Enquiry
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order summary -->
            <div class="cart-summary">
                <h3>Your Order</h3>
                <div class="summary-divider"></div>

                <?php foreach ($cart_rows as $item): ?>
                <div class="summary-line">
                    <span>
                        <?= htmlspecialchars(substr($item['title'], 0, 24)) ?><?= strlen($item['title']) > 24 ? '…' : '' ?>
                        <small style="color:var(--gray-400);">×<?= $item['quantity'] ?></small><br>
                        <small style="color:var(--gray-500);">Seller: <?= htmlspecialchars($item['seller']) ?></small>
                    </span>
                    <span style="white-space:nowrap;">R <?= number_format($item['subtotal'], 2) ?></span>
                </div>
                <?php endforeach; ?>

                <div class="summary-divider"></div>

                <div class="summary-line summary-total">
                    <span>Total</span>
                    <span>R <?= number_format($total, 2) ?></span>
                </div>

                <a href="cart.php" class="btn btn-gray btn-full" style="margin-top:16px;">
                    ← Edit Cart
                </a>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
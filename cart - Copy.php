<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = currentUserId();
$success = '';
$error   = '';

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity   = max(1, (int)$_POST['quantity']);

    // Get product stock
    $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id"));

    if (!$prod) {
        $error = "Product not found.";
    } elseif ($prod['user_id'] === $user_id) {
        $error = "You cannot add your own listing to your cart.";
    } else {
        // Check if already in cart
        $existing = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM cart WHERE user_id=$user_id AND product_id=$product_id"
        ));

        if ($existing) {
            $new_qty = $existing['quantity'] + $quantity;
            if ($new_qty > $prod['stock']) {
                $new_qty = $prod['stock'];
                $error = "Only " . $prod['stock'] . " available. Quantity adjusted.";
            }
            mysqli_query($conn, "UPDATE cart SET quantity=$new_qty WHERE id={$existing['id']}");
        } else {
            if ($quantity > $prod['stock']) {
                $quantity = $prod['stock'];
                $error = "Only " . $prod['stock'] . " available. Quantity adjusted.";
            }
            mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity)
                VALUES ($user_id, $product_id, $quantity)");
        }
        if (!$error) $success = "Item added to cart!";
    }
    // Redirect back to the product page
    $redirect = isset($_POST['from']) ? $_POST['from'] : 'cart.php';
    header("Location: " . $redirect . ($error ? "?carterror=" . urlencode($error) : "?cartsuccess=1"));
    exit();
}

// Update quantity
if (isset($_GET['update']) && isset($_GET['qty'])) {
    $cart_id  = (int)$_GET['update'];
    $new_qty  = max(1, (int)$_GET['qty']);

    // Get cart item and check stock
    $item = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id
         WHERE c.id=$cart_id AND c.user_id=$user_id"
    ));

    if ($item) {
        if ($new_qty > $item['stock']) $new_qty = $item['stock'];
        mysqli_query($conn, "UPDATE cart SET quantity=$new_qty WHERE id=$cart_id AND user_id=$user_id");
    }
    header("Location: cart.php");
    exit();
}

// Remove item
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    mysqli_query($conn, "DELETE FROM cart WHERE id=$cart_id AND user_id=$user_id");
    header("Location: cart.php");
    exit();
}

// Clear entire cart
if (isset($_GET['clear'])) {
    mysqli_query($conn, "DELETE FROM cart WHERE user_id=$user_id");
    header("Location: cart.php");
    exit();
}

// Load cart items
$cart_items = mysqli_query($conn, "
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.title,
           p.price, p.stock, p.location, u.username AS seller
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE c.user_id = $user_id
    ORDER BY c.added_at DESC
");

// Calculate total
$total     = 0;
$cart_rows = [];
while ($row = mysqli_fetch_assoc($cart_items)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cart_rows[] = $row;
}

$item_count = count($cart_rows);

$page_title = 'My Cart';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">🛒 My Cart</h2>

    <?php if (isset($_GET['cartsuccess'])): ?>
        <div class="alert alert-success alert-auto-hide">Item added to cart!</div>
    <?php endif; ?>
    <?php if (isset($_GET['carterror'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['carterror']) ?></div>
    <?php endif; ?>

    <?php if ($item_count === 0): ?>
        <div class="cart-empty">
            <div style="font-size:48px;margin-bottom:16px;">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Browse listings and add items to your cart.</p>
            <a href="browse.php" class="btn btn-green" style="margin-top:16px;">Browse Listings</a>
        </div>
    <?php else: ?>

        <div class="cart-layout">

            <!-- Cart items -->
            <div class="cart-items">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <span style="font-size:14px;color:var(--gray-500);"><?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?> in your cart</span>
                    <a href="cart.php?clear=1"
                       class="btn btn-gray" style="font-size:12px;padding:6px 12px;"
                       onclick="return confirmDelete('Clear your entire cart?')">Clear Cart</a>
                </div>

                <?php foreach ($cart_rows as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-info">
                        <a href="product-details.php?id=<?= $item['product_id'] ?>" class="cart-item-title">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                        <div class="cart-item-meta">
                            Seller: <?= htmlspecialchars($item['seller']) ?>
                            <?php if ($item['location']): ?>
                                &bull; 📍 <?= htmlspecialchars($item['location']) ?>
                            <?php endif; ?>
                            &bull; <?= $item['stock'] ?> available
                        </div>
                        <div class="cart-item-price">R <?= number_format($item['price'], 2) ?> each</div>
                    </div>

                    <div class="cart-item-controls">
                        <!-- Quantity controls -->
                        <div class="qty-controls">
                            <a href="cart.php?update=<?= $item['cart_id'] ?>&qty=<?= $item['quantity'] - 1 ?>"
                               class="qty-btn <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>">−</a>
                            <span class="qty-display"><?= $item['quantity'] ?></span>
                            <a href="cart.php?update=<?= $item['cart_id'] ?>&qty=<?= $item['quantity'] + 1 ?>"
                               class="qty-btn <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>">+</a>
                        </div>

                        <div class="cart-item-subtotal">R <?= number_format($item['subtotal'], 2) ?></div>

                        <a href="cart.php?remove=<?= $item['cart_id'] ?>"
                           class="btn btn-red" style="font-size:12px;padding:6px 12px;"
                           onclick="return confirmDelete('Remove this item?')">Remove</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Order summary -->
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-divider"></div>

                <?php foreach ($cart_rows as $item): ?>
                <div class="summary-line">
                    <span><?= htmlspecialchars(substr($item['title'], 0, 28)) ?><?= strlen($item['title']) > 28 ? '…' : '' ?>
                        <small style="color:var(--gray-400);">×<?= $item['quantity'] ?></small>
                    </span>
                    <span>R <?= number_format($item['subtotal'], 2) ?></span>
                </div>
                <?php endforeach; ?>

                <div class="summary-divider"></div>

                <div class="summary-line summary-total">
                    <span>Total</span>
                    <span>R <?= number_format($total, 2) ?></span>
                </div>

                <a href="checkout.php" class="btn btn-green btn-full" style="margin-top:20px;padding:14px;">
                    Proceed to Checkout →
                </a>

                <a href="browse.php" class="btn btn-gray btn-full" style="margin-top:8px;">
                    Continue Shopping
                </a>

                <p style="font-size:12px;color:var(--gray-400);text-align:center;margin-top:12px;">
                    You will contact sellers directly after checkout.
                </p>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
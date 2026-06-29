<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = currentUserId();

// Load cart items with seller info
$result = mysqli_query($conn, "
    SELECT c.id AS cart_id, c.quantity,
           p.id AS product_id, p.title, p.price, p.stock,
           p.location, p.user_id AS seller_id,
           u.username AS seller
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN users u    ON p.user_id    = u.id
    WHERE c.user_id = $user_id
    ORDER BY c.added_at DESC
");

$cart_rows = [];
$total     = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cart_rows[] = $row;
}

// Empty cart → back to cart page
if (count($cart_rows) === 0) {
    header("Location: cart.php");
    exit();
}

// Buyer's current balance
$buyer_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT balance FROM users WHERE id = $user_id"
));
$balance = (float)$buyer_row['balance'];

$error          = '';
$success        = false;
$payment_method = '';

// ── PROCESS ORDER ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {

    $payment_method = $_POST['payment_method'] ?? '';

    if (!in_array($payment_method, ['wallet', 'on_collection'])) {
        $error = "Please select a payment method.";

    } elseif ($payment_method === 'wallet' && $balance < $total) {
        $shortfall = number_format($total - $balance, 2);
        $error = "Not enough wallet credit. You are R{$shortfall} short. "
               . "<a href='wallet.php'>Add funds here</a>.";

    } else {
        // One order row per cart item (handles multiple sellers)
        foreach ($cart_rows as $item) {
            $pid       = (int)$item['product_id'];
            $seller_id = (int)$item['seller_id'];
            $qty       = (int)$item['quantity'];
            $subtotal  = (float)$item['subtotal'];
            $pm        = mysqli_real_escape_string($conn, $payment_method);

            // Status: wallet payments are immediately 'paid', collection is 'pending'
            $status = $payment_method === 'wallet' ? 'paid' : 'pending';

            mysqli_query($conn, "
                INSERT INTO orders
                    (buyer_id, seller_id, product_id, quantity, total_price, status, payment_method)
                VALUES
                    ($user_id, $seller_id, $pid, $qty, $subtotal, '$status', '$pm')
            ");
            $order_id = mysqli_insert_id($conn);

            // Wallet: move money now
            if ($payment_method === 'wallet') {
                mysqli_query($conn,
                    "UPDATE users SET balance = balance - $subtotal WHERE id = $user_id");
                mysqli_query($conn,
                    "UPDATE users SET balance = balance + $subtotal WHERE id = $seller_id");

                $nb = mysqli_real_escape_string($conn,
                    "Wallet payment for: " . $item['title']);
                $ns = mysqli_real_escape_string($conn,
                    "Received wallet payment for: " . $item['title']);

                mysqli_query($conn, "
                    INSERT INTO wallet_transactions (user_id, type, amount, note, related_order_id)
                    VALUES ($user_id,    'payment',  $subtotal, '$nb', $order_id)
                ");
                mysqli_query($conn, "
                    INSERT INTO wallet_transactions (user_id, type, amount, note, related_order_id)
                    VALUES ($seller_id, 'received', $subtotal, '$ns', $order_id)
                ");
            }

            // Notify the seller via the messaging system
            $method_label = $payment_method === 'wallet'
                ? 'wallet — already paid'
                : 'cash at collection';
            $msg = mysqli_real_escape_string($conn,
                "ORDER #$order_id: I have ordered {$qty}× {$item['title']} "
                . "(R" . number_format($subtotal, 2) . "). "
                . "Payment: $method_label. "
                . "Please reply to arrange collection."
            );
            mysqli_query($conn, "
                INSERT INTO messages (product_id, sender_id, receiver_id, message)
                VALUES ($pid, $user_id, $seller_id, '$msg')
            ");
        }

        // Clear the cart
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");

        $success = true;
    }
}
// ───────────────────────────────────────────────────────────────

$page_title = 'Checkout';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Checkout</h2>

    <?php if ($success): ?>
        <!-- ── SUCCESS SCREEN ── -->
        <div class="checkout-success">
            <div style="font-size:52px;margin-bottom:16px;">
                <?= $payment_method === 'wallet' ? '💳' : '🤝' ?>
            </div>
            <?php if ($payment_method === 'wallet'): ?>
                <h3>Payment complete!</h3>
                <p>Your wallet was charged and the seller received the funds immediately.</p>
                <p style="margin-top:6px;">Check <a href="messages.php">Messages</a> — each seller
                   has been notified and will arrange a collection time with you.</p>
                <p style="font-size:13px;color:var(--gray-400);margin-top:8px;">
                    Your updated balance is in <a href="wallet.php">My Wallet</a>.
                </p>
            <?php else: ?>
                <h3>Order placed!</h3>
                <p>Each seller has been messaged about your order. Check
                   <a href="messages.php">Messages</a> to arrange a meetup and pay cash on collection.</p>
            <?php endif; ?>
            <div class="flex-row" style="justify-content:center;margin-top:24px;">
                <a href="orders.php"  class="btn btn-green">View My Orders</a>
                <a href="browse.php"  class="btn btn-gray">Keep Shopping</a>
                <a href="wallet.php"  class="btn btn-gray">My Wallet</a>
            </div>
        </div>

    <?php else: ?>
        <!-- ── CHECKOUT FORM ── -->
        <div class="cart-layout">

            <!-- LEFT: payment choice -->
            <div>

                <!-- How payment works explanation -->
                <div class="profile-box"
                     style="background:var(--green-xlight);border-color:#bbf7d0;margin-bottom:20px;">
                    <h3 style="color:var(--green-dark);margin-bottom:10px;">
                        How payment works
                    </h3>
                    <p style="font-size:14px;color:var(--gray-700);line-height:1.7;margin-bottom:10px;">
                        MyMarket-ZA is a direct community marketplace —
                        you deal with sellers personally. Pick how you want to pay:
                    </p>
                    <ul style="font-size:14px;color:var(--gray-700);line-height:1.9;padding-left:18px;margin:0;">
                        <li>
                            <strong>💳 Pay now (wallet)</strong> — your balance is deducted instantly
                            and the seller receives the money straight away. No cash needed.
                        </li>
                        <li>
                            <strong>🤝 Pay at collection</strong> — pay the seller in cash (or EFT)
                            when you meet to collect. The seller is notified via messages.
                        </li>
                    </ul>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <div class="profile-box">
                    <h3 style="margin-bottom:16px;">Choose how to pay</h3>

                    <form method="POST" action="checkout.php">

                        <!-- Option 1: Wallet -->
                        <?php $wallet_ok = $balance >= $total; ?>
                        <label for="pay_wallet" style="
                            display:flex;align-items:flex-start;gap:14px;
                            border:2px solid <?= $wallet_ok ? 'var(--gray-200)' : 'var(--gray-100)' ?>;
                            border-radius:8px;padding:16px;margin-bottom:12px;
                            cursor:<?= $wallet_ok ? 'pointer' : 'not-allowed' ?>;
                            opacity:<?= $wallet_ok ? '1' : '0.55' ?>;
                            text-transform:none;font-size:15px;font-weight:400;
                        " id="wallet-label">
                            <input type="radio" id="pay_wallet" name="payment_method"
                                   value="wallet"
                                   style="width:18px;height:18px;margin-top:3px;
                                          flex-shrink:0;accent-color:var(--green);"
                                   <?= !$wallet_ok ? 'disabled' : '' ?>
                                   <?= $wallet_ok   ? 'checked'  : '' ?>>
                            <div>
                                <strong style="font-size:15px;display:block;margin-bottom:4px;">
                                    💳 Pay now with wallet
                                </strong>
                                <span style="font-size:13px;color:var(--gray-500);">
                                    Your balance:
                                    <strong style="color:<?= $wallet_ok ? 'var(--green)' : 'var(--red)' ?>;">
                                        R <?= number_format($balance, 2) ?>
                                    </strong>
                                    <?php if (!$wallet_ok): ?>
                                        &mdash; you need R <?= number_format($total - $balance, 2) ?> more.
                                        <a href="wallet.php" style="font-weight:600;">Top up →</a>
                                    <?php else: ?>
                                        &mdash; enough to cover this order ✓
                                    <?php endif; ?>
                                </span>
                            </div>
                        </label>

                        <!-- Option 2: Pay at collection -->
                        <label for="pay_collection" style="
                            display:flex;align-items:flex-start;gap:14px;
                            border:2px solid var(--gray-200);border-radius:8px;
                            padding:16px;margin-bottom:20px;cursor:pointer;
                            text-transform:none;font-size:15px;font-weight:400;
                        " id="collection-label">
                            <input type="radio" id="pay_collection" name="payment_method"
                                   value="on_collection"
                                   style="width:18px;height:18px;margin-top:3px;
                                          flex-shrink:0;accent-color:var(--green);"
                                   <?= !$wallet_ok ? 'checked' : '' ?>>
                            <div>
                                <strong style="font-size:15px;display:block;margin-bottom:4px;">
                                    🤝 Pay at collection
                                </strong>
                                <span style="font-size:13px;color:var(--gray-500);">
                                    Pay the seller directly in cash or EFT when you collect.
                                    The seller will message you to arrange a time and place.
                                </span>
                            </div>
                        </label>

                        <button type="submit" name="confirm_order"
                                class="btn btn-green btn-full btn-lg">
                            ✅ Confirm Order
                        </button>
                        <a href="cart.php" class="btn btn-gray btn-full" style="margin-top:10px;">
                            ← Back to Cart
                        </a>
                    </form>
                </div>
            </div>

            <!-- RIGHT: order summary -->
            <div class="cart-summary">
                <h3>Your Order</h3>
                <div class="summary-divider"></div>

                <?php foreach ($cart_rows as $item): ?>
                <div class="summary-line">
                    <span>
                        <?= htmlspecialchars(substr($item['title'], 0, 24)) ?>
                        <?= strlen($item['title']) > 24 ? '…' : '' ?>
                        <small style="color:var(--gray-400);">×<?= $item['quantity'] ?></small><br>
                        <small style="color:var(--gray-500);">
                            <?= htmlspecialchars($item['seller']) ?>
                        </small>
                    </span>
                    <span style="white-space:nowrap;">
                        R <?= number_format($item['subtotal'], 2) ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <div class="summary-divider"></div>

                <div class="summary-line summary-total">
                    <span>Total</span>
                    <span>R <?= number_format($total, 2) ?></span>
                </div>

                <!-- Wallet balance vs total -->
                <div style="margin-top:14px;padding:12px;background:var(--gray-50);
                            border-radius:6px;font-size:13px;line-height:1.7;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--gray-500);">Wallet balance</span>
                        <strong>R <?= number_format($balance, 2) ?></strong>
                    </div>
                    <?php if ($wallet_ok): ?>
                        <div style="color:var(--green);font-weight:600;margin-top:4px;">
                            ✓ Sufficient for wallet payment
                        </div>
                    <?php else: ?>
                        <div style="color:var(--red);margin-top:4px;">
                            Short by R <?= number_format($total - $balance, 2) ?>
                            — <a href="wallet.php">top up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script>
// Highlight the selected payment radio's parent label
(function () {
    var labels = {
        'pay_wallet':     document.getElementById('wallet-label'),
        'pay_collection': document.getElementById('collection-label')
    };
    var green = 'var(--green)';
    var gray  = 'var(--gray-200)';

    function highlight() {
        Object.keys(labels).forEach(function (id) {
            var radio = document.getElementById(id);
            var lbl   = labels[id];
            if (!radio || !lbl || radio.disabled) return;
            lbl.style.borderColor = radio.checked ? green : gray;
        });
    }

    document.querySelectorAll('input[name="payment_method"]').forEach(function (r) {
        r.addEventListener('change', highlight);
    });
    highlight(); // run on load
})();
</script>

<?php include 'includes/footer.php'; ?>
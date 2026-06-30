<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = currentUserId();

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

if (count($cart_rows) === 0) { header("Location: cart.php"); exit(); }

$buyer_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM users WHERE id = $user_id"));
$balance   = (float)$buyer_row['balance'];

$all_messaged = true;
foreach ($cart_rows as $item) {
    $pid       = (int)$item['product_id'];
    $seller_id = (int)$item['seller_id'];
    $check = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT id FROM messages
        WHERE product_id = $pid AND sender_id = $user_id AND receiver_id = $seller_id
        LIMIT 1
    "));
    if (!$check) { $all_messaged = false; break; }
}

$error          = '';
$success        = false;
$payment_method = '';
$msg_error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $text = trim($_POST['message'] ?? '');
    if (!$text) {
        $msg_error = "Please enter a message.";
    } else {
        $text_esc = mysqli_real_escape_string($conn, $text);
        $sellers_done = [];
        foreach ($cart_rows as $item) {
            $pid       = (int)$item['product_id'];
            $seller_id = (int)$item['seller_id'];
            if (in_array($seller_id, $sellers_done)) continue;
            mysqli_query($conn, "
                INSERT INTO messages (product_id, sender_id, receiver_id, message)
                VALUES ($pid, $user_id, $seller_id, '$text_esc')
            ");
            $sellers_done[] = $seller_id;
        }
        $all_messaged = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $payment_method = $_POST['payment_method'] ?? '';

    if (!$all_messaged) {
        $error = "Please message the seller first.";
    } elseif (!in_array($payment_method, ['wallet', 'on_collection'])) {
        $error = "Please select a payment method.";
    } elseif ($payment_method === 'wallet' && $balance < $total) {
        $error = "Not enough wallet balance. Add funds in your wallet.";
    } else {
        foreach ($cart_rows as $item) {
            $pid       = (int)$item['product_id'];
            $seller_id = (int)$item['seller_id'];
            $qty       = (int)$item['quantity'];
            $subtotal  = (float)$item['subtotal'];
            $pm        = mysqli_real_escape_string($conn, $payment_method);
            $status    = $payment_method === 'wallet' ? 'paid' : 'pending';

            mysqli_query($conn, "
                INSERT INTO orders (buyer_id, seller_id, product_id, quantity, total_price, status, payment_method)
                VALUES ($user_id, $seller_id, $pid, $qty, $subtotal, '$status', '$pm')
            ");
            $order_id = mysqli_insert_id($conn);

            if ($payment_method === 'wallet') {
                mysqli_query($conn, "UPDATE users SET balance = balance - $subtotal WHERE id = $user_id");
                mysqli_query($conn, "UPDATE users SET balance = balance + $subtotal WHERE id = $seller_id");

                $nb = mysqli_real_escape_string($conn, "Payment for: " . $item['title']);
                $ns = mysqli_real_escape_string($conn, "Payment received for: " . $item['title']);
                mysqli_query($conn, "INSERT INTO wallet_transactions (user_id, type, amount, note, related_order_id) VALUES ($user_id, 'payment', $subtotal, '$nb', $order_id)");
                mysqli_query($conn, "INSERT INTO wallet_transactions (user_id, type, amount, note, related_order_id) VALUES ($seller_id, 'received', $subtotal, '$ns', $order_id)");
            }
        }

        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
        $success = true;
    }
}

$page_title = 'Checkout';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Checkout</h2>

    <?php if ($success): ?>
        <p>Order placed.</p>
        <p><a href="messages.php">Go to messages</a></p>
        <p><a href="browse.php">Continue shopping</a></p>

    <?php else: ?>

        <?php if ($error): ?>
            <p style="color:red;"><?= $error ?></p>
        <?php endif; ?>

        <?php foreach ($cart_rows as $item): ?>
            <p>
                <?= htmlspecialchars($item['title']) ?>
                x<?= $item['quantity'] ?>
                - R <?= number_format($item['subtotal'], 2) ?>
                (<?= htmlspecialchars($item['seller']) ?>)
            </p>
        <?php endforeach; ?>

        <p><strong>Total: R <?= number_format($total, 2) ?></strong></p>

        <hr>

        <?php if (!$all_messaged): ?>

            <p>You need to message the seller before you can order.</p>

            <?php if ($msg_error): ?>
                <p style="color:red;"><?= htmlspecialchars($msg_error) ?></p>
            <?php endif; ?>

            <form method="POST" action="checkout.php">
                <label for="message">Message</label><br>
                <textarea id="message" name="message" rows="4" cols="40"></textarea><br>
                <button type="submit" name="send_message" value="1">Send Message</button>
            </form>

        <?php else: ?>

            <form method="POST" action="checkout.php">
                <p>
                    <label>
                        <input type="radio" name="payment_method" value="wallet"
                               <?= $balance >= $total ? 'checked' : '' ?>
                               <?= $balance < $total ? 'disabled' : '' ?>>
                        Pay now with wallet (balance: R <?= number_format($balance, 2) ?>)
                    </label>
                </p>
                <p>
                    <label>
                        <input type="radio" name="payment_method" value="on_collection"
                               <?= $balance < $total ? 'checked' : '' ?>>
                        Pay at collection
                    </label>
                </p>
                <button type="submit" name="confirm_order">Confirm Order</button>
            </form>

        <?php endif; ?>

        <p><a href="cart.php">Back to cart</a></p>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
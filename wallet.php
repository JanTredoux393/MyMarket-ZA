<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = currentUserId();
$success = '';
$error   = '';
$show_form = isset($_GET['topup']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup'])) {
    $amount  = (float)$_POST['amount'];
    $card_no = trim($_POST['card_number'] ?? '');
    $expiry  = trim($_POST['expiry'] ?? '');
    $cvv     = trim($_POST['cvv'] ?? '');

    // Basic fake validation
    $card_clean = preg_replace('/\s+/', '', $card_no);
    if ($amount <= 0 || $amount > 50000) {
        $error = "Please enter an amount between R1 and R50 000.";
    } elseif (strlen($card_clean) < 16) {
        $error = "Please enter a valid 16-digit card number.";
    } elseif (!$expiry) {
        $error = "Please enter your card expiry date.";
    } elseif (strlen($cvv) < 3) {
        $error = "Please enter a valid CVV.";
    } else {
        mysqli_query($conn,
            "UPDATE users SET balance = balance + $amount WHERE id = $user_id");
        $note = mysqli_real_escape_string($conn,
            "Card deposit — R" . number_format($amount, 2));
        mysqli_query($conn, "
            INSERT INTO wallet_transactions (user_id, type, amount, note)
            VALUES ($user_id, 'topup', $amount, '$note')
        ");
        $success  = "R" . number_format($amount, 2) . " has been added to your wallet.";
        $show_form = false;
    }
}

$row     = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT balance FROM users WHERE id = $user_id"));
$balance = (float)($row['balance'] ?? 0);

$transactions = mysqli_query($conn, "
    SELECT * FROM wallet_transactions
    WHERE user_id = $user_id
    ORDER BY created_at DESC
    LIMIT 50
");

$page_title = 'Wallet';
include 'includes/header.php';
?>

<style>
.card-preview {
    background: linear-gradient(135deg, #14532d 0%, #166534 60%, #15803d 100%);
    border-radius: 14px;
    padding: 28px 28px 22px;
    color: white;
    max-width: 340px;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    font-family: 'Inter', monospace;
    position: relative;
    overflow: hidden;
}
.card-preview::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 160px; height: 160px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
}
.card-chip {
    width: 38px; height: 28px;
    background: var(--gold);
    border-radius: 5px;
    margin-bottom: 24px;
}
.card-number {
    font-size: 18px;
    letter-spacing: 3px;
    font-weight: 600;
    margin-bottom: 20px;
    font-family: monospace;
}
.card-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    opacity: 0.75;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.card-meta strong {
    display: block;
    font-size: 14px;
    opacity: 1;
    color: white;
    letter-spacing: 1px;
}
.card-brand {
    position: absolute;
    bottom: 22px; right: 24px;
    font-size: 22px;
    font-weight: 800;
    font-style: italic;
    color: rgba(255,255,255,0.5);
    letter-spacing: -1px;
}
</style>

<div class="container">
    <h2 class="page-title">Wallet</h2>

    <?php if ($success): ?>
        <div class="alert alert-success alert-auto-hide"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Balance -->
    <div class="profile-box" style="margin-bottom:20px;">
        <p style="font-size:12px;font-weight:700;text-transform:uppercase;
                   letter-spacing:0.07em;color:var(--gray-400);margin-bottom:6px;">
            Available Balance
        </p>
        <div style="font-size:48px;font-weight:800;color:var(--green);
                    letter-spacing:-2px;line-height:1;margin-bottom:10px;">
            R <?= number_format($balance, 2) ?>
        </div>
        <a href="wallet.php?topup=1" class="btn btn-green">Add Funds</a>
    </div>

    <?php if ($show_form): ?>
    <!-- Card deposit form -->
    <div class="profile-box" style="margin-bottom:24px;">
        <h3 style="margin-bottom:20px;">Add Funds</h3>

        <!-- Live card preview -->
        <div class="card-preview" id="card-preview">
            <div class="card-chip"></div>
            <div class="card-number" id="preview-number">**** **** **** ****</div>
            <div class="card-meta">
                <div>
                    <span>Expires</span>
                    <strong id="preview-expiry">MM/YY</strong>
                </div>
                <div style="text-align:right;">
                    <span>Cardholder</span>
                    <strong id="preview-name">YOUR NAME</strong>
                </div>
            </div>
            <div class="card-brand">MyPay</div>
        </div>

        <form method="POST" action="wallet.php" style="max-width:380px;">
            <input type="hidden" name="topup" value="1">

            <label for="amount">Deposit Amount (R)</label>
            <div class="flex-row" style="margin-bottom:14px;flex-wrap:wrap;">
                <?php foreach ([100, 250, 500, 1000, 2000] as $p): ?>
                    <button type="button" class="btn btn-outline" style="min-width:70px;"
                            onclick="document.getElementById('amount').value='<?= $p ?>'">
                        R <?= number_format($p) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <input type="number" id="amount" name="amount"
                   min="1" max="50000" step="1" placeholder="e.g. 500"
                   value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                   required>

            <label for="card_name">Name on Card</label>
            <input type="text" id="card_name" name="card_name"
                   placeholder="e.g. Jan van der Berg" maxlength="26"
                   value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>">

            <label for="card_number">Card Number</label>
            <input type="text" id="card_number" name="card_number"
                   placeholder="0000 0000 0000 0000" maxlength="19"
                   value="<?= htmlspecialchars($_POST['card_number'] ?? '') ?>"
                   required>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label for="expiry">Expiry Date</label>
                    <input type="text" id="expiry" name="expiry"
                           placeholder="MM/YY" maxlength="5"
                           value="<?= htmlspecialchars($_POST['expiry'] ?? '') ?>"
                           required>
                </div>
                <div>
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv"
                           placeholder="123" maxlength="4"
                           value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>"
                           required>
                </div>
            </div>

            <div class="alert alert-info" style="margin-top:4px;margin-bottom:16px;">
                This is a demo — no real card is charged.
                Any card details entered here are not stored or processed.
            </div>

            <div class="flex-row">
                <button type="submit" class="btn btn-green btn-lg">Deposit Funds</button>
                <a href="wallet.php" class="btn btn-gray">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- How it works -->
    <div class="profile-box" style="background:var(--green-xlight);border-color:#bbf7d0;margin-bottom:20px;">
        <h3 style="color:var(--green-dark);margin-bottom:10px;">How the wallet works</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
                    gap:14px;font-size:14px;color:var(--gray-700);line-height:1.6;">
            <div><strong style="display:block;margin-bottom:2px;">1. Add funds</strong>
                Enter your card details and deposit amount. No real money is charged.</div>
            <div><strong style="display:block;margin-bottom:2px;">2. Pay at checkout</strong>
                Choose <em>Pay now</em> when checking out. The amount moves from your balance to the seller instantly.</div>
            <div><strong style="display:block;margin-bottom:2px;">3. Sellers get paid</strong>
                The seller sees the money in their wallet straight away.</div>
            <div><strong style="display:block;margin-bottom:2px;">4. Or pay at collection</strong>
                Prefer cash? Choose <em>Pay at collection</em> at checkout instead.</div>
        </div>
    </div>

    <!-- Transaction history -->
    <h3 style="margin-bottom:12px;color:var(--green);">Transaction History</h3>

    <?php if (!$transactions || mysqli_num_rows($transactions) === 0): ?>
        <p style="color:var(--gray-400);padding:32px 0;text-align:center;">
            No transactions yet.
        </p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Type</th>
                    <th>Note</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
                <?php while ($tx = mysqli_fetch_assoc($transactions)):
                    $credit = in_array($tx['type'], ['topup', 'received']);
                    $color  = $credit ? 'var(--green)' : 'var(--red)';
                    $sign   = $credit ? '+' : '-';
                    $labels = [
                        'topup'    => 'Deposit',
                        'payment'  => 'Sent',
                        'received' => 'Received',
                        'refund'   => 'Refund',
                    ];
                    $label = $labels[$tx['type']] ?? ucfirst($tx['type']);
                ?>
                <tr>
                    <td style="white-space:nowrap;font-size:13px;color:var(--gray-500);">
                        <?= date('d M Y, H:i', strtotime($tx['created_at'])) ?>
                    </td>
                    <td><span style="font-weight:700;color:<?= $color ?>;"><?= $label ?></span></td>
                    <td style="font-size:13px;color:var(--gray-500);">
                        <?= htmlspecialchars($tx['note'] ?? '') ?>
                    </td>
                    <td style="text-align:right;font-weight:700;color:<?= $color ?>;white-space:nowrap;">
                        <?= $sign ?> R <?= number_format($tx['amount'], 2) ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Format card number with spaces as user types
document.getElementById('card_number').addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').substring(0, 16);
    this.value = v.replace(/(.{4})/g, '$1 ').trim();
    var display = v.length > 0
        ? (v + '****************').substring(0, 16).replace(/(.{4})/g, '$1 ').trim()
        : '**** **** **** ****';
    document.getElementById('preview-number').textContent = display;
});

// Format expiry MM/YY
document.getElementById('expiry').addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '').substring(0, 4);
    if (v.length >= 3) v = v.substring(0,2) + '/' + v.substring(2);
    this.value = v;
    document.getElementById('preview-expiry').textContent = v || 'MM/YY';
});

// Name on card preview
document.getElementById('card_name').addEventListener('input', function () {
    document.getElementById('preview-name').textContent =
        this.value.toUpperCase() || 'YOUR NAME';
});

// CVV — hide digits on preview (nothing to show, just a nice touch)
document.getElementById('cvv').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').substring(0, 4);
});
</script>

<?php include 'includes/footer.php'; ?>
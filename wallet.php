<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = currentUserId();
$success = '';
$error   = '';
$action  = $_GET['action'] ?? '';

// Handle deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
    $amount   = (float)$_POST['amount'];
    $card_no  = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry   = trim($_POST['expiry'] ?? '');
    $cvv      = trim($_POST['cvv'] ?? '');

    if ($amount <= 0 || $amount > 50000) {
        $error = "Please enter an amount between R1 and R50 000.";
    } elseif (strlen($card_no) < 16) {
        $error = "Please enter a valid 16-digit card number.";
    } elseif (!$expiry) {
        $error = "Please enter the card expiry date.";
    } elseif (strlen($cvv) < 3) {
        $error = "Please enter a valid CVV.";
    } else {
        mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE id = $user_id");
        $note = mysqli_real_escape_string($conn, "Card deposit of R" . number_format($amount, 2));
        mysqli_query($conn, "INSERT INTO wallet_transactions (user_id, type, amount, note)
            VALUES ($user_id, 'topup', $amount, '$note')");
        $success = "R" . number_format($amount, 2) . " added to your wallet.";
        $action  = '';
    }
}

// Handle withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $amount      = (float)$_POST['amount'];
    $bank_name   = trim($_POST['bank_name'] ?? '');
    $account_no  = trim($_POST['account_no'] ?? '');

    $row_now = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM users WHERE id = $user_id"));
    $current = (float)($row_now['balance'] ?? 0);

    if ($amount <= 0 || $amount > 50000) {
        $error = "Please enter a valid amount.";
    } elseif ($amount > $current) {
        $error = "You only have R" . number_format($current, 2) . " available.";
    } elseif (!$bank_name || !$account_no) {
        $error = "Please enter your bank name and account number.";
    } else {
        mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id = $user_id");
        $note = mysqli_real_escape_string($conn, "Withdrawal of R" . number_format($amount, 2) . " to $bank_name");
        mysqli_query($conn, "INSERT INTO wallet_transactions (user_id, type, amount, note)
            VALUES ($user_id, 'refund', $amount, '$note')");
        $success = "R" . number_format($amount, 2) . " withdrawn.";
        $action  = '';
    }
}

$row     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT balance FROM users WHERE id = $user_id"));
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
                    letter-spacing:-2px;line-height:1;margin-bottom:16px;">
            R <?= number_format($balance, 2) ?>
        </div>
        <div class="flex-row">
            <a href="wallet.php?action=deposit" class="btn btn-green">Add Funds</a>
            <a href="wallet.php?action=withdraw" class="btn btn-gray">Withdraw Funds</a>
        </div>
    </div>

    <?php if ($action === 'deposit'): ?>
    <!-- Deposit form -->
    <div class="profile-box" style="margin-bottom:24px;">
        <h3 style="margin-bottom:20px;">Add Funds</h3>
        <form method="POST" action="wallet.php" style="max-width:380px;">

            <label for="amount">Amount (R)</label>
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
                    <label for="expiry">Expiry</label>
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

            <div class="flex-row" style="margin-top:8px;">
                <button type="submit" name="deposit" value="1" class="btn btn-green">Deposit</button>
                <a href="wallet.php" class="btn btn-gray">Cancel</a>
            </div>
        </form>
    </div>

    <?php elseif ($action === 'withdraw'): ?>
    <!-- Withdraw form -->
    <div class="profile-box" style="margin-bottom:24px;">
        <h3 style="margin-bottom:20px;">Withdraw Funds</h3>
        <form method="POST" action="wallet.php" style="max-width:380px;">

            <label for="amount">Amount (R)</label>
            <input type="number" id="amount" name="amount"
                   min="1" max="50000" step="1"
                   placeholder="Max R <?= number_format($balance, 2) ?>"
                   value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                   required>

            <label for="bank_name">Bank Name</label>
            <input type="text" id="bank_name" name="bank_name"
                   placeholder="e.g. FNB" maxlength="50"
                   value="<?= htmlspecialchars($_POST['bank_name'] ?? '') ?>"
                   required>

            <label for="account_no">Account Number</label>
            <input type="text" id="account_no" name="account_no"
                   placeholder="e.g. 62012345678" maxlength="20"
                   value="<?= htmlspecialchars($_POST['account_no'] ?? '') ?>"
                   required>

            <div class="flex-row" style="margin-top:8px;">
                <button type="submit" name="withdraw" value="1" class="btn btn-green">Withdraw</button>
                <a href="wallet.php" class="btn btn-gray">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

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
                    <th>Date</th>
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
                        'refund'   => 'Withdrawal',
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
var cn = document.getElementById('card_number');
if (cn) {
    cn.addEventListener('input', function () {
        var v = this.value.replace(/\D/g, '').substring(0, 16);
        this.value = v.replace(/(.{4})/g, '$1 ').trim();
    });
}

var ex = document.getElementById('expiry');
if (ex) {
    ex.addEventListener('input', function () {
        var v = this.value.replace(/\D/g, '').substring(0, 4);
        if (v.length >= 3) v = v.substring(0, 2) + '/' + v.substring(2);
        this.value = v;
    });
}

var cv = document.getElementById('cvv');
if (cv) {
    cv.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').substring(0, 3);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
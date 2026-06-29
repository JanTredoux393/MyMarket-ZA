<?php
$page_title = isset($page_title) ? $page_title . ' | MyMarket-ZA' : 'MyMarket-ZA';

$cart_count  = 0;
$unread_msgs = 0;
$balance     = 0.00;

if (isLoggedIn()) {
    $uid = currentUserId();

    $cart_q     = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(quantity) AS total FROM cart WHERE user_id=$uid"));
    $cart_count = (int)($cart_q['total'] ?? 0);

    $umq         = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS n FROM messages WHERE receiver_id=$uid AND is_read=0"));
    $unread_msgs = (int)($umq['n'] ?? 0);

    if (!isAdmin()) {
        $col_check = mysqli_query($conn,
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'users'
               AND COLUMN_NAME  = 'balance'");
        if ($col_check && mysqli_num_rows($col_check) > 0) {
            $bal     = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT balance FROM users WHERE id=$uid"));
            $balance = (float)($bal['balance'] ?? 0);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="/MyMarket-ZA/css/style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <h1>
            <a href="/MyMarket-ZA/index.php">
                My<span>Market</span><span style="color:var(--gold);">-ZA</span>
            </a>
        </h1>
        <nav>
            <a href="/MyMarket-ZA/browse.php"
               <?= basename($_SERVER['PHP_SELF']) === 'browse.php' ? 'class="active"' : '' ?>>
                Browse
            </a>

            <?php if (isLoggedIn()): ?>

                <?php if (isAdmin()): ?>
                    <a href="/MyMarket-ZA/admin/dashboard.php">Admin Panel</a>
                    <a href="/MyMarket-ZA/messages.php"
                       <?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'class="active"' : '' ?>>
                        Messages
                        <?php if ($unread_msgs > 0): ?>
                            <span class="cart-badge" id="unread-nav-badge"><?= $unread_msgs ?></span>
                        <?php else: ?>
                            <span class="cart-badge" id="unread-nav-badge" style="display:none;"></span>
                        <?php endif; ?>
                    </a>
                    <a href="/MyMarket-ZA/logout.php">Logout</a>

                <?php else: ?>
                    <a href="/MyMarket-ZA/cart.php"
                       <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'class="active"' : '' ?>>
                        Cart
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="/MyMarket-ZA/messages.php"
                       <?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'class="active"' : '' ?>>
                        Messages
                        <?php if ($unread_msgs > 0): ?>
                            <span class="cart-badge" id="unread-nav-badge"><?= $unread_msgs ?></span>
                        <?php else: ?>
                            <span class="cart-badge" id="unread-nav-badge" style="display:none;"></span>
                        <?php endif; ?>
                    </a>

                    <a href="/MyMarket-ZA/wallet.php"
                       <?= basename($_SERVER['PHP_SELF']) === 'wallet.php' ? 'class="active"' : '' ?>
                       style="font-variant-numeric:tabular-nums;">
                        R <?= number_format($balance, 2) ?>
                    </a>

                    <?php if (isSeller()): ?>
                        <a href="/MyMarket-ZA/create-listing.php"
                           class="nav-sell<?= basename($_SERVER['PHP_SELF']) === 'create-listing.php' ? ' active' : '' ?>">
                            + Sell
                        </a>
                    <?php endif; ?>

                    <a href="/MyMarket-ZA/profile.php"
                       <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'class="active"' : '' ?>>
                        Profile
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <a href="/MyMarket-ZA/login.php"
                   <?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'class="active"' : '' ?>>
                    Login
                </a>
                <a href="/MyMarket-ZA/register.php"
                   <?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'class="active"' : '' ?>>
                    Register
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if (isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'messages.php'): ?>
<script>
(function () {
    function refreshBadge() {
        fetch('/MyMarket-ZA/messages.php?badge_only=1')
            .then(function (r) { return r.text(); })
            .then(function (n) {
                var badge = document.getElementById('unread-nav-badge');
                if (!badge) return;
                n = parseInt(n, 10) || 0;
                badge.textContent = n || '';
                badge.style.display = n > 0 ? 'inline-flex' : 'none';
            })
            .catch(function () {});
    }
    setInterval(refreshBadge, 8000);
})();
</script>
<?php endif; ?>
<?php
$page_title = isset($page_title) ? $page_title . ' | MyMarket-ZA' : 'MyMarket-ZA';

$cart_count  = 0;
$unread_msgs = 0;
$dark_mode   = false;

if (isLoggedIn()) {
    $uid = currentUserId();

    $cart_q     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) AS total FROM cart WHERE user_id=$uid"));
    $cart_count = (int)($cart_q['total'] ?? 0);

    $umq         = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM messages WHERE receiver_id=$uid AND is_read=0"));
    $unread_msgs = (int)($umq['n'] ?? 0);

    $dm_user   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT dark_mode FROM users WHERE id=$uid"));
    $dark_mode = (bool)($dm_user['dark_mode'] ?? false);
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
<body class="<?= $dark_mode ? 'dark' : '' ?>">

<header>
    <div class="header-inner">
        <h1><a href="/MyMarket-ZA/index.php">MyMarket<span>-ZA</span></a></h1>
        <nav>
            <a href="/MyMarket-ZA/browse.php"
               <?= basename($_SERVER['PHP_SELF']) === 'browse.php' ? 'class="active"' : '' ?>>Browse</a>

            <a href="/MyMarket-ZA/cart.php"
               <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'class="active"' : '' ?>>
                🛒 Cart<?php if ($cart_count > 0): ?><span class="cart-badge"><?= $cart_count ?></span><?php endif; ?>
            </a>

            <?php if (isLoggedIn()): ?>
                <a href="/MyMarket-ZA/messages.php"
                   <?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'class="active"' : '' ?>>
                    💬 Messages
                    <span class="cart-badge" id="unread-nav-badge"
                          style="<?= $unread_msgs > 0 ? '' : 'display:none;' ?>">
                        <?= $unread_msgs ?>
                    </span>
                </a>

                <?php if (isSeller()): ?>
                    <a href="/MyMarket-ZA/create-listing.php"
                       class="nav-sell<?= basename($_SERVER['PHP_SELF']) === 'create-listing.php' ? ' active' : '' ?>">+ Sell Item</a>
                <?php endif; ?>
                <a href="/MyMarket-ZA/my-listing.php"
                   <?= basename($_SERVER['PHP_SELF']) === 'my-listing.php' ? 'class="active"' : '' ?>>My Listings</a>
                <a href="/MyMarket-ZA/profile.php"
                   <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'class="active"' : '' ?>>My Profile</a>
                <?php if (isAdmin()): ?>
                    <a href="/MyMarket-ZA/admin/dashboard.php"
                       <?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'class="active"' : '' ?>>Admin</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="/MyMarket-ZA/login.php"
                   <?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'class="active"' : '' ?>>Login</a>
                <a href="/MyMarket-ZA/register.php"
                   <?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'class="active"' : '' ?>>Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
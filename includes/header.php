<?php
// Shared page header and navigation
// $page_title should be set before including this file
$page_title = isset($page_title) ? $page_title . ' | MyMarket-ZA' : 'MyMarket-ZA';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="/ITECA-project/css/style.css">
</head>
<body>

<header>
    <h1><a href="index.php" style="color:inherit;text-decoration:none;">MyMarket<span>-ZA</span></a></h1>
    <nav>
        <a href="browse.php" <?= basename($_SERVER['PHP_SELF']) === 'browse.php' ? 'class="active"' : '' ?>>Browse</a>

        <?php if (isLoggedIn()): ?>
            <?php if (isSeller()): ?>
                <a href="create-listing.php" <?= basename($_SERVER['PHP_SELF']) === 'create-listing.php' ? 'class="active"' : '' ?>>+ Sell Item</a>
            <?php endif; ?>
            <a href="my-listings.php" <?= basename($_SERVER['PHP_SELF']) === 'my-listings.php' ? 'class="active"' : '' ?>>My Listings</a>
            <a href="profile.php" <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'class="active"' : '' ?>>My Profile</a>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" <?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'class="active"' : '' ?>>Admin</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php" <?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'class="active"' : '' ?>>Login</a>
            <a href="register.php" <?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'class="active"' : '' ?>>Register</a>
        <?php endif; ?>
    </nav>
</header>
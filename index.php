<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect to browse page - index is just a landing/welcome page
// You can also make this the browse page if you prefer one less file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyMarket-ZA | Buy & Sell Near You</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>MyMarket<span>-ZA</span></h1>
    <nav>
        <a href="browse.php">Browse</a>
        <?php if (isLoggedIn()): ?>
            <a href="profile.php">My Profile</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">

    <!-- Hero / Welcome -->
    <div class="hero">
        <h2>Buy and sell anything, anywhere in South Africa</h2>
        <p>MyMarket-ZA is a simple marketplace built for everyday South Africans &mdash; from township traders to small independent sellers.</p>
        <div class="hero-buttons">
            <a href="browse.php" class="btn btn-green">Browse Listings</a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-yellow">Register Free</a>
            <?php else: ?>
                <a href="create-listing.php" class="btn btn-yellow">Sell an Item</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Simple feature highlights -->
    <div class="features">
        <div class="feature-card">
            <div class="feature-icon">🛒</div>
            <h3>Easy to Browse</h3>
            <p>Search and filter listings by category or location. No account needed to look around.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📦</div>
            <h3>Simple to Sell</h3>
            <p>Register a free seller account and post your items in minutes. No fees, no complicated forms.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🤝</div>
            <h3>Connect Directly</h3>
            <p>Contact sellers directly through the listing page. No middlemen.</p>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
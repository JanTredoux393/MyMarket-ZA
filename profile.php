<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$view_id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? currentUserId() : 0);

if ($view_id === 0) {
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id, username, email, role, created_at FROM users WHERE id=$view_id
"));

if (!$user) {
    header("Location: browse.php");
    exit();
}

$is_own = isLoggedIn() && currentUserId() === $view_id;

$listing_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM products WHERE user_id=$view_id"
))['total'];

$success = '';
$error   = '';

if ($is_own && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $new_username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $new_email    = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $new_password = $_POST['new_password'];
    $confirm      = $_POST['confirm_password'];
    $current_pass = $_POST['current_password'];

    $check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT password FROM users WHERE id=$view_id"
    ));

    if (!password_verify($current_pass, $check['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($new_password && strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password && $new_password !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $taken = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM users WHERE (username='$new_username' OR email='$new_email') AND id != $view_id"
        ));
        if ($taken) {
            $error = "That username or email is already in use.";
        } else {
            if ($new_password) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET username='$new_username', email='$new_email', password='$hashed' WHERE id=$view_id");
            } else {
                mysqli_query($conn, "UPDATE users SET username='$new_username', email='$new_email' WHERE id=$view_id");
            }
            $_SESSION['username'] = $new_username;
            $success = "Settings updated successfully.";
            $user = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id, username, email, role, created_at FROM users WHERE id=$view_id"
            ));
        }
    }
}

$page_title = $user['username'] . "'s Profile";
include 'includes/header.php';
?>

<div class="container">

    <?php if (isset($_GET['error']) && $_GET['error'] === 'notseller'): ?>
        <div class="alert alert-error">You need a seller account to post listings. An admin can upgrade your account.</div>
    <?php endif; ?>

    <!-- Profile card -->
    <div class="profile-box">
        <h2>
            <?= htmlspecialchars($user['username']) ?>
            <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
        </h2>
        <?php if ($is_own): ?>
            <p><?= htmlspecialchars($user['email']) ?></p>
        <?php endif; ?>
        <p>Member since <?= date('d F Y', strtotime($user['created_at'])) ?></p>
<p><?= $listing_count ?> active listing<?= $listing_count !== 1 ? 's' : '' ?></p>

        <?php if ($is_own): ?>
        <div class="flex-row" style="margin-top:16px;">
            <a href="my-listing.php" class="btn btn-green">My Listings</a>
            <?php if (isSeller()): ?>
                <a href="create-listing.php" class="btn btn-yellow">+ New Listing</a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" class="btn btn-gray">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-red"
               onclick="return confirm('Are you sure you want to log out?')">Logout</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Settings — only shown on own profile -->
    <?php if ($is_own): ?>
    <div class="profile-box">
        <h3 style="margin-bottom:4px;">Account Settings</h3>
        <p style="margin-bottom:20px;font-size:13px;color:var(--gray-400);">Enter your current password to save any changes.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-auto-hide"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

</button>

        <form method="POST" action="profile.php">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required minlength="3"
                   value="<?= htmlspecialchars($user['username']) ?>">

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($user['email']) ?>">

            <label for="new_password">New Password <span style="font-weight:400;text-transform:none;font-size:12px;color:var(--gray-400);">(leave blank to keep current)</span></label>
            <input type="password" id="new_password" name="new_password" minlength="6"
                   placeholder="Leave blank to keep current password">

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Repeat new password">

            <label for="current_password">Current Password <span style="color:var(--red);font-size:13px;">*required</span></label>
            <input type="password" id="current_password" name="current_password" required
                   placeholder="Enter your current password to confirm changes">

            <button type="submit" name="update_settings" class="btn btn-green">Save Changes</button>
            
        </form>
    </div>
    <?php endif; ?>

    <!-- Their public listings -->
    <h2 class="page-title">
        <?= $is_own ? 'My Listings' : htmlspecialchars($user['username']) . "'s Listings" ?>
    </h2>

    <?php
    $listings = mysqli_query($conn, "
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = $view_id
        ORDER BY p.created_at DESC
    ");
    ?>

    <?php if (mysqli_num_rows($listings) === 0): ?>
        <p class="no-results">No listings posted yet.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php while ($p = mysqli_fetch_assoc($listings)): ?>
            <div class="product-card">
                <div>
                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <div class="price">R <?= number_format($p['price'], 2) ?></div>
                    <?php if ($p['location']): ?>
                        <div class="location"><?= htmlspecialchars($p['location']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="product-details.php?id=<?= $p['id'] ?>" class="btn btn-green">View</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin();

$success = '';
$error   = '';

// Change role — also clears the seller request flag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = in_array($_POST['role'], ['admin', 'seller', 'buyer']) ? $_POST['role'] : 'buyer';
    mysqli_query($conn, "UPDATE users SET role='$role', seller_request=0 WHERE id=$uid");
    $success = "Role updated successfully.";
}

// Delete user
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === currentUserId()) {
        $error = "You cannot delete your own account.";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id=$uid");
        $success = "User deleted.";
    }
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | Manage Users</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <h1><a href="../index.php" style="color:inherit;text-decoration:none;">MyMarket<span>-ZA</span></a> &mdash; Admin</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php" class="active">Users</a>
            <a href="products.php">Listings</a>
            <a href="../browse.php">View Site</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <h2 class="page-title">Manage Users</h2>

    <?php if ($success): ?>
        <div class="alert alert-success alert-auto-hide"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
        <?php while ($u = mysqli_fetch_assoc($users)): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td>
                <a href="../profile.php?id=<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></a>
                <?php if (!empty($u['seller_request']) && $u['seller_request'] == 1): ?>
                    <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;margin-left:6px;">
                        ⭐ Wants to sell
                    </span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <form method="POST" action="users.php" style="display:inline-flex;gap:4px;align-items:center;">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role" style="padding:4px 6px;font-size:13px;width:auto;margin-bottom:0;">
                        <option value="buyer"  <?= $u['role']==='buyer'  ? 'selected':'' ?>>Buyer</option>
                        <option value="seller" <?= $u['role']==='seller' ? 'selected':'' ?>>Seller</option>
                        <option value="admin"  <?= $u['role']==='admin'  ? 'selected':'' ?>>Admin</option>
                    </select>
                    <button type="submit" name="change_role" class="btn btn-yellow"
                            style="padding:4px 8px;font-size:12px;">Save</button>
                </form>
            </td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ($u['id'] !== currentUserId()): ?>
                    <a href="users.php?delete=<?= $u['id'] ?>"
                       class="btn btn-red" style="padding:4px 8px;font-size:12px;"
                       onclick="return confirmDelete('Delete this user and all their listings?')">Delete</a>
                <?php else: ?>
                    <span style="font-size:12px;color:#888;">(you)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<footer>&copy; <?= date('Y') ?> <?= SITE_NAME ?> | Admin Panel</footer>
<script src="../js/script.js"></script>
</body>
</html>
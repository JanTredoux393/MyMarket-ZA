<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin();

$current_admin_id = currentUserId();
$success = '';
$error   = '';

// Change role — prevent changing an admin's own role; prevent setting others to admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid     = (int)$_POST['user_id'];
    $new_role = $_POST['role'];

    // Block: admin cannot change their own role
    if ($uid === $current_admin_id) {
        $error = "You cannot change your own role.";
    }
    // Block: cannot promote anyone else to admin through this form
    elseif ($new_role === 'admin') {
        $error = "You cannot assign the admin role from this panel.";
    }
    elseif (in_array($new_role, ['seller', 'buyer'])) {
        mysqli_query($conn, "UPDATE users SET role='$new_role', seller_request=0 WHERE id=$uid");
        $success = "Role updated successfully.";
    } else {
        $error = "Invalid role.";
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === $current_admin_id) {
        $error = "You cannot delete your own account.";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id=$uid");
        $success = "User deleted.";
    }
}

// Search
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where_clause = $search_term
    ? "WHERE username LIKE '%$search_term%' OR email LIKE '%$search_term%'"
    : '';

$users = mysqli_query($conn, "SELECT * FROM users $where_clause ORDER BY created_at DESC");
$user_count = mysqli_num_rows($users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | Manage Users</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Uniform admin topbar — fixed height, no reflow */
        .admin-topbar {
            background: linear-gradient(135deg, #052e16 0%, #14532d 50%, #166534 100%);
            color: white;
            padding: 0 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-topbar-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 68px;
            gap: 16px;
        }
        .admin-topbar-brand {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            white-space: nowrap;
            color: white;
            text-decoration: none;
        }
        .admin-topbar-brand span { color: var(--gold); }
        .admin-topbar nav {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .admin-topbar nav a {
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            font-weight: 500;
            padding: 7px 14px;
            border-radius: 999px;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s, color 0.15s;
        }
        .admin-topbar nav a:hover {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .admin-topbar nav a.active {
            background: var(--gold);
            color: #14532d;
            font-weight: 700;
        }
        .admin-topbar nav a.nav-viewsite {
            border: 1px solid rgba(255,255,255,0.35);
        }
    </style>
</head>
<body>

<div class="admin-topbar">
    <div class="admin-topbar-inner">
        <a class="admin-topbar-brand" href="../index.php">MyMarket<span>-ZA</span> &mdash; Admin</a>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php" class="active">Users</a>
            <a href="products.php">Listings</a>
            <a href="../browse.php" class="nav-viewsite" target="_blank">View Site ↗</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>
</div>

<div class="container">
    <h2 class="page-title">Manage Users</h2>

    <?php if ($success): ?>
        <div class="alert alert-success alert-auto-hide"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Search bar -->
    <form method="GET" action="users.php" style="margin-bottom:20px;">
        <div class="search-bar">
            <input type="text" name="search"
                   placeholder="Search by username or email..."
                   value="<?= htmlspecialchars($search_term) ?>"
                   style="max-width:360px;">
            <button type="submit" class="btn btn-green" style="padding:10px 22px;">🔍 Search</button>
            <?php if ($search_term): ?>
                <a href="users.php" class="btn btn-gray">Clear</a>
            <?php endif; ?>
        </div>
        <p style="font-size:13px;color:var(--gray-400);margin-top:8px;">
            <?= $user_count ?> user<?= $user_count !== 1 ? 's' : '' ?> found
            <?= $search_term ? ' for "' . htmlspecialchars($search_term) . '"' : '' ?>
        </p>
    </form>

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
                <?php if ($u['id'] === $current_admin_id): ?>
                    <span style="background:#dbeafe;color:#1e40af;font-size:11px;font-weight:600;
                                 padding:2px 8px;border-radius:999px;margin-left:6px;">You</span>
                <?php endif; ?>
                <?php if (!empty($u['seller_request']) && $u['seller_request'] == 1): ?>
                    <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;
                                 padding:2px 8px;border-radius:999px;margin-left:6px;">⭐ Wants to sell</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <?php if ($u['id'] === $current_admin_id): ?>
                    <!-- Can't change own role -->
                    <span class="role-badge role-admin">Admin (you)</span>
                <?php elseif ($u['role'] === 'admin'): ?>
                    <!-- Other admins: show badge, no change (protect them too) -->
                    <span class="role-badge role-admin">Admin</span>
                <?php else: ?>
                    <form method="POST" action="users.php" style="display:inline-flex;gap:4px;align-items:center;">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="role" style="padding:4px 6px;font-size:13px;width:auto;margin-bottom:0;">
                            <option value="buyer"  <?= $u['role']==='buyer'  ? 'selected':'' ?>>Buyer</option>
                            <option value="seller" <?= $u['role']==='seller' ? 'selected':'' ?>>Seller</option>
                        </select>
                        <button type="submit" name="change_role"
                                class="btn btn-yellow" style="padding:4px 8px;font-size:12px;">Save</button>
                    </form>
                <?php endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ($u['id'] !== $current_admin_id): ?>
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
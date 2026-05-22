<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: browse.php");
    exit();
}

$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));
if (!$product) {
    header("Location: browse.php");
    exit();
}

// Only the owner or admin can edit
if (currentUserId() !== $product['user_id'] && !isAdmin()) {
    header("Location: browse.php");
    exit();
}

$error      = '';
$success    = '';
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $desc     = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $price    = (float)$_POST['price'];
    $location = trim(mysqli_real_escape_string($conn, $_POST['location']));
    $cat_id   = (int)$_POST['category_id'];

    if (!$title) {
        $error = "Please enter a title.";
    } elseif ($price <= 0) {
        $error = "Please enter a valid price.";
    } else {
        mysqli_query($conn, "
            UPDATE products
            SET title='$title', description='$desc', price=$price,
                location='$location', category_id=$cat_id
            WHERE id=$id
        ");
        $success = "Listing updated successfully.";
        // Refresh product data
        $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));
    }
}

$page_title = 'Edit Listing';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Edit Listing</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-auto-hide"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <form method="POST" action="edit-listing.php?id=<?= $id ?>">

            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required maxlength="150"
                   value="<?= htmlspecialchars($product['title']) ?>">

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="0">Select a category</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="price">Price (R) *</label>
            <input type="number" id="price" name="price" required min="0.01" step="0.01"
                   value="<?= htmlspecialchars($product['price']) ?>">

            <label for="location">Location</label>
            <input type="text" id="location" name="location" maxlength="100"
                   value="<?= htmlspecialchars($product['location']) ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($product['description']) ?></textarea>

            <div class="flex-row">
                <button type="submit" class="btn btn-green">Save Changes</button>
                <a href="product-details.php?id=<?= $id ?>" class="btn btn-gray">Cancel</a>
                <a href="delete-listing.php?id=<?= $id ?>"
                   class="btn btn-red"
                   onclick="return confirmDelete('Are you sure you want to delete this listing?')">Delete Listing</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
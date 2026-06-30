<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) { header("Location: browse.php"); exit(); }

$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));
if (!$product) { header("Location: browse.php"); exit(); }

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
    $stock    = max(1, (int)$_POST['stock']);
    $image    = $product['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, PNG, GIF or WEBP images are allowed.";
        } else {
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            $filename = uniqid('img_') . '.' . $ext;
            $savepath = 'uploads/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $savepath)) {
                if (!empty($product['image']) && file_exists($product['image'])) {
                    unlink($product['image']);
                }
                $image = $savepath;
            } else {
                $error = "Image upload failed. Please try again.";
            }
        }
    }

    if (!$error) {
        if (!$title) {
            $error = "Please enter a title.";
        } elseif ($price <= 0) {
            $error = "Please enter a valid price.";
        } else {
            $image_escaped = mysqli_real_escape_string($conn, $image);
            mysqli_query($conn, "
                UPDATE products
                SET title='$title', description='$desc', price=$price,
                    location='$location', category_id=$cat_id, stock=$stock, image='$image_escaped'
                WHERE id=$id
            ");
            $success = "Listing updated successfully.";
            $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));
        }
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
        <form method="POST" action="edit-listing.php?id=<?= $id ?>" enctype="multipart/form-data">

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

            <label for="stock">Stock Available *</label>
            <input type="number" id="stock" name="stock" required min="1" step="1"
                   value="<?= htmlspecialchars($product['stock'] ?? '1') ?>">

            <label for="location">Location</label>
            <input type="text" id="location" name="location" maxlength="100"
                   value="<?= htmlspecialchars($product['location']) ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($product['description']) ?></textarea>

            <label for="image">
                Product Image
                <span style="font-weight:400;text-transform:none;font-size:12px;color:var(--gray-400);">
                    (leave blank to keep current)
                </span>
            </label>

            <?php if (!empty($product['image'])): ?>
                <div style="margin-bottom:10px;">
                    <img src="<?= htmlspecialchars($product['image']) ?>"
                         alt="Current image"
                         style="max-width:200px;max-height:150px;object-fit:cover;
                                border-radius:6px;display:block;">
                    <p style="font-size:12px;color:var(--gray-400);margin-top:4px;">Current image</p>
                </div>
            <?php endif; ?>

            <input type="file" id="image" name="image" accept="image/*"
                   style="margin-bottom:16px;">

            <div class="flex-row" style="margin-top:8px;">
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
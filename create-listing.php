<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

if (!isSeller()) {
    header("Location: profile.php?error=notseller");
    exit();
}

$error      = '';
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $desc     = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $price    = (float)$_POST['price'];
    $location = trim(mysqli_real_escape_string($conn, $_POST['location']));
    $cat_id   = (int)$_POST['category_id'];
    $stock    = max(1, (int)$_POST['stock']);
    $user_id  = currentUserId();
    $image    = ''; 
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, PNG, GIF or WEBP images are allowed.";
        } else {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }

            $filename = uniqid('img_') . '.' . $ext;
            $savepath = 'uploads/' . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $savepath)) {
                $image = $savepath; 
            } else {
                $error = "Image upload failed. Please try again.";
            }
        }
    }

    if (!$error) {
        if (!$title) {
            $error = "Please enter a title for your listing.";
        } elseif ($price <= 0) {
            $error = "Please enter a valid price greater than R0.";
        } else {
            $image_escaped = mysqli_real_escape_string($conn, $image);
            mysqli_query($conn, "
                INSERT INTO products (user_id, category_id, title, description, price, location, stock, image)
                VALUES ($user_id, $cat_id, '$title', '$desc', $price, '$location', $stock, '$image_escaped')
            ");
            $new_id = mysqli_insert_id($conn);
            header("Location: product-details.php?id=$new_id");
            exit();
        }
    }
}

$page_title = 'Post a Listing';
include 'includes/header.php';
?>

<div class="container">
    <h2 class="page-title">Post a New Listing</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-box">

        <form method="POST" action="create-listing.php" id="listing-form" enctype="multipart/form-data">

            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required maxlength="150"
                   placeholder="e.g. Second-hand mountain bike"
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="0">Select a category</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= $cat['id'] ?>"
                        <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="price">Price (R) *</label>
            <input type="number" id="price" name="price" required min="0.01" step="0.01"
                   placeholder="0.00"
                   value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">

            <label for="stock">Stock Available *</label>
            <input type="number" id="stock" name="stock" required min="1" step="1"
                   placeholder="e.g. 1"
                   value="<?= htmlspecialchars($_POST['stock'] ?? '1') ?>">

            <label for="location">Location</label>
            <input type="text" id="location" name="location" maxlength="100"
                   placeholder="e.g. Soweto, Johannesburg"
                   value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description"
                      placeholder="Describe your item — condition, size, colour, reason for selling..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            <small id="desc-counter" style="color:#888;font-size:12px;display:block;margin-top:-10px;margin-bottom:12px;"></small>

            <label for="image">Product Image <span style="font-weight:400;text-transform:none;font-size:12px;color:var(--gray-400);">(optional — JPG, PNG, GIF or WEBP)</span></label>
            <div class="image-upload-box">
                <input type="file" id="image" name="image" accept="image/*">
                <p class="image-upload-hint">Click to choose an image from your device</p>
                <img id="image-preview" src="" alt="Preview" style="display:none;">
            </div>

            <div class="flex-row" style="margin-top:8px;">
                <button type="submit" class="btn btn-green">Post Listing</button>
                <a href="browse.php" class="btn btn-gray">Cancel</a>
            </div>

        </form>
    </div>
</div>

<script>
    charCounter('description', 'desc-counter', 1000);

    document.getElementById('image').addEventListener('change', function () {
        var preview = document.getElementById('image-preview');
        var hint    = document.querySelector('.image-upload-hint');
        var file    = this.files[0]; 

        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src     = e.target.result;
                preview.style.display = 'block';
                hint.style.display    = 'none';
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

// Only sellers and admins can create listings
if (!isSeller()) {
    header("Location: profile.php?error=notseller");
    exit();
}

$error   = '';
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim(mysqli_real_escape_string($conn, $_POST['title']));
    $desc     = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $price    = (float)$_POST['price'];
    $location = trim(mysqli_real_escape_string($conn, $_POST['location']));
    $cat_id   = (int)$_POST['category_id'];
    $user_id  = currentUserId();

    if (!$title) {
        $error = "Please enter a title for your listing.";
    } elseif ($price <= 0) {
        $error = "Please enter a valid price greater than R0.";
    } else {
        mysqli_query($conn, "
            INSERT INTO products (user_id, category_id, title, description, price, location)
            VALUES ($user_id, $cat_id, '$title', '$desc', $price, '$location')
        ");
        $new_id = mysqli_insert_id($conn);
        header("Location: product-details.php?id=$new_id");
        exit();
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
        <form method="POST" action="create-listing.php" id="listing-form">

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

            <label for="location">Location</label>
            <input type="text" id="location" name="location" maxlength="100"
                   placeholder="e.g. Soweto, Johannesburg"
                   value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description"
                      placeholder="Describe your item — condition, size, colour, reason for selling..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            <small id="desc-counter" style="color:#888;font-size:12px;display:block;margin-top:-10px;margin-bottom:12px;"></small>

            <div class="flex-row">
                <button type="submit" class="btn btn-green">Post Listing</button>
                <a href="browse.php" class="btn btn-gray">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Attach character counter to description
    charCounter('description', 'desc-counter', 1000);
</script>

<?php include 'includes/footer.php'; ?>
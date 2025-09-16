<?php
include 'includes/session.php';
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $desc = trim($_POST['description']);
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = 'uploads/item_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }
    if (!$title || !$category || !$price) {
        $errors[] = 'Please fill all required fields.';
    }
    if (empty($errors)) {
        $stmt = $conn->prepare('INSERT INTO listings (user_id, title, category, price_per_day, description, image) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issdss', $user_id, $title, $category, $price, $desc, $image);
        $stmt->execute();
        $stmt->close();
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Item - Rentile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div><a href="index.php">Rentile</a></div>
    <div>
        <a href="index.php">Home</a>
        <a href="listings.php">Listings</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <h2>Add Item for Rent</h2>
    <?php if ($errors): ?>
        <div style="color:red;">
            <?php foreach($errors as $e) echo '<div>'.$e.'</div>'; ?>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Title*</label>
        <input type="text" name="title" required>
        <label>Category*</label>
        <select name="category" required>
            <option value="">Select</option>
            <option>Electronics</option>
            <option>Vehicles</option>
            <option>Furniture</option>
            <option>Tools</option>
            <option>Other</option>
        </select>
        <label>Price per Day ($)*</label>
        <input type="number" name="price" min="1" step="0.01" required>
        <label>Description</label>
        <textarea name="description"></textarea>
        <label>Image</label>
        <input type="file" name="image" accept="image/*">
        <button class="btn" type="submit">Add Item</button>
    </form>
</div>
</body>
</html>

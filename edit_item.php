<?php
include 'includes/session.php';
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}
$item_id = intval($_GET['id']);

// Fetch item
$stmt = $conn->prepare('SELECT * FROM listings WHERE id=? AND user_id=?');
$stmt->bind_param('ii', $item_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res->fetch_assoc();
$stmt->close();
if (!$item) {
    echo '<div class="container"><h2>Item not found or you do not have permission to edit.</h2></div>';
    exit;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $desc = trim($_POST['description']);
    $image = $item['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = 'uploads/item_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }
    if (!$title || !$category || !$price) {
        $errors[] = 'Please fill all required fields.';
    }
    if (empty($errors)) {
        $stmt = $conn->prepare('UPDATE listings SET title=?, category=?, price_per_day=?, description=?, image=? WHERE id=? AND user_id=?');
        $stmt->bind_param('ssdssii', $title, $category, $price, $desc, $image, $item_id, $user_id);
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
    <title>Edit Item - Rentile</title>
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
    <h2>Edit Item</h2>
    <?php if ($errors): ?>
        <div style="color:red;">
            <?php foreach($errors as $e) echo '<div>'.$e.'</div>'; ?>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Title*</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
        <label>Category*</label>
        <select name="category" required>
            <option <?php if($item['category']=='Electronics') echo 'selected'; ?>>Electronics</option>
            <option <?php if($item['category']=='Vehicles') echo 'selected'; ?>>Vehicles</option>
            <option <?php if($item['category']=='Furniture') echo 'selected'; ?>>Furniture</option>
            <option <?php if($item['category']=='Tools') echo 'selected'; ?>>Tools</option>
            <option <?php if($item['category']=='Other') echo 'selected'; ?>>Other</option>
        </select>
        <label>Price per Day ($)*</label>
        <input type="number" name="price" min="1" step="0.01" value="<?php echo $item['price_per_day']; ?>" required>
        <label>Description</label>
        <textarea name="description"><?php echo htmlspecialchars($item['description']); ?></textarea>
        <label>Image</label>
        <?php if ($item['image']): ?>
            <img src="<?php echo $item['image']; ?>" style="max-width:100px;"><br>
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">
        <button class="btn" type="submit">Update Item</button>
    </form>
</div>
</body>
</html>

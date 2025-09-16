<?php
include 'includes/session.php';
include 'includes/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $id_proof = '';
    
    // File upload
    if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION);
        $id_proof = 'uploads/id_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['id_proof']['tmp_name'], $id_proof);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = 'Please fill all required fields.';
    }
    
    // Check if email exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = 'Email already registered.';
    }
    $stmt->close();
    
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('INSERT INTO users (name, email, password, phone, address, id_proof) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $name, $email, $hash, $phone, $address, $id_proof);
        $stmt->execute();
        $stmt->close();
        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Rentile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div><a href="index.php">Rentile</a></div>
    <div>
        <a href="index.php">Home</a>
        <a href="listings.php">Listings</a>
        <a href="login.php">Login</a>
    </div>
</div>
<div class="container">
    <h2>Create Account</h2>
    <?php if ($errors): ?>
        <div style="color:red;">
            <?php foreach($errors as $e) echo '<div>'.$e.'</div>'; ?>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Name*</label>
        <input type="text" name="name" required>
        <label>Email*</label>
        <input type="email" name="email" required>
        <label>Password*</label>
        <input type="password" name="password" required>
        <label>Phone</label>
        <input type="text" name="phone">
        <label>Address</label>
        <input type="text" name="address">
        <label>ID Proof (image/pdf)</label>
        <input type="file" name="id_proof" accept="image/*,application/pdf">
        <button class="btn" type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>

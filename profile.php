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
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $id_proof = '';
    if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION);
        $id_proof = 'uploads/id_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['id_proof']['tmp_name'], $id_proof);
    }
    $sql = "UPDATE users SET name=?, phone=?, address=?";
    $params = [$name, $phone, $address];
    if ($id_proof) {
        $sql .= ", id_proof=?";
        $params[] = $id_proof;
    }
    $sql .= " WHERE id=?";
    $params[] = $user_id;
    $types = str_repeat('s', count($params)-1) . 'i';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
}
$stmt = $conn->prepare('SELECT name, email, phone, address, id_proof, status FROM users WHERE id=?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $address, $id_proof, $status);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Rentile</title>
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
    <h2>My Profile <span class="status-badge <?php echo $status; ?>"><?php echo ucfirst($status); ?></span></h2>
    <form method="post" enctype="multipart/form-data">
        <label>Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        <label>Email</label>
        <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
        <label>Phone</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
        <label>Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>">
        <label>ID Proof</label>
        <?php if ($id_proof): ?>
            <a href="<?php echo $id_proof; ?>" target="_blank">View</a><br>
        <?php endif; ?>
        <input type="file" name="id_proof" accept="image/*,application/pdf">
        <button class="btn" type="submit">Update Profile</button>
    </form>
</div>
</body>
</html>

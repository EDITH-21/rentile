<?php
include 'includes/session.php';
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) {
    header('Location: listings.php');
    exit;
}
$item_id = intval($_GET['id']);
// Get item
$stmt = $conn->prepare('SELECT * FROM listings WHERE id=? AND status="approved"');
$stmt->bind_param('i', $item_id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res->fetch_assoc();
$stmt->close();
if (!$item) {
    echo '<div class="container"><h2>Item not found or not available.</h2></div>';
    exit;
}
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rent_date = date('Y-m-d');
    $amount = $item['price_per_day'];
    $owner_id = $item['user_id'];
    $stmt = $conn->prepare('INSERT INTO transactions (listing_id, renter_id, owner_id, rent_date, amount) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iiisd', $item_id, $user_id, $owner_id, $rent_date, $amount);
    $stmt->execute();
    $stmt->close();
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rent Item - Rentile</title>
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
    <h2>Rent: <?php echo htmlspecialchars($item['title']); ?></h2>
    <?php if ($success): ?>
        <div style="color:green;">Rental request submitted! Check your dashboard for status.</div>
    <?php else: ?>
        <form method="post">
            <p>Price per day: <b>$<?php echo $item['price_per_day']; ?></b></p>
            <button class="btn" type="submit">Confirm Rent</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>

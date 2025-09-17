<?php
include 'includes/session.php';
include 'includes/db.php';

// Handle rental request
$success_msg = '';
if (isset($_POST['rent_item_id']) && isset($_SESSION['user_id'])) {
    $item_id = intval($_POST['rent_item_id']);
    $user_id = $_SESSION['user_id'];
    // Get item and owner
$stmt = $conn->prepare('SELECT * FROM listings WHERE id=? AND status="approved"');
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();
    if ($item && $item['user_id'] != $user_id) {
        $owner_id = $item['user_id'];
        $rent_date = date('Y-m-d');
        $amount = $item['price_per_day'];
        // Check if already requested
        $stmt = $conn->prepare('SELECT id FROM transactions WHERE listing_id=? AND renter_id=? AND status="pending"');
        $stmt->bind_param('ii', $item_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $stmt->close();
            // Create pending transaction
            $stmt = $conn->prepare('INSERT INTO transactions (listing_id, renter_id, owner_id, rent_date, amount, status) VALUES (?, ?, ?, ?, ?, "pending")');
            $stmt->bind_param('iiisd', $item_id, $user_id, $owner_id, $rent_date, $amount);
            $stmt->execute();
            $stmt->close();
            // Notify owner
            $msg = 'Your item "' . $item['title'] . '" has a new rental request.';
            $link = 'owner_rentals.php';
            $stmt = $conn->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $owner_id, $msg, $link);
            $stmt->execute();
            $stmt->close();
            $success_msg = 'Rental request sent! The owner will confirm.';
        } else {
            $success_msg = 'You have already requested to rent this item.';
        }
    }
}

// Filters
$where = "WHERE l.status='approved'";
$params = [];
if (!empty($_GET['category'])) {
    $where .= " AND category=?";
    $params[] = $_GET['category'];
}
if (!empty($_GET['min_price'])) {
    $where .= " AND price_per_day>=?";
    $params[] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where .= " AND price_per_day<=?";
    $params[] = $_GET['max_price'];
}
$sql = "SELECT l.*, u.name FROM listings l JOIN users u ON l.user_id=u.id $where ORDER BY l.created_at DESC";
$stmt = $conn->prepare($sql . (count($params) ? str_repeat('s', count($params)) : ''));
if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$res = $stmt->get_result();
$listings = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Listings - Rentile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div><a href="index.php">Rentile</a></div>
    <div>
        <a href="index.php">Home</a>
        <a href="listings.php">Listings</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</div>
<div class="container">
    <h2>Browse Listings</h2>
    <form method="get" style="margin-bottom:1.5rem;">
        <select name="category">
            <option value="">All Categories</option>
            <option>Electronics</option>
            <option>Vehicles</option>
            <option>Furniture</option>
            <option>Tools</option>
            <option>Other</option>
        </select>
        <input type="number" name="min_price" placeholder="Min Price" min="0">
        <input type="number" name="max_price" placeholder="Max Price" min="0">
        <button class="btn" type="submit">Filter</button>
    </form>
    <?php if ($success_msg): ?>
        <div style="color:green;margin-bottom:1rem;"> <?php echo $success_msg; ?> </div>
    <?php endif; ?>
    <?php if ($listings): foreach($listings as $item): ?>
        <div class="card">
            <?php if ($item['image']): ?><img src="<?php echo $item['image']; ?>" style="max-width:120px;float:right;margin-left:1rem;"><?php endif; ?>
            <b><?php echo htmlspecialchars($item['title']); ?></b> <span class="status-badge verified">Approved</span><br>
            <span>Category: <?php echo htmlspecialchars($item['category']); ?></span><br>
            <span>Price: $<?php echo $item['price_per_day']; ?>/day</span><br>
            <span>Owner: <?php echo htmlspecialchars($item['name']); ?></span><br>
            <?php if(isset($_SESSION['user_id']) && $item['user_id'] != $_SESSION['user_id']): ?>
                <form method="post" style="margin-top:0.5rem;display:inline;">
                    <input type="hidden" name="rent_item_id" value="<?php echo $item['id']; ?>">
                    <button class="btn" type="submit">Request to Rent</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; else: ?>
        <p>No listings found.</p>
    <?php endif; ?>
</div>
</body>
</html>

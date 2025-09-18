<?php
include 'includes/session.php';
include 'includes/db.php';

// Handle rental request
$success_msg = '';
if (isset($_POST['rent_item_id']) && isset($_SESSION['user_id'])) {
    $item_id = intval($_POST['rent_item_id']);
    $user_id = $_SESSION['user_id'];
    $start_date = $_POST['rent_start_date'] ?? '';
    $end_date = $_POST['rent_end_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    // Validate dates and payment method
    if (!$start_date || !$end_date || $start_date > $end_date) {
        $success_msg = 'Please select a valid rental period.';
    } elseif (!$payment_method) {
        $success_msg = 'Please select a payment method.';
    } else {
        // Get item and owner
        $stmt = $conn->prepare('SELECT * FROM listings WHERE id=? AND status="approved"');
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $item = $res->fetch_assoc();
        $stmt->close();
        if ($item && $item['user_id'] != $user_id) {
            $owner_id = $item['user_id'];
            $amount = $item['price_per_day'] * (1 + (strtotime($end_date) - strtotime($start_date)) / 86400);
            // Check if already requested
            $stmt = $conn->prepare('SELECT id FROM transactions WHERE listing_id=? AND renter_id=? AND status="pending"');
            $stmt->bind_param('ii', $item_id, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 0) {
                $stmt->close();
                // Create pending transaction with payment method
                $stmt = $conn->prepare('INSERT INTO transactions (listing_id, renter_id, owner_id, rent_start_date, rent_end_date, amount, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, "pending", ?)');
                $stmt->bind_param('iiissds', $item_id, $user_id, $owner_id, $start_date, $end_date, $amount, $payment_method);
                $stmt->execute();
                $tx_id = $stmt->insert_id;
                $stmt->close();
                // Notify owner
                $msg = 'Your item "' . $item['title'] . '" has a new rental request.';
                $link = 'rental_details.php?id=' . $tx_id;
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
        <?php if (strpos($success_msg, 'Rental request sent!') !== false): ?>
            <script>alert('Rental request sent! The owner will confirm.');</script>
        <?php endif; ?>
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
                    <label>Start Date:</label>
                    <input type="date" name="rent_start_date" required>
                    <label>End Date:</label>
                    <input type="date" name="rent_end_date" required>
                    <label>Payment Method:</label>
                    <select name="payment_method" required>
                        <option value="Cash on Delivery">Cash on Delivery</option>
                        <option value="FakePay">FakePay (Demo)</option>
                    </select>
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

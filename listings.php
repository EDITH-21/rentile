<?php
include 'includes/session.php';
include 'includes/db.php';

// Filters
$where = "WHERE status='approved'";
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
    <?php if ($listings): foreach($listings as $item): ?>
        <div class="card">
            <?php if ($item['image']): ?><img src="<?php echo $item['image']; ?>" style="max-width:120px;float:right;margin-left:1rem;"><?php endif; ?>
            <b><?php echo htmlspecialchars($item['title']); ?></b> <span class="status-badge verified">Approved</span><br>
            <span>Category: <?php echo htmlspecialchars($item['category']); ?></span><br>
            <span>Price: $<?php echo $item['price_per_day']; ?>/day</span><br>
            <span>Owner: <?php echo htmlspecialchars($item['name']); ?></span><br>
            <a href="rent_item.php?id=<?php echo $item['id']; ?>" class="btn" style="margin-top:0.5rem;">Rent This</a>
        </div>
    <?php endforeach; else: ?>
        <p>No listings found.</p>
    <?php endif; ?>
</div>
</body>
</html>

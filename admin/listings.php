<?php
// Admin manage listings
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require '../includes/db.php';
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'approve') {
        $conn->query("UPDATE listings SET status='approved' WHERE id=$id");
    } elseif ($_GET['action'] === 'reject') {
        $conn->query("UPDATE listings SET status='rejected' WHERE id=$id");
    } elseif ($_GET['action'] === 'delete') {
        $conn->query("DELETE FROM listings WHERE id=$id");
    }
    header('Location: listings.php'); exit;
}
$res = $conn->query("SELECT l.*, u.name FROM listings l JOIN users u ON l.user_id=u.id ORDER BY l.created_at DESC");
$listings = $res->fetch_all(MYSQLI_ASSOC);
?><!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'><title>Admin Listings - Rentile</title>
<link rel='stylesheet' href='../css/style.css'>
</head>
<body>
<div class='navbar'>
    <div><a href='../index.php'>Rentile</a></div>
    <div>
        <a href='dashboard.php'>Dashboard</a>
        <a href='users.php'>Users</a>
        <a href='listings.php'>Listings</a>
        <a href='transactions.php'>Transactions</a>
        <a href='logout.php'>Logout</a>
    </div>
</div>
<div class='container'>
    <h2>All Listings</h2>
    <?php foreach($listings as $l): ?>
        <div class='card'>
            <b><?php echo htmlspecialchars($l['title']); ?></b> (<?php echo $l['category']; ?>)
            <span class='status-badge <?php echo $l['status']; ?>'><?php echo ucfirst($l['status']); ?></span><br>
            Owner: <?php echo htmlspecialchars($l['name']); ?> | Price: $<?php echo $l['price_per_day']; ?>/day
            <div style='margin-top:0.5rem;'>
                <a href='?action=approve&id=<?php echo $l['id']; ?>' class='btn'>Approve</a>
                <a href='?action=reject&id=<?php echo $l['id']; ?>' class='btn' style='background:#ffa726;'>Reject</a>
                <a href='?action=delete&id=<?php echo $l['id']; ?>' class='btn' style='background:#bdbdbd;color:#222;'>Delete</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>

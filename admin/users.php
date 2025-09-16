<?php
// Admin user management
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require '../includes/db.php';
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'approve') {
        $conn->query("UPDATE users SET status='verified' WHERE id=$id");
    } elseif ($_GET['action'] === 'suspend') {
        $conn->query("UPDATE users SET status='suspended' WHERE id=$id");
    } elseif ($_GET['action'] === 'reject') {
        $conn->query("DELETE FROM users WHERE id=$id");
    }
    header('Location: users.php'); exit;
}
$res = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $res->fetch_all(MYSQLI_ASSOC);
?><!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'><title>Admin Users - Rentile</title>
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
    <h2>All Users</h2>
    <?php foreach($users as $u): ?>
        <div class='card'>
            <b><?php echo htmlspecialchars($u['name']); ?></b> (<?php echo $u['email']; ?>)
            <span class='status-badge <?php echo $u['status']; ?>'><?php echo ucfirst($u['status']); ?></span>
            <div style='margin-top:0.5rem;'>
                <a href='?action=approve&id=<?php echo $u['id']; ?>' class='btn'>Approve</a>
                <a href='?action=suspend&id=<?php echo $u['id']; ?>' class='btn' style='background:#e53935;'>Suspend</a>
                <a href='?action=reject&id=<?php echo $u['id']; ?>' class='btn' style='background:#bdbdbd;color:#222;'>Delete</a>
                <button onclick="alert('Name: <?php echo htmlspecialchars($u['name']); ?>\nEmail: <?php echo $u['email']; ?>\nPhone: <?php echo $u['phone']; ?>\nAddress: <?php echo $u['address']; ?>');" class='btn' style='background:#ffa726;'>View</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>

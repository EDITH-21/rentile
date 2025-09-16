<?php
// Admin transactions
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require '../includes/db.php';
$res = $conn->query("SELECT t.*, l.title, u1.name as renter, u2.name as owner FROM transactions t JOIN listings l ON t.listing_id=l.id JOIN users u1 ON t.renter_id=u1.id JOIN users u2 ON t.owner_id=u2.id ORDER BY t.created_at DESC");
$txs = $res->fetch_all(MYSQLI_ASSOC);
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions.csv"');
    echo "ID,Item,Renter,Owner,Date,Amount,Status\n";
    foreach($txs as $t) {
        echo "$t[id],$t[title],$t[renter],$t[owner],$t[rent_date],$t[amount],$t[status]\n";
    }
    exit;
}
?><!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'><title>Admin Transactions - Rentile</title>
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
    <h2>All Transactions <a href='?export=csv' class='btn' style='float:right;'>Export CSV</a></h2>
    <table border='1' cellpadding='6' style='width:100%;margin-top:1rem;'>
        <tr><th>ID</th><th>Item</th><th>Renter</th><th>Owner</th><th>Date</th><th>Amount</th><th>Status</th></tr>
        <?php foreach($txs as $t): ?>
        <tr>
            <td><?php echo $t['id']; ?></td>
            <td><?php echo htmlspecialchars($t['title']); ?></td>
            <td><?php echo htmlspecialchars($t['renter']); ?></td>
            <td><?php echo htmlspecialchars($t['owner']); ?></td>
            <td><?php echo $t['rent_date']; ?></td>
            <td>$<?php echo $t['amount']; ?></td>
            <td><?php echo ucfirst($t['status']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>

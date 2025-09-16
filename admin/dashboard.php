<?php
// Admin dashboard
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require '../includes/db.php';
// Stats
$users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$listings = $conn->query("SELECT COUNT(*) FROM listings")->fetch_row()[0];
$transactions = $conn->query("SELECT COUNT(*) FROM transactions")->fetch_row()[0];
$month = date('Y-m');
$monthly_users = $conn->query("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_row()[0];
$monthly_rentals = $conn->query("SELECT COUNT(*) FROM transactions WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Rentile</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="navbar">
    <div><a href="../index.php">Rentile</a></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="listings.php">Listings</a>
        <a href="transactions.php">Transactions</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <h2>Admin Dashboard</h2>
    <div style="display:flex;gap:2rem;flex-wrap:wrap;">
        <div class="card"><b>Total Users:</b> <?php echo $users; ?></div>
        <div class="card"><b>Total Listings:</b> <?php echo $listings; ?></div>
        <div class="card"><b>Total Transactions:</b> <?php echo $transactions; ?></div>
    </div>
    <h3 style="margin-top:2rem;">Monthly Activity</h3>
    <div style="display:flex;gap:2rem;flex-wrap:wrap;">
        <div class="card"><b>Users Added This Month:</b> <?php echo $monthly_users; ?></div>
        <div class="card"><b>Rentals This Month:</b> <?php echo $monthly_rentals; ?></div>
    </div>
    <canvas id="reportChart" width="600" height="250" style="margin-top:2rem;"></canvas>
    <script>
    fetch('report_data.php').then(r=>r.json()).then(data=>{
        new Chart(document.getElementById('reportChart'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {label:'Users',data:data.users,backgroundColor:'#1976d2'},
                    {label:'Rentals',data:data.rentals,backgroundColor:'#43a047'}
                ]
            }
        });
    });
    </script>
</div>
</body>
</html>

<?php include 'includes/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentile - Rental Marketplace</title>
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
        <h1>Welcome to Rentile</h1>
        <p>Rent anything, anytime. List your items or find what you need!</p>
        <div style="margin-top:2rem;">
            <a href="listings.php" class="btn">Browse Listings</a>
            <a href="register.php" class="btn" style="margin-left:1rem;">Get Started</a>
        </div>
    </div>
</body>
</html>

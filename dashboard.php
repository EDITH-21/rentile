<?php
include 'includes/session.php';
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
// Fetch user's items
$stmt = $conn->prepare('SELECT id, title, status FROM listings WHERE user_id=? ORDER BY created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$my_items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Fetch user's rental history
$stmt = $conn->prepare('SELECT t.*, l.title FROM transactions t JOIN listings l ON t.listing_id=l.id WHERE t.renter_id=? ORDER BY t.created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$my_rentals = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Fetch marketplace listings (approved, not user's own)
$stmt = $conn->prepare('SELECT l.*, u.name FROM listings l JOIN users u ON l.user_id=u.id WHERE l.status="approved" AND l.user_id!=? ORDER BY l.created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$marketplace = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Fetch notifications for this user (owner)
$notifs = [];
$stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$notifs = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Rentile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<script>
// AJAX dismiss notification
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(function(form) {
        if (form.querySelector('input[name="dismiss_notif_id"]')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(form);
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(function(resp) {
                    if (resp.ok) {
                        // Remove the notification from the UI
                        form.closest('li').remove();
                    }
                });
            });
        }
    });
});
</script>
<body>
<div class="navbar">
    <div><a href="index.php">Rentile</a></div>
    <div>
        <a href="index.php">Home</a>
        <a href="listings.php">Listings</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <?php if ($notifs): ?>
        <div style="background:#fff3cd;color:#856404;padding:1rem 1.5rem;border-radius:6px;margin-bottom:1.5rem;">
            <b>Notifications:</b>
            <ul style="margin:0 0 0 1.2rem;">
            <?php foreach($notifs as $n): ?>
                <li style="display:flex;align-items:center;gap:0.5rem;">
                    <?php echo htmlspecialchars($n['message']); ?>
                    <?php if ($n['link']): ?><a href="<?php echo $n['link']; ?>" class="btn" style="margin-left:0.5rem;" target="_blank">View</a><?php endif; ?>
                    <form method="post" style="display:inline;margin:0;">
                        <input type="hidden" name="dismiss_notif_id" value="<?php echo $n['id']; ?>">
                        <button type="submit" style="background:none;border:none;color:#e53935;font-size:1.2rem;cursor:pointer;" title="Dismiss">&times;</button>
                    </form>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <h2>My Dashboard</h2>
    <a href="add_item.php" class="btn">Add Item for Rent</a>
    <a href="owner_rentals.php" class="btn" style="margin-left:1rem;background:#007bff;">Manage Rental Requests</a>
    <h3 style="margin-top:2rem;">My Listings</h3>
    <?php if ($my_items): foreach($my_items as $item): ?>
        <div class="card">
            <b><?php echo htmlspecialchars($item['title']); ?></b>
            <span class="status-badge <?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span>
            <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn" style="margin-top:0.5rem;">Edit</a>
        </div>
    <?php endforeach; else: ?>
        <p>No items listed yet.</p>
    <?php endif; ?>

    <h3 style="margin-top:2.5rem;">Marketplace – Rent Items</h3>
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
    <?php if ($marketplace): foreach($marketplace as $item): ?>
        <div class="card">
            <?php if ($item['image']): ?><img src="<?php echo $item['image']; ?>" style="max-width:120px;float:right;margin-left:1rem;"><?php endif; ?>
            <b><?php echo htmlspecialchars($item['title']); ?></b> <span class="status-badge verified">Approved</span><br>
            <span>Category: <?php echo htmlspecialchars($item['category']); ?></span><br>
            <span>Price: $<?php echo $item['price_per_day']; ?>/day</span><br>
            <span>Owner: <?php echo htmlspecialchars($item['name']); ?></span><br>
            <?php if(isset($_SESSION['user_id']) && $item['user_id'] != $_SESSION['user_id']): ?>
                <form method="post" action="listings.php" style="margin-top:0.5rem;display:inline;">
                    <input type="hidden" name="rent_item_id" value="<?php echo $item['id']; ?>">
                    <label>Start Date:</label>
                    <input type="date" name="rent_start_date" required>
                    <label>End Date:</label>
                    <input type="date" name="rent_end_date" required>
                    <button class="btn" type="submit">Request to Rent</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; else: ?>
        <p>No items available for rent right now.</p>
    <?php endif; ?>
    <h3 style="margin-top:2rem;">My Rental History</h3>
    <?php if ($my_rentals): foreach($my_rentals as $r): ?>
        <div class="card">
            <b><?php echo htmlspecialchars($r['title']); ?></b><br>
            Rented on: <?php echo $r['rent_date']; ?> | Amount: $<?php echo $r['amount']; ?> | Status: <?php echo ucfirst($r['status']); ?><br>
            Payment Method: <span style="color:#007bff;"><?php echo htmlspecialchars($r['payment_method'] ?? ''); ?></span>
        </div>
    <?php endforeach; else: ?>
        <p>No rentals yet.</p>
    <?php endif; ?>
</div>
</body>
</html>

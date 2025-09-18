<?php
include 'includes/session.php';
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Handle confirm/reject actions
if (isset($_POST['action'], $_POST['tx_id'])) {
    $tx_id = intval($_POST['tx_id']);
    $action = $_POST['action'];
    // Get transaction and renter
    $stmt = $conn->prepare('SELECT t.*, u.email, u.name, l.title FROM transactions t JOIN users u ON t.renter_id=u.id JOIN listings l ON t.listing_id=l.id WHERE t.id=? AND t.owner_id=?');
    $stmt->bind_param('ii', $tx_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $tx = $res->fetch_assoc();
    $stmt->close();
    if ($tx && $tx['status'] === 'pending') {
        if ($action === 'confirm') {
            $stmt = $conn->prepare('UPDATE transactions SET status="completed" WHERE id=?');
            $stmt->bind_param('i', $tx_id);
            $stmt->execute();
            $stmt->close();
            // Notify renter
            $msg = 'Your rental request for "' . $tx['title'] . '" was approved!';
            $link = 'rental_details.php?id=' . $tx_id;
            $stmt = $conn->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $tx['renter_id'], $msg, $link);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare('UPDATE transactions SET status="cancelled" WHERE id=?');
            $stmt->bind_param('i', $tx_id);
            $stmt->execute();
            $stmt->close();
            // Notify renter
            $msg = 'Your rental request for "' . $tx['title'] . '" was rejected.';
            $link = 'rental_details.php?id=' . $tx_id;
            $stmt = $conn->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $tx['renter_id'], $msg, $link);
            $stmt->execute();
            $stmt->close();
        }
        // Mark notification as read
        $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND message LIKE ?');
        $like = '%rental request%';
        $stmt->bind_param('is', $user_id, $like);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch pending rental requests for this owner
$stmt = $conn->prepare('SELECT t.*, u.name as renter_name, l.title FROM transactions t JOIN users u ON t.renter_id=u.id JOIN listings l ON t.listing_id=l.id WHERE t.owner_id=? AND t.status="pending" ORDER BY t.created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$requests = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Requests - Rentile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div><a href="index.php">Rentile</a></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container">
    <h2>Pending Rental Requests</h2>
    <?php if ($requests): foreach($requests as $r): ?>
        <div class="card">
            <b><?php echo htmlspecialchars($r['title']); ?></b><br>
            Requested by: <?php echo htmlspecialchars($r['renter_name']); ?><br>
            Date: <?php echo $r['rent_date']; ?> | Amount: $<?php echo $r['amount']; ?><br>
            <form method="post" style="margin-top:0.7rem;display:inline;">
                <input type="hidden" name="tx_id" value="<?php echo $r['id']; ?>">
                <button class="btn" name="action" value="confirm">Confirm</button>
                <button class="btn" name="action" value="reject" style="background:#e53935;">Reject</button>
            </form>
        </div>
    <?php endforeach; else: ?>
        <p>No pending rental requests.</p>
    <?php endif; ?>
</div>
</body>
</html>

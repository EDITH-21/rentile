<?php
include 'includes/session.php';
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) {
    echo '<div class="container"><h2>No rental selected.</h2></div>';
    exit;
}
$tx_id = intval($_GET['id']);
// Fetch transaction details
$stmt = $conn->prepare('SELECT t.*, l.title, l.image, u1.name as renter_name, u2.name as owner_name FROM transactions t JOIN listings l ON t.listing_id=l.id JOIN users u1 ON t.renter_id=u1.id JOIN users u2 ON t.owner_id=u2.id WHERE t.id=? AND (t.renter_id=? OR t.owner_id=?)');
$stmt->bind_param('iii', $tx_id, $user_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$tx = $res->fetch_assoc();
$stmt->close();
if (!$tx) {
    echo '<div class="container"><h2>Rental not found or you do not have access.</h2></div>';
    exit;
}

// Handle review submission
$review_msg = '';
if ($tx['status'] === 'completed' && $user_id == $tx['renter_id']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'], $_POST['comment'])) {
        $rating = intval($_POST['rating']);
        $comment = trim($_POST['comment']);
        // Check if already reviewed
        $stmt = $conn->prepare('SELECT id FROM reviews WHERE transaction_id=? AND reviewer_id=?');
        $stmt->bind_param('ii', $tx_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0 && $rating >= 1 && $rating <= 5) {
            $stmt->close();
            $stmt = $conn->prepare('INSERT INTO reviews (transaction_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iiiis', $tx_id, $user_id, $tx['owner_id'], $rating, $comment);
            $stmt->execute();
            $stmt->close();
            $review_msg = 'Thank you for your review!';
        } else {
            $review_msg = 'You have already reviewed this rental or invalid rating.';
        }
    }
    // Fetch existing review
    $stmt = $conn->prepare('SELECT * FROM reviews WHERE transaction_id=? AND reviewer_id=?');
    $stmt->bind_param('ii', $tx_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $my_review = $res->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Details - Rentile</title>
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
    <h2>Rental Details</h2>
    <div class="card">
        <b>Item:</b> <?php echo htmlspecialchars($tx['title']); ?><br>
        <?php if ($tx['image']): ?><img src="<?php echo $tx['image']; ?>" style="max-width:120px;"><br><?php endif; ?>
        <b>Renter:</b> <?php echo htmlspecialchars($tx['renter_name']); ?><br>
        <b>Owner:</b> <?php echo htmlspecialchars($tx['owner_name']); ?><br>
        <b>Rental Period:</b> <?php echo $tx['rent_start_date']; ?> to <?php echo $tx['rent_end_date']; ?><br>
        <b>Amount:</b> $<?php echo $tx['amount']; ?><br>
        <b>Status:</b> <?php echo ucfirst($tx['status']); ?><br>
        <b>Requested On:</b> <?php echo $tx['created_at']; ?><br>
    </div>
    <?php if ($tx['status'] === 'completed' && $user_id == $tx['renter_id']): ?>
        <div style="margin-top:2rem;">
            <h3>Leave a Review for the Owner</h3>
            <?php if (!empty($review_msg)): ?><div style="color:green;"><?php echo $review_msg; ?></div><?php endif; ?>
            <?php if (empty($my_review)): ?>
            <form method="post">
                <label>Rating:</label>
                <select name="rating" required>
                    <option value="">Select</option>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Terrible</option>
                </select>
                <label>Comment:</label>
                <textarea name="comment" required></textarea>
                <button class="btn" type="submit">Submit Review</button>
            </form>
            <?php else: ?>
                <div><b>Your Review:</b> <?php echo $my_review['rating']; ?>/5<br><?php echo htmlspecialchars($my_review['comment']); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>
</body>
</html>

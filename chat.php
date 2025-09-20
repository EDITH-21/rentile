<?php
include 'includes/session.php';
include 'includes/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$listing_id = isset($_GET['listing_id']) ? intval($_GET['listing_id']) : 0;
if (!$listing_id) {
    die('Listing not specified.');
}
// Get listing and owner
$stmt = $conn->prepare('SELECT l.*, u.name as owner_name, u.id as owner_id FROM listings l JOIN users u ON l.user_id=u.id WHERE l.id=?');
$stmt->bind_param('i', $listing_id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$listing) die('Listing not found.');
$owner_id = $listing['owner_id'];
// Determine chat partner
if ($user_id == $owner_id) {
    // Owner: chat with all renters who messaged
    $stmt = $conn->prepare('SELECT DISTINCT sender_id FROM messages WHERE listing_id=? AND receiver_id=?');
    $stmt->bind_param('ii', $listing_id, $owner_id);
    $stmt->execute();
    $renters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $chat_with = isset($_GET['renter_id']) ? intval($_GET['renter_id']) : ($renters[0]['sender_id'] ?? 0);
    if (!$chat_with) die('No renter to chat with.');
} else {
    // Renter: chat with owner
    $chat_with = $owner_id;
}
// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && trim($_POST['message'])) {
    $msg = trim($_POST['message']);
    $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, listing_id, message) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiis', $user_id, $chat_with, $listing_id, $msg);
    $stmt->execute();
    $stmt->close();
    header('Location: chat.php?listing_id=' . $listing_id . ($user_id == $owner_id ? '&renter_id=' . $chat_with : ''));
    exit;
}
// Fetch messages
$stmt = $conn->prepare('SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.listing_id=? AND ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)) ORDER BY m.sent_at ASC');
$stmt->bind_param('iiiii', $listing_id, $user_id, $chat_with, $chat_with, $user_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat - <?php echo htmlspecialchars($listing['title']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chat-box {background:#f9f9f9;padding:1rem;border-radius:8px;max-width:600px;margin:2rem auto;}
        .msg {margin-bottom:1rem;}
        .msg.me {text-align:right;}
        .msg .bubble {display:inline-block;padding:0.5rem 1rem;border-radius:16px;background:#e3f2fd;max-width:70%;}
        .msg.me .bubble {background:#bbdefb;}
        .chat-form {display:flex;gap:0.5rem;margin-top:1rem;}
        .chat-form input[type=text] {flex:1;padding:0.5rem;border-radius:6px;border:1px solid #ccc;}
        .chat-form button {padding:0.5rem 1.2rem;}
    </style>
</head>
<body>
<div class="navbar">
    <div><a href="index.php">Rentile</a></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="chat-box">
    <h2>Chat about: <?php echo htmlspecialchars($listing['title']); ?></h2>
    <?php if ($user_id == $owner_id && count($renters) > 1): ?>
        <form method="get" style="margin-bottom:1rem;">
            <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
            <label>Chat with renter:</label>
            <select name="renter_id" onchange="this.form.submit()">
                <?php foreach($renters as $r): ?>
                    <option value="<?php echo $r['sender_id']; ?>" <?php if($chat_with == $r['sender_id']) echo 'selected'; ?>>User #<?php echo $r['sender_id']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
    <div style="min-height:200px;">
        <?php foreach($messages as $m): ?>
            <div class="msg<?php echo $m['sender_id'] == $user_id ? ' me' : ''; ?>">
                <div class="bubble">
                    <b><?php echo htmlspecialchars($m['sender_name']); ?>:</b> <?php echo nl2br(htmlspecialchars($m['message'])); ?><br>
                    <small><?php echo $m['sent_at']; ?></small>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?>
            <div style="color:#888;">No messages yet.</div>
        <?php endif; ?>
    </div>
    <form method="post" class="chat-form">
        <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
        <button class="btn" type="submit">Send</button>
    </form>
    <a href="rental_details.php?id=<?php echo $listing_id; ?>" class="btn" style="margin-top:1rem;">Back to Rental Details</a>
</div>
</body>
</html>

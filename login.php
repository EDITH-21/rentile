<?php
include 'includes/session.php';
include 'includes/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate CAPTCHA
    if (!validate_captcha($_POST['captcha'] ?? '')) {
        $errors[] = 'Invalid CAPTCHA answer.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id, password, status FROM users WHERE email=?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $hash, $status);
            $stmt->fetch();
            if ($status === 'suspended') {
                $errors[] = 'Account suspended. Contact support.';
            } elseif (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $id;
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid credentials.';
            }
        } else {
            $errors[] = 'Invalid credentials.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Rentile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div><a href="index.php">Rentile</a></div>
    <div>
        <a href="index.php">Home</a>
        <a href="listings.php">Listings</a>
        <a href="register.php">Register</a>
    </div>
</div>
<div class="container">
    <h2>Login</h2>
    <?php if (isset($_GET['registered'])): ?>
        <div style="color:green;">Registration successful! Please login.</div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div style="color:red;">
            <?php foreach($errors as $e) echo '<div>'.$e.'</div>'; ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <label>CAPTCHA: <?php echo generate_captcha(); ?></label>
        <input type="text" name="captcha" required>
        <button class="btn" type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register</a></p>
</div>
</body>
</html>

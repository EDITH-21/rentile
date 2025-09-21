<?php
// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic CAPTCHA generation and validation functions
function generate_captcha() {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_answer'] = $num1 + $num2;
    return "What is $num1 + $num2?";
}

function validate_captcha($input) {
    return isset($_SESSION['captcha_answer']) && intval($input) === $_SESSION['captcha_answer'];
}
?>

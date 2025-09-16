<?php
// Returns JSON for monthly report chart
require '../includes/db.php';
$labels = [];
$users = [];
$rentals = [];
for ($i=5; $i>=0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $labels[] = $month;
    $users[] = $conn->query("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_row()[0];
    $rentals[] = $conn->query("SELECT COUNT(*) FROM transactions WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetch_row()[0];
}
echo json_encode(['labels'=>$labels,'users'=>$users,'rentals'=>$rentals]);

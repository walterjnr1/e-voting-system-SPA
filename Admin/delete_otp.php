<?php 
include '../database/connection.php';
include('../inc/app_data.php'); // if log_activity() is inside here
if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "DELETE FROM otps WHERE id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$id]);

    // log activity
    $user_id = $_SESSION['user_id']; // current logged-in user
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    if (function_exists('log_activity')) {
        log_activity($dbh, $user_id, "Deleted OTP", 'otps', $id, $ip_address);
    }

    // success message
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'OTP deleted successfully!'];
    header("Location: otp-records");
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid OTP ID!'];
    header("Location: otp-records");
    exit;
}

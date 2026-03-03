<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) { exit; }

if (isset($_GET['id'])) {
    $target_id = $_GET['id'];
    
    // First, get current status
    $stmt = $dbh->prepare("SELECT is_verified FROM users WHERE id = ? AND role != 'eleco'");
    $stmt->execute([$target_id]);
    $user = $stmt->fetch();

    if ($user) {
        $new_status = ($user['is_verified'] == 1) ? 0 : 1;
        $update = $dbh->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
        $update->execute([$new_status, $target_id]);
        
        $msg = ($new_status == 1) ? "Account enabled successfully." : "Account disabled successfully.";
        $_SESSION['toast'] = ['type' => 'success', 'message' => $msg];
        
        log_activity($dbh, $user_id, "Status Update: Toggled status for User ID: $target_id", $ip_address);
    }
}
header("Location: user-record");
exit;
<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) { exit; }

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Prevent deleting admins
    $stmt = $dbh->prepare("DELETE FROM users WHERE id = ? AND role != 'eleco'");
    if ($stmt->execute([$user_id])) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'User deleted permanently.'];
        log_activity($dbh, $user_id, "User Deletion User ID: $user_id", $ip_address);
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to delete user.'];
    }
}
header("Location: user-record");
exit;
<?php 
include '../database/connection.php';
include('../inc/app_data.php'); 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "DELETE FROM marquee_notifications WHERE id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$id]);

    // log activity
    if (function_exists('log_activity')) {
        log_activity($dbh, $user_id, "Deleted Marquee notifications", 'marquee_notifications', $id, $ip_address);
    }

    // success message
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Marquee Notifications deleted successfully!'];
    header("Location: marquee-notification-records");
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid ID!'];
    header("Location: marquee-notification-records");
    exit;
}

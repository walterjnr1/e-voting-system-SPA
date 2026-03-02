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
    $sql = "DELETE FROM demo_requests WHERE id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$id]);

    // log activity
    if (function_exists('log_activity')) {
        log_activity($dbh, $user_id, "Deleted demo requests", 'demo_requests', $id, $ip_address);
    }

    // success message
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Demo Request deleted successfully!'];
    header("Location: demo-records");
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid Demo ID!'];
    header("Location: demo-records");
    exit;
}

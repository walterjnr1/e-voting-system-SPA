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
    $sql = "DELETE FROM subjects WHERE id = ?";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$id]);

    // log activity
    if (function_exists('log_activity')) {
        log_activity($dbh, $user_id, "Deleted subject", 'subjects', $id, $ip_address);
    }

    // success message
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'subject deleted successfully!'];
    header("Location: subject-records");
    exit;
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid subject ID!'];
    header("Location: subject-records");
    exit;
}

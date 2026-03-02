<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  
    header("Location: ../login");
    exit;
}

// ✅ Log activity as logout
$action = "User logged out on $current_date";
log_activity($dbh, $user_id, $action, 'users', $user_id, $ip_address);

// ✅ Destroy session and redirect
session_unset();
session_destroy();

header("Location: ../login");
exit;

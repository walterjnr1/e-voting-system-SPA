<?php
//include('inc/app_data.php');
include 'database/connection.php'; 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  
    header("Location: login");
    exit;
}

// ✅ Log activity as logout
        if (function_exists('log_activity')) {
            log_activity($dbh, $user_id,'User logged out on $current_date',  $ip_address);
        }

// ✅ Destroy session and redirect
session_unset();
session_destroy();

header("Location: login");
exit;

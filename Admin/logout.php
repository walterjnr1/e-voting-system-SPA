<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  
    header("Location: ../login");
    exit;
}

$session_token = $_SESSION['session_token'] ?? null;
$user_id       = $_SESSION['user_id'];

 // Update logout_time in voter_sessions table
    if ($session_token) {
        
        $updateSession = $dbh->prepare("UPDATE voter_sessions SET logout_time = NOW() WHERE session_token = ? AND user_id = ? AND logout_time IS NULL");
        $updateSession->execute([$session_token, $user_id]);
    }

// ✅ Log activity as logout
        if (function_exists('log_activity')) {
            log_activity($dbh, $user_id,'User logged out on $current_date',  $ip_address);
        }

// ✅ Destroy session and redirect
session_unset();
session_destroy();

header("Location: ../login");
exit;

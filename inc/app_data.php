<?php 
session_start([
  'cookie_httponly' => true,
  'cookie_secure'  => true, // HTTPS only
  'use_strict_mode'=> true
]);

error_reporting(1);
include('../database/connection.php'); 
include('../config/activity_log_function.php'); 
include('../config/email_dashboard.php'); // ✅ add this line

// 2. Timezone & Globals
date_default_timezone_set('Africa/Lagos');
$current_date = date('Y-m-d H:i:s');
$ip_address = $_SERVER['REMOTE_ADDR'];

// 3. Website Settings (Moved outside so they are available even when logged out)
$stmt = $dbh->query("SELECT * FROM website_settings LIMIT 1");
$row_website = $stmt->fetch();
$app_name      = $row_website['site_name'];
$app_email     = $row_website['site_email'];
$app_logo      = $row_website['logo'];

// 3. election data 
$stmt = $dbh->query("SELECT * FROM elections LIMIT 1");
$row_election= $stmt->fetch();
$title      = $row_election['title'];

// 4. Inactivity & Alert Logic
$timeout_duration = 900; // Corrected to 15 minutes (900s)

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION["user_id"];

    // Check for inactivity
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        
        // Log activity before destroying
        if (function_exists('log_activity')) {
            log_activity($dbh, $user_id,'System auto-logout due to inactivity',  $ip_address);
        }
        
        session_unset();
        session_destroy();
        
        // Redirect with reason
        //header("Location: e_voting/login?reason=timeout");
       header("Location: ../login?reason=timeout");

        exit;
    }
    
    // Update activity timestamp
    $_SESSION['last_activity'] = time();

    // Fetch Logged-in User Data
    $stmt = $dbh->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row_user = $stmt->fetch();
    $role = $row_user['role'] ?? '';

} else {
    // If NOT logged in, check if we just got redirected by a timeout to set the alert
    if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
      echo "<script>alert('Error: Logged out due to 15 minutes of inactivity!');</script>";

    }
}

// 5. Statistics (Only fetch if needed for dashboard performance)
if (isset($role) && ($role === 'admin' || $role === 'eleco')) {
    $totalUsers = $dbh->query("SELECT COUNT(*) FROM users WHERE role!='admin'")->fetchColumn();
    $totalstudents = $dbh->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $totalteachers = $dbh->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
    $totalparents = $dbh->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
}
?>
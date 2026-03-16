<?php
 session_start();

error_reporting(1);
include('../database/connection.php'); 
include('../config/activity_log_function.php'); 
include('../config/email_dashboard.php'); // ✅ add this line

// 2. Timezone & Globals
date_default_timezone_set('Africa/Lagos');
$current_date = date('Y-m-d H:i:s');
$ip_address = $_SERVER['REMOTE_ADDR'];



$user_id = $_SESSION['user_id'];
// 3. Website Settings (Moved outside so they are available even when logged out)
$stmt = $dbh->query("SELECT * FROM website_settings LIMIT 1");
$row_website = $stmt->fetch();
$app_name      = $row_website['site_name'];
$app_email     = $row_website['site_email'];
$app_logo      = $row_website['logo'];

// 3. election data 
$stmt = $dbh->query("SELECT id, title FROM elections ORDER BY created_at DESC LIMIT 1");
$row_election= $stmt->fetch();
$title            = $row_election['title'];
$election_id      = $row_election['id'];


    // Fetch Logged-in User Data
    $stmt = $dbh->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row_user = $stmt->fetch();
    $role = $row_user['role'] ?? '';




// 5. Statistics (Only fetch if needed for dashboard performance)
if (isset($role) && ($role === 'admin' || $role === 'eleco')) {
    $totalUsers = $dbh->query("SELECT COUNT(*) FROM users WHERE role!='admin'")->fetchColumn();
    $totalstudents = $dbh->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $totalteachers = $dbh->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
    $totalparents = $dbh->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
}

define('ENCRYPTION_KEY', 'Escobar2012@@'); // Keep this secret
define('ENCRYPTION_IV', '1234567890123456'); // 16 chars for AES-256-CTR


?>

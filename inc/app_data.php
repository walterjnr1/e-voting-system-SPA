<?php 
//prevent session hijacking
session_start([
  'cookie_httponly' => true,
  'cookie_secure'  => true, // HTTPS only
  'use_strict_mode'=> true
]);

error_reporting(1);
include('../database/connection.php'); 
include('../config/activity_log_function.php'); 
include('../config/email_dashboard.php'); // ✅ add this line

//set time
date_default_timezone_set('Africa/Lagos');
$current_date = date('Y-m-d H:i:s');


//fetch user data
$user_id = $_SESSION["user_id"];
$stmt = $dbh->query("SELECT * FROM users where id='$user_id'");
$row_user = $stmt->fetch();
$role = $row_user['role'];

//website settings
$stmt = $dbh->query("SELECT * FROM website_settings");
$row_website = $stmt->fetch();
$app_name= $row_website['site_name'] ;
$app_email = $row_website['site_email'] ;
$whatsapp_phone = $row_website['whatsapp_phone'] ;
$app_logo = $row_website['logo'] ;
$app_url = $row_website['site_url'] ;
$paystack_secret_key = $row_website['paystack_secret_key'] ;
$paystack_public_key = $row_website['paystack_public_key'] ;
$OPENAI_API_KEY =  $row_website['open_ai_key'] ;
$twitter_id = $row_website['twitter_id'] ;
$facebook_id = $row_website['facebook_id'] ;
$instagram_id = $row_website['instagram_id'] ;


//admin data
$stmt = $dbh->query("SELECT COUNT(*) AS total_users FROM users where role!='admin'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalUsers = $row['total_users'];

$stmt = $dbh->query("SELECT COUNT(*) AS total_students FROM users where role='student'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalstudents = $row['total_students'];

$stmt = $dbh->query("SELECT COUNT(*) AS total_teachers FROM users where role='teacher'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalteachers = $row['total_teachers'];

$stmt = $dbh->query("SELECT COUNT(*) AS total_parents FROM users where role='parent'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalparents = $row['total_parents'];

?>

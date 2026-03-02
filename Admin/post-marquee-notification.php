<?php
include('../inc/app_data.php');
include '../database/connection.php'; 
include 'hasFeature.php'; // Assuming sendEmail and log_activity are here

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title     = trim($_POST['title'] ?? '');
    $notif_msg = trim($_POST['message'] ?? ''); 
    $days      = (int)($_POST['days'] ?? 0);
    $start_at  = trim($_POST['start_at'] ?? '');

    if (empty($title) || empty($notif_msg) || empty($start_at) || $days < 1) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Please fill in all fields correctly!'];
    } else {
        try {
            $start_at_fmt = date('Y-m-d H:i:s', strtotime($start_at));
            $expire_at = date('Y-m-d H:i:s', strtotime("+$days days", strtotime($start_at_fmt)));

            $checkSql = "SELECT COUNT(*) FROM marquee_notifications WHERE title = :title AND expire_at > NOW()";
            $checkStmt = $dbh->prepare($checkSql);
            $checkStmt->execute([':title' => $title]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'An active notification with this title already exists!'];
            } else {
                $created_at = date('Y-m-d H:i:s');
                $sql = "INSERT INTO marquee_notifications (title, message, start_at, created_at, expire_at) 
                        VALUES (:title, :message, :start_at, :created_at, :expire_at)";
                
                $stmt = $dbh->prepare($sql);
                $stmt->execute([
                    ':title'      => $title,
                    ':message'    => $notif_msg,
                    ':start_at'   => $start_at_fmt,
                    ':created_at' => $created_at,
                    ':expire_at'  => $expire_at
                ]);

                $notif_id = $dbh->lastInsertId();

                // ✅ PREPARE EMAIL CONTENT USING EXTERNAL CSS
                $css_content = file_exists('assets/css/email_template.css') ? file_get_contents('assets/css/email_template.css') : '';
                
                // Social Icons (Using standard Flaticon CDN links)
                $fb_icon = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                $tw_icon = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                $ig_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";

                $userStmt = $dbh->prepare("SELECT name, email FROM users WHERE status = '1'");
                $userStmt->execute();
                $all_users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

                $subject = "📢 New Announcement: " . $title;

                foreach ($all_users as $user) {
                    $email_body = '
                    <html>
                    <head>
                        <style>'.$css_content.'</style>
                    </head>
                    <body class="email-body">
                        <div class="email-container">
                            <div class="email-header">
                                <h2>ANNOUNCEMENT</h2>
                            </div>
                            <div class="email-content">
                                <p>Hello <strong>'.htmlspecialchars($user['name']).'</strong>,</p>
                                <h3 style="color: #0b0932;">'.$title.'</h3>
                                <p>We have a new update for you. Please find the details of the announcement below:</p>
                                
                                <div class="info-box">
                                    '.nl2br(htmlspecialchars($notif_msg)).'
                                </div>

                                <p>Effective from: <strong>'.date('jS M, Y H:i', strtotime($start_at_fmt)).'</strong></p>
                            </div>
                            <div class="email-footer">
                                <div class="social-icons">
                                    <a href="https://facebook.com/'.$facebook_id.'"><img src="'.$fb_icon.'" width="24" height="24"></a>
                                    <a href="https://twitter.com/'.$twitter_id.'"><img src="'.$tw_icon.'" width="24" height="24"></a>
                                    <a href="https://instagram.com/'.$instagram_id.'"><img src="'.$ig_icon.'" width="24" height="24"></a>
                                </div>
                                <p>
                                    Support: '.$whatsapp_phone.' | Email: '.$app_email.'<br>
                                    &copy; '.date('Y').' '.htmlspecialchars($app_name).'. All rights reserved.
                                </p>
                            </div>
                        </div>
                    </body>
                    </html>';

                    sendEmail($user['email'], $subject, $email_body);
                }

                if (function_exists('log_activity')) {
                    log_activity($dbh, $_SESSION['user_id'], "added Marquee Notification", 'marquee_notifications', $notif_id, $_SERVER['REMOTE_ADDR']);
                }

                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Notification posted and users notified!'];
                header("Location: post-marquee-notification");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['toast'] = [ 'type' => 'error', 'message' => 'Database error!' ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Marquee Notification | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <style>
        .hidden { display: none; }
        .style1 { color: #000000; font-weight: 500; }
    </style>
</head>
<body>

<div class="d-flex">
  <nav id="sidebar" class="d-flex flex-column p-3">
    <?php include('partials/sidebar.php'); ?>
  </nav>

  <div id="content" class="flex-grow-1">
    <div class="navbar-custom d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
        <h5>Post Marquee Notification</h5>
      </div>
      <div>
        <a href="logout" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
      </div>
    </div>

    <div class="container-fluid">
        <div class="card mt-4 mb-4 shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i> New Notification (Mobile App Broadcast)</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="marqueeForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label style1">Notification Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Short catchy title" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label style1">Notification Message</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Detailed announcement message..." required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label style1">Start Date & Time</label>
                            <input type="datetime-local" name="start_at" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label style1">Display Duration (Days)</label>
                            <input type="number" name="days" class="form-control" min="1" value="7" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                            <span id="btnText"><i class="fas fa-paper-plane me-2"></i> Post & Notify Users</span>
                            <span id="btnSpinner" class="hidden"><i class="fas fa-spinner fa-spin me-2"></i> Sending Emails...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mb-4">
            <a href="marquee-notification-records" class="btn btn-light border">
                <i class="fas fa-history me-1"></i> View Notification Records
            </a>
        </div>
    </div>
  </div>
</div>

<footer><?php include('partials/footer.php'); ?></footer>
<?php include('partials/sweetalert.php'); ?>

<script>
    document.getElementById('marqueeForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        // Disable button and show spinner
        btn.disabled = true;
        text.classList.add('hidden');
        spinner.classList.remove('hidden');
    });
</script>

<?php include('partials/toogle-down.php'); ?>
</body>
</html>
<?php 
include('../inc/app_data.php');
include '../database/connection.php';

// Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

$user_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// --- PHP PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
// --- SECURITY: CSRF VALIDATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: change-password");
        exit;
    }  
$old_password = htmlspecialchars(trim($_POST['old_password']));
    $new_password = htmlspecialchars(trim($_POST['new_password']));
    $confirm_password = htmlspecialchars(trim($_POST['confirm_password']));

    // 1. Validation: Check if new passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'New passwords do not match!'];
    } else {
        // 2. Fetch current hashed password from DB
        $stmt = $dbh->prepare("SELECT password, full_name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // 3. Verify Old Password
        if ($user && password_verify($old_password, $user['password'])) {
            
            // 4. Hash New Password & Update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $dbh->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $update->execute([$hashed_password, $user_id]);

            if ($result) {
                // --- EMAIL NOTIFICATION ---
                $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";
                
                $css = file_exists('assets/css/email_template.css') ? file_get_contents('assets/css/email_template.css') : '';
                
                $subject = "Security Alert: Password Changed - $app_name";
                $message = "
                <html>
                <head><style>{$css}</style></head>
                <body class='email-body'>
                    <div class='email-container'>
                        <div class='email-header'><h2>Security Notification</h2></div>
                        <div class='email-content'>
                            <p>Hello <strong>".htmlspecialchars($user['full_name'])."</strong>,</p>
                            <p>This is a confirmation that the password for your <strong>$app_name</strong> account was recently changed.</p>
                            
                            <div class='info-box' style='border-left: 4px solid #0a192f; background: #f8f9fa; padding: 15px;'>
                                <strong>Change Details:</strong><br>
                                Status: Successfully Updated<br>
                                Time: ".date('F j, Y, g:i a')."<br>
                                IP Address: ".$ip_address."
                            </div>

                            <p>If you performed this action, you can safely ignore this email. No further action is required.</p>
                            
                            <div class='alert-box' style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>
                                <strong>Important:</strong> If you did NOT change your password, please contact our support team immediately or reset your password to secure your account.
                            </div>
                        </div>
                        <div class='email-footer'>
                            <div class='social-icons'>
                                <a href='#'><img src='".$facebook_icon."' width='24'></a>
                                <a href='#'><img src='".$twitter_icon."' width='24'></a>
                                <a href='#'><img src='".$whatsapp_icon."' width='24'></a>
                            </div>
                            <p>&copy; ".date('Y')." ".htmlspecialchars($app_name).". All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>";
                
                if(function_exists('sendEmail')) {
                    sendEmail($user['email'], $subject, $message);
                }
                
                log_activity($dbh, $user_id, "Password Changed", $ip_address);

                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Password updated successfully!'];
                 // Regenerate CSRF for next action
                    unset($_SESSION['csrf_token']);
                header("Location: change-password");
                exit;
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Current password is incorrect!'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
</head>
<body>

<div id="sidebar-overlay"></div>

<div class="d-flex">
    <nav id="sidebar" class="d-flex flex-column p-3 shadow">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <div id="content" class="flex-grow-1">
        <div class="navbar-custom d-flex justify-content-between align-items-center sticky-top">
            <?php include('partials/navbar.php');?>
        </div>

        <div class="p-3 p-md-4">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">Security Settings</h4>
                <a href="dashboard" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-xl-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-lock me-2"></i>Update Password</h6>
                        </div>
                        <div class="card-body p-4">
                            <form action="" method="POST" id="passwordForm" class="needs-validation" novalidate>
                                                         <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="old_password" class="form-control" placeholder="Enter current password" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Minimum 8 characters" minlength="8" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat new password" required>
                                    </div>

                                    <div class="col-12 text-end">
                                        <hr class="my-4">
                                        <button type="submit" name="change_password" id="submitBtn" class="btn btn-primary px-5" style="background-color: #0a192f; border: none;">
                                            <span id="btnText"><i class="fas fa-save me-2"></i>Update Password</span>
                                            <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="main-footer text-center mt-5 py-3">
        <?php include('partials/footer.php'); ?>
        </footer>
        </div>
    </div>
     
</div>


<?php include('partials/sweetalert.php'); ?>

<script>
    document.getElementById('passwordForm').addEventListener('submit', function (event) {
        const form = event.target;
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;
        
        // Custom Check: Passwords Match
        if (newPass !== confirmPass) {
            alert("New passwords do not match!");
            event.preventDefault();
            return;
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        btn.style.pointerEvents = 'none'; 
        btn.style.opacity = '0.8';
        text.classList.add('d-none');
        spinner.classList.remove('d-none');
    });
</script>

</body>
</html>
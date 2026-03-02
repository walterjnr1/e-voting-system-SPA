<?php 
include('../inc/app_data.php');
include '../database/connection.php';
include 'hasFeature.php'; // Assuming sendEmail and log_activity are here

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user info
$stmt = $dbh->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    $_SESSION['toast'] = ['type'=>'error','message'=>'User not found!'];
    header("Location: login");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])){
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if(!password_verify($current_password, $user['password'])){
        $_SESSION['toast'] = ['type'=>'error','message'=>'Current password is incorrect.'];
    }
    elseif(strlen($new_password) < 8){
        $_SESSION['toast'] = ['type'=>'error','message'=>'New password must be at least 8 characters.'];
    }
    elseif($new_password !== $confirm_password){
        $_SESSION['toast'] = ['type'=>'error','message'=>'Confirm password does not match.'];
    }
    else{
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Updated with updated_at field
        $update = $dbh->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $result = $update->execute([$hashed_password, $user_id]);

        if($result){
            // ✅ Social Media Icons for Email
            $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
            $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
            $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
            $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";

            // ✅ Load External CSS for Email
            $css = file_exists('assets/css/email_template.css') ? file_get_contents('assets/css/email_template.css') : '';
            
            $subject = "Security Alert: Password Updated Successfully";
            $message = "
            <html>
            <head><style>{$css}</style></head>
            <body class='email-body'>
                <div class='email-container'>
                    <div class='email-header'><h2>Password Security Update</h2></div>
                    <div class='email-content'>
                        <p>Hello <strong>".htmlspecialchars($user['name'])."</strong>,</p>
                        <p>Your account password for <strong>".htmlspecialchars($app_name)."</strong> was recently changed successfully.</p>
                        
                        <div class='info-box'>
                            <strong>Change Details:</strong><br>
                            Date: ".date('Y-m-d H:i:s')." UTC<br>
                            New Password: <strong>".htmlspecialchars($new_password)."</strong><br>
                            IP Address: ".htmlspecialchars($_SERVER['REMOTE_ADDR'])."
                        </div>

                        <div class='alert-box'>
                            <strong>Important:</strong> If you did not authorize this change, please contact us immediately via WhatsApp at ".htmlspecialchars($whatsapp_phone)." or email ".htmlspecialchars($app_email).".
                        </div>
                    </div>
                    <div class='email-footer'>
                        <div class='social-icons'>
                            <a href='https://facebook.com/".htmlspecialchars($facebook_id ?? '')."'><img src='".$facebook_icon."' width='24'></a>
                            <a href='https://twitter.com/".htmlspecialchars($twitter_id ?? '')."'><img src='".$twitter_icon."' width='24'></a>
                            <a href='https://instagram.com/".htmlspecialchars($instagram_id ?? '')."'><img src='".$instagram_icon."' width='24'></a>
                            <a href='https://wa.me/".preg_replace('/[^0-9]/', '', $whatsapp_phone ?? '')."'><img src='".$whatsapp_icon."' width='24'></a>
                        </div>
                        <p>
                            &copy; ".date('Y')." ".htmlspecialchars($app_name).". All rights reserved.<br>
                            ".htmlspecialchars($app_email)."
                        </p>
                    </div>
                </div>
            </body>
            </html>";

            sendEmail($user['email'], $subject, $message);
            log_activity($dbh, $user_id, "changed password", 'users', $user_id, $_SERVER['REMOTE_ADDR']);

            $_SESSION['toast'] = ['type'=>'success','message'=>'Password updated successfully!'];
            header("Location: change-password"); // Or change-password.php
            exit;
        } else {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Failed to update password.'];
        }
    }
    header("Location: change-password");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password | <?= htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        .hidden { display: none; }
        .password-container { position: relative; }
        .password-toggle { position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer; color: #6c757d; }
    .style1 {color: #000000}
    </style>
</head>
<body>
<div class="d-flex">
    <nav id="sidebar"><?php include('partials/sidebar.php'); ?></nav>

    <div id="content" class="d-flex flex-column">
        <div class="navbar-custom d-flex justify-content-between align-items-center p-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div><a href="logout" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></div>
        </div>

        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm p-4 border-0">
                        <form method="POST" id="passwordForm">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="mb-3">
                                <label class="form-label style1">Current Password</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" name="current_password" id="curr" required>
                                    <i class="fa-solid fa-eye password-toggle" data-target="curr"></i>
                                </div>
                          </div>
                            <div class="mb-3">
                                <label class="form-label style1">New Password</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" name="new_password" id="newp" required>
                                    <i class="fa-solid fa-eye password-toggle" data-target="newp"></i>
                                </div>
                          </div>
                            <div class="mb-3">
                                <label class="form-label style1">Confirm Password</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" name="confirm_password" id="conf" required>
                                    <i class="fa-solid fa-eye password-toggle" data-target="conf"></i>
                                </div>
                          </div>
                            <button type="submit" name="update_password" class="btn btn-primary w-100" id="submitBtn">
                                <span id="btnText">Update Password</span>
                                <span id="btnSpinner" class="hidden"><i class="fas fa-spinner fa-spin me-2"></i>Processing...</span>
                            </button>
                        </form>
                    </div>
                    
                    <div class="mt-3 text-center text-muted small">
                        <strong>Last Updated:</strong> 
                        <?= $user['updated_at'] ? date('M d, Y - h:i A', strtotime($user['updated_at'])) : 'Never'; ?>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-auto"><?php include('partials/footer.php'); ?></footer>
    </div>
</div>

<?php include('partials/sweetalert.php'); ?>
<?php include('partials/toogle-down.php'); ?>

<script>
// --- Spinner Logic ---
document.getElementById("passwordForm").addEventListener("submit", function(e) {
    const btn = document.getElementById("submitBtn");
    const text = document.getElementById("btnText");
    const spinner = document.getElementById("btnSpinner");

    text.classList.add("hidden");
    spinner.classList.remove("hidden");
    btn.style.pointerEvents = "none"; // Prevent double clicking without breaking POST
});

// --- Password Toggle Logic ---
document.querySelectorAll('.password-toggle').forEach(el => {
    el.addEventListener('click', function() {
        const target = document.getElementById(this.getAttribute('data-target'));
        const isPass = target.type === 'password';
        target.type = isPass ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
});
</script>
</body>
</html>
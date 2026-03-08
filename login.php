<?php 
include 'database/connection.php';
include('inc/app_data.php');
require 'email_vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CSRF TOKEN GENERATION ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    // CSRF VALIDATION
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $device_name = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';

    if ($email && !empty($password)) {
        try {
            // 1. Fetch User Data
            $stmt = $dbh->prepare("SELECT id, full_name, email, password, role, is_verified, status FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Validate Credentials
            if (!$user || !password_verify($password, $user['password'])) {
                $error = "Invalid email or password.";
                
                // Log Failed Login to table
                $failed_uid = $user ? $user['id'] : null;
                $logFailed = $dbh->prepare("INSERT INTO failed_login (user_id, ip_address, attempt_time) VALUES (?, ?, NOW())");
                $logFailed->execute([$failed_uid, $ip_address]);

            } elseif ($user['status'] === 'suspended') {
                $error = "Your account has been suspended. Please contact support.";
            } elseif ((int)$user['is_verified'] !== 1) {
                // SPECIFIC VERIFICATION ERROR
                $error = "Your account has not yet been verified by Eleco. Please wait for approval.";
            } else {
                // 3. SUCCESSFUL LOGIN - INITIALIZE SESSION
                $user_id = $user['id'];
                $session_token = bin2hex(random_bytes(32));
                
                // Update Last Login
                $upd = $dbh->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $upd->execute([$user_id]);

                // Record in voter_sessions
                $sess = $dbh->prepare("INSERT INTO voter_sessions (user_id, ip_address, device_name, session_token, login_time, created_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $sess->execute([$user_id, $ip_address, $device_name, $session_token]);

                // Record in audit_logs
                $audit = $dbh->prepare("INSERT INTO audit_logs (user_id, action, ip_address, created_at) VALUES (?, ?, ?, NOW())");
                $audit->execute([$user_id, "Successful Login Attempt (Awaiting OTP)", $ip_address]);

                // 4. GENERATE 5-DIGIT OTP
                $otp_code = rand(10000, 99999);
                $dbh->prepare("DELETE FROM otps WHERE user_id = ?")->execute([$user_id]);
                $insOtp = $dbh->prepare("INSERT INTO otps (user_id, code, created_at) VALUES (?, ?, NOW())");
                $insOtp->execute([$user_id, $otp_code]);

                // 5. SEND OTP EMAIL
                try {
                    $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                    $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                    $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
                    $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";

                    $htmlMessage = '
                    <div style="background:#f0f2f5; padding:20px; font-family:sans-serif;">
                        <div style="max-width:600px; margin:auto; background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden;">
                            <div style="background:#1e3a8a; color:#fff; padding:30px; text-align:center;">
                                <h2 style="margin:0;">Identity Verification</h2>
                            </div>
                            <div style="padding:40px; color:#334155; line-height:1.6; text-align:center;">
                                <p>Hello <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                                <p>You are receiving this because a login was attempted on your account. Enter the code below to continue:</p>
                                <div style="background:#f8fafc; border:1px solid #cbd5e1; display:inline-block; padding:15px 30px; margin:20px 0; font-size:32px; font-weight:bold; letter-spacing:8px; color:#1e3a8a; border-radius:8px;">
                                    ' . $otp_code . '
                                </div>
                                <p style="color:#ef4444; font-size:14px;"><strong>Note:</strong> This code expires in 15 minutes.</p>
                            </div>
                            <div style="background:#f1f5f9; padding:20px; text-align:center;">
                                <div style="margin-bottom:10px;">
                                    <a href="#"><img src="'.$facebook_icon.'" width="24" style="margin:0 5px;"></a>
                                    <a href="#"><img src="'.$twitter_icon.'" width="24" style="margin:0 5px;"></a>
                                    <a href="#"><img src="'.$instagram_icon.'" width="24" style="margin:0 5px;"></a>
                                    <a href="#"><img src="'.$whatsapp_icon.'" width="24" style="margin:0 5px;"></a>
                                </div>
                                <p style="font-size:11px; color:#94a3b8;">&copy; ' . date("Y") . ' ' . $app_name . ' | Secure Voting Portal</p>
                            </div>
                        </div>
                    </div>';

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'newleastpaysolution@gmail.com'; 
                    $mail->Password   = 'swhayyxzazfdcmif'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    $mail->setFrom($app_email, $app_name);
                    $mail->addAddress($user['email']);
                    $mail->isHTML(true);
                    $mail->Subject = "Login OTP - " . $app_name;
                    $mail->Body    = $htmlMessage;
                    $mail->send();
                } catch (Exception $e) { }

                // 6. Set Sessions and Redirect
                $_SESSION['temp_user_id'] = $user_id;
                $_SESSION['full_name']    = $user['full_name'];
                $_SESSION['email']        = $user['email'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['session_token']= $session_token;

                header("Location: otp_verify");
                exit;
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again later.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-sans">

    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
            
            <div class="bg-[#1e3a8a] p-8 text-center text-white">
                <i class="fas fa-fingerprint text-4xl mb-3"></i>
                <h2 class="text-2xl font-bold uppercase tracking-tight">Secure Login</h2>
                <p class="text-blue-200 text-xs">Voter Authentication Portal</p>
            </div>

            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm flex items-start">
                    <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="loginForm" class="p-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all" 
                               placeholder="john@example.com">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-gray-700 text-sm font-bold">Password</label>
                        <a href="forgot_password" class="text-xs text-blue-600 hover:underline">Forgot?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all" 
                               placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" name="login_btn" id="submitBtn"
                        class="w-full bg-[#1e3a8a] text-white font-bold py-4 rounded-xl shadow-lg hover:bg-blue-800 active:scale-95 transition-all flex justify-center items-center">
                    <span id="btnText">Continue <i class="fas fa-shield-alt ml-2"></i></span>
                    <div id="btnSpinner" class="hidden animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                </button>

                <p class="text-center text-sm text-gray-600">
                    New voter? <a href="voter_signup" class="text-blue-700 font-bold hover:underline">Create an account</a>
                </p>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');

            text.classList.add('hidden');
            spinner.classList.remove('hidden');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.8';
        });
    </script>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <?php include('footer.php'); ?>
    </footer>
</body>
</html>
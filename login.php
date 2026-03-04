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
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $device_name = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';

    if ($email && !empty($password)) {
        try {
            // Fetch User
            $stmt = $dbh->prepare("SELECT id, full_name, email, password, role, is_verified FROM users WHERE email = :ue LIMIT 1");
            $stmt->execute([':ue' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // --- FAILED LOGIN LOGIC ---
            if (!$user || !password_verify($password, $user['password'])) {
                $error = "Invalid email or password.";
                
                // Save to failed_login table
                $failed_uid = $user ? $user['id'] : null;
                $logFailed = $dbh->prepare("INSERT INTO failed_login (user_id, ip_address, attempt_time) VALUES (?, ?, NOW())");
                $logFailed->execute([$failed_uid, $ip_address]);

            } elseif ((int)$user['is_verified'] !== 1) {
                $error = "Your account is not verified. Contact Eleco";
                        } else {
                // --- SUCCESS LOGIC ---
                session_regenerate_id(true);
                $user_id = $user['id'];

                // Update last login in users table
                $update = $dbh->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->execute([$user_id]);

                // Save to logins table
                $logSuccess = $dbh->prepare("INSERT INTO logins (user_id, ip_address, device_name, created_at) VALUES (?, ?, ?, NOW())");
                $logSuccess->execute([$user_id, $ip_address, $device_name]);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                // --- SEND LOGIN NOTIFICATION EMAIL ---
                try {
                    $css_path  = 'assets/css/email_template.css';
                    $email_css = file_exists($css_path) ? file_get_contents($css_path) : '';
                    
                    $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                    $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                    $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
                    $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";

                    $subject = "Security Alert: New Login Detected";
                    $current_time = date('M d, Y h:i A');

                    $htmlMessage = '
                    <html>
                    <head><style>' . $email_css . '</style></head>
                    <body class="email-body" style="background-color: #f0f2f5; font-family: sans-serif; padding: 20px;">
                        <div class="email-container" style="max-width: 600px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                            <div class="email-header" style="background-color: #0a192f; color: #fff; padding: 30px; text-align: center;">
                                <h2 style="margin:0;">Login Notification</h2>
                            </div>
                            <div class="email-content" style="padding: 30px; color: #334155; line-height: 1.6;">
                                <p>Hello <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                                <p>A successful login was just recorded for your <strong>' . htmlspecialchars($app_name) . '</strong> account.</p>
                                <div class="info-box" style="background: #f8fafc; border-left: 4px solid #1e3a8a; padding: 15px; margin: 20px 0;">
                                    <strong>Details:</strong><br>
                                    Time: ' . $current_time . '<br>
                                    IP Address: ' . htmlspecialchars($ip_address) . '<br>
                                    Device: ' . htmlspecialchars($device_name) . '
                                </div>
                                <p style="font-size: 14px; color: #64748b;">If this was not you, please secure your account by changing your password immediately.</p>
                            </div>
                            <div class="email-footer" style="background: #f1f5f9; padding: 20px; text-align: center;">
                                <div class="social-icons" style="margin-bottom: 10px;">
                                    <a href="#"><img src="'.$facebook_icon.'" width="24" style="margin: 0 5px;"></a>
                                    <a href="#"><img src="'.$twitter_icon.'" width="24" style="margin: 0 5px;"></a>
                                    <a href="#"><img src="'.$instagram_icon.'" width="24" style="margin: 0 5px;"></a>
                                    <a href="#"><img src="'.$whatsapp_icon.'" width="24" style="margin: 0 5px;"></a>
                                </div>
                                <p style="font-size: 12px; color: #94a3b8;">&copy; ' . date("Y") . ' ' . htmlspecialchars($app_name) . '</p>
                            </div>
                        </div>
                    </body>
                    </html>';

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
                    $mail->Subject = $subject;
                    $mail->Body    = $htmlMessage;
                    $mail->send();

                } catch (Exception $e) {
                    // Silent fail for mail
                }

                // Log audit activity
                $dbh->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?,?,?)")
                    ->execute([$user_id, "Successful login", $ip_address]);

                // ROLE-BASED REDIRECTION
                if ($user['role'] === 'eleco') {
                    header("Location: Admin/dashboard");
                } else {
                    header("Location: index");
                }
                exit;
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again later.";
        }
    } else {
        $error = "Please fill in all fields.";
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
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            <div class="bg-[#0a192f] p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-900/50 rounded-full mb-4">
                    <i class="fas fa-fingerprint text-blue-400 text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Secure Access</h2>
                <p class="text-blue-200 text-sm mt-1">Login Portal</p>
            </div>
            
            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="loginForm" class="p-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="name@example.com">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <label class="text-gray-700 text-sm font-semibold">Password</label>
                        <a href="forgot_password" class="text-xs text-blue-600 hover:underline">Forgot password ?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" name="login_btn" id="submitBtn"
                        class="w-full bg-[#1e3a8a] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-800 transition duration-200 flex justify-center items-center">
                    <span id="btnText">Login <i class="fas fa-shield-alt ml-2"></i></span>
                    <div id="btnSpinner" class="hidden animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                </button>

                <p class="text-center text-sm text-gray-600">
                    Not Yet a registered Voter ? <a href="voter_signup" class="text-blue-600 font-bold hover:underline">Register</a>
                </p>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');

            text.classList.add('hidden');
            spinner.classList.remove('hidden');
            
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';
        });
    </script>
    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <?php include('footer.php'); ?>
    </footer>

</body>
</html>
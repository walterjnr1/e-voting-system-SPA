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

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password_btn'])) {
  // --- SECURITY: CSRF VALIDATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: forgot_password");
        exit;
    }

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    if ($email) {
        try {
            // 1. Check if user exists
            $stmt = $dbh->prepare("SELECT id, full_name FROM users WHERE email = :ue LIMIT 1");
            $stmt->execute([':ue' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Generic message for security (User Enumeration protection)
            $message = "If this email is registered in our system, a new password has been sent to it.";
            $message_type = "success";

            if ($user) {
                // 2. Generate 8-digit Alphanumeric Password
                $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $new_password = substr(str_shuffle($chars), 0, 8);
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // 3. Update Database
                $update = $dbh->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$hashed_password, $user['id']]);

                // 4. Send Email
                try {
                    $css_path  = 'assets/css/email_template.css';
                    $email_css = file_exists($css_path) ? file_get_contents($css_path) : '';
                    
                    $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                    $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                    $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
                    $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";

                    $subject = "Password Reset - " . $app_name;
                    
                    $htmlMessage = '
                    <html>
                    <head><style>' . $email_css . '</style></head>
                    <body class="email-body" style="background-color: #f0f2f5; font-family: sans-serif; padding: 20px;">
                        <div class="email-container" style="max-width: 600px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                            <div class="email-header" style="background-color: #0a192f; color: #fff; padding: 30px; text-align: center;">
                                <h2 style="margin:0;">Password Recovery</h2>
                            </div>
                            <div class="email-content" style="padding: 30px; color: #334155; line-height: 1.6;">
                                <p>Hello <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                                <p>We received a request to reset your password. Your new temporary password is shown below:</p>
                                <div class="info-box" style="background: #f8fafc; border: 2px dashed #1e3a8a; padding: 20px; text-align: center; margin: 20px 0; font-size: 24px; font-weight: bold; letter-spacing: 2px; color: #0a192f;">
                                    ' . $new_password . '
                                </div>
                                <p style="font-size: 14px; color: #64748b;">For security reasons, we strongly recommend that you log in and change this password immediately.</p>
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
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $htmlMessage;
                    $mail->send();

                    // Log the reset action
                    $dbh->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?,?,?)")
                        ->execute([$user['id'], "Password reset requested", $ip_address]);
                    unset($_SESSION['csrf_token']);

                } catch (Exception $e) {
                    // Silently log or handle mail failure
                }
            }
        } catch (PDOException $e) {
            $message = "System error. Please try again later.";
            $message_type = "error";
        }
    } else {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-sans">
   

    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            <div class="bg-[#0a192f] p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-900/50 rounded-full mb-4">
                    <i class="fas fa-key text-blue-400 text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Reset Password</h2>
                <p class="text-blue-200 text-sm mt-1">Enter your email to recover access</p>
            </div>
            
            <?php if ($message): ?>
                <div class="mx-8 mt-6 p-3 rounded border-l-4 <?php echo ($message_type === 'success') ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> text-sm">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="forgotForm" class="p-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Registered Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" 
                               placeholder="your-email@example.com">
                    </div>
                </div>

                <button type="submit" name="forgot_password_btn" id="submitBtn"
                        class="w-full bg-[#1e3a8a] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-800 transition duration-200 flex justify-center items-center">
                    <span id="btnText">Send New Password <i class="fas fa-paper-plane ml-2"></i></span>
                    <div id="btnSpinner" class="hidden animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                </button>

                <p class="text-center text-sm text-gray-600">
                    Remembered it? <a href="login.php" class="text-blue-600 font-bold hover:underline">Back to Login</a>
                </p>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('forgotForm').addEventListener('submit', function (e) {
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
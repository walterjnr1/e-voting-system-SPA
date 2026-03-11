<?php 
include 'database/connection.php';
include('inc/app_data.php');
require 'email_vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Security Check: Ensure user is coming from the login process
if (empty($_SESSION['temp_user_id'])) {
    header("Location: login");
    exit;
}

// --- CSRF TOKEN GENERATION ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";
$user_id = $_SESSION['temp_user_id'];
$user_email = $_SESSION['email'];
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// --- HANDLE OTP VERIFICATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_btn'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $input_code = filter_var(trim($_POST['otp_code']), FILTER_SANITIZE_NUMBER_INT);

    try {
        // Fetch the most recent OTP for this user
        $stmt = $dbh->prepare("SELECT code, created_at FROM otps WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otp_record) {
            $created_at = strtotime($otp_record['created_at']);
            $current_time = time();
            $minutes_passed = ($current_time - $created_at) / 60;

            if ($minutes_passed > 1440) {
                $error = "This code has expired. Please click 'Resend Email'.";
            } elseif ($input_code == $otp_record['code']) {
                // SUCCESS: Finalize the login
                $_SESSION['user_id'] = $user_id; // Transfer to official session
                unset($_SESSION['temp_user_id']); // Remove temp session
                
                // Clear the OTP from table
                $dbh->prepare("DELETE FROM otps WHERE user_id = ?")->execute([$user_id]);

                // Log Audit
                $audit = $dbh->prepare("INSERT INTO audit_logs (user_id, action, ip_address, created_at) VALUES (?, ?, ?, NOW())");
                $audit->execute([$user_id, "MFA Verification Successful", $ip_address]);

                // Redirect based on role
                if ($_SESSION['role'] === 'eleco') {
                    header("Location: Admin/dashboard");
                } else {
                    header("Location: index");
                }
                exit;
            } else {
                $error = "Invalid verification code. Please check and try again.";
            }
        } else {
            $error = "No active verification code found.";
        }
    } catch (PDOException $e) {
        $error = "System error. Please try again.";
    }
}

// --- HANDLE RESEND OTP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    // CSRF Validation for resend
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security violation.");
    }

    try {
        $new_otp = rand(10000, 99999);
        $dbh->prepare("DELETE FROM otps WHERE user_id = ?")->execute([$user_id]);
        $dbh->prepare("INSERT INTO otps (user_id, code, created_at) VALUES (?, ?, NOW())")->execute([$user_id, $new_otp]);

        // --- SEND EMAIL ---
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'newleastpaysolution@gmail.com'; 
        $mail->Password   = 'swhayyxzazfdcmif'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom($app_email, $app_name);
        $mail->addAddress($user_email);
        $mail->isHTML(true);
        $mail->Subject = "New Verification Code";
        
        $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
        $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
        $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";

        $mail->Body = '
        <div style="background:#f4f7f6; padding:20px; font-family:sans-serif;">
            <div style="max-width:550px; margin:auto; background:#fff; border-radius:10px; border:1px solid #eee; overflow:hidden; text-align:center;">
                <div style="background:#1e3a8a; padding:20px; color:#fff;"><h2>Verification Code</h2></div>
                <div style="padding:30px;">
                    <p>Your new 5-digit verification code is:</p>
                    <h1 style="letter-spacing:10px; color:#1e3a8a; font-size:40px;">' . $new_otp . '</h1>
                    <p style="color:#ef4444;">This code expires after 24 hours.</p>
                </div>
                <div style="padding:20px; background:#f9f9f9;">
                     <a href="#"><img src="'.$facebook_icon.'" width="24" style="margin:0 5px;"></a>
                     <a href="#"><img src="'.$twitter_icon.'" width="24" style="margin:0 5px;"></a>
                     <a href="#"><img src="'.$instagram_icon.'" width="24" style="margin:0 5px;"></a>
                     <p style="font-size:12px; color:#999; margin-top:10px;">&copy; '.date("Y").' '.$app_name.'</p>
                </div>
            </div>
        </div>';
        $mail->send();
        $success = "A new code has been sent to your email.";
    } catch (Exception $e) {
        $error = "Failed to resend email. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            <div class="bg-[#1e3a8a] p-8 text-white text-center">
                <div class="w-20 h-20 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold">Verification Required</h2>
                <p class="text-blue-100 mt-2 text-sm">A 5-digit code was sent to your email.</p>
            </div>

            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm italic">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mx-8 mt-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="otpForm" class="p-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div>
                    <input type="text" name="otp_code" maxlength="5" required autocomplete="off" autofocus
                           oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                           class="w-full text-center text-4xl font-bold tracking-[1rem] py-4 border-2 border-gray-200 rounded-xl focus:border-blue-600 focus:ring-0 outline-none transition"
                           placeholder="*****">
                </div>

                <button type="submit" name="verify_btn" id="submitBtn"
                        class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-blue-700 transition duration-300 flex justify-center items-center uppercase tracking-widest">
                    <span id="btnText">Confirm Access</span>
                    <div id="btnSpinner" class="hidden animate-spin rounded-full h-5 w-5 border-b-2 border-white ml-2"></div>
                </button>
            </form>

            <div class="pb-8 text-center">
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" name="resend_otp" class="text-blue-600 font-semibold hover:underline text-sm">
                        Didn't get the code? <span class="text-gray-400 font-normal">Resend Email</span>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('otpForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');

            text.innerText = "Processing...";
            spinner.classList.remove('hidden');
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
        });
    </script>
    
   <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <?php include('footer.php'); ?>
    </footer>
</body>
</html>
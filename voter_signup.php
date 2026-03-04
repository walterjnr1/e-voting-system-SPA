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
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_btn'])) {
    // CSRF VALIDATION
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'voter'; // Explicitly set role

    if ($full_name && $email && $phone && $password) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                // Check if User Already Exists
                $check = $dbh->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
                $check->execute([$email, $phone]);
                
                if ($check->rowCount() > 0) {
                    $error = "A user with this email or phone number already exists.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    // Insert User
                    $stmt = $dbh->prepare("INSERT INTO users (full_name, email, phone, password, role, is_verified, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                    $result = $stmt->execute([$full_name, $email, $phone, $hashed_password, $role]);

                    if ($result) {
                        $success = "Registration successful! Please check your email for confirmation.";

                        // --- SEND NOTIFICATION EMAIL ---
                        try {
                            $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                            $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                            $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
                            $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";

                            $htmlMessage = '
                            <div style="background-color: #f4f7f6; padding: 20px; font-family: sans-serif;">
                                <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #ddd;">
                                    <div style="background-color: #1e3a8a; color: #fff; padding: 20px; text-align: center;">
                                        <h2>Registration Received</h2>
                                    </div>
                                    <div style="padding: 30px; line-height: 1.6; color: #333;">
                                        <p>Dear <strong>' . htmlspecialchars($full_name) . '</strong>,</p>
                                        <p>Thank you for registering as a voter on the <strong>' . htmlspecialchars($app_name) . '</strong> platform.</p>
                                        <p>Your account is currently <strong>pending verification</strong> by the ELECO committee. You will be notified once you are cleared to vote.</p>
                                    </div>
                                    <div style="background: #f9f9f9; padding: 20px; text-align: center;">
                                        <div style="margin-bottom: 15px;">
                                            <a href="#"><img src="'.$facebook_icon.'" width="24" style="margin:0 5px;"></a>
                                            <a href="#"><img src="'.$twitter_icon.'" width="24" style="margin:0 5px;"></a>
                                            <a href="#"><img src="'.$instagram_icon.'" width="24" style="margin:0 5px;"></a>
                                            <a href="#"><img src="'.$whatsapp_icon.'" width="24" style="margin:0 5px;"></a>
                                        </div>
                                        <p style="font-size: 12px; color: #777;">&copy; ' . date("Y") . ' ' . $app_name . '</p>
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
                            $mail->addAddress($email);
                            $mail->isHTML(true);
                            $mail->Subject = "Voter Registration Confirmation";
                            $mail->Body    = $htmlMessage;
                            $mail->send();
                        } catch (Exception $e) { /* Silent fail */ }
                    }
                }
            } catch (PDOException $e) {
                $error = "System error: " . $e->getMessage();
            }
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-sans">

    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="max-w-xl w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            <div class="bg-[#1e3a8a] p-6 text-center text-white">
                <i class="fas fa-user-check text-4xl mb-2"></i>
                <h2 class="text-2xl font-bold">Voter Registration</h2>
                <p class="text-blue-200 text-sm">Join the electoral process</p>
            </div>

            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mx-8 mt-6 p-3 bg-green-100 border-l-4 border-green-500 text-green-700 text-sm">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="regForm" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Full Name (As per records)</label>
                    <input type="text" name="full_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="John Doe">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Email Address</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="john@example.com">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Phone Number</label>
                    <input type="text" name="phone" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="08012345678">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••">
                </div>

                <div class="md:col-span-2">
                    <button type="submit" name="register_btn" id="submitBtn"
                            class="w-full bg-[#1e3a8a] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-800 transition duration-200 flex justify-center items-center">
                        <span id="btnText">Register as Voter <i class="fas fa-paper-plane ml-2"></i></span>
                        <div id="btnSpinner" class="hidden animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    </button>
                </div>

                <p class="md:col-span-2 text-center text-sm text-gray-600">
                    Already registered? <a href="login" class="text-blue-600 font-bold hover:underline">Login here</a>
                </p>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('regForm').addEventListener('submit', function (e) {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');

            text.classList.add('hidden');
            spinner.classList.remove('hidden');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';
        });
    </script>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('footer.php'); ?>
    </footer>
</body>
</html>
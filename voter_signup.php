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
    $nickname = trim($_POST['nickname'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $captured_image = $_POST['user_image'] ?? ''; // Base64 data from webcam
    $role = 'voter';

    if ($full_name && $nickname && $email && $phone && $password && !empty($captured_image)) {
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
                    // --- PROCESS WEBCAM IMAGE ---
                    $final_db_path = "";
                    if (preg_match('/^data:image\/(\w+);base64,/', $captured_image, $type)) {
                        $data = substr($captured_image, strpos($captured_image, ',') + 1);
                        $type = strtolower($type[1]); // jpg, png, etc
                        $data = base64_decode($data);
                        
                        $img_filename = 'voter_' . time() . '_' . uniqid() . '.' . $type;
                        $directory = 'uploadImage/Profile/';
                        $upload_path = $directory . $img_filename;
                        
                        // Ensure directory exists
                        if (!is_dir($directory)) { 
                            mkdir($directory, 0777, true); 
                        }
                        
                        if (file_put_contents($upload_path, $data)) {
                            $final_db_path = $upload_path; 
                        }
                    }

                    if (empty($final_db_path)) {
                        $error = "Failed to process identity photo. Please try again.";
                    } else {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                        // Insert User with specific user_image format
                        $stmt = $dbh->prepare("INSERT INTO users (full_name, nickname, email, phone, password, user_image, role, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
                        $result = $stmt->execute([$full_name, $nickname, $email, $phone, $hashed_password, $final_db_path, $role]);

                        if ($result) {
                            $success = "Registration successful! Your account is pending verification.";

                            // --- SEND NOTIFICATION EMAIL ---
                            try {
                                $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                                $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                                $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
                                $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";

                                $htmlMessage = '
                                <div style="background-color: #f4f7f6; padding: 20px; font-family: sans-serif;">
                                    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                                        <div style="background-color: #1e3a8a; color: #fff; padding: 30px; text-align: center;">
                                            <h2 style="margin:0;">Voter Registration</h2>
                                        </div>
                                        <div style="padding: 30px; line-height: 1.6; color: #334155;">
                                            <p>Hello <strong>' . htmlspecialchars($full_name) . '</strong>,</p>
                                            <p>Your voter registration on <strong>' . htmlspecialchars($app_name) . '</strong> has been received successfully with your identity photo.</p>
                                            <p style="background: #f8fafc; padding: 15px; border-left: 4px solid #1e3a8a;">
                                                <strong>Status:</strong> Pending Approval<br>
                                                Our team will review your identity capture and clear you for the upcoming election.
                                            </p>
                                        </div>
                                        <div style="background: #f1f5f9; padding: 20px; text-align: center;">
                                            <div style="margin-bottom: 10px;">
                                                <a href="#"><img src="'.$facebook_icon.'" width="24" style="margin:0 5px;"></a>
                                                <a href="#"><img src="'.$twitter_icon.'" width="24" style="margin:0 5px;"></a>
                                                <a href="#"><img src="'.$instagram_icon.'" width="24" style="margin:0 5px;"></a>
                                                <a href="#"><img src="'.$whatsapp_icon.'" width="24" style="margin:0 5px;"></a>
                                            </div>
                                            <p style="font-size: 11px; color: #94a3b8;">&copy; ' . date("Y") . ' ' . $app_name . ' | Security Portal</p>
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
                                $mail->Subject = "Voter Registration";
                                $mail->Body    = $htmlMessage;
                                $mail->send();
                            } catch (Exception $e) { /* Silent fail */ }
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = "System error: " . $e->getMessage();
            }
        }
    } else {
        $error = "All fields, including a live photo capture, are mandatory.";
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
        <div class="max-w-4xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
            
            <div class="bg-[#1e3a8a] p-8 text-center text-white">
                <i class="fas fa-id-card text-5xl mb-3"></i>
                <h2 class="text-3xl font-bold tracking-tight">Voter Enrollment</h2>
                <p class="text-blue-200 text-sm">Secure your identity for the upcoming election</p>
            </div>

            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm flex items-center">
                    <i class="fas fa-times-circle mr-3"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mx-8 mt-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm flex items-center">
                    <i class="fas fa-check-circle mr-3"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="regForm" class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="user_image" id="user_image_input">
                
                <div class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Full Name</label>
                            <input type="text" name="full_name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">School Nickname</label>
                            <input type="text" name="nickname" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Phone Number</label>
                        <input type="text" name="phone" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Confirm Password</label>
                            <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-center justify-center bg-gray-50 p-6 rounded-2xl border-2 border-dashed border-gray-200">
                    <h3 class="text-gray-800 text-xs font-black uppercase tracking-widest mb-4">Identity Verification</h3>
                    
                    <div class="relative w-full aspect-square max-w-[280px] bg-black rounded-full overflow-hidden border-4 border-white shadow-xl ring-1 ring-gray-200">
                        <video id="webcam" autoplay playsinline class="w-full h-full object-cover"></video>
                        <canvas id="canvas" class="hidden"></canvas>
                        <img id="photo-preview" class="hidden w-full h-full object-cover scale-x-[-1]">
                    </div>

                    <div class="mt-6 flex flex-col w-full gap-3">
                        <button type="button" id="capture-btn" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition flex items-center justify-center">
                            <i class="fas fa-camera-retro mr-2"></i> Capture ID Photo
                        </button>
                        <button type="button" id="retake-btn" class="hidden w-full bg-gray-600 text-white py-3 rounded-xl font-bold hover:bg-gray-700 transition">
                            <i class="fas fa-redo mr-2"></i> Retake Photo
                        </button>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-4 text-center leading-tight">Must be a live photo of your face.<br>Background should be clear and well-lit.</p>
                </div>

                <div class="lg:col-span-2">
                    <button type="submit" name="register_btn" id="submitBtn"
                            class="w-full bg-[#1e3a8a] text-white font-black py-4 rounded-xl shadow-xl hover:bg-blue-900 transition-all duration-300 flex justify-center items-center uppercase tracking-widest">
                        <span id="btnText">Submit<i class="fas fa-shield-check ml-2"></i></span>
                        <div id="btnSpinner" class="hidden animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                    </button>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <a href="forgot_password" class="text-blue-600 hover:underline">Forgot password?</a>
                    <span class="text-gray-400">|</span>
                    <a href="login" class="text-blue-600 font-bold hover:underline">Login Here</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('capture-btn');
        const retakeBtn = document.getElementById('retake-btn');
        const preview = document.getElementById('photo-preview');
        const imageInput = document.getElementById('user_image_input');

        async function initWebcam() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 640, height: 640, facingMode: "user" } 
                });
                video.srcObject = stream;
            } catch (err) {
                alert("Camera access is strictly required for identity verification.");
            }
        }

        captureBtn.addEventListener('click', () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Flip horizontally for natural mirror feel
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const dataUrl = canvas.toDataURL('image/png');
            imageInput.value = dataUrl;
            
            preview.src = dataUrl;
            preview.classList.remove('hidden');
            video.classList.add('hidden');
            captureBtn.classList.add('hidden');
            retakeBtn.classList.remove('hidden');
        });

        retakeBtn.addEventListener('click', () => {
            imageInput.value = "";
            preview.classList.add('hidden');
            video.classList.remove('hidden');
            captureBtn.classList.remove('hidden');
            retakeBtn.classList.add('hidden');
        });

        document.getElementById('regForm').addEventListener('submit', function (e) {
            if (!imageInput.value) {
                e.preventDefault();
                alert("Please capture your identity photo to continue.");
                return;
            }
            document.getElementById('btnText').classList.add('hidden');
            document.getElementById('btnSpinner').classList.remove('hidden');
        });

        initWebcam();
    </script>

    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('footer.php'); ?>
    </footer>
</body>
</html>
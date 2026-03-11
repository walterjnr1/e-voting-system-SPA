<?php 
include 'database/connection.php';
include('inc/app_data.php');
require 'email_vendor/autoload.php';

// Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login");
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CSRF TOKEN GENERATION ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// 1. Fetch Active Election ID (Assuming one active election at a time)
$eStmt = $dbh->prepare("SELECT id FROM elections WHERE status = 'active' LIMIT 1");
$eStmt->execute();
$active_election = $eStmt->fetch(PDO::FETCH_ASSOC);
$election_id = $active_election['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_nomination'])) {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security violation: CSRF token mismatch.");
    }

    $user_id = $_SESSION['user_id'];
    $position_id = filter_var($_POST['position_id'], FILTER_SANITIZE_NUMBER_INT);
    $manifesto = htmlspecialchars(trim($_POST['manifesto']), ENT_QUOTES, 'UTF-8'); // XSS Prevention
    
    // Check if user already applied for this election
    $check = $dbh->prepare("SELECT id FROM candidates WHERE election_id = ? AND user_id = ?");
    $check->execute([$election_id, $user_id]);

    if (!$election_id) {
        $error = "No active election found for nominations.";
    } elseif ($check->rowCount() > 0) {
        $error = "You have already submitted a nomination for this election.";
    } else {
        // Handle File Upload
        $target_dir = "uploadImage/Profile/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES["candidate_photo"]["name"], PATHINFO_EXTENSION);
        $photo_name = "candidate_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $photo_name;

        if (move_uploaded_file($_FILES["candidate_photo"]["tmp_name"], $target_file)) {
            try {
                $stmt = $dbh->prepare("INSERT INTO candidates (election_id, position_id, user_id, manifesto, photo, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$election_id, $position_id, $user_id, $manifesto, $target_file]);
                
                $success = "Nomination submitted successfully! Check your email for confirmation.";

                // --- SEND PROFESSIONAL EMAIL ---
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'newleastpaysolution@gmail.com'; 
                    $mail->Password   = 'swhayyxzazfdcmif'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    $mail->setFrom($app_email, $app_name);
                    $mail->addAddress($_SESSION['email']);
                    $mail->isHTML(true);
                    $mail->Subject = "Nomination Received - " . $app_name;

                    $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
                    $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
                    $instagram_icon = "https://cdn-icons-png.flaticon.com/512/174/174855.png";
                    
                    $mail->Body = '
                    <div style="background:#f4f7f6; padding:20px; font-family:sans-serif;">
                        <div style="max-width:600px; margin:auto; background:#fff; border-radius:10px; border:1px solid #eee; overflow:hidden;">
                            <div style="background:#1e3a8a; color:#fff; padding:30px; text-align:center;">
                                <h2>Application Confirmed</h2>
                            </div>
                            <div style="padding:30px; color:#333;">
                                <p>Hello <strong>' . htmlspecialchars($_SESSION['full_name']) . '</strong>,</p>
                                <p>Your nomination for the upcoming election has been received successfully. Our committee will review your manifesto and portrait.</p>
                                <p><strong>Status:</strong> <span style="color:#f59e0b;">Pending Screening</span></p>
                                <p>You will receive further instructions once the screening process is complete.</p>
                            </div>
                            <div style="background:#f9f9f9; padding:20px; text-align:center;">
                                <a href="#"><img src="'.$facebook_icon.'" width="24" style="margin:0 5px;"></a>
                                <a href="#"><img src="'.$twitter_icon.'" width="24" style="margin:0 5px;"></a>
                                <a href="#"><img src="'.$instagram_icon.'" width="24" style="margin:0 5px;"></a>
                                <p style="font-size:12px; color:#777; margin-top:15px;">&copy; '.date("Y").' '.$app_name.'</p>
                            </div>
                        </div>
                    </div>';
                    $mail->send();
                } catch (Exception $e) {}

            } catch (PDOException $e) { $error = "Database error: " . $e->getMessage(); }
        } else { $error = "Failed to upload photo."; }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Nomination | <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <nav class="bg-blue-900 text-white p-4 shadow-lg sticky top-0 z-50">
       <?php include('nav.php'); ?>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 p-8 text-white text-center">
                <h2 class="text-3xl font-bold">Candidate Election Form</h2>
                <p class="text-blue-200 mt-2">Submit your details to run for an executive position.</p>
            </div>

            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mx-8 mt-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 text-sm">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="nominationForm" class="p-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly 
                               class="w-full px-4 py-3 border border-gray-100 bg-gray-50 rounded-lg text-gray-500 outline-none">
                    </div>

                    <?php
// --- FETCH POSITIONS FOR THE ACTIVE ELECTION ---
$positions = [];
if ($election_id) {
    try {
        $pStmt = $dbh->prepare("SELECT id, title FROM positions WHERE election_id = ? ORDER BY id ASC");
        $pStmt->execute([$election_id]);
        $positions = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error silently or log it
    }
}
?>

<div>
    <label class="block text-gray-700 font-semibold mb-2">Position Applied For</label>
    <select name="position_id" required 
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
        
        <?php if (empty($positions)): ?>
            <option value="">-- No positions available --</option>
        <?php else: ?>
            <option value="">-- Select Position --</option>
            <?php foreach ($positions as $row): ?>
                <option value="<?php echo (int)$row['id']; ?>">
                    <?php echo htmlspecialchars($row['title']); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
        
    </select>
</div>
                </div>

               <div>
    <label class="block text-gray-700 font-semibold mb-2 flex justify-between">
        <span>Manifesto</span>
        <span id="charCount" class="text-sm font-normal text-gray-500">0 / 150</span>
    </label>
    
    <textarea 
        name="manifesto" 
        id="manifestoField"
        rows="5" 
        maxlength="150" 
        required 
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all" 
        placeholder="Tell the alumni why they should vote for you..."
        oninput="updateCount()"></textarea>
    
    <p id="limitWarning" class="text-xs text-red-500 mt-1 hidden italic">
        Character limit reached.
    </p>
</div>



                <div class="bg-gray-50 p-6 rounded-xl border-2 border-dashed border-gray-300 text-center">
                    <label class="block text-gray-700 font-semibold mb-2">Upload Official Portrait</label>
                    <div id="image-preview-container" class="hidden mb-4">
                        <img id="image-preview" src="" class="w-32 h-32 object-cover rounded-lg mx-auto border-4 border-white shadow-md">
                    </div>
                    <div class="flex flex-col items-center">
                        <i class="fas fa-camera text-4xl text-gray-400 mb-3" id="camera-icon"></i>
                        <input type="file" name="candidate_photo" id="photo-input" accept="image/*" required 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>

                <div class="flex items-start bg-blue-50 p-4 rounded-lg">
                    <input type="checkbox" required class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded">
                    <label class="ml-3 text-sm text-blue-900">
                        I declare that the information provided is accurate and I agree to the election guidelines.
                    </label>
                </div>

                <div class="flex flex-col items-center">
                    <button type="submit" name="submit_nomination" id="submitBtn"
                            class="w-full md:w-1/2 bg-blue-600 text-white font-bold py-4 rounded-full shadow-lg hover:bg-blue-700 transform hover:scale-105 transition duration-200 flex justify-center items-center">
                        <span id="btnText">Submit Application <i class="fas fa-paper-plane ml-2"></i></span>
                        <div id="btnSpinner" class="hidden animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Image Preview Logic
        document.getElementById('photo-input').onchange = function (evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('image-preview').src = URL.createObjectURL(file);
                document.getElementById('image-preview-container').classList.remove('hidden');
                document.getElementById('camera-icon').classList.add('hidden');
            }
        };

        // Button Spinner Logic
        document.getElementById('nominationForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');

            text.classList.add('hidden');
            spinner.classList.remove('hidden');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.8';
        });
    </script>
<script>
function updateCount() {
    const textarea = document.getElementById('manifestoField');
    const countDisplay = document.getElementById('charCount');
    const warning = document.getElementById('limitWarning');
    const currentLength = textarea.value.length;
    
    // Update the counter text
    countDisplay.innerText = `${currentLength} / 150`;
    
    // Change color and show warning if limit is hit
    if (currentLength >= 150) {
        countDisplay.classList.replace('text-gray-500', 'text-red-600');
        warning.classList.remove('hidden');
    } else {
        countDisplay.classList.replace('text-red-600', 'text-gray-500');
        warning.classList.add('hidden');
    }
}
</script>
    <footer class="bg-gray-900 text-gray-400 py-10">
        <?php include('footer.php'); ?>
    </footer>
</body>
</html>
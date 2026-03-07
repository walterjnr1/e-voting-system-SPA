<?php 
include 'database/connection.php'; 
include('inc/app_data.php'); 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// --- PROFILE UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $new_password = $_POST['new_password'];
    $manifesto = $_POST['manifesto'] ?? '';

    try {
        $dbh->beginTransaction();

        // 1. Update Basic User Info
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $dbh->prepare("UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $hashed_password, $user_id]);
        } else {
            $stmt = $dbh->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $user_id]);
        }

        // 2. Fetch role
        $role_check = $dbh->prepare("SELECT role FROM users WHERE id = ?");
        $role_check->execute([$user_id]);
        $user_role = $role_check->fetchColumn();

        if ($user_role !== 'voter') {
            // Ensure candidate record exists
            $check_cand = $dbh->prepare("SELECT id FROM candidates WHERE user_id = ?");
            $check_cand->execute([$user_id]);
            $has_record = $check_cand->fetch();

            if (!$has_record) {
                // Create skeleton record if missing (e.g. newly promoted candidate)
                $ins_cand = $dbh->prepare("INSERT INTO candidates (user_id, manifesto, created_at) VALUES (?, ?, NOW())");
                $ins_cand->execute([$user_id, $manifesto]);
            } else if ($user_role === 'candidate') {
                $upd_cand = $dbh->prepare("UPDATE candidates SET manifesto = ? WHERE user_id = ?");
                $upd_cand->execute([$manifesto, $user_id]);
            }

            // Handle Photo Upload
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploadImage/Profile/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                
                $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($file_ext, $allowed)) {
                    $file_name = "IMG_" . $user_id . "_" . time() . "." . $file_ext;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
                        // Store full path in DB as requested: uploadImage/Profile/sample.jpeg
                        $full_path = $target_dir . $file_name;
                        $upd_photo = $dbh->prepare("UPDATE candidates SET photo = ? WHERE user_id = ?");
                        $upd_photo->execute([$full_path, $user_id]);
                    }
                } else {
                    throw new Exception("Invalid file type. Only JPG, PNG, and WebP allowed.");
                }
            }
        }

        $dbh->commit();
        $success = "Profile updated successfully!";
        $_SESSION['full_name'] = $full_name;

    } catch (Exception $e) {
        if ($dbh->inTransaction()) { $dbh->rollBack(); }
        $error = "Update failed: " . $e->getMessage();
    }
}

// --- FETCH CURRENT DATA ---
try {
    $stmt = $dbh->prepare("
        SELECT u.*, c.photo as candidate_photo, c.manifesto 
        FROM users u 
        LEFT JOIN candidates c ON u.id = c.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_user) { die("User record not found."); }

    // Logic for display: Use DB path or default
    $display_photo = 'uploadImage/Profile/default.png';
    if (!empty($current_user['candidate_photo']) && file_exists($current_user['candidate_photo'])) {
        $display_photo = $current_user['candidate_photo'];
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | <?php echo htmlspecialchars($app_name); ?></title>
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
            
            <div class="bg-gradient-to-r from-blue-700 to-blue-900 p-8 text-white relative">
                <div class="relative z-10">
                    <h2 class="text-3xl font-bold">Profile Settings</h2>
                    <p class="text-blue-200 mt-2">Update your personal and candidate information.</p>
                    
                    <div class="flex gap-2 mt-3">
                        <span class="px-3 py-1 bg-white/20 rounded-full text-[10px] font-bold uppercase tracking-widest">
                            Role: <?php echo htmlspecialchars($current_user['role']); ?>
                        </span>
                        <span class="px-3 py-1 <?php echo $current_user['financial_status'] == 'active' ? 'bg-green-500/30' : 'bg-red-500/30'; ?> rounded-full text-[10px] font-bold uppercase tracking-widest">
                            Finance: <?php echo htmlspecialchars($current_user['financial_status']); ?>
                        </span>
                    </div>
                </div>
                <i class="fas fa-user-edit absolute right-8 top-1/2 -translate-y-1/2 text-8xl text-white opacity-10"></i>
            </div>

            <?php if ($success): ?>
                <div class="mx-8 mt-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mx-8 mt-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="profileForm" enctype="multipart/form-data" class="p-8 space-y-6">
                
                <?php if ($current_user['role'] !== 'voter'): ?>
                <div class="flex flex-col items-center mb-6">
                    <div class="relative group">
                        <img id="preview" src="<?php echo $display_photo; ?>" 
                             class="h-32 w-32 rounded-full object-cover border-4 border-blue-100 shadow-md transition group-hover:opacity-90">
                        <label for="photoInput" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full border-2 border-white text-xs cursor-pointer hover:bg-blue-700 transition shadow-lg">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <p class="text-xs text-gray-400 mt-3 uppercase font-bold tracking-widest">Profile Photo</p>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Email (Static)</label>
                        <input type="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled 
                               class="w-full px-4 py-3 border border-gray-200 bg-gray-50 text-gray-400 rounded-lg cursor-not-allowed">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Change Password</label>
                        <input type="password" name="new_password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" 
                               placeholder="Leave empty to keep current">
                    </div>
                </div>

                <?php if ($current_user['role'] === 'candidate'): ?>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-sm">Manifesto</label>
                    <textarea name="manifesto" rows="4" 
                              class="w-full px-4 py-3 border border-blue-100 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-blue-50/20"><?php echo htmlspecialchars($current_user['manifesto'] ?? ''); ?></textarea>
                </div>
                <?php endif; ?>

                <?php if ($current_user['role'] !== 'voter'): ?>
                <div class="bg-gray-50 p-6 rounded-xl border-2 border-dashed border-gray-200 hover:border-blue-400 transition">
                    <label class="block text-gray-600 font-semibold mb-2 text-center text-sm">New Photo Upload</label>
                    <input type="file" name="profile_photo" id="photoInput" accept="image/*" 
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                </div>
                <?php endif; ?>

                <div class="flex flex-col items-center pt-4">
                    <button type="submit" name="update_profile" id="submitBtn"
                            class="w-full md:w-1/2 bg-[#1e3a8a] text-white font-bold py-4 rounded-full shadow-xl hover:bg-blue-800 transition duration-300 flex justify-center items-center uppercase tracking-widest">
                        <span id="btnText">Save Changes</span>
                        <div id="btnSpinner" class="hidden animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent ml-3"></div>
                    </button>
                    <a href="index" class="mt-4 text-sm text-gray-500 hover:text-blue-700 font-medium transition italic">Return to Dashboard</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <?php include('footer.php'); ?>
    </footer>

    <script>
        const photoInput = document.getElementById('photoInput');
        const preview = document.getElementById('preview');
        const profileForm = document.getElementById('profileForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');

        if(photoInput) {
            photoInput.onchange = evt => {
                const [file] = photoInput.files;
                if (file) { preview.src = URL.createObjectURL(file); }
            }
        }

        profileForm.onsubmit = () => {
            if(submitBtn.hasAttribute('data-submitting')) return false;
            btnText.textContent = "Updating...";
            btnSpinner.classList.remove('hidden');
            submitBtn.setAttribute('data-submitting', 'true');
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
            return true;
        }
    </script>
</body>
</html>
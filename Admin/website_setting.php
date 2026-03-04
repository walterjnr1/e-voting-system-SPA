<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- 1. INITIALIZE CSRF TOKEN ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$admin_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// --- 2. FETCH CURRENT SETTINGS ---
// We assume there is only one settings row with ID 1
$stmt = $dbh->query("SELECT * FROM website_settings WHERE id = 1 LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no settings exist yet, create a default row
if (!$settings) {
    $dbh->query("INSERT INTO website_settings (id, site_name, site_email) VALUES (1, 'Election System', 'admin@example.com')");
    $settings = $dbh->query("SELECT * FROM website_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
}

// --- 3. HANDLE UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    
    // SECURITY: CSRF VALIDATION
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: website_setting");
        exit;
    }

    $site_name = htmlspecialchars(trim($_POST['site_name']));
    $site_email = filter_var(trim($_POST['site_email']), FILTER_SANITIZE_EMAIL);
    $allow_reg = isset($_POST['allow_registration']) ? 1 : 0;
    $maintenance = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    $logo_path = $settings['logo']; // Default to current logo

    // SECURITY: LOGO UPLOAD HANDLING
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $file_tmp = $_FILES['logo']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'jpg', 'jpeg', 'webp'];
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        $allowed_mimes = ['image/png', 'image/jpeg', 'image/webp'];

        if (in_array($file_ext, $allowed_exts) && in_array($mime, $allowed_mimes)) {
            $new_filename = "logo_" . time() . "." . $file_ext;
            $target_dir = "../uploadImage/Logo/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
            
            if (move_uploaded_file($file_tmp, $target_dir . $new_filename)) {
                $logo_path = "uploadImage/Logo/" . $new_filename;
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid image format.'];
        }
        finfo_close($finfo);
    }

    // UPDATE DATABASE
    $update = $dbh->prepare("UPDATE website_settings SET 
        site_name = ?, 
        site_email = ?, 
        logo = ?, 
        allow_registration = ?, 
        maintenance_mode = ?, 
        updated_at = NOW() 
        WHERE id = 1");
    
    if ($update->execute([$site_name, $site_email, $logo_path, $allow_reg, $maintenance])) {
        // Log activity
        log_activity($dbh, $admin_id, "Updated website settings", $ip_address);
        
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Settings updated successfully!'];
        header("Location: website_setting");
        exit;
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database update failed.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Settings | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
            <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">

    <style>
        .logo-preview { width: 100px; height: 100px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 5px; background: #f9f9f9; }
        .form-check-input:checked { background-color: #0a192f; border-color: #0a192f; }
    </style>
</head>
<body>



<div class="d-flex">
    <nav id="sidebar" class="d-flex flex-column p-3 shadow">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <div id="content" class="flex-grow-1">
        <div class="navbar-custom d-flex justify-content-between align-items-center sticky-top">
            <?php include('partials/navbar.php');?>
        </div>

        <div class="p-3 p-md-4">
            <div class="mb-4">
                <h4 class="fw-bold mb-0">Website Configuration</h4>
                <p class="text-muted small">Manage global site identity and access controls</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-cogs me-2"></i>Global Settings</h6>
                        </div>
                        <div class="card-body p-4">
                            <form action="" method="POST" id="settingsForm" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                <div class="row mb-4 align-items-center">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Platform Logo</label>
                                        <div class="mb-2">
                                            <img src="../<?= $settings['logo'] ?? 'assets/img/default-logo.png' ?>" class="logo-preview" id="logoView">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="file" name="logo" class="form-control" id="logoInput" accept="image/*">
                                        <div class="form-text">Recommended: Transparent PNG (200x200px)</div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Site Name</label>
                                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Support Email</label>
                                        <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars($settings['site_email']) ?>" required>
                                    </div>

                                    <div class="col-12"><hr></div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-3 border rounded shadow-sm bg-light">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="allow_registration" id="regCheck" <?= $settings['allow_registration'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold" for="regCheck">Allow New Registrations</label>
                                            <p class="text-muted small mb-0">Enable or disable public voter signup.</p>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch p-3 border rounded shadow-sm bg-light">
                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="maintenance_mode" id="maintCheck" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold" for="maintCheck">Maintenance Mode</label>
                                            <p class="text-muted small mb-0">Lock the platform for general users.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 pt-3 border-top text-end">
                                    <button type="submit" name="update_settings" id="submitBtn" class="btn btn-primary px-5" style="background-color: #0a192f; border: none;">
                                        <span id="btnText"><i class="fas fa-save me-2"></i>Save Configuration</span>
                                        <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                                    </button>
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
    // Logo Preview Logic
    document.getElementById('logoInput').onchange = function (evt) {
        const [file] = this.files;
        if (file) {
            document.getElementById('logoView').src = URL.createObjectURL(file);
        }
    };

    // Form Submission Spinner
    document.getElementById('settingsForm').addEventListener('submit', function (event) {
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        const btn = document.getElementById('submitBtn');
        document.getElementById('btnText').classList.add('d-none');
        document.getElementById('btnSpinner').classList.remove('d-none');
        btn.style.pointerEvents = 'none'; 
        btn.style.opacity = '0.8';
    });
</script>
 
</body>
</html>
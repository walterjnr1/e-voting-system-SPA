<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {

    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// Fetch current settings
$stmt = $dbh->prepare("SELECT * FROM website_settings LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    // If settings record doesn't exist, create one
    $dbh->exec("INSERT INTO website_settings (site_name, created_at) VALUES ('My Website', NOW())");
    $stmt = $dbh->prepare("SELECT * FROM website_settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['update_settings'])) {

    // GENERAL
    $site_name = trim($_POST['site_name']);
    $site_email = trim($_POST['site_email']);
    $site_url = trim($_POST['site_url']);

    // CONTACT
    $whatsapp_phone = trim($_POST['whatsapp_phone']);
    $address = trim($_POST['address']);
    $phone1 = trim($_POST['phone1']);
    $phone2 = trim($_POST['phone2']);

    // PAYSTACK
    $paystack_public_key = trim($_POST['paystack_public_key']);
    $paystack_secret_key = trim($_POST['paystack_secret_key']);
    $max_device_limit = trim($_POST['max_device_limit']);

    // EMAIL
    $email_username = trim($_POST['email_username']);
    $email_password = trim($_POST['email_password']);

    // OPENAI
    $open_ai_key = trim($_POST['open_ai_key']);

    // SOCIAL MEDIA
    $twitter_id = trim($_POST['twitter_id']);
    $facebook_id = trim($_POST['facebook_id']);
    $instagram_id = trim($_POST['instagram_id']);

    // --- LOGO UPLOAD ---
    $logo = $settings['logo'];

    if (!empty($_FILES['logo']['name'])) {

        $targetDir = "../uploadImage/Logo/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["logo"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFilePath)) {
                $logo = "uploadImage/Logo/" . $fileName;
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'Logo upload failed.'];
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid logo format.'];
        }
    }

    // -------- UPDATE DATABASE --------
    $sql = "UPDATE website_settings SET
                site_name = :site_name,
                site_email = :site_email,
                logo = :logo,
                site_url = :site_url,

                whatsapp_phone = :whatsapp_phone,
                address = :address,
                phone1 = :phone1,
                phone2 = :phone2,

                paystack_public_key = :paystack_public_key,
                paystack_secret_key = :paystack_secret_key,
                max_device_limit = :max_device_limit,

                email_username = :email_username,
                email_password = :email_password,

                open_ai_key = :open_ai_key,

                twitter_id = :twitter_id,
                facebook_id = :facebook_id,
                instagram_id = :instagram_id,

                updated_at = NOW()
            WHERE id = :id";

    $stmt = $dbh->prepare($sql);

    $updated = $stmt->execute([
        ':site_name' => $site_name,
        ':site_email' => $site_email,
        ':logo' => $logo,
        ':site_url' => $site_url,
        ':whatsapp_phone' => $whatsapp_phone,
        ':address' => $address,
        ':phone1' => $phone1,
        ':phone2' => $phone2,
        ':paystack_public_key' => $paystack_public_key,
        ':paystack_secret_key' => $paystack_secret_key,
        ':max_device_limit' => $max_device_limit,
        ':email_username' => $email_username,
        ':email_password' => $email_password,
        ':open_ai_key' => $open_ai_key,
        ':twitter_id' => $twitter_id,
        ':facebook_id' => $facebook_id,
        ':instagram_id' => $instagram_id,
        ':id' => $settings['id']
    ]);

    if ($updated) {
        log_activity($dbh, $user_id, "Updated website settings", 'website_settings', $settings['id'], $ip_address);

        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Website settings updated successfully!'];
        header("Location: website-setting");
        exit;
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to update settings.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Website Settings | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
</head>
<body>

<div class="d-flex">

    <!-- Sidebar -->
    <nav id="sidebar" class="d-flex flex-column p-3">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <div id="content" class="flex-grow-1">

        <!-- Navbar -->
        <div class="navbar-custom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
                <h5>Website Settings</h5>
            </div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>

        <!-- Settings Form -->
        <div class="settings-form p-4">
            <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <!-- GENERAL -->
                <h5 class="mt-3 mb-2"><strong>General Settings</strong></h5>
                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name"
                               value="<?php echo htmlspecialchars($settings['site_name']); ?>"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Site Email</label>
                        <input type="email" name="site_email"
                               value="<?php echo htmlspecialchars($settings['site_email']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Site URL</label>
                        <input type="text" name="site_url"
                               value="<?php echo htmlspecialchars($settings['site_url']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Logo</label><br>
                        <?php if (!empty($settings['logo'])): ?>
                            <img src="../<?php echo htmlspecialchars($settings['logo']); ?>"
                                 height="60" class="mb-2"><br>
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control">
                    </div>
                </div>

                <!-- CONTACT -->
                <h5 class="mt-3 mb-2"><strong>Contact Information</strong></h5>
                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">WhatsApp Phone</label>
                        <input type="text" name="whatsapp_phone"
                               value="<?php echo htmlspecialchars($settings['whatsapp_phone']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" name="address"
                               value="<?php echo htmlspecialchars($settings['address']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone 1</label>
                        <input type="text" name="phone1"
                               value="<?php echo htmlspecialchars($settings['phone1']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone 2</label>
                        <input type="text" name="phone2"
                               value="<?php echo htmlspecialchars($settings['phone2']); ?>"
                               class="form-control">
                    </div>
                </div>

                <!-- PAYSTACK -->
                <h5 class="mt-3 mb-2"><strong>Paystack Settings</strong></h5>
                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">Public Key</label>
                        <input type="text" name="paystack_public_key"
                               value="<?php echo htmlspecialchars($settings['paystack_public_key']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Secret Key</label>
                        <input type="text" name="paystack_secret_key"
                               value="<?php echo htmlspecialchars($settings['paystack_secret_key']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Max Device Limit</label>
                        <input type="number" name="max_device_limit"
                               value="<?php echo htmlspecialchars($settings['max_device_limit']); ?>"
                               class="form-control">
                    </div>
                </div>

                <!-- EMAIL -->
                <h5 class="mt-3 mb-2"><strong>Email Settings</strong></h5>
                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">Email Username</label>
                        <input type="text" name="email_username"
                               value="<?php echo htmlspecialchars($settings['email_username']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email Password</label>
                        <input type="password" name="email_password"
                               value="<?php echo htmlspecialchars($settings['email_password']); ?>"
                               class="form-control">
                    </div>
                </div>

                <!-- SOCIAL MEDIA -->
                <h5 class="mt-3 mb-2"><strong>Social Media</strong></h5>
                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">Twitter Username</label>
                        <input type="text" name="twitter_id"
                               value="<?php echo htmlspecialchars($settings['twitter_id']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Facebook Username</label>
                        <input type="text" name="facebook_id"
                               value="<?php echo htmlspecialchars($settings['facebook_id']); ?>"
                               class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Instagram Username</label>
                        <input type="text" name="instagram_id"
                               value="<?php echo htmlspecialchars($settings['instagram_id']); ?>"
                               class="form-control">
                    </div>
                </div>

                <!-- OPENAI -->
                <h5 class="mt-3 mb-2"><strong>OpenAI Settings</strong></h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label">OpenAI API Key</label>
                        <input type="text" name="open_ai_key"
                               value="<?php echo htmlspecialchars($settings['open_ai_key']); ?>"
                               class="form-control">
                    </div>
                </div>

                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save Settings
                </button>

                <a href="dashboard" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<footer>
    <?php include('partials/footer.php'); ?>
</footer>

<?php include('partials/sweetalert.php'); ?>
<?php include('partials/toogle-down.php'); ?>

</body>
</html>

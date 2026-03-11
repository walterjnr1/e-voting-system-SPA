<?php 
include('../inc/app_data.php');
include '../database/connection.php';

// Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

$user_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// --- FETCH CURRENT USER DATA ---
$stmt = $dbh->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// --- PHP PROCESSING LOGIC: UPDATE PROFILE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // --- SECURITY: CSRF VALIDATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: profile");
        exit;
    }
    $full_name = htmlspecialchars(trim($_POST['fullname']));
    $email     = htmlspecialchars(trim($_POST['email']));
    $phone     = htmlspecialchars(trim($_POST['phone']));
    $nickname     = htmlspecialchars(trim($_POST['nickname']));

    // Check if email is being changed and if new email already exists elsewhere
    $checkEmail = $dbh->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkEmail->execute([$email, $user_id]);
    
    if ($checkEmail->rowCount() > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'This email is already taken by another account.'];
    } else {
        $update = $dbh->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?,nickname = ? WHERE id = ?");
        $result = $update->execute([$full_name, $email, $phone,$nickname, $user_id]);

        if ($result) {
            // Log Activity
            log_activity($dbh, $user_id, "Profile Update", $ip_address);

            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Profile updated successfully!'];
             // Regenerate CSRF for next action
                    unset($_SESSION['csrf_token']);
            header("Location: profile");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database error during update.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .profile-header-bg { background: #0a192f; height: 100px; border-radius: 10px 10px 0 0; }
        .profile-avatar { width: 100px; height: 100px; margin-top: -50px; border: 5px solid #fff; background: #e9ecef; color: #0a192f; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; }
    </style>
</head>
<body>

<div id="sidebar-overlay"></div>

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
                <h4 class="fw-bold mb-0">My Account</h4>
                <p class="text-muted">Manage your personal information and view account status</p>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="profile-header-bg"></div>
                        <div class="card-body text-center">
                            <div class="profile-avatar rounded-circle mx-auto shadow-sm">
                                <i class="fas fa-user"></i>
                            </div>
                            <h5 class="mt-3 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 mb-3">
                                <?php echo strtoupper($user['role']); ?>
                            </span>
                            <hr>
                            <div class="text-start">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Financial Status:</span>
                                    <span class="fw-bold small <?php echo $user['financial_status'] == 'cleared' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo ucfirst($user['financial_status']); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Voting Status:</span>
                                    <span class="fw-bold small">
                                        <?php echo $user['has_voted'] ? '<i class="fas fa-check-circle text-success"></i> Voted' : '<i class="fas fa-times-circle text-warning"></i> Not Voted'; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Account Status:</span>
                                    <span class="badge <?php echo $user['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?> btn-xs">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Last Login:</span>
                                    <span class="text-dark small fw-bold"><?php echo date('d M, Y H:i', strtotime($user['last_login'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-edit me-2"></i>Update Personal Information</h6>
                        </div>
                        <div class="card-body p-4">
                            <form action="" method="POST" id="profileForm" class="needs-validation" novalidate>
                               <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
                            <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">Full Name</label>
                                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">Nick Name</label>
                                        <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($user['nickname']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email Address</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="alert alert-info border-0 small mb-0">
                                            <i class="fas fa-info-circle me-2"></i> Role, Financial Status, and Voting permissions are managed by the administrator.
                                        </div>
                                    </div>

                                    <div class="col-12 text-end">
                                        <hr class="my-3">
                                        <button type="submit" name="update_profile" id="submitBtn" class="btn btn-primary px-5" style="background-color: #0a192f; border: none;">
                                            <span id="btnText"><i class="fas fa-save me-2"></i>Save Changes</span>
                                            <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                                        </button>
                                    </div>
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
    document.getElementById('profileForm').addEventListener('submit', function (event) {
        const form = event.target;
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        btn.style.pointerEvents = 'none'; 
        btn.style.opacity = '0.8';
        text.classList.add('d-none');
        spinner.classList.remove('d-none');
    });
</script>
 
</body>
</html>
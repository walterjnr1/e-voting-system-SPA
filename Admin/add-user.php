<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  
    header("Location: ../login");
    exit;
}

// --- PHP PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
    
// --- SECURITY: CSRF VALIDATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: add-user");
        exit;
    }

    $full_name = htmlspecialchars(trim($_POST['fullname']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $role = htmlspecialchars(trim($_POST['role'])); 
    $raw_password = htmlspecialchars(trim($_POST['password']));

    // 1. Validation: Check if email already exists
    $checkEmail = $dbh->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    
    if ($checkEmail->rowCount() > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Email address is already registered.'];
    } else {
        // 2. Hash Password & Insert
        $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
        
        $insert = $dbh->prepare("INSERT INTO users (full_name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $result = $insert->execute([$full_name, $email, $phone, $hashed_password, $role]);

        if ($result) {
            $facebook_icon  = "https://cdn-icons-png.flaticon.com/512/733/733547.png";
            $twitter_icon   = "https://cdn-icons-png.flaticon.com/512/5969/5969020.png";
            $whatsapp_icon  = "https://cdn-icons-png.flaticon.com/512/733/733585.png";
            
            $css = file_exists('assets/css/email_template.css') ? file_get_contents('assets/css/email_template.css') : '';
            
            $subject = "Welcome to $app_name E-Voting System - Account Created";
            $message = "
            <html>
            <head><style>{$css}</style></head>
            <body class='email-body'>
                <div class='email-container'>
                    <div class='email-header'><h2>Welcome to $app_name</h2></div>
                    <div class='email-content'>
                        <p>Hello <strong>".htmlspecialchars($full_name)."</strong>,</p>
                        <p>Your account has been created successfully. You can now log in to the portal using the credentials below.</p>
                        
                        <div class='info-box'>
                            <strong>Account Credentials:</strong><br>
                            Email: ".htmlspecialchars($email)."<br>
                            Password: <strong>".htmlspecialchars($raw_password)."</strong><br>
                            Role: ".ucfirst($role)."
                        </div>

                        <p>For security reasons, we recommend you change your password immediately after your first login.</p>
                        
                        <div class='alert-box'>
                            <strong>Notice:</strong> This is an automated security notification. If you did not expect this, please contact support.
                        </div>
                    </div>
                    <div class='email-footer'>
                        <div class='social-icons'>
                            <a href='#'><img src='".$facebook_icon."' width='24'></a>
                            <a href='#'><img src='".$twitter_icon."' width='24'></a>
                            <a href='#'><img src='".$whatsapp_icon."' width='24'></a>
                        </div>
                        <p>&copy; ".date('Y')." ".htmlspecialchars($app_name).". All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            if(function_exists('sendEmail')) {
                sendEmail($email, $subject, $message);
            }
            
            log_activity($dbh, $user_id, "User Registration $full_name ($role)", $ip_address);

            $_SESSION['toast'] = ['type' => 'success', 'message' => 'User account created successfully!'];
            // Regenerate CSRF for next action
                    unset($_SESSION['csrf_token']);
            header("Location: add-user");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database error.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
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
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">User Management</h4>
                <a href="dashboard" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user-plus me-2"></i>Create New Account</h6>
                        </div>
                        <div class="card-body p-4">
                            <form action="" method="POST" id="registrationForm" class="needs-validation" novalidate>
                                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="fullname" class="form-control" placeholder="John Doe" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" placeholder="080XXXXXXXX" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">System Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="" selected disabled>Select Role</option>
                                            <option value="eleco">Election Committee (ELECO)</option>
                                            <option value="voter">Standard Voter</option>
                                            <option value="candidate">Official Candidate</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Set Password</label>
                                        <input type="text" name="password" class="form-control" placeholder="Minimum 8 characters" minlength="8" required>
                                    </div>

                                    <div class="col-12 text-end">
                                        <hr class="my-4">
                                        <button type="submit" name="register_user" id="submitBtn" class="btn btn-primary px-5" style="background-color: #0a192f; border: none;">
                                            <span id="btnText"><i class="fas fa-check-circle me-2"></i>Create User Account</span>
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
    document.getElementById('registrationForm').addEventListener('submit', function (event) {
        const form = event.target;
        
        // If the form is invalid, stop here and show Bootstrap validation
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        // If form is valid:
        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        // 1. Disable button to prevent double clicks
        btn.style.pointerEvents = 'none'; 
        btn.style.opacity = '0.8';

        // 2. Switch text for spinner
        text.classList.add('d-none');
        spinner.classList.remove('d-none');

        // The form will now naturally submit to the PHP logic above.
    });
</script>
 
</body>
</html>
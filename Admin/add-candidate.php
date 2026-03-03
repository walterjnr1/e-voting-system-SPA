<?php 
include('../inc/app_data.php');
include '../database/connection.php';

// Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

$admin_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// --- PHP PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_candidate'])) {
    $election_id = $_POST['election_id'];
    $position_id = $_POST['position_id'];
    $user_id_to_reg = $_POST['user_id'];
    $manifesto = trim($_POST['manifesto']);
    $status = 'pending'; 

    // 1. Validation: Check duplicate registration
    $stmt = $dbh->prepare("SELECT id FROM candidates WHERE election_id = ? AND user_id = ?");
    $stmt->execute([$election_id, $user_id_to_reg]);
    
    if ($stmt->fetch()) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'This user is already registered for this election!'];
    } else {
        // 2. Handle Image Upload
        $photo_db_path = "";
        $upload_ok = true;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $target_dir = "../uploadImage/Profile/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            
            $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed_types)) {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'Only image files are allowed!'];
                $upload_ok = false;
            } else {
                $new_filename = "CAND_" . time() . "_" . uniqid() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                    $photo_db_path = "uploadImage/Profile/" . $new_filename;
                } else {
                    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Upload failed.'];
                    $upload_ok = false;
                }
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Please upload a photo.'];
            $upload_ok = false;
        }

        if ($upload_ok) {
            // 3. Insert Record
            $insert = $dbh->prepare("INSERT INTO candidates (election_id, position_id, user_id, manifesto, photo, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $result = $insert->execute([$election_id, $position_id, $user_id_to_reg, $manifesto, $photo_db_path, $status]);

            if ($result) {
                // Fetch details for email
                $uStmt = $dbh->prepare("SELECT full_name, email FROM users WHERE id = ?");
                $uStmt->execute([$user_id_to_reg]);
                $candidate = $uStmt->fetch();

                // Notification Logic
                $subject = "Nomination Filed: Candidate Registration - $app_name";
                $message = "<html>... (Template as per your requirements) ...</html>"; 
                
                if(function_exists('sendEmail')) {
                    sendEmail($candidate['email'], $subject, $message);
                }
                
                log_activity($dbh, $admin_id, "Registered Candidate: ".$user_id_to_reg, $ip_address);
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Candidate registered successfully!'];
                header("Location: add-candidate");
                exit;
            }
        }
    }
}

// Fetch Dropdown Data
$elections = $dbh->query("SELECT id, title FROM elections WHERE status != 'closed'")->fetchAll();
$positions = $dbh->query("SELECT id, title FROM positions")->fetchAll();
$users = $dbh->query("SELECT id, full_name FROM users WHERE role = 'candidate'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Candidate | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
        <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">

    <style>
        .preview-container {
            width: 120px;
            height: 120px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
        #imagePreview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
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
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">Candidate Registration</h4>
                <a href="dashboard" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form action="" method="POST" id="regForm" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label d-block">Candidate Photo</label>
                                        <div class="preview-container">
                                            <i class="fas fa-user fa-3x text-muted" id="placeholderIcon"></i>
                                            <img id="imagePreview" src="#" alt="Preview">
                                        </div>
                                        <input type="file" name="photo" id="photoInput" class="form-control" accept="image/*" required>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Select User</label>
                                                <select name="user_id" class="form-select" required>
                                                    <option value="">Choose User...</option>
                                                    <?php foreach($users as $u): ?>
                                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Target Election</label>
                                                <select name="election_id" class="form-select" required>
                                                    <option value="">Choose Election...</option>
                                                    <?php foreach($elections as $e): ?>
                                                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Position</label>
                                                <select name="position_id" class="form-select" required>
                                                    <option value="">Choose Position...</option>
                                                    <?php foreach($positions as $p): ?>
                                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Manifesto</label>
                                        <textarea name="manifesto" class="form-control" rows="4" placeholder="Describe the candidate's goals..." required></textarea>
                                    </div>

                                    <div class="col-12 text-end">
                                        <hr>
                                        <button type="submit" name="register_candidate" id="submitBtn" class="btn btn-primary px-5" style="background-color: #0a192f; border: none;">
                                            <span id="btnText"><i class="fas fa-check-circle me-2"></i>Register Candidate</span>
                                            <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('partials/sweetalert.php'); ?>

<script>
    // Image Preview Logic
    document.getElementById('photoInput').onchange = function (evt) {
        const [file] = this.files;
        if (file) {
            const preview = document.getElementById('imagePreview');
            const icon = document.getElementById('placeholderIcon');
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            icon.style.display = 'none';
        }
    };

    // Form Submission Spinner
    document.getElementById('regForm').addEventListener('submit', function (event) {
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            this.classList.add('was-validated');
        } else {
            const btn = document.getElementById('submitBtn');
            document.getElementById('btnText').classList.add('d-none');
            document.getElementById('btnSpinner').classList.remove('d-none');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.8';
        }
    });
</script>

</body>
</html>
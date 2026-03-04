<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

$candidate_id = $_GET['id'] ?? null;

// Fetch existing candidate data
$stmt = $dbh->prepare("SELECT c.*, u.full_name FROM candidates c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$stmt->execute([$candidate_id]);
$candidate = $stmt->fetch();

if (!$candidate) {
    die("Candidate not found.");
}

// Processing Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_candidate'])) {
    $election_id = $_POST['election_id'];
    $position_id = $_POST['position_id'];
    $manifesto = trim($_POST['manifesto']);
    $status = $_POST['status'];
    $photo_path = $candidate['photo']; // Default to old photo

    // Handle new photo upload if provided
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "../uploadImage/Profile/";
        $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $new_filename = "CAND_UPD_" . time() . "." . $file_ext;
        
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $new_filename)) {
            // Delete old photo file
            if (file_exists("../" . $candidate['photo'])) { unlink("../" . $candidate['photo']); }
            $photo_path = "uploadImage/Profile/" . $new_filename;
        }
    }

    $update = $dbh->prepare("UPDATE candidates SET election_id=?, position_id=?, manifesto=?, photo=?, status=? WHERE id=?");
    if ($update->execute([$election_id, $position_id, $manifesto, $photo_path, $status, $candidate_id])) {
         $_SESSION['toast'] = ['type' => 'success', 'message' => 'Update successful!.'];
header("Location: candidate-records");
        exit;
    }
}

$elections = $dbh->query("SELECT id, title FROM elections WHERE status != 'closed'")->fetchAll();
$positions = $dbh->query("SELECT id, title FROM positions")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Candidate | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
        <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">

    <style>
        .preview-box { width: 150px; height: 150px; border: 2px solid #0a192f; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
        .preview-box img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>
<div class="d-flex">
    <nav id="sidebar" class="d-flex flex-column p-3 shadow"><?php include('partials/sidebar.php'); ?></nav>
<div id="content" class="flex-grow-1">
        <div class="navbar-custom d-flex justify-content-between align-items-center sticky-top">
            <?php include('partials/navbar.php');?>
        </div>

        <div class="p-3 p-md-4">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold">Candidate Records</h4>
                    <p class="text-muted small">Manage all registered election candidates</p>
                </div>
                <a href="add-candidate" class="btn btn-primary shadow-sm" style="background-color: #0a192f; border:none;">
                    <i class="fas fa-user-plus me-2"></i>New Candidate
                </a>
            </div>
                    <div class="p-4">
            <div class="card shadow border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Edit Candidate: <?= htmlspecialchars($candidate['full_name']) ?></h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST" id="editForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="preview-box mx-auto">
                                    <img id="imgPrev" src="../<?= htmlspecialchars($candidate['photo']) ?>">
                                </div>
                                <input type="file" name="photo" id="photoInput" class="form-control form-control-sm" accept="image/*">
                                <small class="text-muted text-xs">Leave empty to keep current photo</small>
                            </div>
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Election</label>
                                        <select name="election_id" class="form-select">
                                            <?php foreach($elections as $e): ?>
                                                <option value="<?= $e['id'] ?>" <?= $e['id'] == $candidate['election_id'] ? 'selected' : '' ?>><?= $e['title'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Position</label>
                                        <select name="position_id" class="form-select">
                                            <?php foreach($positions as $p): ?>
                                                <option value="<?= $p['id'] ?>" <?= $p['id'] == $candidate['position_id'] ? 'selected' : '' ?>><?= $p['title'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Nomination Status</label>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?= $candidate['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="approved" <?= $candidate['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                                            <option value="rejected" <?= $candidate['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Manifesto</label>
                                        <textarea name="manifesto" class="form-control" rows="5"><?= htmlspecialchars($candidate['manifesto']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" name="update_candidate" id="subBtn" class="btn btn-primary px-5" style="background-color: #0a192f;">
                                <span id="btxt">Save Changes</span>
                                <div id="bspin" class="spinner-border spinner-border-sm d-none"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
             <footer class="main-footer text-center mt-5 py-3">
                <?php include('partials/footer.php'); ?>
            </footer>
        </div>
    </div>
</div>

<script>
    document.getElementById('photoInput').onchange = function() {
        const [file] = this.files;
        if (file) { document.getElementById('imgPrev').src = URL.createObjectURL(file); }
    };

    document.getElementById('editForm').onsubmit = function() {
        document.getElementById('btxt').classList.add('d-none');
        document.getElementById('bspin').classList.remove('d-none');
    };
</script>
</body>
</html>
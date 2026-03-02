<?php 
include('../inc/app_data.php');
include '../database/connection.php';
if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

// Get subject ID from query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['toast'] = ['type'=>'error','message'=>'Invalid subject ID.'];
    header("Location: subject-records");
    exit;
}

// Fetch subject details
$stmt = $dbh->prepare("SELECT * FROM subjects WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    $_SESSION['toast'] = ['type'=>'error','message'=>'Subject not found.'];
    header("Location: subject-records");
    exit;
}

if (isset($_POST['update_subject'])) {
    $name = trim($_POST['name']);

    if (!empty($name)) {
        // Update subject
        $stmt = $dbh->prepare("UPDATE subjects SET name = :name WHERE id = :id");
        $result = $stmt->execute([':name' => $name, ':id' => $id]);

        if ($result) {
            // activity log
            log_activity($dbh, $user_id, "Updated subject", 'subjects', $id, $ip_address);

            $_SESSION['toast'] = ['type'=>'success','message'=>'Subject updated successfully!'];
            header("Location: subject-records");
            exit;
        } else {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Failed to update subject.'];
        }
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Subject name cannot be empty.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Subject | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <nav id="sidebar" class="d-flex flex-column p-3">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <!-- Page Content -->
    <div id="content" class="flex-grow-1">
        <!-- Navbar -->
        <div class="navbar-custom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
                <h5>Edit Subject</h5>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Edit Subject Form -->
        <div class="settings-form p-3">
            <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="mb-3">
                    <label class="form-label">Subject Name</label>
                    <input type="text" class="form-control" name="name" 
                           value="<?php echo htmlspecialchars($subject['name']); ?>" 
                           placeholder="Enter subject name" required>
                </div>
                <button type="submit" name="update_subject" class="btn btn-primary">
                    <i class="fa fa-save"></i> Update
                </button>
                <a href="subject-records" class="btn btn-secondary">Cancel</a>
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

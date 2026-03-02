<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

if(isset($_POST['add_subject'])){
    $name = trim($_POST['name']);

    if(!empty($name)){
        // Insert new subject
        $stmt = $dbh->prepare("INSERT INTO subjects (name) VALUES (:name)");
        $result = $stmt->execute([':name' => $name]);

        if($result){
            // activity log
            log_activity($dbh, $user_id, "Added new subject", 'subjects', $dbh->lastInsertId(), $ip_address);

            $_SESSION['toast'] = ['type'=>'success','message'=>'Subject added successfully!'];
            header("Location: subject-records");
            exit;
        } else {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Failed to add subject.'];
        }
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Subject name cannot be empty.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Subject | <?php echo htmlspecialchars($app_name); ?></title>
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
                <h5>Add Subject</h5>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Add Subject Form -->
        <div class="settings-form p-3">
            <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="mb-3">
                    <label class="form-label">Subject Name</label>
                    <input type="text" class="form-control" name="name" placeholder="Enter subject name" required>
                </div>
                <button type="submit" name="add_subject" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Save </button>
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

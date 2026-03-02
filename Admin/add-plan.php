<?php 
include('../inc/app_data.php');
include '../database/connection.php';
if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

if(isset($_POST['add_plan'])){
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $duration_days = trim($_POST['duration_days']);
    $question_limit = trim($_POST['question_limit']);
    $features = trim($_POST['features']);

    if(!empty($name) && !empty($price) && !empty($duration_days)){
        // Insert new plan
        $stmt = $dbh->prepare("INSERT INTO plans (name, price, duration_days, question_limit, features, created_at) 
                               VALUES (:name, :price, :duration_days, :question_limit, :features, NOW())");

        $result = $stmt->execute([
            ':name' => $name,
            ':price' => $price,
            ':duration_days' => $duration_days,
            ':question_limit' => !empty($question_limit) ? $question_limit : null,
            ':features' => $features
        ]);

        if($result){
            // activity log
            log_activity($dbh, $user_id, "Added new plan", 'plans', $dbh->lastInsertId(), $ip_address);

            $_SESSION['toast'] = ['type'=>'success','message'=>'Plan added successfully!'];
            header("Location: plan-records");
            exit;
        } else {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Failed to add plan.'];
        }
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Name, Price, and Duration are required.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Plan | <?php echo htmlspecialchars($app_name); ?></title>
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
                <h5>Add Plan</h5>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Add Plan Form -->
      <div class="settings-form p-3">
            <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="mb-3">
                    <label class="form-label">Plan Name</label>
                    <input type="text" class="form-control" name="name" placeholder="Enter plan name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price (₦ or $)</label>
                    <input type="number" step="0.01" class="form-control" name="price" placeholder="Enter price" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Duration (Days)</label>
                    <input type="number" class="form-control" name="duration_days" placeholder="e.g. 30 for 1 month" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Question Limit</label>
                    <input type="number" class="form-control" name="question_limit" placeholder="Leave empty for unlimited">
                </div>

                <div class="mb-3">
                    <label class="form-label">Features</label>
                    <textarea class="form-control" name="features" rows="3" placeholder='Example: "all features, unlimited access"'></textarea>
                    <small class="text-muted">Enter features as comma-separated list or JSON string.</small>
                </div>

                <button type="submit" name="add_plan" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Save
                </button>
                <a href="plan-records" class="btn btn-secondary">Cancel</a>
            </form>
            <p>&nbsp;</p>
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

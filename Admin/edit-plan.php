<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

// Get plan ID from query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['toast'] = ['type'=>'error','message'=>'Invalid plan ID.'];
    header("Location: plan-records");
    exit;
}

// Fetch plan details
$stmt = $dbh->prepare("SELECT * FROM plans WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    $_SESSION['toast'] = ['type'=>'error','message'=>'Plan not found.'];
    header("Location: plan-records");
    exit;
}

if (isset($_POST['update_plan'])) {
    $name           = trim($_POST['name']);
    $price          = trim($_POST['price']);
    $duration_days  = trim($_POST['duration_days']);
    $question_limit = trim($_POST['question_limit']);
    $features       = trim($_POST['features']);

    if (!empty($name) && is_numeric($price) && is_numeric($duration_days)) {
        // Update plan
        $stmt = $dbh->prepare("UPDATE plans 
            SET name = :name, price = :price, duration_days = :duration_days, 
                question_limit = :question_limit, features = :features 
            WHERE id = :id");

        $result = $stmt->execute([
            ':name'           => $name,
            ':price'          => $price,
            ':duration_days'  => $duration_days,
            ':question_limit' => $question_limit,
            ':features'       => $features,
            ':id'             => $id
        ]);

        if ($result) {
            // activity log
            log_activity($dbh, $user_id, "Updated plan", 'plans', $id, $ip_address);

            $_SESSION['toast'] = ['type'=>'success','message'=>'Plan updated successfully!'];
            header("Location: plan-records");
            exit;
        } else {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Failed to update plan.'];
        }
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Name, Price and Duration are required.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Plan | <?php echo htmlspecialchars($app_name); ?></title>
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
                <h5>Edit Plan</h5>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Edit Plan Form -->
      <div class="settings-form p-3">
        <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="mb-3">
                    <label class="form-label">Plan Name</label>
                    <input type="text" class="form-control" name="name" 
                           value="<?php echo htmlspecialchars($plan['name']); ?>" 
                           placeholder="Enter plan name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price ($)</label>
                    <input type="number" step="0.01" class="form-control" name="price" 
                           value="<?php echo htmlspecialchars($plan['price']); ?>" 
                           placeholder="Enter plan price" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Duration (Days)</label>
                    <input type="number" class="form-control" name="duration_days" 
                           value="<?php echo htmlspecialchars($plan['duration_days']); ?>" 
                           placeholder="Enter duration in days" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Question Limit</label>
                    <input type="number" class="form-control" name="question_limit" 
                           value="<?php echo htmlspecialchars($plan['question_limit']); ?>" 
                           placeholder="Enter question limit (0 = Unlimited)">
                </div>

                <div class="mb-3">
                    <label class="form-label">Features</label>
                    <textarea class="form-control" name="features" rows="4"
                              placeholder="Enter plan features"><?php echo htmlspecialchars($plan['features']); ?></textarea>
                </div>

                <button type="submit" name="update_plan" class="btn btn-primary">
                    <i class="fa fa-save"></i> Update
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

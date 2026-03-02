<?php 
include('../inc/app_data.php');
include '../database/connection.php';
if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

// ✅ Get selected plan
$planId = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;

// Fetch all plans
$plans = $dbh->query("SELECT id, name FROM plans ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all features
$features = $dbh->query("SELECT * FROM features ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch assigned features for selected plan
$assignedFeatures = [];
if ($planId > 0) {
    $stmt = $dbh->prepare("SELECT feature_id FROM plan_features WHERE plan_id = :plan_id");
    $stmt->execute([':plan_id' => $planId]);
    $assignedFeatures = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ✅ Handle Save Mapping
if (isset($_POST['save_mapping'])) {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $selectedFeatures = $_POST['features'] ?? [];

    if ($planId > 0) {
        // Delete old mapping
        $dbh->prepare("DELETE FROM plan_features WHERE plan_id = :plan_id")->execute([':plan_id' => $planId]);

        // Insert new mapping
        $stmt = $dbh->prepare("INSERT INTO plan_features (plan_id, feature_id) VALUES (:plan_id, :feature_id)");
        foreach ($selectedFeatures as $fid) {
            $stmt->execute([':plan_id' => $planId, ':feature_id' => $fid]);
        }

        $_SESSION['toast'] = ['type'=>'success','message'=>'Features mapped to plan successfully.'];

        // Activity log
        log_activity($dbh, $user_id, "mapped features to plan", 'plan_features', $planId, $ip_address);

        header("Location: plan-feature-mapping?plan_id=".$planId);
        exit;
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Please select a valid plan.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Map Plan to Features | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <style>.style1 {color:#000000}</style>
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
                <h5>Map Plan to Features</h5>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>

        <div class="row p-3">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header style1">Assign Features to Plan</div>
                    <div class="card-body">
                        <form method="GET" class="mb-3">
                            <label class="form-label style1">Select Plan</label>
                            <select name="plan_id" class="form-select" onChange="this.form.submit()">
                                <option value="">-- Choose Plan --</option>
                                <?php foreach($plans as $p): ?>
                                    <option value="<?= $p['id']; ?>" <?= ($planId==$p['id'])?'selected':''; ?>>
                                        <?= htmlspecialchars($p['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>

                        <?php if($planId): ?>
                        <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <input type="hidden" name="plan_id" value="<?= $planId; ?>">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($features as $f): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="features[]" value="<?= $f['id']; ?>"
                                                <?= in_array($f['id'], $assignedFeatures) ? 'checked' : ''; ?>>
                                        </td>
                                        <td><?= htmlspecialchars($f['name']); ?></td>
                                        <td><?= htmlspecialchars($f['code']); ?></td>
                                        <td><?= htmlspecialchars($f['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="submit" name="save_mapping" class="btn btn-primary">
                                <i class="fa fa-save"></i> Save Mapping
                            </button>
                            <p>&nbsp;</p>
                        </form>
                        <?php else: ?>
                            <p class="text-muted">Please select a plan to assign features.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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

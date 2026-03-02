<?php
include('../inc/app_data.php');
include '../database/connection.php'; // should create $dbh (PDO connection)

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}
// Pagination settings
$limit = 10; // records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total records
$stmt = $dbh->query("SELECT COUNT(*) FROM plans");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch plans
$sql = "SELECT * FROM plans 
        ORDER BY id DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Plan Records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <script type="text/javascript">
        function deldata(){
            return confirm("ARE YOU SURE YOU WISH TO DELETE THIS PLAN?");
        }
    </script>
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
            <h5>Plan Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Top Controls -->
    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
        <div>
            <a href="add-plan.php" class="btn btn-success">
                <i class="fa fa-plus"></i> New Plan
            </a>
        </div>
        <div>
            <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search Plan...">
        </div>
    </div>

    <!-- Plans Table -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">Plan Records</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped" id="transactionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Plan Name</th>
              <th>Price</th>
              <th>Duration (days)</th>
              <th>Question Limit</th>
              <th>Features</th>
              <th>Created At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($plans) {
              $cnt = $offset + 1;
              foreach ($plans as $plan) { ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td><?php echo htmlspecialchars($plan['name']); ?></td>
                  <td><?php echo htmlspecialchars($plan['price']); ?></td>
                  <td><?php echo htmlspecialchars($plan['duration_days']); ?></td>
                  <td><?php echo htmlspecialchars($plan['question_limit']); ?></td>
                  <td><?php echo nl2br(htmlspecialchars($plan['features'])); ?></td>
                  <td><?php echo htmlspecialchars($plan['created_at']); ?></td>
                  <td>
                    <a href="edit-plan.php?id=<?php echo $plan['id'];?>" 
                       class="me-2" title="Edit Plan">
                       <i class="fa fa-edit"></i>
                    </a>
                    <a href="delete_plan.php?id=<?php echo $plan['id'];?>" 
                       onclick="return deldata();" title="Delete Plan">
                       <i class="fa fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="8" class="text-center">No Plans Found</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <?php include('partials/pagination.php'); ?>
    </nav>
  </div>
</div>

<!-- Footer -->
<footer>
  <?php include('partials/footer.php'); ?>
</footer>

<?php include('partials/sweetalert.php'); ?>
<?php include('partials/table-script.php'); ?>
<?php include('partials/toogle-down.php'); ?>
</body>
</html>

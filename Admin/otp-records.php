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
$stmt = $dbh->query("SELECT COUNT(*) FROM otps");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch OTP records with user names
$sql = "SELECT ot.*, u.name 
        FROM otps ot 
        LEFT JOIN users u ON ot.user_id = u.id
        ORDER BY ot.id DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$otp = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>OTP records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <script type="text/javascript">
        function deldata(){
            return confirm("ARE YOU SURE YOU WISH TO DELETE THIS OTP?");
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
            <h5>OTP Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search OTP...">
    </div>

    <!-- OTP Records Table -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">OTP Records</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped" id="transactionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>OTP</th> 
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($otp) {
              $cnt = $offset + 1;
              foreach ($otp as $otps) { ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td><?php echo htmlspecialchars($otps['name'] ?? 'Unknown'); ?></td>
                  <td><?php echo htmlspecialchars($otps['otp']); ?></td>
                  <td><?php echo htmlspecialchars($otps['created_at']); ?></td>
                  <td>
                    <a href="delete_otp.php?id=<?php echo $otps['id'];?>" 
                       onclick="return deldata();">
                       <i class="fa fa-trash" aria-hidden="true" title="Delete Record"></i>
                    </a>
                  </td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="5" class="text-center">No OTP Found</td></tr>
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

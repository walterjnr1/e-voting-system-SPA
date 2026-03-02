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
$stmt = $dbh->query("SELECT COUNT(*) FROM activity_logs");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch activity logs with user names
$sql = "SELECT al.*, u.name 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.id DESC
        LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Activity Log | <?php echo htmlspecialchars($app_name); ?></title>
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
            <h5>Activity Log</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search Activity...">
    </div>

    <!-- Activity Log Table -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">Activity Log</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped" id="transactionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Action</th>
              <th>Table</th>
              <th>Record ID</th>
              <th>IP Address</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($logs) {
              $cnt = $offset + 1;
              foreach ($logs as $log) { ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td><?php echo htmlspecialchars($log['name'] ?? 'Unknown'); ?></td>
                  <td><?php echo htmlspecialchars($log['action']); ?></td>
                  <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                  <td><?php echo htmlspecialchars($log['record_id']); ?></td>
                  <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                  <td><?php echo htmlspecialchars($log['action_time']); ?></td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="7" class="text-center">No Activity Found</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <p>
      <!-- Pagination -->
      <nav aria-label="Page navigation">
        <?php include('partials/pagination.php'); ?>
      </nav>
      <footer>
        <?php include('partials/footer.php'); ?>
      </footer>
      
      <?php include('partials/table-script.php'); ?>
      <?php include('partials/toogle-down.php'); ?>
</p>
    <p>&nbsp;    </p>
  </div>
</div>

</body>
</html>

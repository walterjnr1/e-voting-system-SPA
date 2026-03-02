<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

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

// Count total records (use logins count)
$stmt = $dbh->query("SELECT COUNT(*) FROM logins");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch login records with user and session details
$sql = "SELECT l.id, l.user_id, l.device_id AS login_device_id,l.device_id AS login_device_name, l.device_type AS login_device_type, l.created_at AS login_date,
               u.name,
               s.device_id, s.device_type, s.ip_address, s.user_agent, s.login_token, s.last_active, s.created_at AS session_date
        FROM logins l
        LEFT JOIN users u ON l.user_id = u.id
        LEFT JOIN user_sessions s ON l.user_id = s.user_id AND l.device_id = s.device_id
        ORDER BY l.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$login = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login records | <?php echo htmlspecialchars($app_name); ?></title>
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
            <h5>Login Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search Login...">
    </div>

    <!-- Login Records Table -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">Login Records</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped" id="transactionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Device Type</th>
              <th>Device ID</th>
                            <th>Device Name</th>

              <th>IP Address</th>
              <th>User Agent</th>
              <th>Last Active</th>
              <th>Login Date</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($login) {
              $cnt = $offset + 1;
              foreach ($login as $logins) { ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td><?php echo htmlspecialchars($logins['name'] ?? 'Unknown'); ?></td>
                  <td><?php echo htmlspecialchars($logins['device_type'] ?? $logins['login_device_type']); ?></td>
                  <td><?php echo htmlspecialchars($logins['device_id'] ?? $logins['login_device_id']); ?></td>
                  <td><?php echo htmlspecialchars($logins['device_name']); ?></td>
                  <td><?php echo htmlspecialchars($logins['ip_address'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars(substr($logins['user_agent'] ?? 'N/A', 0, 40)); ?>...</td>
                  <td><?php echo $logins['last_active'] ? date('d-m-Y h:i A', strtotime($logins['last_active'])) : 'N/A'; ?></td>
                  <td><?php echo date('d-m-Y h:i A', strtotime($logins['login_date'])); ?></td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="8" class="text-center">No Login Found</td></tr>
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
    </p>
    <p>&nbsp;  </p>
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

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

// Count total records
$stmt = $dbh->query("SELECT COUNT(*) FROM marquee_notifications");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch notifications
$sql = "SELECT * FROM marquee_notifications ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Marquee Notification Records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <script type="text/javascript">
        function deldata(){
            return confirm("ARE YOU SURE YOU WISH TO DELETE THIS NOTIFICATION ?");
        }
    </script>
</head>
<body>

<div class="d-flex">
  <nav id="sidebar" class="d-flex flex-column p-3">
    <?php include('partials/sidebar.php'); ?>
  </nav>

  <div id="content" class="flex-grow-1">
    <div class="navbar-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
            <h5>Marquee Notification Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search in App Notification...">
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">Marquee Notification Record</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped" id="transactionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Message</th>
              <th>Start At</th>
              <th>Expires At</th>
              <th>Status</th>
               <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($notifications) {
              $cnt = $offset + 1;
              $current_time = date('Y-m-d H:i:s');
              
              foreach ($notifications as $note) { 
                  $start_time = $note['start_at'];
                  $expire_time = $note['expire_at'];

                  // Logic for Status
                  if ($start_time > $current_time) {
                      $status_label = '<span class="badge bg-info text-dark">Upcoming</span>';
                  } elseif (!empty($expire_time) && $expire_time < $current_time) {
                      $status_label = '<span class="badge bg-secondary">Expired</span>';
                  } else {
                      $status_label = '<span class="badge bg-success">Active</span>';
                  }
              ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td><?php echo htmlspecialchars($note['title']); ?></td>
                  <td><?php echo htmlspecialchars($note['message']); ?></td>
                  <td><?php echo htmlspecialchars($note['start_at']); ?></td>
                  <td><?php echo htmlspecialchars($note['expire_at'] ?: 'No Expiry'); ?></td>
                  <td>
                    <?php echo $status_label; ?>
                  </td>
                  <td>
                    <div align="center">
                      <a href="delete-marquee-notification?id=<?php echo $note['id'];?>" onClick="return deldata();">
                        <i class="fa fa-trash text-danger" aria-hidden="true" title="Delete Record"></i>
                      </a> 
                      <a href="edit-marquee-notification?id=<?php echo $note['id'];?>">
                        <i class="fa fa-edit text-primary" aria-hidden="true" title="Edit Record"></i>
                      </a>      
                    </div>
                  </td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="7" class="text-center">No Notifications Found</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <p>
      <nav aria-label="Page navigation">
        <?php include('partials/pagination.php'); ?>
      </nav>
      <footer>
        <?php include('partials/footer.php'); ?>
      </footer>
      
      <?php include('partials/table-script.php'); ?>
      <?php include('partials/toogle-down.php'); ?>
      <?php include('partials/sweetalert.php'); ?>
    </p>
    <p>&nbsp;      </p>
  </div>
</div>

</body>
</html>
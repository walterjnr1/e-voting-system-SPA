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
$stmt = $dbh->query("SELECT COUNT(*) FROM subjects");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch subjects records
$sql = "SELECT * FROM subjects ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Subject Records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <script type="text/javascript">
        function deldata(){
            return confirm("ARE YOU SURE YOU WISH TO DELETE THIS SUBJECT?");
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
        <h5>Subject Records</h5>
      </div>
      <div>
        <a href="logout" class="btn btn-outline-danger">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search subjects...">
    </div>

    <!-- Subjects Records Card -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">Subject Records</h5>
      </div>

      <!-- Add New Subject Button -->
      <div class="p-3">
        <a href="add-subject" class="btn btn-primary">
          <i class="fa fa-plus"></i> Add New Subject
        </a>
      </div>

      <div class="card-body table-responsive">
        <table class="table table-striped" id="transactionTable">
          <thead>
            <tr>
              <th width="17"><div align="center">#</div></th>
              <th width="40"><div align="center">Name</div></th>
              <th width="85"><div align="center">Action</div></th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($subjects) {
              $cnt = $offset + 1;
              foreach ($subjects as $subject) { ?>
                <tr>
                  <td><div align="center"><?php echo $cnt++; ?></div></td>
                  <td><div align="center"><?php echo htmlspecialchars($subject['name']); ?></div></td>
                  <td>
                    <div align="center">
                      <a href="delete_subject?id=<?php echo $subject['id'];?>" onClick="return deldata();">
                        <i class="fa fa-trash text-danger" aria-hidden="true" title="Delete Subject"></i>
                      </a> 
                      <a href="edit-subject?id=<?php echo $subject['id'];?>">
                        <i class="fa fa-edit text-primary" aria-hidden="true" title="Edit Subject"></i>
                      </a>      
                    </div>
                  </td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="3" class="text-center">No subject Found</td></tr>
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

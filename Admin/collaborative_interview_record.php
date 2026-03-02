<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// Pagination settings
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total unique shared batches
$stmt = $dbh->query("SELECT COUNT(DISTINCT batch_id) FROM interview_batch_shares");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

/**
 * Fetch shared interviews. 
 * We group by batch_id so the admin sees the "Shared Package" rather than individual lines for every question.
 */
$sql = "SELECT 
            ibs.batch_id, 
            ibs.created_at AS shared_at,
            sender.name AS shared_by_name,
            recipient.name AS shared_with_name,
            i.job_title, 
            i.industry, 
            i.category,
            COUNT(i.id) as question_count
        FROM interview_batch_shares ibs
        JOIN users sender ON ibs.sender_id = sender.id
        JOIN users recipient ON ibs.shared_with_user_id = recipient.id
        JOIN interviews i ON ibs.batch_id = i.batch_id
        GROUP BY ibs.batch_id, ibs.sender_id, ibs.shared_with_user_id
        ORDER BY ibs.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$shared_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Collaborative Interview Records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <style>
        .batch-id-pill { font-family: monospace; font-size: 0.85rem; background: #e9ecef; padding: 2px 8px; border-radius: 4px; }
    </style>
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
            <h5>Collaborative Interview Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search Shared Batch...">
    </div>

    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i>Shared Interview Batches</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle" id="transactionTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Batch ID</th>
              <th>Shared By</th>
              <th>Shared With</th>
              <th>Job Info</th>
              <th>Category</th>
              <th>Qns</th>
              <th>Date Shared</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($shared_batches) {
              $cnt = $offset + 1;
              foreach ($shared_batches as $batch) { ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td><span class="batch-id-pill"><?php echo htmlspecialchars($batch['batch_id']); ?></span></td>
                  <td><strong><?php echo htmlspecialchars($batch['shared_by_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($batch['shared_with_name']); ?></td>
                  <td>
                      <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;"><?php echo htmlspecialchars($batch['industry']); ?></small>
                      <?php echo htmlspecialchars($batch['job_title']); ?>
                  </td>
                  <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($batch['category']); ?></span></td>
                  <td><span class="badge rounded-pill bg-secondary"><?php echo $batch['question_count']; ?></span></td>
                  <td><small><?php echo date('d M Y, h:i A', strtotime($batch['shared_at'])); ?></small></td>
                  
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="9" class="text-center py-4">No Collaborative interview Found</td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <nav aria-label="Page navigation">
      <?php include('partials/pagination.php'); ?>
    </nav>
    
    <footer>
      <?php include('partials/footer.php'); ?>
    </footer>
      
    <?php include('partials/table-script.php'); ?>
    <?php include('partials/toogle-down.php'); ?>
  </div>
</div>

</body>
</html>
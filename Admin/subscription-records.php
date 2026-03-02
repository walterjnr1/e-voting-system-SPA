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

// Count total records (subscriptions count)
$stmt = $dbh->query("SELECT COUNT(*) FROM subscriptions");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch subscription records with user, plan, and invoice details
$sql = "SELECT s.id, s.user_id, s.plan_id, s.start_date, s.end_date, s.status, s.reference_id,
               u.name AS user_name,
               p.name AS plan_name, p.price, p.duration_days,
               inv.invoice_number, inv.amount AS invoice_amount, inv.status AS invoice_status
        FROM subscriptions s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN plans p ON s.plan_id = p.id
        LEFT JOIN invoices inv ON s.id = inv.subscription_id
        ORDER BY s.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Helper function for status badges
 */
function getStatusBadge($status) {
    switch ($status) {
        case 'active': return '<span class="badge bg-success">Active</span>';
        case 'trialing': return '<span class="badge bg-info">Trialing</span>';
        case 'paused': return '<span class="badge bg-warning text-dark">Paused</span>';
        case 'canceled': return '<span class="badge bg-secondary">Canceled</span>';
        case 'expired': return '<span class="badge bg-danger">Expired</span>';
        default: return '<span class="badge bg-dark">' . htmlspecialchars($status) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Subscription Records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
</head>
<body>

<div class="d-flex">
  <nav id="sidebar" class="d-flex flex-column p-3">
    <?php include('partials/sidebar.php'); ?>
  </nav>

  <div id="content" class="flex-grow-1">
    <div class="navbar-custom d-flex justify-content-between align-items-center p-3 shadow-sm mb-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
            <h5 class="mb-0">Subscription Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="d-flex justify-content-end mb-3">
            <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search records...">
        </div>

        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-primary">Billing & Subscription History</h5>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-hover align-middle" id="transactionTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Invoice / Ref</th>
                  <th>User</th>
                  <th>Plan</th>
                  <th>Amount</th>
                  <th>Duration</th>
                  <th>Period</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                if ($subscriptions) {
                  $cnt = $offset + 1;
                  foreach ($subscriptions as $sub) { ?>
                    <tr>
                      <td><?php echo $cnt++; ?></td>
                      <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($sub['invoice_number'] ?? 'N/A'); ?></div>
                        <small class="text-muted">Ref: <?php echo htmlspecialchars($sub['reference_id'] ?? 'N/A'); ?></small>
                      </td>
                      <td><?php echo htmlspecialchars($sub['user_name'] ?? 'Unknown'); ?></td>
                      <td><?php echo htmlspecialchars($sub['plan_name'] ?? 'N/A'); ?></td>
                      <td><?php echo '₦' . number_format((float)($sub['invoice_amount'] ?? $sub['price']), 2); ?></td>
                      <td><?php echo htmlspecialchars($sub['duration_days']); ?> Days</td>
                      <td>
                        <small class="d-block text-success">S: <?php echo date('d-m-Y', strtotime($sub['start_date'])); ?></small>
                        <small class="d-block text-danger">E: <?php echo date('d-m-Y', strtotime($sub['end_date'])); ?></small>
                      </td>
                      <td><?php echo getStatusBadge($sub['status']); ?></td>
                    </tr>
                  <?php }
                } else { ?>
                  <tr><td colspan="8" class="text-center py-4">No Subscription Records Found</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

        <nav aria-label="Page navigation" class="mt-3">
            <?php include('partials/pagination.php'); ?>
        </nav>
    </div>

    <footer class="mt-5">
        <?php include('partials/footer.php'); ?>
    </footer>
      
    <?php include('partials/table-script.php'); ?>
    <?php include('partials/toogle-down.php'); ?>
  </div>
</div>

<script>
// Simple filter logic for Search Input
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#transactionTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- PAGINATION LOGIC ---
$limit = 15; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Count total logs
$total_stmt = $dbh->query("SELECT COUNT(*) FROM audit_logs");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Fetch logs with user names
$stmt = $dbh->prepare("SELECT a.*, u.full_name, u.role 
                       FROM audit_logs a 
                       LEFT JOIN users u ON a.user_id = u.id 
                       ORDER BY a.created_at DESC 
                       LIMIT $start, $limit");
$stmt->execute();
$allLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .ip-badge { font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; }
        .action-text { color: #374151; font-weight: 500; }
    </style>
</head>
<body>

<div id="sidebar-overlay"></div>
<div class="d-flex">
    <nav id="sidebar" class="d-flex flex-column p-3 shadow">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <div id="content" class="flex-grow-1">
        <div class="navbar-custom d-flex justify-content-between align-items-center sticky-top">
            <?php include('partials/navbar.php');?>
        </div>

        <div class="p-3 p-md-4">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold">System Audit Logs</h4>
                    <p class="text-muted small">Track user activities and system changes</p>
                </div>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Activity History</h6>
                            <div class="input-group input-group-sm" style="width: 280px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-filter text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="dynamicSearch" placeholder="Search by user or action...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3" style="width: 200px;">Timestamp</th>
                                        <th>User</th>
                                        <th>Action Performed</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if (count($allLogs) > 0): ?>
                                        <?php foreach ($allLogs as $row): ?>
                                        <tr>
                                            <td class="ps-3 text-muted">
                                                <i class="far fa-clock me-1"></i>
                                                <?= date('M d, Y | H:i:s', strtotime($row['created_at'])) ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['full_name'] ?? 'System/Unknown') ?></div>
                                                <div class="text-xs text-muted text-uppercase"><?= htmlspecialchars($row['role'] ?? 'N/A') ?></div>
                                            </td>
                                            <td class="action-text">
                                                <?= htmlspecialchars($row['action']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border ip-badge">
                                                    <i class="fas fa-network-wired me-1 text-muted"></i>
                                                    <?= htmlspecialchars($row['ip_address']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <i class="fas fa-history fa-3x text-light mb-3"></i>
                                                <p class="text-muted">No activity logs found in the database.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer bg-white border-0 py-3">
                            <nav>
                               <?php include('partials/pagination.php'); ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="main-footer text-center mt-5 py-3">
                <?php include('partials/footer.php'); ?>
            </footer>
        </div>
    </div>
</div>

<?php include('partials/table-script.php'); ?>

<script>
    // Simple live search for the audit log
    document.getElementById('dynamicSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#tableBody tr');

        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

</body>
</html>
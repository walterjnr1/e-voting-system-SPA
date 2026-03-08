<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- PAGINATION LOGIC ---
$limit = 15; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

try {
    // Fetch total records from failed_login table
    $total_stmt = $dbh->query("SELECT COUNT(*) FROM failed_login");
    $total_results = $total_stmt->fetchColumn();
    $total_pages = ceil($total_results / $limit);

    // Fetch Paginated Data with User details (if the user exists)
    $stmt = $dbh->prepare("SELECT fl.*, u.full_name, u.email, u.phone 
                           FROM failed_login fl 
                           LEFT JOIN users u ON fl.user_id = u.id 
                           ORDER BY fl.attempt_time DESC LIMIT :start, :limit");
    
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $failedAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $failedAttempts = [];
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Failed Logins | <?php echo htmlspecialchars($app_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
    <style>
        .text-xs { font-size: 0.75rem; }
        .bg-danger-light { background-color: #fff5f5; }
        .border-danger-subtle { border-color: #feb2b2 !important; }
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
                    <h4 class="fw-bold text-danger"><i class="fas fa-shield-alt me-2"></i>Failed Login Records</h4>
                    <p class="text-muted small">Monitor unauthorized access attempts and potential brute-force attacks</p>
                </div>
                <button onclick="window.location.reload()" class="btn btn-outline-danger btn-sm shadow-sm">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Logs
                </button>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-top border-danger border-4">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Security Incident Logs</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="dynamicSearch" placeholder="Search IP or Email...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Target Account</th>
                                        <th>IP Address</th>
                                        <th>Attempt Date & Time</th>
                                        <th>Status</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if(empty($failedAttempts)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No failed login records found.</td></tr>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($failedAttempts as $attempt): ?>
                                    <tr class="<?= is_null($attempt['user_id']) ? 'bg-danger-light' : '' ?>">
                                        <td class="ps-3">
                                            <?php if($attempt['user_id']): ?>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($attempt['full_name']) ?></div>
                                                <div class="text-muted text-xs"><?= htmlspecialchars($attempt['email']) ?></div>
                                            <?php else: ?>
                                                <div class="text-danger fw-bold"><i class="fas fa-user-secret me-1"></i> Non-existent User</div>
                                                <div class="text-muted text-xs">Unknown Credentials Used</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code class="text-danger fw-bold"><?= htmlspecialchars($attempt['ip_address']) ?></code>
                                            <a href="https://whois.domaintools.com/<?= $attempt['ip_address'] ?>" target="_blank" class="ms-1 text-muted" title="Lookup IP">
                                                <i class="fas fa-external-link-alt fa-xs"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= date('M d, Y', strtotime($attempt['attempt_time'])) ?></div>
                                            <div class="text-muted text-xs"><?= date('h:i A', strtotime($attempt['attempt_time'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-danger">Failed Attempt</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <button onclick="confirmDelete(<?= $attempt['id'] ?>)" class="btn btn-sm btn-light text-danger" title="Clear Record">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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

<?php include('partials/sweetalert.php'); ?>
<?php include('partials/table-script.php'); ?>

</body>
</html>
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
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

try {
    // Fetch total records from voter_sessions table
    $total_stmt = $dbh->query("SELECT COUNT(*) FROM voter_sessions");
    $total_results = $total_stmt->fetchColumn();
    $total_pages = ceil($total_results / $limit);

    // Fetch Paginated Data with User details (Full Name and Nickname)
    $stmt = $dbh->prepare("SELECT vs.*, u.full_name, u.nickname, u.email 
                           FROM voter_sessions vs 
                           LEFT JOIN users u ON vs.user_id = u.id 
                           ORDER BY vs.id DESC LIMIT :start, :limit");
    
    // Using bindValue for integer limits to avoid PDO string casting issues
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $allSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allSessions = [];
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Sessions | <?php echo htmlspecialchars($app_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo htmlspecialchars($app_logo); ?>" type="image/x-icon">
    <style>
        .text-xs { font-size: 0.75rem; }
        .badge-logout { background-color: #f8d7da; color: #842029; }
        .badge-active { background-color: #d1e7dd; color: #0f5132; }
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
                    <h4 class="fw-bold">Voter Session Logs</h4>
                    <p class="text-muted small">Monitor active and past voter access sessions</p>
                </div>
                <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Logs
                </button>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Session History</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="dynamicSearch" placeholder="Search sessions...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Voter / Nickname</th>
                                        <th>IP Address</th>
                                        <th>Device / Browser</th>
                                        <th>Login Time</th>
                                        <th>Logout Time</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if(empty($allSessions)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No session records found.</td></tr>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($allSessions as $sess): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($sess['full_name'] ?? 'Unknown Voter') ?></div>
                                            <div class="text-primary text-xs italic">
                                                <?= !empty($sess['nickname']) ? '"'.htmlspecialchars($sess['nickname']).'"' : 'No Nickname' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($sess['ip_address']) ?></span>
                                        </td>
                                        <td style="max-width: 250px;">
                                            <span class="text-muted text-xs d-block text-truncate" title="<?= htmlspecialchars($sess['device_name']) ?>">
                                                <?= htmlspecialchars($sess['device_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= date('M d, Y', strtotime($sess['login_time'])) ?></div>
                                            <div class="text-muted text-xs"><?= date('h:i A', strtotime($sess['login_time'])) ?></div>
                                        </td>
                                        <td>
                                            <?php if($sess['logout_time']): ?>
                                                <div class="fw-semibold text-danger"><?= date('M d, Y', strtotime($sess['logout_time'])) ?></div>
                                                <div class="text-muted text-xs"><?= date('h:i A', strtotime($sess['logout_time'])) ?></div>
                                            <?php else: ?>
                                                <span class="badge badge-active fw-normal">Currently Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <button onclick="confirmDelete(<?= $sess['id'] ?>)" class="btn btn-sm btn-light text-danger" title="Remove Record">
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
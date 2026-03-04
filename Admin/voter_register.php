<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- PAGINATION LOGIC ---
$limit = 20; // Increased limit for a register view
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Only count voters who are Verified, Financially Active, and Account Active
$count_sql = "SELECT COUNT(*) FROM users WHERE role = 'voter' AND is_verified = 1 AND status = 'active'";
$total_results = $dbh->query($count_sql)->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Fetch only qualified voters
$stmt = $dbh->prepare("SELECT full_name, email, phone, created_at, has_voted FROM users WHERE role = 'voter' AND is_verified = 1 AND status = 'active' 
                       ORDER BY full_name ASC LIMIT $start, $limit");
$stmt->execute();
$qualifiedVoters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Voter Register | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .text-xs { font-size: 0.72rem; }
        .voted-badge { font-size: 0.65rem; padding: 2px 8px; }
        @media print {
            #sidebar, .navbar-custom, .btn, .input-group, .card-footer { display: none !important; }
            #content { margin-left: 0 !important; width: 100% !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
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
                    <h4 class="fw-bold text-dark"><i class="fas fa-clipboard-check me-2"></i>Official Voter Register</h4>
                    <p class="text-muted small">List of all verified voters.</p>
                </div>
                <button onclick="window.print()" class="btn btn-outline-dark shadow-sm">
                    <i class="fas fa-print me-2"></i>Print Register
                </button>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-uppercase text-primary" style="letter-spacing: 1px;">Verified Voters (Total: <?= $total_results ?>)</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <input type="text" class="form-control" id="dynamicSearch" placeholder="Search register...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr class="text-xs text-uppercase">
                                        <th class="ps-3" style="width: 50px;">#</th>
                                        <th>Voter Name</th>
                                        <th>Contact Information</th>
                                        <th>Registration Date</th>
                                        <th class="text-center">Voting Status</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if(count($qualifiedVoters) > 0): ?>
                                        <?php $sn = $start + 1; foreach ($qualifiedVoters as $row): ?>
                                        <tr>
                                            <td class="ps-3 text-muted"><?= $sn++ ?>.</td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                                            </td>
                                            <td>
                                                <div class="text-muted text-xs"><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($row['email']) ?></div>
                                                <div class="text-muted text-xs"><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                            </td>
                                            <td>
                                                <div class="text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <?php if($row['has_voted']): ?>
                                                    <span class="badge bg-success voted-badge"><i class="fas fa-check me-1"></i> BALLOT CAST</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border voted-badge">PENDING</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fas fa-user-slash fa-3x mb-3"></i><br>
                                                No voters currently meet the qualification criteria.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-0 py-3">
                            <?php include('partials/pagination.php'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('partials/table-script.php'); ?>


</body>
</html>
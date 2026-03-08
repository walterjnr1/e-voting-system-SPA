<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 



if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- PAGINATION LOGIC ---
$limit = 20; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Only count voters who are Verified or Pending, and Account Active
$count_sql = "SELECT COUNT(*) FROM users WHERE status = 'active'";
$total_results = $dbh->query($count_sql)->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Fetch qualified voters including user_image and nickname
$stmt = $dbh->prepare("SELECT full_name, nickname, email, phone, user_image, created_at, has_voted, is_verified 
                       FROM users 
                       WHERE status = 'active' 
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
        .voted-badge { font-size: 0.65rem; padding: 4px 10px; border-radius: 50px; }
        .voter-avatar { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #e9ecef; }
        .nickname-tag { font-size: 0.68rem; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; font-weight: 600; }
        @media print {
            #sidebar, .navbar-custom, .btn, .input-group, .card-footer, .nickname-tag { display: none !important; }
            #content { margin-left: 0 !important; width: 100% !important; }
            .card { border: none !important; box-shadow: none !important; }
            .table-dark { background-color: #fff !important; color: #000 !important; border-bottom: 2px solid #000; }
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
                    <p class="text-muted small">Manage and view all verified and pending members.</p>
                </div>
                <button onclick="window.print()" class="btn btn-outline-dark shadow-sm">
                    <i class="fas fa-print me-2"></i>Print Register
                </button>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-uppercase text-primary" style="letter-spacing: 1px;">Voter List (Total: <?= $total_results ?>)</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <input type="text" class="form-control" id="dynamicSearch" placeholder="Search register...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr class="text-xs text-uppercase">
                                        <th class="ps-3" style="width: 50px;">#</th>
                                        <th>Voter Details</th>
                                        <th>Contact</th>
                                        <th>Reg. Date</th>
                                        <th class="text-center">Verification</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if(count($qualifiedVoters) > 0): ?>
                                        <?php $sn = $start + 1; foreach ($qualifiedVoters as $row): 
                                            $image_path = (!empty($row['user_image'])) ? '../' . $row['user_image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                                        ?>
                                        <tr>
                                            <td class="ps-3 text-muted"><?= $sn++ ?>.</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($image_path) ?>" class="voter-avatar me-3" alt="Profile" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name']) ?></div>
                                                        <?php if(!empty($row['nickname'])): ?>
                                                            <span class="nickname-tag uppercase">"<?= htmlspecialchars($row['nickname']) ?>"</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-muted text-xs"><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($row['email']) ?></div>
                                                <div class="text-muted text-xs"><i class="fas fa-phone me-1 text-success"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                            </td>
                                            <td>
                                                <div class="text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <?php if((int)$row['is_verified'] === 1): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle voted-badge">
                                                        <i class="fas fa-check-circle me-1"></i> VERIFIED
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle voted-badge">
                                                        <i class="fas fa-clock me-1"></i> PENDING
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fas fa-user-slash fa-3x mb-3"></i><br>
                                                No voters currently found in the system.
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
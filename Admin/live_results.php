<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- PAGINATION LOGIC ---
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Count total candidates for pagination
$total_stmt = $dbh->query("SELECT COUNT(*) FROM candidates");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Fetch total votes in the system for percentage calculation
$total_votes_overall = $dbh->query("SELECT COUNT(*) FROM votes")->fetchColumn();

// SQL logic: Fetch candidates and join with a subquery that counts their specific votes
$sql = "SELECT c.*, u.full_name, e.title as election_title, p.title as position_title,
        (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) as vote_count,
        (SELECT COUNT(*) FROM votes v2 WHERE v2.position_id = c.position_id) as total_position_votes
        FROM candidates c
        JOIN users u ON c.user_id = u.id
        JOIN elections e ON c.election_id = e.id
        JOIN positions p ON c.position_id = p.id
        WHERE c.status = 'approved'
        ORDER BY vote_count DESC LIMIT $start, $limit";

$stmt = $dbh->prepare($sql);
$stmt->execute();
$allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Election Results | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .candidate-img { width: 45px; height: 45px; object-fit: cover; border-radius: 10px; border: 2px solid #eef0f7; }
        .progress { height: 8px; border-radius: 10px; background-color: #f0f2f5; }
        .live-indicator { width: 10px; height: 10px; background: #22c55e; border-radius: 50%; display: inline-block; margin-right: 5px; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
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
                    <h4 class="fw-bold"><span class="live-indicator"></span>Live Election Tally</h4>
                    <p class="text-muted small">Real-time vote distribution across all active positions</p>
                </div>
                <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="fas fa-sync-alt me-2"></i>Refresh Data
                </button>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-uppercase small tracking-wider">Standing Records</h6>
                            <span class="badge bg-light text-dark border">Total Votes Cast: <?= number_format($total_votes_overall) ?></span>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Candidate</th>
                                        <th>Election & Category</th>
                                        <th style="width: 30%;">Vote Performance</th>
                                        <th class="text-center">Tally</th>
                                        <th class="text-end pe-3">Standing</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if (count($allResults) > 0): ?>
                                        <?php foreach ($allResults as $row): 
                                            // Calculate percentage relative to its position
                                            $total_pos = $row['total_position_votes'] ?: 1;
                                            $percentage = ($row['vote_count'] / $total_pos) * 100;
                                        ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="../<?= htmlspecialchars($row['photo']) ?>" class="candidate-img" onerror="this.src='../assets/img/default-user.png'">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                                                        <div class="text-muted x-small">ID: #<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['position_title']) ?></div>
                                                <div class="text-muted text-xs"><?= htmlspecialchars($row['election_title']) ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="fw-bold text-primary"><?= number_format($percentage, 1) ?>%</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percentage ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <h5 class="mb-0 fw-black text-dark"><?= number_format($row['vote_count']) ?></h5>
                                                <small class="text-muted uppercase x-small">Votes</small>
                                            </td>
                                            <td class="text-end pe-3">
                                                <?php if($percentage >= 50): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Leading</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-pill px-3">Contending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-5">No voting records found.</td></tr>
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

<script>
    // Live refresh every 30 seconds
    setInterval(function(){
        location.reload();
    }, 30000);
</script>

<?php include('partials/table-script.php'); ?>
<?php include('partials/sweetalert.php'); ?>
</body>
</html>
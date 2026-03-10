<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- DECRYPTION SETUP ---
$encryption_method = "AES-256-CBC";
$key = hash('sha256', ENCRYPTION_KEY);
$iv = substr(hash('sha256', ENCRYPTION_IV), 0, 16);

// --- 1. TALLY ALL ENCRYPTED VOTES IN PHP ---
// We fetch all votes for the entire system once to avoid heavy loops
$vote_stmt = $dbh->query("SELECT candidate_id, position_id FROM votes");
$raw_votes = $vote_stmt->fetchAll(PDO::FETCH_ASSOC);

$candidate_tallies = [];
$position_tallies = [];
$total_votes_overall = count($raw_votes);

foreach ($raw_votes as $v) {
    $decrypted_id = openssl_decrypt($v['candidate_id'], $encryption_method, $key, 0, $iv);
    if ($decrypted_id) {
        // Tally for specific candidate
        $candidate_tallies[$decrypted_id] = ($candidate_tallies[$decrypted_id] ?? 0) + 1;
        // Tally for the position to calculate percentages
        $position_tallies[$v['position_id']] = ($position_tallies[$v['position_id']] ?? 0) + 1;
    }
}

// --- 2. PAGINATION LOGIC ---
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$total_stmt = $dbh->query("SELECT COUNT(*) FROM candidates WHERE status = 'approved'");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// --- 3. FETCH CANDIDATE DETAILS ---
$sql = "SELECT c.*, u.full_name, e.title as election_title, p.title as position_title
        FROM candidates c
        JOIN users u ON c.user_id = u.id
        JOIN elections e ON c.election_id = e.id
        JOIN positions p ON c.position_id = p.id
        WHERE c.status = 'approved'
        LIMIT $start, $limit";

$stmt = $dbh->prepare($sql);
$stmt->execute();
$candidates_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 4. MERGE TALLIES INTO LIST ---
$allResults = [];
foreach ($candidates_list as $row) {
    $row['vote_count'] = $candidate_tallies[$row['id']] ?? 0;
    $row['total_position_votes'] = $position_tallies[$row['position_id']] ?? 0;
    $allResults[] = $row;
}

// Sort the current page by vote count manually since we couldn't do it in SQL
usort($allResults, function($a, $b) {
    return $b['vote_count'] <=> $a['vote_count'];
});
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
        .x-small { font-size: 0.75rem; }
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
                    <p class="text-muted small">Real-time decryption of vote vault across all active positions</p>
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
                            <span class="badge bg-primary-subtle text-primary border">Total Votes Cast: <?= number_format($total_votes_overall) ?></span>
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
                                                <div class="text-muted x-small"><?= htmlspecialchars($row['election_title']) ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-primary"><?= number_format($percentage, 1) ?>%</span>
                                                    <span class="text-muted x-small"><?= $row['vote_count'] ?> / <?= $total_pos ?></span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percentage ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <h5 class="mb-0 fw-bold text-dark"><?= number_format($row['vote_count']) ?></h5>
                                                <small class="text-muted text-uppercase x-small">Votes</small>
                                            </td>
                                            <td class="text-end pe-3">
                                                <?php if($percentage >= 50 && $row['vote_count'] > 0): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Leading</span>
                                                <?php elseif($row['vote_count'] > 0): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">Contending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-pill px-3">No Votes</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-5">No approved candidates found.</td></tr>
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
    // Live refresh every 30 seconds to update decryptions
    setInterval(function(){
        location.reload();
    }, 30000);
</script>

<?php include('partials/table-script.php'); ?>
<?php include('partials/sweetalert.php'); ?>
</body>
</html>
<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- ENCRYPTION CONFIG (Must match ballot.php) ---
$encryption_method = "AES-256-CBC";
$key = hash('sha256', ENCRYPTION_KEY);
$iv = substr(hash('sha256', ENCRYPTION_IV), 0, 16);

// --- PAGINATION LOGIC ---
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$total_stmt = $dbh->query("SELECT COUNT(*) FROM votes");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

/**
 * NOTE: We cannot JOIN on candidate_id anymore because it's encrypted.
 * We fetch the raw votes first, then resolve candidate details in PHP.
 */
$sql = "SELECT v.*, 
               e.title as election_title, 
               p.title as position_title
        FROM votes v
        JOIN elections e ON v.election_id = e.id
        JOIN positions p ON v.position_id = p.id
        ORDER BY v.voted_at DESC LIMIT $start, $limit";

$stmt = $dbh->prepare($sql);
$stmt->execute();
$rawVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allVotes = [];

foreach ($rawVotes as $row) {
    // 1. Decrypt the candidate ID
    $decrypted_candidate_id = openssl_decrypt($row['candidate_id'], $encryption_method, $key, 0, $iv);
    
    // 2. Fetch Candidate and User details for this specific decrypted ID
    $canStmt = $dbh->prepare("SELECT u.full_name, c.photo 
                              FROM candidates c 
                              JOIN users u ON c.user_id = u.id 
                              WHERE c.id = ?");
    $canStmt->execute([$decrypted_candidate_id]);
    $candidateDetails = $canStmt->fetch(PDO::FETCH_ASSOC);

    // 3. Merge data back into the row for the UI
    $row['candidate_name'] = $candidateDetails['full_name'] ?? 'Unknown Candidate';
    $row['candidate_photo'] = $candidateDetails['photo'] ?? 'uploadImage/Profile/default.png';
    
    $allVotes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Audit Trail | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .text-xs { font-size: 0.72rem; }
        .candidate-img { width: 35px; height: 35px; border-radius: 6px; object-fit: cover; border: 1px solid #eee; }
        .ip-badge { font-family: 'Courier New', monospace; font-size: 0.8rem; background: #f8f9fa; border: 1px solid #dee2e6; padding: 2px 6px; border-radius: 4px; }
        .encryption-tag { font-size: 0.65rem; padding: 1px 4px; border-radius: 3px; background: #e0e7ff; color: #4338ca; font-weight: bold; }
        .btn-vote { background-color: #0a192f; color: white; border: none; transition: 0.3s; }
        .btn-vote:hover { background-color: #162a4a; color: #fff; transform: translateY(-2px); }
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
            <div class="mb-4 d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="fw-bold text-dark">Votes Record</h4>
                    <p class="text-muted small">Live decrypted audit trail of the secure vote vault</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="../election/ballot" target="_blank" class="btn btn-vote btn-sm px-3 shadow-sm">
                        <i class="fas fa-vote-yea me-2"></i>Cast Vote
                    </a>
                    <span class="badge bg-primary d-flex align-items-center">Total Ballots: <?= number_format($total_results) ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Recent Ballots</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <input type="text" class="form-control" id="dynamicSearch" placeholder="Search decrypted records...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="text-xs text-uppercase text-muted">
                                        <th class="ps-3">Vote ID</th>
                                        <th>Candidate Recipient</th>
                                        <th>Election Details</th>
                                        <th>Origin (IP)</th>
                                        <th>Timestamp</th>
                                        <th class="text-end pe-3">Security</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if (count($allVotes) > 0): ?>
                                        <?php foreach ($allVotes as $row): ?>
                                        <tr>
                                            <td class="ps-3 text-muted">#<?= $row['id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../<?= htmlspecialchars($row['candidate_photo']) ?>" 
                                                         class="candidate-img me-2" 
                                                         onerror="this.src='../uploadImage/Profile/default.png'">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['candidate_name']) ?></div>
                                                        <div class="text-muted text-xs"><?= htmlspecialchars($row['position_title']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-dark fw-medium"><?= htmlspecialchars($row['election_title']) ?></div>
                                                <span class="encryption-tag">AES-256-CBC</span>
                                            </td>
                                            <td>
                                                <span class="ip-badge"><i class="fas fa-network-wired me-1 text-muted"></i><?= $row['voter_ip'] ?></span>
                                            </td>
                                            <td>
                                                <div class="text-dark"><?= date('M d, Y', strtotime($row['voted_at'])) ?></div>
                                                <div class="text-muted text-xs"><?= date('h:i A', strtotime($row['voted_at'])) ?></div>
                                            </td>
                                            <td class="text-end pe-3">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="fas fa-lock me-1"></i> Decrypted
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No votes have been cast yet.</td></tr>
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
<?php include('partials/sweetalert.php'); ?>
</body>
</html>
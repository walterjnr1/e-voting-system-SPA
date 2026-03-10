<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

/** 1. FETCH DYNAMIC STATS **/
// Total Voters
$totalVoters = $dbh->query("SELECT COUNT(*) FROM users ")->fetchColumn();

// Total Votes Cast
$totalVotesCast = $dbh->query("SELECT COUNT(*) FROM votes")->fetchColumn();

// Total Approved Candidates
$totalCandidates = $dbh->query("SELECT COUNT(*) FROM candidates WHERE status = 'approved'")->fetchColumn();

// Active Elections
$activeElections = $dbh->query("SELECT COUNT(*) FROM elections WHERE status = 'active'")->fetchColumn();

/** 2. FETCH VOTING TREND (Last 24 Hours) **/
$trendStmt = $dbh->query("SELECT DATE_FORMAT(voted_at, '%H:00') as time, COUNT(*) as count 
                          FROM votes 
                          WHERE voted_at >= NOW() - INTERVAL 1 DAY 
                          GROUP BY time ORDER BY time ASC");
$votingTrend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

/** 3. FETCH RECENT ACTIVITY (Last 5 Votes) **/
$recentVotesStmt = $dbh->query("SELECT u.full_name as voter, e.title as election, v.voted_at as time 
                                FROM votes v JOIN users u ON v.candidate_id = u.id JOIN elections e ON v.election_id = e.id 
                                ORDER BY v.voted_at DESC LIMIT 5");
$recentVotes = $recentVotesStmt->fetchAll(PDO::FETCH_ASSOC);

/** 4. SYSTEM AUDIT LOGS **/
$auditStmt = $dbh->query("SELECT a.*, u.full_name as username 
                          FROM audit_logs a 
                          LEFT JOIN users u ON a.user_id = u.id 
                          ORDER BY a.created_at DESC LIMIT 5");
$activityLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

/** 5. VOTER TURNOUT (Financial Status Breakdown) **/
$turnoutStmt = $dbh->query("SELECT financial_status as name, COUNT(*) as count FROM users GROUP BY financial_status");
$financialBreakdown = $turnoutStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        /* Mobile Toggle Fix */
        @media (max-width: 991.98px) {
            #sidebar { margin-left: -250px; transition: all 0.3s; position: fixed; z-index: 1050; height: 100%; }
            #sidebar.active { margin-left: 0; }
            #sidebar-overlay.active { display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; }
        }
        .card-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 2.5rem; opacity: 0.2; }
    </style>
</head>
<body>

<div id="sidebar-overlay"></div>

<div class="d-flex">
    <nav id="sidebar" class="bg-dark text-white p-3 shadow" style="width: 250px; min-height: 100vh;">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <div id="content" class="flex-grow-1" style="width: 100%;">
       <header class="navbar-custom d-flex justify-content-between align-items-center sticky-top">
            <div class="d-flex align-items-center">
                
                <h5 class="mb-0 d-none d-md-block fw-bold text-dark">Management Panel</h5>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <?php include('partials/navbar.php');?>
            </div>
        </header>

        <div class="p-3 p-md-4">
            <div class="mb-4 d-flex justify-content-between align-items-end">
                <div>
                    <h4 class="fw-bold mb-0">Admin Dashboard</h4>
                    <p class="text-muted small mb-0"><?php echo date('l, jS F Y'); ?></p>
                </div>
                <div class="badge bg-primary px-3 py-2">System Live</div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm text-white" style="background: #4f46e5;">
                        <div class="card-body position-relative">
                            <h6 class="small uppercase opacity-75">Voters</h6>
                            <h3 class="fw-bold"><?= number_format($totalVoters) ?></h3>
                            <i class="fas fa-users card-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm text-white" style="background: #059669;">
                        <div class="card-body position-relative">
                            <h6 class="small uppercase opacity-75">Total Votes</h6>
                            <h3 class="fw-bold"><?= number_format($totalVotesCast) ?></h3>
                            <i class="fas fa-vote-yea card-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm text-white" style="background: #d97706;">
                        <div class="card-body position-relative">
                            <h6 class="small uppercase opacity-75">Candidates</h6>
                            <h3 class="fw-bold"><?= $totalCandidates ?></h3>
                            <i class="fas fa-user-tie card-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border-0 shadow-sm text-white" style="background: #dc2626;">
                        <div class="card-body position-relative">
                            <h6 class="small uppercase opacity-75">Active Election</h6>
                            <h3 class="fw-bold"><?= $activeElections ?></h3>
                            <i class="fas fa-clock card-icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-8">
                    <div class="card shadow-sm p-3 h-100">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-line text-primary me-2"></i>24h Voting Frequency</h6>
                        <div style="height:300px;"><canvas id="votingTrendChart"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm p-3 h-100">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie text-success me-2"></i>Voter Eligibility</h6>
                        <div style="height:250px;"><canvas id="financialChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
              

                <div class="col-12 col-lg-15">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">System Logs</h6></div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($activityLogs as $log): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                                <div class="me-auto">
                                    <div class="fw-bold small"><?= htmlspecialchars($log['action']) ?></div>
                                    <div class="text-muted x-small"><?= $log['username'] ?? 'System' ?> • <?= $log['ip_address'] ?></div>
                                </div>
                                <span class="text-muted x-small"><?= date('M d', strtotime($log['created_at'])) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <footer class="text-center py-4 text-muted border-top mt-4">
            <?php include('partials/footer.php'); ?>
        </footer>
    </div>
</div>

<script>
  

    // Charts
    new Chart(document.getElementById('votingTrendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($votingTrend, 'time')) ?>,
            datasets: [{
                label: 'Votes',
                data: <?= json_encode(array_column($votingTrend, 'count')) ?>,
                borderColor: '#4f46e5',
                fill: true,
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('financialChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($financialBreakdown, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($financialBreakdown, 'count')) ?>,
                backgroundColor: ['#059669', '#dc2626'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '70%' }
    });
</script>
</body>
</html>
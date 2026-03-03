<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
  
    header("Location: ../login");
    exit;
}
// --- START MOCK DATA SECTION ---
$app_name = "Secured Vote";
$totalVoters = 12450;
$totalVotesCast = 8922;
$totalCandidates = 32;
$activeElections = 3;

// Mock Voting Trend (Used for Line Chart)
$votingTrend = [
    ['time' => '08:00', 'votes' => 120],
    ['time' => '10:00', 'votes' => 450],
    ['time' => '12:00', 'votes' => 1290],
    ['time' => '14:00', 'votes' => 1820],
    ['time' => '16:00', 'votes' => 910],
    ['time' => '18:00', 'votes' => 350]
];

// Mock Participation Breakdown (Used for Doughnut Chart)
$departmentBreakdown = [
    ['name' => 'Engineering', 'count' => 2200],
    ['name' => 'Science', 'count' => 1950],
    ['name' => 'Arts', 'count' => 1100],
    ['name' => 'Business', 'count' => 1820],
    ['name' => 'Law', 'count' => 950]
];

// Mock Recent Activity (Used for Table)
$recentVotes = [
    ['voter' => 'Chinedu Okeke', 'election' => 'Student Union 2026', 'time' => '1 min ago'],
    ['voter' => 'Fatima Yusuf', 'election' => 'Faculty Rep', 'time' => '4 mins ago'],
    ['voter' => 'Blessing Idris', 'election' => 'Student Union 2026', 'time' => '7 mins ago'],
    ['voter' => 'Adebayo Samuel', 'election' => 'Student Union 2026', 'time' => '12 mins ago']
];

// Mock System Logs (Used for Audit List)
$activityLogs = [
    ['username' => 'super_admin', 'action' => 'Published Results', 'ip_address' => '192.168.1.1'],
    ['username' => 'officer_01', 'action' => 'Verified Candidate', 'ip_address' => '192.168.1.45'],
    ['username' => 'system_bot', 'action' => 'Auto-Archived 2025', 'ip_address' => 'localhost']
];
// --- END MOCK DATA SECTION ---
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
            <div class="mb-4">
                <h4 class="fw-bold">Dashboard Overview</h4>
                <p class="text-muted small"><?php echo date('l, jS F Y'); ?></p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-start border-primary border-4" style="background: linear-gradient(135deg, #4f46e5, #818cf8); color: white;">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase small opacity-75">Voters</h6>
                            <h3 class="mb-0"><?php echo number_format($totalVoters); ?></h3>
                            <i class="fas fa-id-card card-icon d-none d-sm-block"></i>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-start border-success border-4" style="background: linear-gradient(135deg, #059669, #34d399); color: white;">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase small opacity-75">Votes</h6>
                            <h3 class="mb-0"><?php echo number_format($totalVotesCast); ?></h3>
                            <i class="fas fa-check-double card-icon d-none d-sm-block"></i>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-start border-warning border-4" style="background: linear-gradient(135deg, #d97706, #fbbf24); color: white;">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase small opacity-75">Candidates</h6>
                            <h3 class="mb-0"><?php echo $totalCandidates; ?></h3>
                            <i class="fas fa-user-tie card-icon d-none d-sm-block"></i>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card h-100 border-start border-danger border-4" style="background: linear-gradient(135deg, #dc2626, #f87171); color: white;">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase small opacity-75">Live</h6>
                            <h3 class="mb-0"><?php echo $activeElections; ?></h3>
                            <i class="fas fa-broadcast-tower card-icon d-none d-sm-block"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-8">
                    <div class="card p-3 p-md-4">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-line text-primary me-2"></i>Live Voting Trend</h6>
                        <div style="position: relative; height:300px;">
                            <canvas id="votingTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card p-3 p-md-4 h-100">
                        <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie text-success me-2"></i>Turnout by Dept</h6>
                        <div style="position: relative; height:250px;">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="card border-0">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold">Recent Activity</h6>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Voter</th>
                                        <th>Election</th>
                                        <th class="d-none d-md-table-cell">Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php foreach ($recentVotes as $vote): ?>
                                    <tr>
                                        <td class="ps-3"><strong><?= $vote['voter'] ?></strong></td>
                                        <td><?= $vote['election'] ?></td>
                                        <td class="text-muted d-none d-md-table-cell"><?= $vote['time'] ?></td>
                                        <td><span class="badge bg-success-subtle text-success">Verified</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card border-0">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold">System Audit</h6>
                        </div>
                      <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($activityLogs as $log): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center small py-3">
                                    <div>
                                        <div class="fw-bold"><?= $log['action'] ?></div>
                                        <div class="text-muted text-xs"><?= $log['username'] ?> • <?= $log['ip_address'] ?></div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted opacity-25"></i>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-header border-0 py-5">
                       
          </div>
        <footer class="main-footer text-center mt-5 py-3">
        <?php include('partials/footer.php'); ?>
        </footer>
        </div>
    </div>
    </div>

<script>

    // Chart Configuration
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
    };

    // Voting Trend Line Chart
    new Chart(document.getElementById('votingTrendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($votingTrend, 'time')) ?>,
            datasets: [{
                label: 'Votes',
                data: <?= json_encode(array_column($votingTrend, 'votes')) ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: chartOptions
    });

    // Department Doughnut Chart
    new Chart(document.getElementById('departmentChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($departmentBreakdown, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($departmentBreakdown, 'count')) ?>,
                backgroundColor: ['#4f46e5', '#059669', '#d97706', '#dc2626', '#6366f1'],
                borderWidth: 0
            }]
        },
        options: {
            ...chartOptions,
            cutout: '70%'
        }
    });
</script>

</body>
</html>
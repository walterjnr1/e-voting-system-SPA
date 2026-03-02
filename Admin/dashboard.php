<?php 
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

// 1️⃣ User Growth Data (users registered per month)
$sql_users = "SELECT DATE_FORMAT(created_at, '%b %Y') AS month, COUNT(*) AS total 
              FROM users 
              GROUP BY month 
              ORDER BY MIN(created_at)";
$stmt_users = $dbh->prepare($sql_users);
$stmt_users->execute();
$userGrowth = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// 2️⃣ Revenue Trend (sum of plan price per month)
$sql_revenue = "SELECT DATE_FORMAT(s.start_date, '%b %Y') AS month, 
                       SUM(p.price) AS total_revenue
                FROM subscriptions s
                JOIN plans p ON s.plan_id = p.id
                WHERE s.status = 'active'
                GROUP BY month 
                ORDER BY MIN(s.start_date)";
$stmt_rev = $dbh->prepare($sql_revenue);
$stmt_rev->execute();
$revenueData = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);

// 3️⃣ Active Subscriptions Breakdown (count by plan)
$sql_plans = "SELECT p.name AS plan_name, COUNT(s.id) AS count
              FROM subscriptions s
              JOIN plans p ON s.plan_id = p.id
              WHERE s.status = 'active'
              GROUP BY p.name";
$stmt_plans = $dbh->prepare($sql_plans);
$stmt_plans->execute();
$planBreakdown = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);

// 4️⃣ Questions Generated per Day (using question_usage)
$sql_questions = "SELECT DATE(used_at) AS date, COUNT(*) AS total
                  FROM question_usage
                  GROUP BY DATE(used_at)
                  ORDER BY DATE(used_at)";
$stmt_q = $dbh->prepare($sql_questions);
$stmt_q->execute();
$questionsPerDay = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

// Fetch Activity Logs (limit 10)
$sql_logs = "SELECT al.*, u.name as username 
             FROM activity_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.id DESC 
             LIMIT 10";
$stmt_logs = $dbh->prepare($sql_logs);
$stmt_logs->execute();
$activityLogs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

// Fetch subscription records
$query = "SELECT s.id AS sub_id, u.name AS user_name, p.name AS plan_name, p.price, s.start_date, s.end_date, s.status 
          FROM subscriptions s 
          JOIN users u ON s.user_id = u.id
          JOIN plans p ON s.plan_id = p.id 
          ORDER BY s.start_date DESC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Admin Dashboard | AI Question Generator</title>
  <?php include('partials/head.php');?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body, html { height: 100%; display: flex; flex-direction: column; }
    #content { flex: 1; }
    footer { text-align: center; padding: 10px; }
    canvas { min-height: 300px; }
    .card-icon { float: right; font-size: 2rem; opacity: 0.2; }
  .style1 {color: #000000}
  </style>
</head>
<body>

<div class="d-flex flex-grow-1">
  <!-- Sidebar -->
  <nav id="sidebar" class="d-flex flex-column p-3">
    <?php include('partials/sidebar.php'); ?>
  </nav>

  <!-- Page Content -->
  <div id="content" class="flex-grow-1">
    <!-- Navbar -->
    <div class="navbar-custom d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
        <h5>Welcome back, <strong><?php echo $row_user['name']; ?></strong></h5>
      </div>
      <div>
        <a href="logout" class="btn btn-outline-danger">
          <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #007bff, #00bfff);">
          <div class="card-body">
            <h5>Total Users</h5>
            <h3><?php echo $totalUsers; ?></h3>
            <i class="fas fa-users card-icon"></i>
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #ffc107, #ffd966); color:#000;">
          <div class="card-body">
            <h5>No. of Students</h5>
            <h3><?php echo $totalstudents; ?></h3>
            <i class="fas fa-user-graduate card-icon"></i>
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #28a745, #71dd8a);">
          <div class="card-body">
            <h5>No. of Teacher(s)</h5>
            <h3><?php echo $totalteachers; ?></h3>
            <i class="fas fa-chalkboard-teacher card-icon"></i>
          </div>
        </div>
      </div>
<div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #191f1bff, #71dd8a);">
          <div class="card-body">
            <h5>No. of Parent(s)</h5>
            <h3><?php echo $totalparents; ?></h3>
            <i class="fas fa-user card-icon"></i>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #dc3545, #f16a6f);">
          <div class="card-body">
            <h5>No. of Questions</h5>
            <h3><?php echo $totalquestions; ?></h3>
            <i class="fas fa-question-circle card-icon"></i>
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #6f42c1, #b084f5);">
          <div class="card-body">
            <h5>No. of Active Users</h5>
            <h3><?php echo $activeUsers; ?></h3>
            <i class="fas fa-user-check card-icon"></i>
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #85d191ff, #63e6be);">
          <div class="card-body">
            <h5>Total Subscriptions</h5>
            <h3>₦<?= number_format($totalAmount,2) ?></h3>
            <i class="fas fa-file-invoice-dollar card-icon"></i>
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6">
        <div class="card" style="background: linear-gradient(135deg, #58535aff, #0c2920ff);">
          <div class="card-body">
            <h5>Total Subscriptions Monthly</h5>
            <h3>₦<?= number_format($totalAmountMonth,2) ?></h3>
            <i class="fas fa-file-invoice-dollar card-icon"></i>
          </div>
        </div>
      </div>

    <!-- Charts Section -->
    <div class="card mb-4">
      <div class="card-header"><h5 class="style1">Analytics Overview</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-4">
            <div class="card p-3">
              <h5 class="style1">User Growth Overview</h5>
              <canvas id="userGrowthChart"></canvas>
            </div>
          </div>

          <div class="col-md-6 mb-4">
            <div class="card p-3">
              <h5 class="style1">Revenue Trend</h5>
              <canvas id="revenueChart"></canvas>
            </div>
          </div>

          <div class="col-md-6 mb-4">
            <div class="card p-3">
              <h5 class="style1">Active Subscriptions Breakdown</h5>
              <canvas id="subscriptionChart"></canvas>
            </div>
          </div>

          <div class="col-md-6 mb-4">
            <div class="card p-3">
              <h5 class="style1">Questions Generated per Day</h5>
              <canvas id="questionsChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ✅ Recent Subscriptions moved BELOW Charts -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">Recent Subscriptions</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th><th>User</th><th>Plan</th><th>Price</th><th>Status</th><th>Start Date</th><th>End Date</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($subscriptions): foreach ($subscriptions as $i => $r): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($r['user_name']) ?></td>
              <td><?= htmlspecialchars($r['plan_name']) ?></td>
              <td>₦<?= number_format($r['price'],2) ?></td>
              <td>
                <?php if ($r['status']=='active'): ?><span class="badge bg-success">Active</span>
                <?php elseif($r['status']=='expired'): ?><span class="badge bg-danger">Expired</span>
                <?php else: ?><span class="badge bg-warning text-dark"><?= ucfirst($r['status']) ?></span><?php endif; ?>
              </td>
              <td><?= $r['start_date'] ?></td>
              <td><?= $r['end_date'] ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center text-muted">No subscriptions found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ✅ Activity Logs moved BELOW Charts -->
    <div class="card mb-4">
      <div class="card-header"><h5 class="style1">Activity Logs</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped">
          <thead><tr><th>#</th><th>User</th><th>Action</th><th>Table</th><th>Record ID</th><th>IP</th></tr></thead>
          <tbody>
          <?php if ($activityLogs): foreach ($activityLogs as $i => $log): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($log['username']) ?></td>
              <td><?= htmlspecialchars($log['action']) ?></td>
              <td><?= htmlspecialchars($log['table_name']) ?></td>
              <td><?= htmlspecialchars($log['record_id']) ?></td>
              <td><?= htmlspecialchars($log['ip_address']) ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center text-muted">No activity logs found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
        <p>&nbsp;</p>
      </div>
    </div>

  </div>
</div>

<footer>
  <?php include('partials/footer.php'); ?>
</footer>

<!-- Chart.js Scripts -->
<script>
  const userGrowthLabels = <?= json_encode(array_column($userGrowth, 'month')) ?>;
  const userGrowthData = <?= json_encode(array_column($userGrowth, 'total')) ?>;

  const revenueLabels = <?= json_encode(array_column($revenueData, 'month')) ?>;
  const revenueValues = <?= json_encode(array_column($revenueData, 'total_revenue')) ?>;

  const planLabels = <?= json_encode(array_column($planBreakdown, 'plan_name')) ?>;
  const planCounts = <?= json_encode(array_column($planBreakdown, 'count')) ?>;

  const questionLabels = <?= json_encode(array_column($questionsPerDay, 'date')) ?>;
  const questionCounts = <?= json_encode(array_column($questionsPerDay, 'total')) ?>;

  new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: {
      labels: userGrowthLabels,
      datasets: [{
        label: 'New Users',
        data: userGrowthData,
        borderColor: '#007bff',
        backgroundColor: 'rgba(0,123,255,0.3)',
        fill: true,
        tension: 0.3
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
      labels: revenueLabels,
      datasets: [{
        label: 'Revenue (₦)',
        data: revenueValues,
        borderColor: '#28a745',
        backgroundColor: 'rgba(40,167,69,0.3)',
        fill: true,
        tension: 0.3
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  new Chart(document.getElementById('subscriptionChart'), {
    type: 'doughnut',
    data: {
      labels: planLabels,
      datasets: [{
        data: planCounts,
        backgroundColor: ['#007bff','#ffc107','#28a745','#dc3545','#6f42c1']
      }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });

  new Chart(document.getElementById('questionsChart'), {
    type: 'bar',
    data: {
      labels: questionLabels,
      datasets: [{
        label: 'Questions Generated',
        data: questionCounts,
        backgroundColor: 'rgba(220,53,69,0.6)',
        borderColor: '#dc3545',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true } }
    }
  });
</script>

<?php include('partials/toogle-down.php'); ?>
</body>
</html>

<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

// Pagination settings
$limit = 10; // records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total records (questions count)
$stmt = $dbh->query("SELECT COUNT(*) FROM questions");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch questions with subjects, topics, and user details
$sql = "SELECT q.id,q.batch_id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer, 
            q.explanation, q.topic, q.created_at,
               s.name AS subject_name,
               u.name AS user_name
        FROM questions q
        LEFT JOIN subjects s ON q.subject_id = s.id
        LEFT JOIN users u ON q.user_id = u.id
        ORDER BY q.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Questions Records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
</head>
<body>

<div class="d-flex">
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
            <h5>Questions Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end mb-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search Question...">
    </div>

    <!-- Questions Records Table -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="style1">Questions Records</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped" id="transactionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Batch ID</th>
              <th>Subject</th>
              <th>Topic</th>
              <th>Question</th>
              <th>Options</th>
              <th>Correct Answer</th>
              <th>Explanation</th>
              <th>user</th>
              <th>Image</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($questions) {
              $cnt = $offset + 1;
              foreach ($questions as $q) { ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td><?php echo htmlspecialchars($q['batch_id'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($q['subject_name'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($q['topic'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($q['question_text']); ?></td>
                  <td>
                      A. <?php echo htmlspecialchars($q['option_a']); ?><br>
                      B. <?php echo htmlspecialchars($q['option_b']); ?><br>
                      C. <?php echo htmlspecialchars($q['option_c']); ?><br>
                      D. <?php echo htmlspecialchars($q['option_d']); ?>
                  </td>
                  <td><span class="badge bg-success"><?php echo htmlspecialchars($q['correct_answer']); ?></span></td>
                  <td><?php echo htmlspecialchars($q['explanation'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($q['user_name'] ?? 'Unknown'); ?></td>
                  <td>
                      <?php if (!empty($q['image_url'])) { ?>
                        <img src="<?php echo htmlspecialchars($q['image_url']); ?>" alt="Question Image" width="60">
                      <?php } else { echo 'N/A'; } ?>
                  </td>
                  <td><?php echo $q['created_at'] ? date('d-m-Y h:i A', strtotime($q['created_at'])) : 'N/A'; ?></td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="11" class="text-center">No Questions Found</td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <p>
      <!-- Pagination -->
      <nav aria-label="Page navigation">
        <?php include('partials/pagination.php'); ?>
      </nav>
      <footer>
        <?php include('partials/footer.php'); ?>
      </footer>
      
      <?php include('partials/table-script.php'); ?>
      <?php include('partials/toogle-down.php'); ?>
</p>
    <p>&nbsp;    </p>
  </div>
</div>

</body>
</html>

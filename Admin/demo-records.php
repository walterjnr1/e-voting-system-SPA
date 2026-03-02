<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

// --- Authentication Check ---
if (empty($_SESSION['user_id'])) {
    
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

// --- Pagination Settings ---
$limit = 10; // records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- Count total records in demo_requests table ---
$stmt = $dbh->query("SELECT COUNT(*) FROM demo_requests");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// --- Fetch Demo Requests ---
$sql = "SELECT id, name, email, company, job_title, message, created_at, is_scheduled, scheduled_at 
        FROM demo_requests 
        ORDER BY id DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$demo_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Demo Request Records | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <script type="text/javascript">
        function deldata(){
            return confirm("ARE YOU SURE YOU WISH TO DELETE THIS DEMO REQUEST?");
        }
    </script>
    <style>
        /* Optional: Add basic style for action icons if not already included in partials/head.php */
        .action-link {
            font-size: 1.2em;
            margin-right: 5px;
            color: #007bff; /* default color */
        }
        .action-link:hover {
            opacity: 0.7;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar" class="d-flex flex-column p-3">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <div id="content" class="flex-grow-1">
        <div class="navbar-custom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
                <h5>Demo Request Records</h5>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
            <div>
                </div>
            <div>
                <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search Request...">
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="style1">Demo Request Records</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped" id="transactionTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Job Title</th>
                            <th>Message</th>
                            <th>Status</th> 
                            <th>Date/Time</th> <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($demo_requests) {
                            $cnt = $offset + 1;
                            foreach ($demo_requests as $request) { 
                                // Logic for Status Badge
                                $is_scheduled = $request['is_scheduled'] ?? 0;
                                $status_badge = $is_scheduled ? 
                                    '<span class="badge bg-success">Scheduled</span>' : 
                                    '<span class="badge bg-warning text-dark">Pending</span>';
                                
                                // FIX: Conditional Date/Time Display Logic
                                if ($is_scheduled && !empty($request['scheduled_at']) && $request['scheduled_at'] !== '0000-00-00 00:00:00') {
                                    $date_label = "Scheduled:";
                                    $display_date = date('M j, Y, g:i A', strtotime($request['scheduled_at']));
                                    $date_class = 'text-success fw-bold';
                                } else {
                                    $date_label = "Requested:";
                                    $display_date = date('M j, Y, g:i A', strtotime($request['created_at']));
                                    $date_class = 'text-muted';
                                }
                                
                                ?>
                                <tr>
                                    <td><?php echo $cnt++; ?></td>
                                    <td><?php echo htmlspecialchars($request['name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['email']); ?></td>
                                    <td><?php echo htmlspecialchars($request['company']); ?></td>
                                    <td><?php echo htmlspecialchars($request['job_title']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars(substr($request['message'], 0, 50) . (strlen($request['message']) > 50 ? '...' : ''))); ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td>
                                        <small class="<?php echo $date_class; ?>"><?php echo $date_label; ?></small><br>
                                        <?php echo $display_date; ?>
                                    </td>
                                    <td>
                                        <a href="schedule_demo.php?id=<?php echo $request['id'];?>" 
                                           class="action-link me-2 text-info" title="Schedule Demo">
                                            <i class="fa fa-calendar-alt"></i>
                                        </a>
                                        <a href="delete_demo_request.php?id=<?php echo $request['id'];?>" 
                                           onclick="return deldata();" class="action-link text-danger" title="Delete Request">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr><td colspan="9" class="text-center">No Demo Requests Found</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <nav aria-label="Page navigation">
            <?php 
            include('partials/pagination.php'); 
            ?>
        </nav>
    </div>
</div>

<footer>
    <?php include('partials/footer.php'); ?>
</footer>

<?php include('partials/sweetalert.php'); ?>
<?php include('partials/table-script.php'); ?>
<?php include('partials/toogle-down.php'); ?>
</body>
</html>
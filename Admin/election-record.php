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

$total_stmt = $dbh->query("SELECT COUNT(*) FROM elections");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$stmt = $dbh->prepare("SELECT * FROM elections ORDER BY id DESC LIMIT $start, $limit");
$stmt->execute();
$allElections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Records | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold">Election Records</h4>
                    <p class="text-muted small">Manage and monitor all election instances</p>
                </div>
                <a href="create-election" class="btn btn-primary shadow-sm" style="background-color: #4f46e5; border:none;">
                    <i class="fas fa-plus me-2"></i>New Election
                </a>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">All Elections</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="dynamicSearch" placeholder="Search elections...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Election Title</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>Results View</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php foreach ($allElections as $row): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold"><?= htmlspecialchars($row['title']) ?></div>
                                            <div class="text-muted text-xs"><?= substr(htmlspecialchars($row['description']), 0, 50) ?>...</div>
                                        </td>
                                        <td>
                                            <div class="text-xs"><strong>Start:</strong> <?= date('M d, y | H:i', strtotime($row['start_datetime'])) ?></div>
                                            <div class="text-xs text-danger"><strong>End:</strong> <?= date('M d, y | H:i', strtotime($row['end_datetime'])) ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = [
                                                    'active' => 'bg-success',
                                                    'draft' => 'bg-secondary',
                                                    'scheduled' => 'bg-info',
                                                    'closed' => 'bg-danger'
                                                ];
                                            ?>
                                            <span class="badge <?= $statusClass[$row['status']] ?>"><?= ucfirst($row['status']) ?></span>
                                        </td>
                                        <td>
                                            <?= $row['allow_result_view'] ? '<span class="text-success"><i class="fas fa-eye me-1"></i> Public</span>' : '<span class="text-muted"><i class="fas fa-eye-slash me-1"></i> Private</span>' ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="edit_election?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer bg-white border-0 py-3">
                            <nav>
                               <?php include('partials/pagination.php'); ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="main-footer text-center mt-5 py-3">
                <?php include('partials/footer.php'); ?>
            </footer>
        </div>
    </div>
</div>

<?php include('partials/sweetalert.php'); ?>
<?php include('partials/table-script.php'); ?>

<script>
    

    function confirmDelete(id) {
        if (confirm("Are you sure? All votes and candidates tied to this election will be lost!")) {
            window.location.href = `delete_election.php?id=${id}`;
        }
    }
</script>
</body>
</html>
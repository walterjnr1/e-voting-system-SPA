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

$total_stmt = $dbh->query("SELECT COUNT(*) FROM candidates");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$sql = "SELECT c.*, u.full_name, e.title as election_title, p.title as position_title 
        FROM candidates c
        JOIN users u ON c.user_id = u.id
        JOIN elections e ON c.election_id = e.id
        JOIN positions p ON c.position_id = p.id
        ORDER BY c.id DESC LIMIT $start, $limit";

$stmt = $dbh->prepare($sql);
$stmt->execute();
$allCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Records | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .candidate-img { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #eef0f7; }
        .status-toggle { cursor: pointer; transition: 0.3s; }
        .status-toggle:hover { opacity: 0.7; transform: scale(1.1); }
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
                    <h4 class="fw-bold">Candidate Records</h4>
                    <p class="text-muted small">Manage and verify election candidates</p>
                </div>
                <a href="add-candidate" class="btn btn-primary shadow-sm" style="background-color: #0a192f; border:none;">
                    <i class="fas fa-user-plus me-2"></i>New Candidate
                </a>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Candidate List</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="dynamicSearch" placeholder="Search candidates...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Candidate</th>
                                        <th>Election & Position</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php if (count($allCandidates) > 0): ?>
                                        <?php foreach ($allCandidates as $row): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="../<?= htmlspecialchars($row['photo']) ?>" class="candidate-img" onerror="this.src='../assets/img/default-user.png'">
                                                    <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($row['position_title']) ?></div>
                                                <div class="text-muted text-xs"><?= htmlspecialchars($row['election_title']) ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $status = $row['status'] ?? 'pending';
                                                    $badgeMap = [
                                                        'approved' => ['class' => 'bg-success', 'icon' => 'fa-check-circle'],
                                                        'pending' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock'],
                                                        'rejected' => ['class' => 'bg-danger', 'icon' => 'fa-times-circle']
                                                    ];
                                                ?>
                                                <span class="badge <?= $badgeMap[$status]['class'] ?> d-inline-flex align-items-center gap-1">
                                                    <i class="fas <?= $badgeMap[$status]['icon'] ?>"></i>
                                                    <?= ucfirst($status) ?>
                                                </span>
                                                <i class="fas fa-sync-alt ms-2 text-muted status-toggle" 
                                                   title="Toggle Status"
                                                   onclick="confirmStatusToggle(<?= $row['id'] ?>, '<?= $status ?>', '<?= addslashes($row['full_name']) ?>')"></i>
                                            </td>
                                            <td><div class="text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></div></td>
                                            <td class="text-end pe-3">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="edit_candidate?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                    <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes($row['full_name']) ?>')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4">No candidates found.</td></tr>
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
    // --- STATUS TOGGLE LOGIC ---
    function confirmStatusToggle(id, currentStatus, name) {
        let nextStatus = '';
        if (currentStatus === 'pending') nextStatus = 'approved';
        else if (currentStatus === 'approved') nextStatus = 'rejected';
        else nextStatus = 'pending';

        const msg = `Change status for ${name} from "${currentStatus.toUpperCase()}" to "${nextStatus.toUpperCase()}"?`;
        
        if (confirm(msg)) {
            // Redirect to a handler file
            window.location.href = `update_candidate_status?id=${id}&status=${nextStatus}`;
        }
    }

    // --- DELETE LOGIC ---
    function confirmDelete(id, name) {
        if (confirm(`Permanent Action: Are you sure you want to delete ${name}?`)) {
            window.location.href = `delete_candidate.php?id=${id}`;
        }
    }

  
</script>
<?php include('partials/table-script.php'); ?>

<?php include('partials/sweetalert.php'); ?>
</body>
</html>
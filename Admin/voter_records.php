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

$total_stmt = $dbh->query("SELECT COUNT(*) FROM users ");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Query to include user_image
$stmt = $dbh->prepare("SELECT * FROM users ORDER BY id DESC LIMIT $start, $limit");
$stmt->execute();
$allVoters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Records | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .toggle-icon { cursor: pointer; transition: transform 0.2s; color: #6c757d; }
        .toggle-icon:hover { transform: scale(1.2); color: #4f46e5; }
        .text-xs { font-size: 0.72rem; }
        .voted-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        /* Expanded width and height for the image */
        .voter-avatar { width: 70px; height: 70px; border-radius: 12px; object-fit: cover; border: 2px solid #eef2ff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
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
                    <h4 class="fw-bold text-dark">Voter Management</h4>
                    <p class="text-muted small">Update credentials and voting eligibility</p>
                </div>
                <a href="add-voter" class="btn btn-primary shadow-sm" style="background-color: #4f46e5; border:none;">
                    <i class="fas fa-plus me-2"></i>Register Voter
                </a>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Voter List</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <input type="text" class="form-control" id="dynamicSearch" placeholder="Filter by name or email...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr class="text-xs text-uppercase text-muted">
                                        <th class="ps-3">Voter Details</th>
                                        <th>Verification</th>
                                        <th>Financials</th>
                                        <th>Participation</th>
                                        <th>Account</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php foreach ($allVoters as $row): 
                                        $image_path = !empty($row['user_image']) ? '../' . $row['user_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($row['full_name']) . '&background=4f46e5&color=fff&size=100';
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $image_path ?>" 
                                                     class="voter-avatar me-3" 
                                                     alt="Profile" 
                                                     onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name']) ?></div>
                                                    <div class="text-muted text-xs"><?= htmlspecialchars($row['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge rounded-pill <?= $row['is_verified'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> border">
                                                    <?= $row['is_verified'] ? 'Verified' : 'Unverified' ?>
                                                </span>
                                                <i class="fas fa-sync-alt ms-2 toggle-icon" title="Toggle Verification" onclick="confirmToggle(<?= $row['id'] ?>, 'is_verified', '<?= $row['is_verified'] ? 0 : 1 ?>')"></i>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge rounded-pill <?= $row['financial_status'] == 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> border">
                                                    <?= ucfirst($row['financial_status']) ?>
                                                </span>
                                                <i class="fas fa-sync-alt ms-2 toggle-icon" title="Toggle Financial Status" onclick="confirmToggle(<?= $row['id'] ?>, 'financial_status', '<?= $row['financial_status'] == 'active' ? 'non-active' : 'active' ?>')"></i>
                                            </div>
                                        </td>

                                        <td>
                                            <?php if($row['has_voted']): ?>
                                                <span class="text-success fw-bold"><span class="voted-dot bg-success"></span>Voted</span>
                                            <?php else: ?>
                                                <span class="text-muted"><span class="voted-dot bg-light border"></span>Not Voted</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge rounded-pill <?= $row['status'] == 'active' ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning' ?> border">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                                <i class="fas fa-sync-alt ms-2 toggle-icon" title="Toggle Account Status" onclick="confirmToggle(<?= $row['id'] ?>, 'status', '<?= $row['status'] == 'active' ? 'suspended' : 'active' ?>')"></i>
                                            </div>
                                        </td>

                                        <td class="text-end pe-3">
                                            <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes($row['full_name']) ?>')" class="btn btn-sm text-danger border-0">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
    function confirmToggle(id, field, newValue) {
        const readableField = field.replace(/_/g, ' ');
        if (confirm(`Change ${readableField} to "${newValue}" for this voter?`)) {
            window.location.href = `update_voter_status.php?id=${id}&field=${field}&value=${newValue}`;
        }
    }

    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to PERMANENTLY delete ${name}? This will remove all their login access.`)) {
            window.location.href = `delete_voter.php?id=${id}`;
        }
    }
</script>
<?php include('partials/table-script.php'); ?>
<?php include('partials/sweetalert.php'); ?>
</body>
</html>
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

// Fetch total records (all users)
$total_stmt = $dbh->query("SELECT COUNT(*) FROM users");
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Fetch Paginated Data for all users
$stmt = $dbh->prepare("SELECT id, full_name, email, role, is_verified, created_at FROM users where role='eleco' ORDER BY id DESC LIMIT $start, $limit");
$stmt->execute();
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Record | <?php echo $app_name; ?></title>
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
                    <h4 class="fw-bold">User Records</h4>
                    <p class="text-muted small">Manage system access and account statuses</p>
                </div>
                <a href="add-user" class="btn btn-primary shadow-sm" style="background-color: #4f46e5; border:none;">
                    <i class="fas fa-plus me-2"></i>New User
                </a>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">User Record</h6>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="dynamicSearch" placeholder="Search users...">
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover mb-0 align-middle" id="dataTable">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Full Name</th>
                                        <th>Role</th>
                                        <th class="d-none d-md-table-cell">Joined Date</th>
                                        <th>Status</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="small" id="tableBody">
                                    <?php foreach ($allUsers as $user): 
                                        $isEleco = ($user['role'] == 'eleco');
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold"><?= htmlspecialchars($user['full_name']) ?></div>
                                            <div class="text-muted text-xs"><?= htmlspecialchars($user['email']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted d-none d-md-table-cell"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?php if ($user['is_verified'] == 1): ?>
                                                <span class="badge bg-success-subtle text-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger"><i class="fas fa-times-circle me-1"></i>Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button 
                                                    onclick="confirmToggle(<?= $user['id'] ?>, '<?= $user['is_verified'] == 1 ? 'disable' : 'enable' ?>')" 
                                                    class="btn btn-sm <?= $user['is_verified'] == 1 ? 'btn-outline-warning' : 'btn-outline-success' ?>" 
                                                    title="<?= $user['is_verified'] == 1 ? 'Disable' : 'Enable' ?>"
                                                    <?= $isEleco ? 'disabled' : '' ?>>
                                                    <i class="fas <?= $user['is_verified'] == 1 ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                                </button>
                                                
                                                <button 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?= $user['id'] ?>)" 
                                                    title="Delete"
                                                    <?= $isEleco ? 'disabled' : '' ?>>
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
   

    // 2. Native Javascript Confirm for Delete
    function confirmDelete(userId) {
        if (confirm("Are you sure? This user will be permanently removed from the system!")) {
            window.location.href = `delete_user.php?id=${userId}`;
        }
    }

    // 3. Native Javascript Confirm for Toggle Status
    function confirmToggle(userId, action) {
        if (confirm(`Are you sure you want to ${action} this user account?`)) {
            window.location.href = `disable_enable_user.php?id=${userId}`;
        }
    }
</script>

</body>
</html>
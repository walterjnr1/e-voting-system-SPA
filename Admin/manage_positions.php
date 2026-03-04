<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

$edit_mode = false;
$edit_data = [];

// --- 1. HANDLE EDIT FETCHING (Same Page) ---
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $dbh->prepare("SELECT * FROM positions WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_data) {
        $edit_mode = true;
    }
}

// --- 2. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $del = $dbh->prepare("DELETE FROM positions WHERE id = ?");
    if ($del->execute([$delete_id])) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Position removed successfully.'];
        header("Location: manage_positions");
        exit;
    }
}

// --- 3. PHP PROCESSING LOGIC (CREATE & UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_position'])) {
    // --- SECURITY: CSRF VALIDATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: manage_positions");
        exit;
    }
    $election_id = $_POST['election_id'];
    $title = htmlspecialchars(trim($_POST['title']));
    $max_vote = (int)$_POST['max_vote'];
    $pos_id = $_POST['pos_id'] ?? null;

    if ($edit_mode && $pos_id) {
        // Update Logic
        $update = $dbh->prepare("UPDATE positions SET election_id = ?, title = ?, max_vote = ? WHERE id = ?");
        $result = $update->execute([$election_id, $title, $max_vote, $pos_id]);
        $msg = "Position updated successfully!";
    } else {
        // Create Logic
        $insert = $dbh->prepare("INSERT INTO positions (election_id, title, max_vote) VALUES (?, ?, ?)");
        $result = $insert->execute([$election_id, $title, $max_vote]);
        $msg = "New position registered!";
    }

    if ($result) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => $msg];
          // Regenerate CSRF for next action
                    unset($_SESSION['csrf_token']);
        header("Location: manage_positions"); 
        exit;
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database error. Action failed.'];
    }
}

// Fetch Elections for Dropdown
$elections = $dbh->query("SELECT id, title FROM elections ORDER BY created_at DESC")->fetchAll();

// Fetch All Positions for Table
$positions = $dbh->query("SELECT p.*, e.title as election_title FROM positions p JOIN elections e ON p.election_id = e.id ORDER BY p.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Positions | <?php echo $app_name; ?></title>
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
            <div class="mb-4">
                <h4 class="fw-bold mb-0">Election Positions</h4>
                <p class="text-muted small">Define roles and voting limits for each election</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 sticky-top" style="top: 80px; z-index: 10;">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus-circle' ?> me-2"></i>
                                <?= $edit_mode ? 'Edit Position' : 'Register New Position' ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" id="positionForm" class="needs-validation" novalidate>
                                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
                            <input type="hidden" name="pos_id" value="<?= $edit_data['id'] ?? '' ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Assign to Election</label>
                                    <select name="election_id" class="form-select" required>
                                        <option value="">Choose Election...</option>
                                        <?php foreach ($elections as $el): ?>
                                            <option value="<?= $el['id'] ?>" <?= (isset($edit_data['election_id']) && $edit_data['election_id'] == $el['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($el['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Position Title</label>
                                    <input type="text" name="title" class="form-control" placeholder="e.g. President" value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold small">Max Votes per Voter</label>
                                    <input type="number" name="max_vote" class="form-control" min="1" value="<?= $edit_data['max_vote'] ?? '1' ?>" required>
                                    <div class="form-text">How many candidates can a voter pick?</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="save_position" id="submitBtn" class="btn btn-primary" style="background-color: #0a192f; border: none;">
                                        <span id="btnText"><?= $edit_mode ? 'Update Changes' : 'Register Position' ?></span>
                                        <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                                    </button>
                                    <?php if($edit_mode): ?>
                                        <a href="manage_positions" class="btn btn-light btn-sm">Cancel Edit</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Active Positions</h6>
                            <span class="badge bg-secondary"><?= count($positions) ?> Total</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase">
                                        <th class="ps-3">Position Details</th>
                                        <th>Election</th>
                                        <th class="text-center">Limit</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($positions)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No positions registered yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($positions as $row): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold"><?= htmlspecialchars($row['title']) ?></div>
                                            <div class="text-muted text-xs">Created: <?= date('d M, Y', strtotime($row['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                                <?= htmlspecialchars($row['election_title']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-light text-dark border"><?= $row['max_vote'] ?> Vote(s)</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?= $row['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="javascript:void(0)" onclick="confirmDelete(<?= $row['id'] ?>)" class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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

<script>
    // Form Validation & Spinner
    document.getElementById('positionForm').addEventListener('submit', function (event) {
        const form = event.target;
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        const btn = document.getElementById('submitBtn');
        document.getElementById('btnText').classList.add('d-none');
        document.getElementById('btnSpinner').classList.remove('d-none');
        btn.style.pointerEvents = 'none'; 
        btn.style.opacity = '0.8';
    });

    // Delete Confirmation
    function confirmDelete(id) {
        if (confirm("Are you sure you want to delete this position? This will affect candidates linked to it.")) {
            window.location.href = "manage_positions?delete=" + id;
        }
    }
</script>
 
</body>
</html>
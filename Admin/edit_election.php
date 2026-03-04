<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $dbh->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->execute([$id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) { header("Location: election-record"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_election'])) {
    // --- SECURITY: CSRF VALIDATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: edit_election");
        exit;
    }
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $start = $_POST['start_datetime'];
    $end = $_POST['end_datetime'];
    $status = $_POST['status'];
    $allow_view = isset($_POST['allow_result_view']) ? 1 : 0;

    $update = $dbh->prepare("UPDATE elections SET title=?, description=?, start_datetime=?, end_datetime=?, status=?, allow_result_view=? WHERE id=?");
    if ($update->execute([$title, $description, $start, $end, $status, $allow_view, $id])) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Election updated successfully!'];
          // Regenerate CSRF for next action
                    unset($_SESSION['csrf_token']);
        header("Location: election-record");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Election | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <h4 class="fw-bold mb-0">Election Management</h4>
                <a href="dashboard" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-vote-yea me-2"></i>Configure New Election</h6>
                        </div>
                        <div class="card-body p-4">
                        <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
                        <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($election['title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($election['description']) ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="datetime-local" name="start_datetime" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($election['start_datetime'])) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="datetime-local" name="end_datetime" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($election['end_datetime'])) ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="draft" <?= $election['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="scheduled" <?= $election['status'] == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="active" <?= $election['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="closed" <?= $election['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="allow_result_view" <?= $election['allow_result_view'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Allow public result view</label>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="election-record.php" class="btn btn-light">Cancel</a>
                                <button type="submit" name="update_election" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                 <footer class="main-footer text-center mt-5 py-3">
                <?php include('partials/footer.php'); ?>
            </footer>
            </div>
        </div>
    </div>
</body>
</html>
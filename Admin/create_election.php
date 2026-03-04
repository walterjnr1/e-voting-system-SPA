<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}


// --- PHP PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_election'])) {
// --- SECURITY: CSRF VALIDATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Security token mismatch. Please refresh.'];
        header("Location: create_election");
        exit;
    }   
$title = trim($_POST['title']);
    $description = htmlspecialchars(trim($_POST['description']));
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $status = $_POST['status'];
    $allow_result_view = isset($_POST['allow_result_view']) ? 1 : 0;

    // 1. Validation: Check if election title already exists
    $checkStmt = $dbh->prepare("SELECT id FROM elections WHERE title = ?");
    $checkStmt->execute([$title]);
    
    if ($checkStmt->rowCount() > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'An election with this title already exists.'];
    } 
    // 2. Validation: Ensure end date is after start date
    elseif (strtotime($end_datetime) <= strtotime($start_datetime)) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'End date must be after start date.'];
    } 
    else {
        // 3. Insert into Elections Table
        $insert = $dbh->prepare("INSERT INTO elections (title, description, start_datetime, end_datetime, status, allow_result_view, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $insert->execute([$title, $description, $start_datetime, $end_datetime, $status, $allow_result_view, $user_id]);

        if ($result) {
            log_activity($dbh, $user_id, "Election Created: $title", $ip_address);

            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Election scheduled successfully!'];
                        // Regenerate CSRF for next action
                    unset($_SESSION['csrf_token']);
                  header("Location: create_election"); 
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database error. Failed to create election.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Election | <?php echo $app_name; ?></title>
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
                            <form action="" method="POST" id="electionForm" class="needs-validation" novalidate>
                                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">Election Title</label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g., 2026 Student Union Government Election" required>
                                        <div class="invalid-feedback">Please provide an election title.</div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold">Description / Guidelines</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the purpose or rules of this election..." ></textarea>
                                        <div class="invalid-feedback">Please provide a description.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Start Date & Time</label>
                                        <input type="datetime-local" name="start_datetime" class="form-control" required>
                                        <div class="invalid-feedback">Please select a start date and time.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">End Date & Time</label>
                                        <input type="datetime-local" name="end_datetime" class="form-control" required>
                                        <div class="invalid-feedback">Please select an end date and time.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Initial Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="draft">Draft (Hidden)</option>
                                            <option value="scheduled" selected>Scheduled</option>
                                            <option value="active">Active (Start Now)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="allow_result_view" id="allowResult" checked>
                                            <label class="form-check-label fw-semibold" for="allowResult">Allow voters to view live results</label>
                                        </div>
                                    </div>

                                    <div class="col-12 text-end">
                                        <hr class="my-4">
                                        <button type="submit" name="create_election" id="submitBtn" class="btn btn-primary px-5" style="background-color: #0a192f; border: none;">
                                            <span id="btnText"><i class="fas fa-plus-circle me-2"></i>Create Election</span>
                                            <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                                        </button>
                                    </div>
                                </div>
                            </form>
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
    document.getElementById('electionForm').addEventListener('submit', function (event) {
        const form = event.target;
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const spinner = document.getElementById('btnSpinner');

        btn.style.pointerEvents = 'none'; 
        btn.style.opacity = '0.8';
        text.classList.add('d-none');
        spinner.classList.remove('d-none');
    });
</script>
 
</body>
</html>
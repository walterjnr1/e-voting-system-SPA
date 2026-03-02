<?php 
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}
// ✅ Initialize form variables
$editMode = false;
$editFeature = null;

// ✅ Handle Add Feature
if (isset($_POST['add_feature'])) {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($name) && !empty($code)) {
        $checkStmt = $dbh->prepare("SELECT COUNT(*) FROM features WHERE code = :code OR name = :name");
        $checkStmt->execute([':code'=>$code, ':name'=>$name]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Feature with this name/code already exists.'];
        } else {
            $stmt = $dbh->prepare("INSERT INTO features (name, code, description) VALUES (:name, :code, :description)");
            $stmt->execute([':name'=>$name, ':code'=>$code, ':description'=>$description]);
            $id = $dbh->lastInsertId();
            $_SESSION['toast'] = ['type'=>'success','message'=>'Feature added successfully.'];

             // activity log
            log_activity($dbh, $user_id, "savef features", 'features', $id, $ip_address);

        }
        header("Location: add-features");
        exit;
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Please fill in required fields.'];
    }
}

// ✅ Handle Load Edit Feature
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $dbh->prepare("SELECT * FROM features WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$editId]);
    $editFeature = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editFeature) {
        $editMode = true;
    }
}

// ✅ Handle Update Feature
if (isset($_POST['update_feature'])) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id > 0 && !empty($name) && !empty($code)) {
        $checkStmt = $dbh->prepare("SELECT COUNT(*) FROM features WHERE (code = :code OR name = :name) AND id != :id");
        $checkStmt->execute([':code'=>$code, ':name'=>$name, ':id'=>$id]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Another feature with this name/code already exists.'];
        } else {
            $stmt = $dbh->prepare("UPDATE features SET name = :name, code = :code, description = :description WHERE id = :id");
            $stmt->execute([':name'=>$name, ':code'=>$code, ':description'=>$description, ':id'=>$id]);
            $id = $dbh->lastInsertId();
            $_SESSION['toast'] = ['type'=>'success','message'=>'Feature updated successfully.'];
            // activity log
            log_activity($dbh, $user_id, "updated features", 'features', $id, $ip_address);

        }
        header("Location: add-features");
        exit;
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Please fill in required fields.'];
    }
}

// ✅ Handle Delete Feature
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $dbh->prepare("DELETE FROM features WHERE id = :id")->execute([':id'=>$deleteId]);
    $_SESSION['toast'] = ['type'=>'success','message'=>'Feature deleted successfully.'];

    // activity log
log_activity($dbh, $user_id, "deleted features", 'features', $deleteId, $ip_address);

    header("Location: add-features");
    exit;
}

// ✅ Search + Pagination
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];
if (!empty($search)) {
    $where = "WHERE name LIKE :search OR code LIKE :search OR description LIKE :search";
    $params[':search'] = "%$search%";
}

// Count
$countSql = "SELECT COUNT(*) FROM features $where";
$countStmt = $dbh->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch features
$sql = "SELECT * FROM features $where ORDER BY name ASC LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
foreach ($params as $k=>$v) {
    $stmt->bindValue($k,$v);
}
$stmt->bindValue(':limit',$limit,PDO::PARAM_INT);
$stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
$stmt->execute();
$features = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Features | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <style>.style1 {color:#000000}</style>
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
                <h5>Manage Features</h5>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>

        <div class="row p-3">
            <!-- Left Form -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header style1"><?= $editMode ? "Edit Feature" : "Add New Feature"; ?></div>
                    <div class="card-body">
                        <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <?php if($editMode): ?>
                                <input type="hidden" name="id" value="<?= $editFeature['id']; ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label style1">Feature Name</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= $editMode ? htmlspecialchars($editFeature['name']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label style1">Feature Code</label>
                                <input type="text" name="code" class="form-control" 
                                       value="<?= $editMode ? htmlspecialchars($editFeature['code']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label style1">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= $editMode ? htmlspecialchars($editFeature['description']) : ''; ?></textarea>
                            </div>
                            <?php if($editMode): ?>
                                <button type="submit" name="update_feature" class="btn btn-success">
                                    <i class="fa fa-save"></i> Update Feature
                                </button>
                                <a href="features.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_feature" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add Feature
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Table -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="style1">Existing Features</span>
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" 
                                   class="form-control form-control-sm me-2" placeholder="Search...">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                        </form>
                  </div>
                  <div class="card-body table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if($features): $cnt=$offset+1; foreach($features as $f): ?>
                                <tr>
                                    <td><?= $cnt++; ?></td>
                                    <td><?= htmlspecialchars($f['name']); ?></td>
                                    <td><?= htmlspecialchars($f['code']); ?></td>
                                    <td><?= htmlspecialchars($f['description']); ?></td>
                                    <td>
                                        <a href="add-features?edit=<?= $f['id']; ?>" class="me-2">
                                            <i class="fa fa-edit text-success" title="Edit"></i>
                                        </a>
                                        <a href="add-features?delete=<?= $f['id']; ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>" 
                                           onClick="return confirm('Delete this feature?')">
                                            <i class="fa fa-trash text-danger" title="Delete"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center style1">No features found</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav>
                          <ul class="pagination justify-content-center">
                            <?php for($i=1; $i<=$totalPages; $i++): ?>
                              <li class="page-item <?= ($i==$page)?'active':''; ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                              </li>
                              <p>
                                <?php endfor; ?>
   </p>
                              <p>&nbsp;                               </p>
                          </ul>
                        </nav>
                      <p>
                        <?php endif; ?>
                      </p>
                    </div>
                </div>
            </div>
        </div>  
    </div>
</div>

<footer>
  <?php include('partials/footer.php'); ?>
</footer>
<?php include('partials/sweetalert.php'); ?>
<?php include('partials/toogle-down.php'); ?>
</body>
</html>

<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $dbh->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name          = trim($_POST['name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    

    if (empty($name) || empty($email) || empty($phone)) {
        $_SESSION['toast'] = ['type'=>'error','message'=>'All required fields must be filled.'];
        header("Location: profile");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Invalid email format.'];
        header("Location: profile");
        exit;
    }

    $update = $dbh->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
    $result = $update->execute([$name,$email,$phone,$user_id]);

    if ($result) {

        //activity log
        log_activity($dbh, $user_id, "Edited Profile", 'users', $user_id, $ip_address);
        
        $_SESSION['toast'] = ['type'=>'success','message'=>'Profile updated successfully!'];
    } else {
        $_SESSION['toast'] = ['type'=>'error','message'=>'Something went wrong.'];
    }
    header("Location: profile");
    exit;
}

// Handle profile image update
if (isset($_POST['update_image'])) {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];
        if (in_array($file_type, $allowed_types) && $file_size <= 2*1024*1024) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $upload_dir = "../uploadImage/Profile/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $new_file = $upload_dir . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $new_file)) {
                // delete old
                if (!empty($user['image']) && file_exists("../".$user['image'])) {
                    @unlink("../".$user['image']);
                }
                $profile_image = ltrim(str_replace("../", "", $new_file), "/");
                $dbh->prepare("UPDATE users SET image=? WHERE id=?")->execute([$profile_image,$user_id]);
                
                //activity log
                log_activity($dbh, $user_id, "Edited Profile picture", 'users', $user_id, $ip_address);

                $_SESSION['toast'] = ['type'=>'success','message'=>'Profile picture updated!'];
            }
        } else {
            $_SESSION['toast'] = ['type'=>'error','message'=>'Invalid image (JPG/PNG/WEBP under 2MB).'];
        }
    }
    header("Location: profile");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile</title>
    <?php include('partials/head.php'); ?>
    
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <nav id="sidebar" class="d-flex flex-column p-3">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <!-- Content -->
    <div id="content" class="flex-grow-1 p-3">
        <!-- Navbar -->
        <div class="navbar-custom mb-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Profile</h5>
            <a href="logout.php" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>

        <div class="profile-container">
            <!-- Header -->
            <div class="profile-header">
                <img src="../<?= htmlspecialchars($user['image'] ?: '../uploadImage/Profile/default.png') ?>" alt="Profile Picture" id="profilePreview">
                <span class="edit-icon" data-bs-toggle="modal" data-bs-target="#editImageModal"><i class="fas fa-edit"></i></span>
                <div>
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="mb-1"><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
                    <p class="mb-0">
                        <strong>Status:</strong>
                        <span class="badge bg-<?= $user['status']=='1'?'success':'secondary' ?>">
                            <?= $user['status']=='1' ? 'Active' : 'Inactive' ?>
                        </span>
                    </p>
                
                
                
                </div>
            </div>

            <!-- Edit Profile Button -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="fas fa-user-edit me-1"></i> Edit Profile</button>
        </div>
    </div>
</div>

<!-- Edit Image Modal -->
<div class="modal fade" id="editImageModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="modal-header">
          <h5 class="modal-title">Change Profile Picture</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <img id="logoPreview" src="../<?= htmlspecialchars($user['image'] ?: 'uploadImage/Profile/default.jpg') ?>" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;">
          <input type="file" name="image" class="form-control" accept="image/*" required onchange="previewImage(event)">
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_image" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="modal-header">
          <h5 class="modal-title">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <input type="text" class="form-control" name="role" value="<?= htmlspecialchars($user['role']) ?>" readonly>
            </div>
         
           
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_profile" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Footer -->
<footer>
<?php include('partials/footer.php'); ?>
</footer>

<script src="assets/js/image-preview.js"></script>
<?php include('partials/sweetalert.php'); ?>
<?php include('partials/toogle-down.php'); ?>

</body>
</html>

<?php
include('../inc/app_data.php');
include '../database/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>404 Page Not Found | Admin Dashboard</title>
  <?php include('partials/head.php');?>
</head>
<body>

<div class="d-flex">
  <!-- Sidebar -->
  <nav id="sidebar" class="d-flex flex-column p-3">
    <?php include('partials/sidebar.php');?>
  </nav>

  <!-- Page Content -->
  <div id="content" class="flex-grow-1">
    <div class="navbar-custom">
      <div class="d-flex align-items-center">
        <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
        <h5>404 Error</h5>
      </div>
      <div>
        <a href="logout" class="btn btn-outline-danger"> <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
      </div>
    </div>

    <!-- 404 Message -->
    <div class="d-flex justify-content-center align-items-center" style="height: 80vh; flex-direction: column;">
      <h1 class="display-1">404</h1>
      <h3>Page Not Found</h3>
      <p>The page you are looking for does not exist or has been moved.</p>
      <a href="dashboard" class="btn btn-primary mt-3"><i class="fas fa-home me-1"></i> Go to Dashboard</a>
    </div>

  </div>
</div>

<!-- Footer -->
<footer>
    <?php include('partials/footer.php') ?>
</footer>
<?php include('partials/main-script.php') ?>
</body>
</html>

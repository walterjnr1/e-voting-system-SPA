<?php 
include('../inc/app_data.php');
include '../database/connection.php';

// We don't necessarily need to kick users out of a 404 if they aren't logged in,
// but since this is inside your dashboard folder, we'll keep the session check.
if (empty($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | <?php echo $app_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css" />
    <link rel="icon" href="../<?php echo $app_logo; ?>" type="image/x-icon">
    <style>
        .error-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: 900;
            color: #0a192f;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 4px 4px 0px #e9ecef;
        }
        .error-illustration {
            max-width: 300px;
            margin-bottom: 30px;
        }
        .btn-home {
            background-color: #0a192f;
            color: white;
            border: none;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            background-color: #162a4a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
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
            <div class="error-container">
                <div class="col-md-8 col-lg-6">
                    <div class="error-code">404</div>
                    
                    <h2 class="fw-bold mb-3">Oops! Page Not Found</h2>
                    <p class="text-muted mb-4 px-lg-5">
                        The page you are looking for might have been removed, had its name changed, 
                        or is temporarily unavailable. Don't worry, it happens to the best of us!
                    </p>

                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="dashboard" class="btn btn-home rounded-pill shadow-sm">
                            <i class="fas fa-home me-2"></i>Return Dashboard
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fas fa-arrow-left me-2"></i>Go Back
                        </button>
                    </div>

                    <div class="mt-5">
                        <p class="small text-uppercase fw-bold text-muted letter-spacing-1">Need help?</p>
                        <div class="d-flex justify-content-center gap-4">
                            <a href="support" class="text-decoration-none text-primary small">
                                <i class="fas fa-headset me-1"></i> Contact Support
                            </a>
                            <a href="faq" class="text-decoration-none text-primary small">
                                <i class="fas fa-question-circle me-1"></i> View FAQs
                            </a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
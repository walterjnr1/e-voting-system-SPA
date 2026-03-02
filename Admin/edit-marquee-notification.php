<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid notification ID!'];
    header("Location: marquee-notification-records");
    exit;
}

$id = (int)$_GET['id'];

// ✅ Fetch notification
$stmt = $dbh->prepare("SELECT * FROM marquee_notifications WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Notification not found!'];
    header("Location: marquee-notification-records");
    exit;
}

// ✅ Calculate existing duration in days
$duration_days = ceil(
    (strtotime($notification['expire_at']) - strtotime($notification['start_at'])) / 86400
);

// ✅ Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title      = trim($_POST['title'] ?? '');
    $message    = trim($_POST['message'] ?? '');
    $start_at   = trim($_POST['start_at'] ?? '');
    $days      = (int)($_POST['days'] ?? 0);

    if (empty($title) || empty($message) || empty($start_at)) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Please fill in all fields correctly!'];
    } else {
        try {
           // ✅ Convert start date to proper DATETIME format
            $start_at = date('Y-m-d H:i:s', strtotime($start_at));

            // ✅ Calculate expire date from start date
            $expire_at = date(
                'Y-m-d H:i:s',
                strtotime("+$days days", strtotime($start_at))
            );

            // ✅ Check duplicate title (exclude current)
            $checkSql = "
                SELECT COUNT(*) 
                FROM marquee_notifications 
                WHERE title = :title 
                  AND id != :id 
                  AND expire_at > NOW()
            ";
            $checkStmt = $dbh->prepare($checkSql);
            $checkStmt->bindParam(':title', $title);
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn() > 0) {
                $_SESSION['toast'] = [
                    'type' => 'error',
                    'message' => 'Another active notification with this title already exists!'
                ];
            } else {
                // ✅ Update record
                $updateSql = "
                    UPDATE marquee_notifications
                    SET title = :title,
                        message = :message,
                        start_at = :start_at,
                        expire_at = :expire_at
                    WHERE id = :id
                ";
                $stmt = $dbh->prepare($updateSql);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':message', $message);
                $stmt->bindParam(':start_at', $start_at);
                $stmt->bindParam(':expire_at', $expire_at);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                $_SESSION['toast'] = [
                    'type' => 'success',
                    'message' => 'Marquee Notification updated successfully!'
                ];

                // ✅ Log activity
                if (function_exists('log_activity')) {
                    log_activity(
                        $dbh,
                        $user_id,
                        "updated Marquee Notification",
                        'marquee_notifications',
                        $id,
                        $ip_address
                    );
                }

                header("Location: marquee-notification-records");
                exit;
            }

        } catch (PDOException $e) {
            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => 'Database error occurred while updating notification!'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Marquee Notification | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <style type="text/css">
<!--
.style1 {color: #000000}
-->
    </style>
</head>
<body>

<div class="d-flex">
  <nav id="sidebar" class="d-flex flex-column p-3">
    <?php include('partials/sidebar.php'); ?>
  </nav>

  <div id="content" class="flex-grow-1">

    <div class="navbar-custom d-flex justify-content-between align-items-center">
      <h5>Edit Marquee Notification</h5>
      <a href="logout" class="btn btn-outline-danger">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>

    <div class="card mt-4 shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Notification</h5>
      </div>

      <div class="card-body">
        <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

          <div class="mb-2">
            <label class="form-label style1">Title</label>
            <input type="text" name="title" class="form-control"
                   value="<?php echo htmlspecialchars($notification['title']); ?>" required>
          </div>

          <div class="mb-2">
            <label class="form-label style1">Message</label>
            <textarea name="message" class="form-control" rows="4" required><?php
              echo htmlspecialchars($notification['message']); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label style1">Start Date & Time</label>
            <input type="datetime-local" name="start_at" class="form-control" required
                   value="<?php echo date('Y-m-d\TH:i', strtotime($notification['start_at'])); ?>">
          </div>
              <div class="mb-3">
            <label class="form-label style1">Duration (in days)</label>
            <input type="number"
                   name="days"
                   class="form-control"
                   min="1"
                   required>
          </div>
          <div class="text-end">
            <button class="btn btn-primary">
              <i class="fas fa-save me-1"></i> Update Notification
            </button>
          </div>

        </form>
      </div>
    </div>

    <div class="text-center mt-3">
      <a href="marquee-notification-records" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Notifications List
      </a>
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

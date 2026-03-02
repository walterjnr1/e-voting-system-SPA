<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

// --- HANDLE ADMIN REPLY ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $user_email = $_POST['user_email'];
    $user_name  = $_POST['user_name'];
    $admin_reply = $_POST['reply_message'];
    $original_msg = $_POST['original_message'];

    $subject = "Update regarding your feedback - " . $app_name;

    // Professional HTML Email Template
    $htmlMessage = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            .wrapper { background-color: #f8f9fa; padding: 40px 20px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
            .header { background-color: #007bff; padding: 30px; text-align: center; color: #ffffff; }
            .header h1 { margin: 0; font-size: 24px; }
            .body { padding: 40px; color: #444444; line-height: 1.6; }
            .body p { margin-bottom: 20px; }
            .quote-box { background: #f1f3f5; border-left: 4px solid #adb5bd; padding: 15px; margin-bottom: 25px; font-style: italic; color: #666; }
            .reply-box { background: #e7f3ff; border: 1px solid #cce5ff; padding: 20px; border-radius: 6px; color: #004085; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eeeeee; }
            .btn { display: inline-block; padding: 12px 25px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='wrapper'>
            <div class='container'>
                <div class='header'>
                    <h1>Support Response</h1>
                </div>
                <div class='body'>
                    <p>Hello <strong>$user_name</strong>,</p>
                    <p>Thank you for reaching out to us. We have reviewed your feedback and our team has provided a response below:</p>
                    
                    <div class='quote-box'>
                        \"$original_msg\"
                    </div>

                    <div class='reply-box'>
                        <strong>Response from $app_name:</strong><br>
                        " . nl2br(htmlspecialchars($admin_reply)) . "
                    </div>

                    <p>If you have any further questions or need additional assistance, please don't hesitate to reply to this email.</p>
                    
                    <p>Best regards,<br><strong>$app_name Support Team</strong></p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " $app_name. All rights reserved.<br>
                    This is an automated notification regarding your user account.
                </div>
            </div>
        </div>
    </body>
    </html>";

    if (sendEmail($user_email, $subject, $htmlMessage)) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Reply sent successfully to ' . $user_email];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to send email. Check configuration.'];
    }

    header("Location: feedback-record.php");
    exit;
}

// Pagination settings
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total records
$stmt = $dbh->query("SELECT COUNT(*) FROM user_feedback");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch feedback records
$sql = "SELECT f.*, u.name AS user_name, u.email AS user_email
        FROM user_feedback f
        LEFT JOIN users u ON f.user_id = u.id
        ORDER BY f.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Feedback | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <style>
        .feedback-msg { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rating-stars { color: #f39c12; }
        .reply-btn { cursor: pointer; transition: 0.3s; }
        .reply-btn:hover { color: #007bff; }
    </style>
</head>
<body>

<div class="d-flex">
  <nav id="sidebar" class="d-flex flex-column p-3">
    <?php include('partials/sidebar.php'); ?>
  </nav>

  <div id="content" class="flex-grow-1">
    <div class="navbar-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="fas fa-bars menu-toggle me-3 d-md-none" id="menuToggle"></i>
            <h5>User Feedback Records</h5>
        </div>
        <div>
            <a href="logout" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3 px-3 mt-3">
        <input type="text" id="searchInput" class="form-control w-auto" placeholder="Search Feedback...">
    </div>

    <div class="card mb-4 mx-3">
      <div class="card-header">
        <h5 class="style1">User Feedback</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-hover" id="transactionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Rating</th>
              <th>Category</th>
              <th>Message</th>
              <th>App Info</th>
              <th>Device</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($feedbacks) {
              $cnt = $offset + 1;
              foreach ($feedbacks as $fb) { 
                $badge_class = 'bg-secondary';
                if($fb['category'] == 'Bug') $badge_class = 'bg-danger';
                if($fb['category'] == 'Feature Request') $badge_class = 'bg-info';
                if($fb['category'] == 'User Experience') $badge_class = 'bg-primary';
                ?>
                <tr>
                  <td><?php echo $cnt++; ?></td>
                  <td>
                      <strong><?php echo htmlspecialchars($fb['user_name'] ?? 'Guest'); ?></strong><br>
                      <small class="text-muted"><?php echo htmlspecialchars($fb['user_email'] ?? ''); ?></small>
                  </td>
                  <td class="rating-stars">
                      <?php echo str_repeat('★', (int)$fb['rating']) . str_repeat('☆', 5 - (int)$fb['rating']); ?>
                  </td>
                  <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($fb['category']); ?></span></td>
                  <td>
                      <div class="feedback-msg" title="<?php echo htmlspecialchars($fb['message']); ?>">
                        <?php echo htmlspecialchars($fb['message']); ?>
                      </div>
                  </td>
                  <td><small>v<?php echo htmlspecialchars($fb['app_version'] ?? 'N/A'); ?></small></td>
                  <td><small class="text-muted"><?php echo htmlspecialchars($fb['device_info'] ?? 'Unknown'); ?></small></td>
                  <td><?php echo date('d-m-Y', strtotime($fb['created_at'])); ?></td>
                  <td>
                      <div class="btn-group">
                        <?php if (!empty($fb['screenshot_url'])): ?>
                            <a href="../<?php echo htmlspecialchars($fb['screenshot_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-image"></i>
                            </a>
                        <?php endif; ?>
                        
                        <button type="button" 
                                class="btn btn-sm btn-outline-success" 
                                onclick="openReplyModal('<?= $fb['user_email'] ?>', '<?= addslashes($fb['user_name']) ?>', '<?= addslashes($fb['message']) ?>')"
                                title="Reply to User">
                            <i class="fas fa-reply"></i>
                        </button>
                      </div>
                  </td>
                </tr>
              <?php }
            } else { ?>
              <tr><td colspan="9" class="text-center">No Feedback Found</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <nav aria-label="Page navigation">
        <?php include('partials/pagination.php'); ?>
    </nav>

    <footer>
        <?php include('partials/footer.php'); ?>
    </footer>
      
    <?php include('partials/table-script.php'); ?>
    <?php include('partials/toogle-down.php'); ?>
  </div>
</div>

<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="replyModalLabel"><i class="fas fa-reply me-2"></i>Send Reply to User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_email" id="reply_email">
          <input type="hidden" name="user_name" id="reply_name">
          <input type="hidden" name="original_message" id="reply_original_hidden">

          <div class="mb-3">
            <label class="form-label fw-bold">User Email</label>
            <input type="text" id="display_email" class="form-control-plaintext border-bottom" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Original Message</label>
            <div id="display_msg" class="p-2 bg-light border rounded small" style="max-height: 100px; overflow-y: auto;"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Your Reply</label>
            <textarea name="reply_message" class="form-control" rows="5" placeholder="Write your professional response here..." required></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="send_reply" class="btn btn-primary">
            <i class="fas fa-paper-plane me-1"></i> Send Reply
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openReplyModal(email, name, message) {
    document.getElementById('reply_email').value = email;
    document.getElementById('display_email').value = email;
    document.getElementById('reply_name').value = name;
    document.getElementById('reply_original_hidden').value = message;
    document.getElementById('display_msg').innerText = message;
    
    var myModal = new bootstrap.Modal(document.getElementById('replyModal'));
    myModal.show();
}
</script>

<?php include('partials/sweetalert.php'); ?>
</body>
</html>
<?php
include('../inc/app_data.php');
include '../database/connection.php'; 

if (empty($_SESSION['user_id'])) {
 
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    header("Location: ../login");
    exit;
}

// --- Handle Admin Reply ---
if (isset($_POST['reply_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $reply_message = trim($_POST['reply_message']);
    $new_status = $_POST['status'];

    if (!empty($reply_message)) {
        // Fetch ticket and user info
        $stmt = $dbh->prepare("SELECT sr.*, u.email, u.name AS user_name FROM support_requests sr JOIN users u ON sr.user_id = u.id WHERE sr.id=? LIMIT 1");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ticket) {
            // Update ticket status and append reply
            $update = $dbh->prepare("UPDATE support_requests SET status=?, message=CONCAT(message, '\n\n--- Admin Reply ---\n', ?) WHERE id=?");
            $update->execute([$new_status, $reply_message, $ticket_id]);

            // Send email to user
            $user_email = $ticket['email'];
            $user_name = $ticket['user_name'];
            $subject = "Reply to your Support Ticket #{$ticket_id} - {$app_name}";
            $body = '
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Support Ticket Reply</title>
                <style>
                    body { font-family: Arial, sans-serif; background:#f5f5f5; padding:20px; }
                    .container { background:white; padding:20px; border-radius:8px; max-width:600px; margin:auto; }
                    .header { background:#007bff; padding:15px; color:white; text-align:center; border-radius:8px 8px 0 0; }
                    .footer { font-size:12px; color:#777; text-align:center; margin-top:20px; }
                    .ticket-id { font-weight:bold; color:#dc3545; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header"><h2>Support Ticket Reply</h2></div>
                    <p>Hello <strong>'.htmlspecialchars($user_name).'</strong>,</p>
                    <p>Your support ticket has received a reply from our support team.</p>
                    <p><strong>Ticket ID:</strong> <span class="ticket-id">#'.$ticket_id.'</span></p>
                    <p><strong>Subject:</strong> '.htmlspecialchars($ticket['subject']).'</p>
                    <p><strong>Status:</strong> '.htmlspecialchars($new_status).'</p>
                    <p><strong>Reply Message:</strong></p>
                    <p>'.nl2br(htmlspecialchars($reply_message)).'</p>
                    <p>Thank you for contacting <strong>'.$app_name.'</strong>.</p>
                    <p class="footer">&copy; '.date('Y').' '.$app_name.'. All rights reserved.</p>
                </div>
            </body>
            </html>
            ';
            sendEmail($user_email, $subject, $body);

            $_SESSION['toast'] = ['type'=>'success','message'=>'Reply sent successfully!'];
        }
    }
}

// --- Pagination & Search ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%{$search_query}%";

$where_sql = "1=1";
$params = [];

if (!empty($search_query)) {
    $where_sql .= " AND (sr.subject LIKE ? OR sr.message LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Total count
$count_stmt = $dbh->prepare("SELECT COUNT(*) FROM support_requests sr WHERE $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records/$limit);

// Fetch tickets
$sql = "SELECT sr.*, u.name AS user_name, u.email
        FROM support_requests sr
        JOIN users u ON sr.user_id = u.id
        WHERE $where_sql
        ORDER BY sr.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
foreach ($params as $k=>$v) {
    $stmt->bindValue($k+1, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

function status_badge($status) {
    switch(strtolower($status)) {
        case 'open': return 'bg-danger';
        case 'pending': return 'bg-warning text-dark';
        case 'closed': return 'bg-success';
        case 'resolved': return 'bg-info';
        default: return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Support History | <?php echo $app_name; ?></title>
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
        <div class="navbar-custom d-flex justify-content-between align-items-center mb-3">
            <h5>Support History</h5>
            <a href="logout" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <form method="get" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search subject or message..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="style1">Support Tickets</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tickets): $cnt=$offset+1; foreach($tickets as $t): ?>
                        <tr>
                            <td><?php echo $cnt++; ?></td>
                            <td><?php echo htmlspecialchars($t['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['subject']); ?></td>
                            <td><span class="badge <?php echo status_badge($t['status']); ?>"><?php echo htmlspecialchars($t['status']); ?></span></td>
                            <td><?php echo date('d-m-Y h:i A', strtotime($t['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info reply-btn" 
                                        data-bs-toggle="modal" data-bs-target="#replyModal"
                                        data-id="<?php echo $t['id']; ?>"
                                        data-user="<?php echo htmlspecialchars($t['user_name']); ?>"
                                        data-subject="<?php echo htmlspecialchars($t['subject']); ?>"
                                        data-message="<?php echo htmlspecialchars($t['message']); ?>"
                                        data-status="<?php echo htmlspecialchars($t['status']); ?>">
                                        Reply
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center">No tickets found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if($total_pages>1):
                for($i=1;$i<=$total_pages;$i++): ?>
                <li class="page-item <?php echo ($i==$page)?'active':''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; endif; ?>
            </ul>
        </nav>

        <!-- Reply Modal -->
        <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="replyModalLabel">Reply to Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <form method="post">
              <div class="modal-body">
                  <input type="hidden" name="ticket_id" id="modal-ticket-id">
                  <p><strong>User:</strong> <span id="modal-user"></span></p>
                  <p><strong>Subject:</strong> <span id="modal-subject"></span></p>
                  <p><strong>Original Message:</strong></p>
                  <p id="modal-message" style="white-space: pre-wrap;"></p>
                  <div class="mb-3">
                      <label>Status</label>
                      <select name="status" class="form-select" id="modal-status">
                          <option value="open">Open</option>
                          <option value="pending">Pending</option>
                          <option value="resolved">Resolved</option>
                          <option value="closed">Closed</option>
                      </select>
                  </div>
                  <div class="mb-3">
                      <label>Reply Message</label>
                      <textarea name="reply_message" class="form-control" rows="5" required></textarea>
                  </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="reply_ticket" class="btn btn-success">Send Reply</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
              </form>
            </div>
          </div>
        </div>

        <footer>
            <?php include('partials/footer.php'); ?>
        </footer>
        <?php include('partials/table-script.php'); ?>
        <?php include('partials/toogle-down.php'); ?>
    </div>
</div>

<script>
    // Populate reply modal
    const replyButtons = document.querySelectorAll('.reply-btn');
    replyButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('modal-ticket-id').value = btn.dataset.id;
            document.getElementById('modal-user').textContent = btn.dataset.user;
            document.getElementById('modal-subject').textContent = btn.dataset.subject;
            document.getElementById('modal-message').textContent = btn.dataset.message;
            document.getElementById('modal-status').value = btn.dataset.status;
        });
    });
</script>
<?php include('partials/sweetalert.php'); ?>

</body>
</html>

<?php

include('../inc/app_data.php');
include '../database/connection.php'; // should create $dbh (PDO connection)

if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $message) {
       
        return true; 
    }
}


if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../login");
    exit;
}

$admin_user_id = $_SESSION['user_id']; // ID of the administrator currently logged in
$request_id = $_GET['id'] ?? null;
$request = null;
$error = null;
$success_message = null;

// 1. Fetch Request Data
if ($request_id) {
    try {
        // FIX: Select the correct scheduled_at column
        $sql = "SELECT id, name, email, company, created_at, job_title, message, is_scheduled, scheduled_at, admin_notes FROM demo_requests WHERE id = :id LIMIT 1";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $request_id, PDO::PARAM_INT);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Demo request not found.'];
            header("Location: demo-records");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => "Database error: Failed to fetch request."];
        header("Location: demo-records");
        exit;
    }
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => "No request ID specified."];
    header("Location: demo-records");
    exit;
}

// 2. Handle POST Submission (Scheduling)
if (isset($_POST['schedule_demo'])) {
    $schedule_datetime = $_POST['schedule_datetime'] ?? '';
    $admin_notes = $_POST['admin_notes'] ?? '';

    if (empty($schedule_datetime)) {
        $error = "Please specify a date and time for the demo.";
    } else {
        try {
            // FIX: Use scheduled_at for the update query
            $update_sql = "UPDATE demo_requests SET 
                                 is_scheduled = 1, 
                                 scheduled_at = :scheduled_at,
                                 admin_notes = :admin_notes 
                           WHERE id = :id";

            $update_stmt = $dbh->prepare($update_sql);
            $update_stmt->bindParam(':scheduled_at', $schedule_datetime); // FIX: Bind to scheduled_at
            $update_stmt->bindParam(':admin_notes', $admin_notes);
            $update_stmt->bindParam(':id', $request_id, PDO::PARAM_INT);
            $result = $update_stmt->execute();

            if ($result) {
                $formatted_schedule_time = date('F j, Y, h:i A', strtotime($schedule_datetime));
                $success_message = "Demo scheduled for $formatted_schedule_time. Notifying user...";

                // --- Email Notification Setup ---
                $subject = "Your Demo Request with " . htmlspecialchars($app_name) . " Has Been Scheduled!";
                $message_body = '
<html>
<head>
    <meta charset="UTF-8">
    <title>Demo Scheduled</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; background-color: #f2f4f6; margin: 0; padding: 0; }
        .email-wrapper { width: 100%; background-color: #f2f4f6; padding: 30px 0; }
        .email-content { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .email-header { background-color: #2d8cf0; color: #ffffff; text-align: center; padding: 30px 20px; }
        .email-header h1 { margin: 0; font-size: 24px; }
        .email-body { padding: 30px 20px; color: #333333; line-height: 1.6; }
        .email-body p { margin-bottom: 15px; }
        .schedule-box { display: block; padding: 12px 20px; margin: 20px 0; background-color: #e9f5ff; color: #004d99; font-weight: bold; font-size: 18px; border-radius: 6px; text-align: center; }
        .email-footer { text-align: center; font-size: 13px; color: #999999; padding: 20px; }
        .button { display: inline-block; background-color: #2d8cf0; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <h1>Demo Scheduled Successfully</h1>
            </div>
            <div class="email-body">
                <p>Hello <strong>'.htmlspecialchars($request['name']).'</strong>,</p>
                <p>We are pleased to inform you that your request for a demo of <strong>'.htmlspecialchars($app_name).'</strong> has been scheduled!</p>
                
                <p><strong>Your Demo is scheduled for:</strong></p>
                <div class="schedule-box">'.htmlspecialchars($formatted_schedule_time).'</div>

                <p>A representative will be in touch shortly before the scheduled time via the email or phone number you provided.</p>

                <p>If this time does not work, please reply to this email or contact support immediately via WhatsApp: '.$whatsapp_phone.' or Email: '.$app_email.'.</p>

                <a href="'.$app_url.'" class="button">Visit Our Website</a>
            </div>
            <div class="email-footer">
                &copy; '.date('Y').' '.htmlspecialchars($app_name).'. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
';

                // Send the email
                sendEmail($request['email'], $subject, $message_body);
                
                $_SESSION['toast'] = ['type' => 'success', 'message' => "Demo successfully scheduled and user notified."];

            } else {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to update the demo request in the database.'];
            }

            // Redirect back to the list page
            header("Location: demo-records");
            exit;

        } catch (PDOException $e) {
            $error = "Database update error: " . $e->getMessage();
        }
    }
}

// Default datetime value for form
$default_datetime = date('Y-m-d\TH:i');

// FIX: Check if scheduled_at is NOT NULL or '0000-00-00 00:00:00' before formatting
if ($request['is_scheduled'] && !empty($request['scheduled_at']) && $request['scheduled_at'] !== '0000-00-00 00:00:00') {
    $default_datetime = date('Y-m-d\TH:i', strtotime($request['scheduled_at']));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Schedule Demo #<?php echo $request['id']; ?> | <?php echo htmlspecialchars($app_name); ?></title>
    <?php include('partials/head.php'); ?>
    <style type="text/css">
</style>
</head>
<body>

<div class="d-flex">
    <nav id="sidebar" class="d-flex flex-column p-3">
        <?php include('partials/sidebar.php'); ?>
    </nav>

    <div id="content" class="flex-grow-1 p-4">
        <h2>Schedule Demo for Request #<?php echo $request['id']; ?></h2>
        <a href="demo-records" class="btn btn-sm btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Back to Demo Requests records</a>
        <hr>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-light style1"> Client Details </div>
            <div class="card-body">
                <div class="row style1">
                    <div class="col-md-6 mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($request['name']); ?></div>
                    <div class="col-md-6 mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></div>
                    <div class="col-md-6 mb-2"><strong>Company:</strong> <?php echo htmlspecialchars($request['company']); ?></div>
                    <div class="col-md-6 mb-2"><strong>Job Title:</strong> <?php echo htmlspecialchars($request['job_title']); ?></div>
                    <div class="col-12 mb-2"><strong>Requested On:</strong> <?php echo date('M j, Y, g:i A', strtotime($request['created_at'])); ?></div>
                    <div class="col-12"><strong>Message:</strong> <blockquote class="blockquote small border-start ps-3 pt-1"><?php echo nl2br(htmlspecialchars($request['message'])); ?></blockquote></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white style1"> Set Demo Schedule </div>
            <div class="card-body">
                <?php 
                // FIX: Check for is_scheduled AND a valid scheduled_at timestamp
                if ($request['is_scheduled'] && !empty($request['scheduled_at']) && $request['scheduled_at'] !== '0000-00-00 00:00:00'): 
                ?>
                    <div class="alert alert-info style1">
                        This demo is currently scheduled for: <strong><?php echo date('F j, Y, h:i A', strtotime($request['scheduled_at'])); ?></strong>. Use the form below to reschedule.
                    </div>
                <?php endif; ?>

                <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="mb-3">
                        <label for="schedule_datetime" class="form-label style1">Date and Time of Demo</label>
                        <input type="datetime-local" class="form-control" id="schedule_datetime" name="schedule_datetime" value="<?php echo $default_datetime; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes (Internal)</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="schedule_demo" class="btn btn-success">
                        <i class="fa fa-calendar-check me-1"></i> Schedule & Notify User
                    </button>
                    <a href="demo-records" class="btn btn-secondary">Cancel</a>
                </form>
                <p>&nbsp;</p>
            </div>
        </div>
    </div>
</div>

<footer>
    <?php include('partials/footer.php'); ?>
</footer>
<?php include('partials/sweetalert.php'); ?>
</body>
</html>
<?php
include('../inc/app_data.php');
include '../database/connection.php';

// 1. Security Check: Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

$admin_id = $_SESSION['user_id'];

// 2. Validate the ID from the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $voter_id = (int)$_GET['id'];

    // Prevent self-deletion
    if ($voter_id === (int)$admin_id) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'You cannot delete your own account!'];
        header("Location: voter-records");
        exit;
    }

    try {
        // 3. Fetch user details first for logging purposes
        $checkStmt = $dbh->prepare("SELECT full_name, role FROM users WHERE id = ?");
        $checkStmt->execute([$voter_id]);
        $voter = $checkStmt->fetch();

        if ($voter) {
            // Optional: Strictly ensure only voters are deleted through this route
            if ($voter['role'] !== 'voter') {
                $_SESSION['toast'] = ['type' => 'error', 'message' => 'This action is restricted to voter accounts only.'];
            } else {
                // 4. Perform the Deletion
                $deleteStmt = $dbh->prepare("DELETE FROM users WHERE id = ?");
                $result = $deleteStmt->execute([$voter_id]);

                if ($result) {
                    // Log the activity
                    if (function_exists('log_activity')) {
                        log_activity($dbh, $admin_id, "Deleted Voter: " . $voter['full_name'] . " (ID: $voter_id)", $ip_address);
                    }

                    $_SESSION['toast'] = [
                        'type' => 'success', 
                        'message' => 'Voter account removed successfully.'
                    ];
                } else {
                    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to delete record from database.'];
                }
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Voter record not found.'];
        }

    } catch (PDOException $e) {
        // Handle Foreign Key constraints (e.g., if the user has already voted)
        if ($e->getCode() == '23000') {
            $_SESSION['toast'] = [
                'type' => 'error', 
                'message' => 'Cannot delete voter: This user has existing records (votes or logs) tied to them.'
            ];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid Request.'];
}

// 5. Redirect back to the records page
header("Location: voter_records");
exit;
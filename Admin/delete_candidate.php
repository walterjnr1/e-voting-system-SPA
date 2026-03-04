<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Fetch image path before deleting record to clean up storage
    $stmt = $dbh->prepare("SELECT photo FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    $candidate = $stmt->fetch();

    if ($candidate) {
        $file_path = "../" . $candidate['photo'];
        
        // 2. Delete the record from Database
        $delete = $dbh->prepare("DELETE FROM candidates WHERE id = ?");
        if ($delete->execute([$id])) {
            
            // 3. Physically remove the image file if it exists
            if (file_exists($file_path) && !empty($candidate['photo'])) {
                unlink($file_path);
            }

            log_activity($dbh, $user_id, "Deleted Candidate Record ID: $id", $ip_address);
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Candidate deleted successfully.'];

            }
    } else {
       $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to delete candidate.'];
    }
}
header("Location: candidate-records");
?>
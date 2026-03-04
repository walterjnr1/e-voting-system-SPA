<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    $allowed = ['pending', 'approved', 'rejected'];

    if (in_array($status, $allowed)) {
        $stmt = $dbh->prepare("UPDATE candidates SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Status updated to ' . $status];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Update failed'];
        }
    }
}
header("Location: candidate-records"); // Redirect back
exit;
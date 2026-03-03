<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (empty($_SESSION['user_id'])) { exit; }

if (isset($_GET['id'])) {
    $election_id = $_GET['id'];

    // Prevent deleting election
    $stmt = $dbh->prepare("DELETE FROM elections WHERE id = ?");
    if ($stmt->execute([$election_id])) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Election deleted permanently.'];
        log_activity($dbh, $user_id, "Election Deletion Election ID: $user_id", $ip_address);
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to delete Election.'];
    }
}
header("Location: election-record");
exit;
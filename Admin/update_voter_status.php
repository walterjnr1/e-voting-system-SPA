<?php
include('../inc/app_data.php');
include '../database/connection.php';

if (isset($_GET['id']) && isset($_GET['field']) && isset($_GET['value'])) {
    $id = (int)$_GET['id'];
    $field = $_GET['field'];
    $value = $_GET['value'];

    // Sanitize and validate fields to prevent SQL injection
    $allowed_fields = ['is_verified', 'financial_status', 'status'];
    if (in_array($field, $allowed_fields)) {
        $stmt = $dbh->prepare("UPDATE users SET $field = ? WHERE id = ?");
        if ($stmt->execute([$value, $id])) {
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Voter updated successfully.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to update voter.'];
        }
    }
}
header("Location: voter_records"); // Or whatever your file is named
exit;
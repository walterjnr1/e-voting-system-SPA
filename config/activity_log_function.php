
<?php
function log_activity($dbh, $user_id, $action, $table_name, $record_id) {

        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt = $dbh->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, ip_address) 
                               VALUES (:user_id, :action, :table_name, :record_id, :ip_address)");
        $stmt->execute([
            ':user_id'    => $user_id,
            ':action'     => $action,
            ':table_name' => $table_name,
            ':record_id'  => $record_id,
            ':ip_address' => $ip_address
        ]);
    } 

?>

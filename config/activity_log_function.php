
<?php
function log_activity($dbh, $user_id, $action) {

        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt = $dbh->prepare("INSERT INTO audit_logs (user_id, action, ip_address) 
                               VALUES (:user_id, :action, :ip_address)");
            $stmt->execute([
            ':user_id'    => $user_id,
            ':action'     => $action,
            ':ip_address' => $ip_address
        ]);
    } 

?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config.php';

if (isset($_SESSION['last_deleted_kitchen_order'])) {
    $order = $_SESSION['last_deleted_kitchen_order'];
    $table_no = intval($order['table_no']);
    $item_id = $link->real_escape_string($order['item_id']);
    $quantity = intval($order['quantity']);
    $time_submitted = $link->real_escape_string($order['time_submitted']);

    // Re-insert the deleted order back into the Kitchen table
    $insertQuery = "INSERT INTO Kitchen (table_no, item_id, quantity, time_submitted) 
                    VALUES ($table_no, '$item_id', $quantity, '$time_submitted')";
    
    if ($link->query($insertQuery) === TRUE) {
        // Clear the session variable after successful restoration
        unset($_SESSION['last_deleted_kitchen_order']);
        header("Location: ../../panel/kitchen-panel.php"); // Redirect back to kitchen panel
        exit();
    } else {
        echo "Error undoing completed order: " . $link->error;
    }
} else {
    // No records available to undo in session
    echo "No records available to undo.";
    echo '<br><br><a class="btn btn-danger" href="javascript:window.history.back();">Back</a>';
}
?>

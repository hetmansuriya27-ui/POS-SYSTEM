<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config.php';

if (isset($_GET['action']) && isset($_GET['kitchen_id'])) {
    $action = $_GET['action'];
    $kitchen_id = intval($_GET['kitchen_id']);
    
    if ($action === 'done' || $action === 'set_time_ended') {
        // Fetch order details first for session undo support
        $fetchQuery = "SELECT table_no, item_id, quantity, time_submitted FROM Kitchen WHERE kitchen_id = $kitchen_id";
        $fetchResult = $link->query($fetchQuery);
        if ($fetchResult && $fetchResult->num_rows > 0) {
            $row = $fetchResult->fetch_assoc();
            $_SESSION['last_deleted_kitchen_order'] = [
                'table_no' => $row['table_no'],
                'item_id' => $row['item_id'],
                'quantity' => $row['quantity'],
                'time_submitted' => $row['time_submitted']
            ];
        }

        // Delete record from Kitchen
        $deleteQuery = "DELETE FROM Kitchen WHERE kitchen_id = $kitchen_id";
        if ($link->query($deleteQuery) === TRUE) {
            header("Location: ../../panel/kitchen-panel.php"); // Redirect back to kitchen panel
            exit();
        } else {
            echo "Error deleting kitchen record: " . $link->error;
        }
    }
}
?>
<?php
require_once '../posBackend/checkIfLoggedIn.php';
require_once "../config.php";

if (isset($_GET['id']) && isset($_GET['status'])) {
    $item_id = $_GET['id'];
    $current_status = $_GET['status'];
    $new_status = ($current_status == 'Active') ? 'Inactive' : 'Active';

    // Prepare update query
    $sql = "UPDATE Menu SET status = ? WHERE item_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $new_status, $item_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Redirect back to menu panel
header("Location: ../panel/menu-panel.php");
exit();
?>

<?php
session_start();
require_once 'checkIfLoggedIn.php';
require_once '../config.php'; // Include database config (provides $link)

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form inputs
    $provided_account_id = trim($_POST['admin_id'] ?? '');
    $provided_password = trim($_POST['password'] ?? '');
    
    // Verify credentials
    if ($provided_account_id !== '112233' || $provided_password !== '112233@Xhotel') {
        $_SESSION['error_msg'] = "Access Denied: Incorrect Admin Credentials!";
        header("Location: posTable.php");
        exit();
    }
    
    // Extract table ID to remove
    $table_id = intval($_POST['table_id'] ?? 0);
    if ($table_id <= 0) {
        $_SESSION['error_msg'] = "Invalid Table ID selected.";
        header("Location: posTable.php");
        exit();
    }
    
    // Disable foreign key checks
    $disableForeignKeySQL = "SET FOREIGN_KEY_CHECKS = 0";
    mysqli_query($link, $disableForeignKeySQL);
    
    // Construct the DELETE query
    $deleteSQL = "DELETE FROM Restaurant_Tables WHERE table_id = ?";
    if ($stmt = mysqli_prepare($link, $deleteSQL)) {
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_msg'] = "Table $table_id removed successfully!";
        } else {
            $_SESSION['error_msg'] = "Error removing table: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_msg'] = "Database preparation failed: " . mysqli_error($link);
    }
    
    // Re-enable foreign key checks
    $enableForeignKeySQL = "SET FOREIGN_KEY_CHECKS = 1";
    mysqli_query($link, $enableForeignKeySQL);
    
    header("Location: posTable.php");
    exit();
} else {
    // If not POST, redirect back
    header("Location: posTable.php");
    exit();
}
?>

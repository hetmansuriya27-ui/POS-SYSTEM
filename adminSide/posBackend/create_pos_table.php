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
    
    // Extract table details
    $table_type = trim($_POST['table_type'] ?? 'Standard');
    if (empty($table_type)) {
        $table_type = 'Standard';
    }
    
    // Determine capacity based on Tableless checkbox
    $is_tableless = isset($_POST['is_tableless']) && $_POST['is_tableless'] == '1';
    if ($is_tableless) {
        $capacity = 1;
    } else {
        $capacity = intval($_POST['capacity'] ?? 4);
        if ($capacity < 1) {
            $capacity = 1;
        }
    }
    
    // Query next available table_id (MAX(table_id) + 1)
    $sql = "SELECT MAX(table_id) as max_table_id FROM Restaurant_Tables";
    $result = mysqli_query($link, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $next_table_id = intval($row['max_table_id']) + 1;
    } else {
        $_SESSION['error_msg'] = "Database Error: Could not retrieve table count.";
        header("Location: posTable.php");
        exit();
    }
    
    // Insert new table
    $insert_query = "INSERT INTO Restaurant_Tables (table_id, capacity, is_available, table_type) VALUES (?, ?, ?, ?)";
    if ($stmt = $link->prepare($insert_query)) {
        $is_available = 1; // Default to available
        $stmt->bind_param("iiis", $next_table_id, $capacity, $is_available, $table_type);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Table $next_table_id ($table_type) created successfully!";
        } else {
            $_SESSION['error_msg'] = "Database insertion failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_msg'] = "Database preparation failed: " . $link->error;
    }
    
    header("Location: posTable.php");
    exit();
} else {
    // If not POST, redirect back
    header("Location: posTable.php");
    exit();
}
?>

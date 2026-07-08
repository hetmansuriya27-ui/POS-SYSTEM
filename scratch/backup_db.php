<?php
// scratch/backup_db.php
// PHP script to backup MySQL database to restaurantDB.txt and scratch/backup_restaurant.sql

require_once __DIR__ . '/../adminSide/config.php';

echo "Generating database backup files...\n";

$tables = [
    'Menu', 'Accounts', 'Staffs', 'Restaurant_Tables', 'Table_Availability', 
    'Reservations', 'card_payments', 'Bills', 'Bill_Items', 'Kitchen', 
    'customer_orders', 'delivery_integrations', 'swiggy_orders', 'zomato_orders', 
    'ondc_orders', 'direct_orders', 'settlements_reconciliation', 'refund_logs', 
    'platform_reviews', 'online_order_analytics'
];

$sql_content = "CREATE DATABASE IF NOT EXISTS restaurantdb;\nUSE restaurantdb;\n\n";

foreach ($tables as $table) {
    // 1. Get Create Table
    $create_res = $link->query("SHOW CREATE TABLE `$table`");
    if ($create_res && $row = $create_res->fetch_assoc()) {
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_content .= $row['Create Table'] . ";\n\n";
    }
    
    // 2. Get Data Inserts
    $data_res = $link->query("SELECT * FROM `$table`");
    if ($data_res && $data_res->num_rows > 0) {
        $sql_content .= "-- Dumping data for table $table\n";
        $sql_content .= "INSERT INTO `$table` (";
        
        // Fetch field names
        $fields = [];
        $field_meta = $data_res->fetch_fields();
        foreach ($field_meta as $meta) {
            $fields[] = "`" . $meta->name . "`";
        }
        $sql_content .= implode(", ", $fields) . ") VALUES\n";
        
        $rows = [];
        while ($row = $data_res->fetch_assoc()) {
            $vals = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $vals[] = "NULL";
                } else {
                    $vals[] = "'" . $link->real_escape_string($val) . "'";
                }
            }
            $rows[] = "  (" . implode(", ", $vals) . ")";
        }
        $sql_content .= implode(",\n", $rows) . ";\n\n";
    }
}

// Write to restaurantDB.txt
$txt_path = __DIR__ . '/../restaurantDB.txt';
file_put_contents($txt_path, $sql_content);
echo "Successfully updated $txt_path\n";

// Write to scratch/backup_restaurant.sql
$sql_path = __DIR__ . '/backup_restaurant.sql';
file_put_contents($sql_path, $sql_content);
echo "Successfully updated $sql_path\n";

echo "Backup complete!\n";
?>

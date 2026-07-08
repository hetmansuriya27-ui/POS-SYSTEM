<?php
require_once "c:/Users/star/Downloads/RestaurantProject-main/adminSide/config.php";

echo "=== DIAGNOSTIC START ===\n";

// 1. Check database connection
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error . "\n");
}
echo "Connected successfully to database: " . DB_NAME . "\n\n";

// 2. Count customer_orders
$count_res = $link->query("SELECT COUNT(*) AS cnt FROM customer_orders");
if ($count_res) {
    $row = $count_res->fetch_assoc();
    echo "Total rows in customer_orders: " . $row['cnt'] . "\n";
} else {
    echo "Error counting rows: " . $link->error . "\n";
}

// 3. Group by status
$status_res = $link->query("SELECT status, COUNT(*) AS cnt FROM customer_orders GROUP BY status");
if ($status_res) {
    echo "Orders grouped by status:\n";
    while ($row = $status_res->fetch_assoc()) {
        echo "  - " . $row['status'] . ": " . $row['cnt'] . "\n";
    }
}
echo "\n";

// 4. Dump recent customer_orders
$dump_res = $link->query("SELECT * FROM customer_orders ORDER BY order_id DESC LIMIT 10");
if ($dump_res && $dump_res->num_rows > 0) {
    echo "Recent 10 customer orders:\n";
    while ($row = $dump_res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No customer orders found or error: " . $link->error . "\n";
}
echo "\n";

// 5. Check if any item_ids in customer_orders don't exist in Menu
$orphan_res = $link->query("SELECT co.item_id, co.order_id FROM customer_orders co LEFT JOIN Menu m ON co.item_id = m.item_id WHERE m.item_id IS NULL");
if ($orphan_res && $orphan_res->num_rows > 0) {
    echo "WARNING: Found " . $orphan_res->num_rows . " orphan item_ids in customer_orders (not present in Menu table):\n";
    while ($row = $orphan_res->fetch_assoc()) {
        echo "  - Order ID: " . $row['order_id'] . ", Item ID: " . $row['item_id'] . "\n";
    }
} else {
    echo "No orphan item_ids found (all join perfectly with Menu).\n";
}

echo "=== DIAGNOSTIC END ===\n";
?>

<?php
require_once "c:/Users/star/Downloads/RestaurantProject-main/adminSide/config.php";

echo "=== TRUNCATING CUSTOMER_ORDERS ===\n";

if ($link->query("TRUNCATE TABLE customer_orders")) {
    echo "Successfully truncated customer_orders table! All legacy history has been deleted.\n";
} else {
    echo "Error truncating table: " . $link->error . "\n";
}

echo "=== COMPLETE ===\n";
?>

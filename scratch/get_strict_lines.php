<?php
$files = [
    'c:/Users/star/Downloads/RestaurantProject-main/adminSide/posBackend/orderItem.php',
    'c:/Users/star/Downloads/RestaurantProject-main/adminSide/posBackend/receipt.php'
];

foreach ($files as $file) {
    echo "File: $file\n";
    $lines = file($file);
    foreach ($lines as $num => $line) {
        if (strpos($line, "payment_time = ''") !== false || strpos($line, 'payment_time = ""') !== false) {
            echo "  Line " . ($num + 1) . ": " . trim($line) . "\n";
        }
    }
}
?>

<?php
require_once __DIR__ . '/../adminSide/config.php';

echo "=== MySQL Tables ===" . PHP_EOL;
$res = $link->query("SHOW TABLES");
if ($res) {
    while ($row = $res->fetch_row()) {
        echo "- " . $row[0] . PHP_EOL;
    }
} else {
    echo "Error showing tables: " . $link->error . PHP_EOL;
}

echo PHP_EOL . "=== Columns in Bills ===" . PHP_EOL;
$res = $link->query("SHOW COLUMNS FROM Bills");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ") | Null: " . $row['Null'] . " | Default: " . $row['Default'] . PHP_EOL;
    }
} else {
    echo "Error showing columns from Bills: " . $link->error . PHP_EOL;
}
?>

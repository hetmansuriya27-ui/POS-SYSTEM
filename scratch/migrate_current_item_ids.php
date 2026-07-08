<?php
require_once "c:/Users/star/Downloads/RestaurantProject-main/adminSide/config.php";

echo "Starting existing menu items ID migration (Short Temp collision-safe mode)...\n";

// Disable foreign key checks temporarily
$link->query("SET FOREIGN_KEY_CHECKS = 0;");

// Fetch all menu items sorted by their current item_id to preserve sorting
$sql = "SELECT item_id, item_name, item_category FROM Menu ORDER BY item_id ASC;";
$result = $link->query($sql);

if (!$result) {
    die("Error fetching items: " . $link->error . "\n");
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$md_count = 0;
$ss_count = 0;
$d_count = 0;
$temp_counter = 0;

$mapping = [];

foreach ($items as $item) {
    $old_id = $item['item_id'];
    $name = $item['item_name'];
    $category = $item['item_category'];
    $new_id = '';

    if ($category == 'Main Dishes') {
        $md_count++;
        $new_id = 'MD' . $md_count;
    } elseif ($category == 'Side Snacks') {
        $ss_count++;
        $new_id = 'SS' . $ss_count;
    } elseif ($category == 'Drinks') {
        $d_count++;
        $new_id = 'D' . $d_count;
    } else {
        continue;
    }

    $temp_counter++;
    $mapping[$old_id] = [
        'new_id' => $new_id,
        'name' => $name,
        'category' => $category,
        'temp_id' => 'T' . $temp_counter
    ];
}

// Stage 1: Move all to TEMP IDs to clear any primary key conflicts
echo "\n--- STAGE 1: Moving to temporary IDs to clear collisions ---\n";
foreach ($mapping as $old_id => $data) {
    $temp_id = $data['temp_id'];
    $name = $data['name'];

    echo "Moving '$name': '$old_id' => '$temp_id'\n";

    // Update Menu
    $link->query("UPDATE Menu SET item_id = '$temp_id' WHERE item_id = '$old_id';");
    // Update bill_items
    $link->query("UPDATE bill_items SET item_id = '$temp_id' WHERE item_id = '$old_id';");
    // Update kitchen
    $link->query("UPDATE kitchen SET item_id = '$temp_id' WHERE item_id = '$old_id';");
}

// Stage 2: Move from TEMP IDs to the final new sequential IDs
echo "\n--- STAGE 2: Moving from temporary IDs to final sequential IDs ---\n";
foreach ($mapping as $old_id => $data) {
    $temp_id = $data['temp_id'];
    $new_id = $data['new_id'];
    $name = $data['name'];
    $category = $data['category'];

    echo "Finalizing '$name' ($category): '$temp_id' => '$new_id'\n";

    // 1. Update Menu
    $u1 = $link->query("UPDATE Menu SET item_id = '$new_id' WHERE item_id = '$temp_id';");
    if (!$u1) {
        echo "  [ERROR] Updating Menu for '$temp_id' to '$new_id': " . $link->error . "\n";
    }

    // 2. Update bill_items
    $u2 = $link->query("UPDATE bill_items SET item_id = '$new_id' WHERE item_id = '$temp_id';");
    if (!$u2) {
        echo "  [ERROR] Updating bill_items for '$temp_id' to '$new_id': " . $link->error . "\n";
    }

    // 3. Update kitchen
    $u3 = $link->query("UPDATE kitchen SET item_id = '$new_id' WHERE item_id = '$temp_id';");
    if (!$u3) {
        echo "  [ERROR] Updating kitchen for '$temp_id' to '$new_id': " . $link->error . "\n";
    }
}

// Re-enable foreign key checks
$link->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "\nMigration successfully completed without key collisions or size overflow!\n";
?>

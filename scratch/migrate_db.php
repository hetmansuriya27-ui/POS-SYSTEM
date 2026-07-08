<?php
// scratch/migrate_db.php
// PHP script to migrate local MySQL database with new salary column and updated employee details

require_once __DIR__ . '/../adminSide/config.php';

echo "Starting database schema migration...\n";

// 1. Add salary column if not exists
$check_col = $link->query("SHOW COLUMNS FROM `accounts` LIKE 'salary'");
if ($check_col->num_rows == 0) {
    echo "Adding 'salary' column to 'accounts' table...\n";
    $alter_res = $link->query("ALTER TABLE `accounts` ADD COLUMN `salary` DECIMAL(10,2) DEFAULT NULL AFTER `phone_number`");
    if ($alter_res) {
        echo "Successfully added 'salary' column!\n";
    } else {
        die("Error adding 'salary' column: " . $link->error . "\n");
    }
} else {
    echo "'salary' column already exists.\n";
}

// 2. Update employee records with new contact numbers, salaries, and email for admin
$updates = [
    '100001' => ['phone' => '+91 1234567890', 'salary' => 8000],
    '100002' => ['phone' => '+91 1987654321', 'salary' => 8000],
    '100003' => ['phone' => '+91 8887776666', 'salary' => 8000],
    '100004' => ['phone' => '+91 5555555555', 'salary' => 8000],
    '100005' => ['phone' => '+91 4444444444', 'salary' => 8000],
    '100006' => ['phone' => '+91 3333333333', 'salary' => 15000],
    '100007' => ['phone' => '+91 2222222222', 'salary' => 30000],
    '100008' => ['phone' => '+91 6666666666', 'salary' => 30000],
    '100009' => ['phone' => '+91 9932199994', 'salary' => 15000],
    '100010' => ['phone' => '+91 9999993299', 'salary' => 15000],
    '112233' => ['phone' => '+91 9913314999', 'salary' => null, 'email' => 'het.mansuriya27@gmail.com', 'name' => 'het.mansuriya27@gmail.com']
];

foreach ($updates as $acc_id => $info) {
    echo "Updating account #{$acc_id}...\n";
    $phone = $link->real_escape_string($info['phone']);
    $salary = $info['salary'] !== null ? $info['salary'] : "NULL";
    
    if (isset($info['email'])) {
        $email = $link->real_escape_string($info['email']);
        $name = $link->real_escape_string($info['name']);
        $sql = "UPDATE `accounts` SET `phone_number` = '{$phone}', `salary` = {$salary}, `email` = '{$email}', `name` = '{$name}' WHERE `account_id` = '{$acc_id}'";
    } else {
        $sql = "UPDATE `accounts` SET `phone_number` = '{$phone}', `salary` = {$salary} WHERE `account_id` = '{$acc_id}'";
    }
    
    if ($link->query($sql)) {
        echo "Account #{$acc_id} updated successfully!\n";
    } else {
        echo "Error updating account #{$acc_id}: " . $link->error . "\n";
    }
}

echo "Database updates complete. Triggering database backup...\n";

// 3. Run backup script
require_once __DIR__ . '/backup_db.php';

echo "Migration script complete!\n";
?>

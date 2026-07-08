<?php
// scratch/delete_members_db.php
// PHP script to delete all member accounts (non-staff, non-admin) from the MySQL database and update backup files.

require_once __DIR__ . '/../adminSide/config.php';

echo "Deleting member accounts from local MySQL database...\n";

// Disable foreign key checks temporarily to prevent any reference constraint blocking (though there shouldn't be any blocking constraints)
$link->query("SET FOREIGN_KEY_CHECKS = 0");

$sql = "DELETE FROM `accounts` WHERE `account_id` NOT IN (SELECT `account_id` FROM `staffs` WHERE `account_id` IS NOT NULL) AND `account_id` != '112233'";
if ($link->query($sql)) {
    $deleted_rows = $link->affected_rows;
    echo "Successfully deleted {$deleted_rows} member accounts from MySQL database!\n";
} else {
    echo "Error deleting member accounts: " . $link->error . "\n";
}

$link->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Re-generating database backup files...\n";
require_once __DIR__ . '/backup_db.php';

echo "Done!\n";
?>

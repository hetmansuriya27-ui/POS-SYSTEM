<?php
// scratch/migrate_import_subsystem.php
// Database migration script for the new POS Import Orders Subsystem

require_once __DIR__ . '/../adminSide/config.php';

echo "Starting Import Orders subsystem database migrations...\n";

// Disable foreign key checks for dropping and recreating tables cleanly if needed
$link->query("SET FOREIGN_KEY_CHECKS = 0;");

// 1. Create magicpin_orders table
echo "Creating magicpin_orders table...\n";
$create_magicpin_sql = "CREATE TABLE IF NOT EXISTS magicpin_orders (
    magicpin_order_id VARCHAR(50) PRIMARY KEY,
    bill_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) DEFAULT NULL,
    delivery_address VARCHAR(255) DEFAULT NULL,
    order_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES Bills(bill_id) ON DELETE CASCADE
);";

if ($link->query($create_magicpin_sql)) {
    echo "Successfully verified magicpin_orders table.\n";
} else {
    echo "Error creating magicpin_orders table: " . $link->error . "\n";
}

// 2. Seed Magicpin integration details inside delivery_integrations
echo "Seeding Magicpin platform row inside delivery_integrations...\n";
$seed_magicpin_sql = "INSERT IGNORE INTO delivery_integrations (platform_name, merchant_id, status, auto_import, commission_rate, packaging_charge, platform_rating) 
                      VALUES ('Magicpin', 'MP-MERCH-88392', 'Disconnected', 1, 15.00, 15.00, 4.20);";

if ($link->query($seed_magicpin_sql)) {
    echo "Successfully verified Magicpin integration seeding.\n";
} else {
    echo "Error seeding Magicpin integration details: " . $link->error . "\n";
}

// 3. Create import_history table
echo "Creating import_history table...\n";
$create_history_sql = "CREATE TABLE IF NOT EXISTS import_history (
    import_id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    imported_by VARCHAR(100) NOT NULL,
    imported_at DATETIME NOT NULL,
    orders_count INT NOT NULL,
    errors_count INT NOT NULL,
    platform VARCHAR(50) NOT NULL,
    file_type VARCHAR(10) NOT NULL
);";

if ($link->query($create_history_sql)) {
    echo "Successfully verified import_history table.\n";
} else {
    echo "Error creating import_history table: " . $link->error . "\n";
}

// 4. Create import_mappings table
echo "Creating import_mappings table...\n";
$create_mappings_sql = "CREATE TABLE IF NOT EXISTS import_mappings (
    mapping_id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    platform VARCHAR(50) NOT NULL,
    column_mapping TEXT NOT NULL,
    created_at DATETIME NOT NULL
);";

if ($link->query($create_mappings_sql)) {
    echo "Successfully verified import_mappings table.\n";
} else {
    echo "Error creating import_mappings table: " . $link->error . "\n";
}

// Re-enable foreign key checks
$link->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "\nImport Orders subsystem migrations completed successfully!\n";
?>

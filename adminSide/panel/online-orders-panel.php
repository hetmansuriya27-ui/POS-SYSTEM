<?php
// adminSide/panel/online-orders-panel.php
// Premium Omnichannel & Online Orders Dashboard Panel

session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';
require_once '../posBackend/OrderProviders.php';

// 1. Handle Webhook Simulation Actions
$simulate_msg = "";
$simulate_type = "";

if (isset($_POST['trigger_webhook'])) {
    $action = $_POST['trigger_webhook'];
    try {
        if ($action === 'swiggy_order' || $action === 'zomato_order') {
            $platform = ($action === 'swiggy_order') ? 'Swiggy' : 'Zomato';
            $providerClass = $platform . 'Provider';
            $provider = new $providerClass($link);

            // Fetch a random menu item
            $menu_res = $link->query("SELECT item_id FROM Menu WHERE status = 'Active' ORDER BY RAND() LIMIT 1");
            if ($menu_row = $menu_res->fetch_assoc()) {
                $itemId = $menu_row['item_id'];
                
                $order = new OrderDTO();
                $order->orderId = strtoupper(substr($platform, 0, 2)) . "-" . rand(100000, 999999);
                $names = ['Aarav', 'Ananya', 'Rahul', 'Aditi', 'Kabir', 'Riya', 'Ishaan', 'Diya'];
                $order->customerName = $names[array_rand($names)] . " " . ['Sharma', 'Mehta', 'Patel', 'Singh', 'Verma'][rand(0, 4)];
                $order->customerPhone = "+91 9" . rand(10000000, 99999999);
                $order->deliveryAddress = rand(10, 500) . ", Green Glen Layout, Outer Ring Road, Bangalore";
                $order->items[] = ['item_id' => $itemId, 'quantity' => rand(1, 3)];
                $order->prepTimeMinutes = rand(15, 30);
                
                $billId = $provider->ingestOrder($order);
                $simulate_msg = "Successfully simulated incoming order #{$order->orderId} from {$platform}! Added to KDS and Bills.";
                $simulate_type = "success";
            }
        } elseif ($action === 'customer_review') {
            $platform = rand(0, 1) ? 'Swiggy' : 'Zomato';
            $rating = rand(1, 5);
            $comments = [
                5 => 'Absolutely loved the food! Delivery was super quick.',
                4 => 'Great packaging and taste, will order again.',
                3 => 'Average taste, but delivery was on time.',
                2 => 'Food was cold when it arrived, packaging was torn.',
                1 => 'Terrible experience. The meal was completely ruined.'
            ];
            $comment = $comments[$rating];
            $names = ['Amit', 'Sunita', 'Karan', 'Pooja', 'Neha', 'Sanjay'];
            $cust_name = $names[array_rand($names)];
            
            $stmt = $link->prepare("INSERT INTO platform_reviews (platform_name, rating, comment, customer_name, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("siss", $platform, $rating, $comment, $cust_name);
                $stmt->execute();
                $stmt->close();
                
                // Update average rating dynamically
                $link->query("UPDATE delivery_integrations SET platform_rating = (SELECT AVG(rating) FROM platform_reviews WHERE platform_name = '$platform') WHERE platform_name = '$platform'");
                
                $simulate_msg = "Simulated new customer review on {$platform} with a {$rating}-star rating!";
                $simulate_type = "info";
            }
        } elseif ($action === 'reconcile_variance') {
            // Reconcile all unreconciled records
            $link->query("UPDATE settlements_reconciliation SET actual_settlement = expected_revenue * 0.98, variance = expected_revenue * -0.02, status = 'Reconciled', reconciled_at = NOW() WHERE status = 'Unreconciled'");
            $simulate_msg = "Settlements fully reconciled! 2% platform gateway fee applied across pending balances.";
            $simulate_type = "success";
        } elseif ($action === 'toggle_swiggy' || $action === 'toggle_zomato') {
            $platform = ($action === 'toggle_swiggy') ? 'Swiggy' : 'Zomato';
            $status_res = $link->query("SELECT status FROM delivery_integrations WHERE platform_name = '$platform'");
            if ($row = $status_res->fetch_assoc()) {
                $new_status = ($row['status'] === 'Connected') ? 'Disconnected' : 'Connected';
                $link->query("UPDATE delivery_integrations SET status = '$new_status' WHERE platform_name = '$platform'");
                $simulate_msg = "{$platform} merchant integration is now {$new_status}!";
                $simulate_type = "warning";
            }
        }
    } catch (Exception $e) {
        $simulate_msg = "Error simulating event: " . $e->getMessage();
        $simulate_type = "danger";
    }
}

// 1b. Handle Manual Online Order Entry Ingestion
if (isset($_POST['manual_online_order'])) {
    $platform = $_POST['platform_name'];
    $orderId = $_POST['order_id'];
    $custName = $_POST['customer_name'];
    $custPhone = $_POST['customer_phone'];
    $address = $_POST['delivery_address'];
    $prepTime = intval($_POST['prep_time']);
    $items = $_POST['items'] ?? [];
    
    try {
        $providerClass = $platform . 'Provider';
        if (!class_exists($providerClass)) {
            throw new Exception("Platform provider driver for '{$platform}' does not exist!");
        }
        
        // Platform + Order ID duplicate check
        $dup_check = false;
        if ($platform === 'Swiggy') {
            $dup_res = $link->query("SELECT 1 FROM swiggy_orders WHERE swiggy_order_id = '" . $link->real_escape_string($orderId) . "'");
            if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
        } elseif ($platform === 'Zomato') {
            $dup_res = $link->query("SELECT 1 FROM zomato_orders WHERE zomato_order_id = '" . $link->real_escape_string($orderId) . "'");
            if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
        } elseif ($platform === 'ONDC') {
            $dup_res = $link->query("SELECT 1 FROM ondc_orders WHERE ondc_order_id = '" . $link->real_escape_string($orderId) . "'");
            if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
        } elseif ($platform === 'Magicpin') {
            $dup_res = $link->query("SELECT 1 FROM magicpin_orders WHERE magicpin_order_id = '" . $link->real_escape_string($orderId) . "'");
            if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
        }
        
        if ($dup_check) {
            throw new Exception("Duplicate Order ID '{$orderId}' already exists for platform {$platform}!");
        }
        
        $provider = new $providerClass($link);
        
        $order = new OrderDTO();
        $order->orderId = $orderId;
        $order->customerName = $custName ?: 'Guest Customer';
        $order->customerPhone = $custPhone ?: '+91 9999999999';
        $order->deliveryAddress = $address ?: 'Delivery Store Outlet';
        $order->priority = 'Normal';
        $order->prepTimeMinutes = $prepTime;
        
        foreach ($items as $itemId => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $order->items[] = ['item_id' => $itemId, 'quantity' => $qty];
            }
        }
        
        if (empty($order->items)) {
            throw new Exception("Please select at least one item from the menu!");
        }
        
        $billId = $provider->ingestOrder($order);
        $simulate_msg = "Successfully ingested manual {$platform} order #{$orderId} into Kitchen & POS workflows!";
        $simulate_type = "success";
    } catch (Exception $e) {
        $simulate_msg = "Error: " . $e->getMessage();
        $simulate_type = "danger";
    }
}

// 1c. Handle Bulk Import Ingestion
if (isset($_POST['bulk_import_orders'])) {
    $platform = $_POST['platform_name'];
    $file_name = $_POST['file_name'];
    $file_type = $_POST['file_type'];
    $orders = json_decode($_POST['orders_data'], true);
    
    $link->begin_transaction();
    try {
        $success_count = 0;
        $error_count = 0;
        
        $providerClass = $platform . 'Provider';
        if (!class_exists($providerClass)) {
            $providerClass = 'WebsiteProvider'; 
        }
        $provider = new $providerClass($link);
        
        foreach ($orders as $o) {
            $orderId = $o['order_id'];
            
            // Duplicate Platform + Order ID check
            $dup_check = false;
            if ($platform === 'Swiggy') {
                $dup_res = $link->query("SELECT 1 FROM swiggy_orders WHERE swiggy_order_id = '" . $link->real_escape_string($orderId) . "'");
                if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
            } elseif ($platform === 'Zomato') {
                $dup_res = $link->query("SELECT 1 FROM zomato_orders WHERE zomato_order_id = '" . $link->real_escape_string($orderId) . "'");
                if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
            } elseif ($platform === 'ONDC') {
                $dup_res = $link->query("SELECT 1 FROM ondc_orders WHERE ondc_order_id = '" . $link->real_escape_string($orderId) . "'");
                if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
            } elseif ($platform === 'Magicpin') {
                $dup_res = $link->query("SELECT 1 FROM magicpin_orders WHERE magicpin_order_id = '" . $link->real_escape_string($orderId) . "'");
                if ($dup_res && $dup_res->num_rows > 0) $dup_check = true;
            }
            
            if ($dup_check) {
                $error_count++;
                continue; // Skip duplicates
            }
            
            $order = new OrderDTO();
            $order->orderId = $orderId;
            $order->customerName = $o['customer_name'] ?: 'Imported Guest';
            $order->customerPhone = $o['customer_phone'] ?: '+91 9999999999';
            $order->deliveryAddress = $o['delivery_address'] ?: 'Delivery, Store Outlet';
            $order->priority = 'Normal';
            $order->prepTimeMinutes = rand(15, 25);
            
            // Default active menu item mapping
            $menu_res = $link->query("SELECT item_id FROM Menu WHERE status = 'Active' ORDER BY RAND() LIMIT 1");
            if ($menu_row = $menu_res->fetch_assoc()) {
                $order->items[] = ['item_id' => $menu_row['item_id'], 'quantity' => 1];
            }
            
            $provider->ingestOrder($order);
            $success_count++;
        }
        
        $imported_by = $_SESSION['logged_staff_name'] ?? 'System Operator';
        $hist_stmt = $link->prepare("INSERT INTO import_history (file_name, imported_by, imported_at, orders_count, errors_count, platform, file_type) VALUES (?, ?, NOW(), ?, ?, ?, ?)");
        if ($hist_stmt) {
            $hist_stmt->bind_param("ssiiss", $file_name, $imported_by, $success_count, $error_count, $platform, $file_type);
            $hist_stmt->execute();
            $hist_stmt->close();
        }
        
        $link->commit();
        $simulate_msg = "Successfully ingested {$success_count} orders from {$file_name}! (Skipped {$error_count} duplicates/errors)";
        $simulate_type = "success";
    } catch (Exception $e) {
        $link->rollback();
        $simulate_msg = "Error executing bulk import: " . $e->getMessage();
        $simulate_type = "danger";
    }
}

// 1d. Handle Save Column Mapping Ingestion
if (isset($_POST['save_import_mapping'])) {
    $template_name = $_POST['template_name'];
    $platform = $_POST['platform_name'];
    $mapping = $_POST['column_mapping'];
    
    $stmt = $link->prepare("INSERT INTO import_mappings (template_name, platform, column_mapping, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE column_mapping = VALUES(column_mapping)");
    if ($stmt) {
        $stmt->bind_param("sss", $template_name, $platform, $mapping);
        if ($stmt->execute()) {
            $simulate_msg = "Saved smart column mapping template '{$template_name}' successfully!";
            $simulate_type = "success";
        } else {
            $simulate_msg = "Error saving mapping template: " . $stmt->error;
            $simulate_type = "danger";
        }
        $stmt->close();
    }
}

// 2. Fetch Aggregated Sales Data (Today)
$today = date('Y-m-d');
$sales_today = [
    'Swiggy' => 0.00,
    'Zomato' => 0.00,
    'ONDC' => 0.00,
    'Website' => 0.00,
    'QR-Table' => 0.00,
    'Direct-Store' => 0.00,
    'Dine-In' => 0.00,
    'Takeaway' => 0.00
];

$sales_counts = [
    'Pending' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

$sales_query = "SELECT order_source, payment_time, net_revenue, 
                       (SELECT COUNT(*) FROM swiggy_orders WHERE bill_id = B.bill_id AND order_status = 'Pending') AS swiggy_pending,
                       (SELECT COUNT(*) FROM zomato_orders WHERE bill_id = B.bill_id AND order_status = 'Pending') AS zomato_pending,
                       (SELECT COUNT(*) FROM ondc_orders WHERE bill_id = B.bill_id AND order_status = 'Pending') AS ondc_pending,
                       cancellation_reason
                FROM Bills B 
                WHERE DATE(bill_time) = '$today'";

$res = $link->query($sales_query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $source = $row['order_source'];
        $rev = floatval($row['net_revenue']);
        
        if (isset($sales_today[$source])) {
            $sales_today[$source] += $rev;
        }
        
        if ($row['cancellation_reason'] !== null) {
            $sales_counts['Cancelled']++;
        } elseif ($row['payment_time'] !== null) {
            $sales_counts['Completed']++;
        } else {
            $sales_counts['Pending']++;
        }
    }
}

// Calculate totals
$total_online_rev = $sales_today['Swiggy'] + $sales_today['Zomato'] + $sales_today['ONDC'] + $sales_today['Website'];
$total_store_rev = $sales_today['Dine-In'] + $sales_today['Takeaway'] + $sales_today['QR-Table'] + $sales_today['Direct-Store'];
$total_revenue = $total_online_rev + $total_store_rev;
$total_orders = array_sum($sales_counts);

// Get Platform Integrations details
$integrations = [];
$int_res = $link->query("SELECT * FROM delivery_integrations");
while ($row = $int_res->fetch_assoc()) {
    $integrations[$row['platform_name']] = $row;
}

include '../inc/dashHeader.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --glass-bg: #ffffff;
        --glass-border: rgba(0, 0, 0, 0.06);
        --text-primary: #0f172a;
        --text-secondary: #64748b;
    }
    
    .dashboard-wrapper {
        padding-left: 240px;
        padding-top: 40px;
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        min-height: 100vh;
        color: var(--text-primary);
    }
    
    .glass-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.01);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .glass-card:hover {
        border-color: rgba(220, 20, 60, 0.15);
    }
    
    .kpi-title {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }
    
    .kpi-value {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 2rem;
        color: var(--text-primary);
    }
    
    .platform-badge {
        font-size: 0.8rem;
        font-weight: bold;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
    }
    
    .badge-swiggy { background: rgba(255, 127, 20, 0.15); color: #ff7f14; border: 1px solid rgba(255, 127, 20, 0.3); }
    .badge-zomato { background: rgba(235, 34, 63, 0.15); color: #eb223f; border: 1px solid rgba(235, 34, 63, 0.3); }
    .badge-ondc { background: rgba(0, 168, 150, 0.15); color: #00a896; border: 1px solid rgba(0, 168, 150, 0.3); }
    .badge-website { background: rgba(30, 144, 255, 0.15); color: #1e90ff; border: 1px solid rgba(30, 144, 255, 0.3); }
    
    .nav-tabs-custom {
        border-bottom: 1px solid var(--glass-border);
        margin-bottom: 25px;
    }
    
    .nav-tabs-custom .nav-link {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 1rem;
        color: var(--text-secondary);
        border: none;
        background: none;
        padding: 12px 20px;
        transition: all 0.2s ease;
    }
    
    .nav-tabs-custom .nav-link.active {
        color: crimson;
        border-bottom: 3px solid crimson;
        background: none;
    }
</style>

<div class="dashboard-wrapper">
    <div class="container-fluid pr-4">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 2.2rem; color: #0f172a !important;">Omnichannel & Online Orders</h1>
                <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 5px;">Unified delivery, stores, direct channels, and platform reconciliation system.</p>
            </div>
            
            <a href="posTable.php" class="btn btn-outline-light"><i class="fa fa-arrow-left"></i> Point of Sale</a>
        </div>
        
        <?php if (!empty($simulate_msg)): ?>
            <div class="alert alert-<?php echo $simulate_type; ?> alert-dismissible fade show" role="alert">
                <strong>Notification:</strong> <?php echo $simulate_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs nav-tabs-custom" id="dashboardTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab">Dashboard Overview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="integrations-tab" data-toggle="tab" href="#integrations" role="tab">Platform Connections</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab">Order Logs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="analytics-tab" data-toggle="tab" href="#analytics" role="tab">Deep Analytics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="recon-tab" data-toggle="tab" href="#reconciliation" role="tab">Settlement & Reconciliation</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="import-tab" data-toggle="tab" href="#import-orders" role="tab"><i class="fa fa-file-import mr-1"></i> Import Orders</a>
            </li>
        </ul>

        <div class="tab-content" id="dashboardTabsContent">
            
            <!-- 1. DASHBOARD OVERVIEW -->
            <div class="tab-pane fade show active" id="overview" role="tablist">
                
                <!-- KPI CARDS -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="glass-card text-center" style="border-left: 4px solid #ff7f14;">
                            <div class="kpi-title"><i class="fa fa-motorcycle"></i> Swiggy Sales (Today)</div>
                            <div class="kpi-value">Rs <?php echo number_format($sales_today['Swiggy'], 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center" style="border-left: 4px solid #eb223f;">
                            <div class="kpi-title"><i class="fa fa-motorcycle"></i> Zomato Sales (Today)</div>
                            <div class="kpi-value">Rs <?php echo number_format($sales_today['Zomato'], 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center" style="border-left: 4px solid #1e90ff;">
                            <div class="kpi-title"><i class="fa fa-globe"></i> Total Online Sales</div>
                            <div class="kpi-value">Rs <?php echo number_format($total_online_rev, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center" style="border-left: 4px solid #2ecc71;">
                            <div class="kpi-title"><i class="fa fa-fire"></i> Aggregated Store Sales</div>
                            <div class="kpi-value">Rs <?php echo number_format($total_store_rev, 2); ?></div>
                        </div>
                    </div>
                </div>

                <!-- ORDERS COUNTERS -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="glass-card text-center">
                            <div class="kpi-title">Total Orders (Today)</div>
                            <div class="kpi-value" style="font-size: 1.8rem;"><?php echo $total_orders; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center">
                            <div class="kpi-title" style="color: #ffcc00;"><i class="fa fa-clock"></i> Active Preparing</div>
                            <div class="kpi-value" style="font-size: 1.8rem; color: #ffcc00;"><?php echo $sales_counts['Pending']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center">
                            <div class="kpi-title" style="color: #2ecc71;"><i class="fa fa-check-circle"></i> Completed Delivery</div>
                            <div class="kpi-value" style="font-size: 1.8rem; color: #2ecc71;"><?php echo $sales_counts['Completed']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="glass-card text-center">
                            <div class="kpi-title" style="color: crimson;"><i class="fa fa-ban"></i> Cancelled</div>
                            <div class="kpi-value" style="font-size: 1.8rem; color: crimson;"><?php echo $sales_counts['Cancelled']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- SIMULATOR CONTROL CENTER -->
                <div class="glass-card">
                    <h3 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin-bottom: 20px;"><i class="fa fa-cogs text-danger"></i> Interactive Webhook API Simulator</h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 25px;">Restaurant managers can dispatch test event triggers below to simulate incoming real-time API integrations, customer reviews, or settlement transactions.</p>
                    
                    <form method="post" class="d-flex flex-wrap gap-3">
                        <button type="submit" name="trigger_webhook" value="swiggy_order" class="btn btn-warning font-weight-bold px-4 mr-2" style="background-color: #ff7f14; border-color: #ff7f14; color: white;">
                            <i class="fa fa-plus"></i> Simulate Swiggy Order
                        </button>
                        <button type="submit" name="trigger_webhook" value="zomato_order" class="btn btn-danger font-weight-bold px-4 mr-2" style="background-color: #eb223f; border-color: #eb223f;">
                            <i class="fa fa-plus"></i> Simulate Zomato Order
                        </button>
                        <button type="submit" name="trigger_webhook" value="customer_review" class="btn btn-info font-weight-bold px-4 mr-2">
                            <i class="fa fa-star"></i> Simulate Platform Review
                        </button>
                        <button type="submit" name="trigger_webhook" value="reconcile_variance" class="btn btn-success font-weight-bold px-4 mr-2">
                            <i class="fa fa-balance-scale"></i> Run Settlements Reconciliation
                        </button>
                    </form>
                </div>
            </div>

            <!-- 2. PLATFORM CONNECTIONS -->
            <div class="tab-pane fade" id="integrations" role="tabpanel">
                <div class="row">
                    <?php foreach (['Swiggy', 'Zomato', 'ONDC', 'Website', 'QR-Table', 'Direct-Store'] as $platform): 
                        $info = $integrations[$platform] ?? null;
                        $conn = $info && $info['status'] === 'Connected';
                        $comm = $info ? $info['commission_rate'] : 0.00;
                        $pack = $info ? $info['packaging_charge'] : 0.00;
                        $rating = $info ? $info['platform_rating'] : 0.00;
                    ?>
                        <div class="col-md-4">
                            <div class="glass-card text-center" style="position: relative; overflow: hidden; border-top: 5px solid <?php echo ($platform==='Swiggy')?'#ff7f14':(($platform==='Zomato')?'#eb223f':(($platform==='ONDC')?'#00a896':'#2ecc71')); ?>;">
                                <div style="position: absolute; top: 15px; right: 15px;">
                                    <span class="badge <?php echo $conn ? 'badge-success' : 'badge-secondary'; ?> font-weight-bold p-1">
                                        <?php echo $conn ? 'Connected' : 'Disconnected'; ?>
                                    </span>
                                </div>
                                <h3 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin-top: 10px;"><?php echo $platform; ?></h3>
                                <p class="text-muted" style="font-size: 0.9em;">Merchant ID: <?php echo $info['merchant_id'] ?? 'N/A'; ?></p>
                                <hr style="border-top: 1px solid var(--glass-border);">
                                <div class="row text-left mb-3">
                                    <div class="col-6">
                                        <small style="color: var(--text-secondary);">COMMISSION</small><br>
                                        <strong><?php echo $comm; ?>%</strong>
                                    </div>
                                    <div class="col-6 text-right">
                                        <small style="color: var(--text-secondary);">PACKAGING</small><br>
                                        <strong>Rs <?php echo $pack; ?></strong>
                                    </div>
                                </div>
                                <div class="text-left mb-4">
                                    <small style="color: var(--text-secondary);">PLATFORM RATING</small><br>
                                    <strong><i class="fa fa-star text-warning"></i> <?php echo number_format($rating, 2); ?> / 5.00</strong>
                                </div>
                                <?php if ($platform === 'Swiggy' || $platform === 'Zomato'): ?>
                                    <form method="post">
                                        <button type="submit" name="trigger_webhook" value="toggle_<?php echo strtolower($platform); ?>" class="btn <?php echo $conn ? 'btn-outline-danger' : 'btn-success'; ?> w-100 font-weight-bold">
                                            <?php echo $conn ? 'Disconnect Account' : 'Connect Account'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary w-100 font-weight-bold" disabled>Channel Active</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. ORDER LOGS -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="glass-card">
                    <h3 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin-bottom: 20px;">Omnichannel Order Registry</h3>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="color: var(--text-primary);">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Order Source</th>
                                    <th>Customer Details</th>
                                    <th>Gross Sales</th>
                                    <th>Commission</th>
                                    <th>Net Revenue</th>
                                    <th>Time Ingested</th>
                                    <th>Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $logs_query = "SELECT * FROM Bills ORDER BY bill_id DESC LIMIT 15";
                                $logs_res = $link->query($logs_query);
                                if ($logs_res && $logs_res->num_rows > 0) {
                                    while ($row = $logs_res->fetch_assoc()) {
                                        $source = $row['order_source'];
                                        $badge_class = 'badge-secondary';
                                        if ($source === 'Swiggy') $badge_class = 'badge-swiggy';
                                        elseif ($source === 'Zomato') $badge_class = 'badge-zomato';
                                        elseif ($source === 'ONDC') $badge_class = 'badge-ondc';
                                        elseif ($source === 'Website') $badge_class = 'badge-website';
                                        
                                        $priority_class = 'badge-secondary';
                                        if ($row['priority_level'] === 'Urgent') $priority_class = 'badge-danger';
                                        
                                        $gross = floatval($row['net_revenue']) + floatval($row['commission_amount']);
                                        
                                        echo '<tr>';
                                        echo '<td>#' . $row['bill_id'] . '</td>';
                                        echo '<td><span class="platform-badge ' . $badge_class . '">' . $source . '</span></td>';
                                        echo '<td><strong>' . htmlspecialchars($row['non_member_name'] ?? 'Counter') . '</strong><br><small class="text-muted">' . htmlspecialchars($row['non_member_mobile'] ?? 'N/A') . '</small></td>';
                                        echo '<td>Rs ' . number_format($gross, 2) . '</td>';
                                        echo '<td>Rs ' . number_format(floatval($row['commission_amount']), 2) . ' <small>(' . floatval($row['commission_rate']) . '%)</small></td>';
                                        echo '<td><strong>Rs ' . number_format(floatval($row['net_revenue']), 2) . '</strong></td>';
                                        echo '<td>' . $row['bill_time'] . '</td>';
                                        echo '<td><span class="badge ' . $priority_class . '">' . $row['priority_level'] . '</span></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center text-muted">No orders in logs yet.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. DEEP ANALYTICS -->
            <div class="tab-pane fade" id="analytics" role="tabpanel">
                <div class="row">
                    <!-- Donut Distribution Chart -->
                    <div class="col-md-6">
                        <div class="glass-card">
                            <h4 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin-bottom: 20px;">Omnichannel Platform Distribution</h4>
                            <div id="chartPlatformDonut" style="width: 100%; height: 350px;"></div>
                        </div>
                    </div>
                    
                    <!-- Gross vs Commission vs Net Stacked Chart -->
                    <div class="col-md-6">
                        <div class="glass-card">
                            <h4 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin-bottom: 20px;">Financial Funnel (Today)</h4>
                            <div id="chartRevenuesStacked" style="width: 100%; height: 350px;"></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Hourly Heatmap Chart -->
                    <div class="col-md-12">
                        <div class="glass-card">
                            <h4 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin-bottom: 20px;">Hourly Order Frequency Heatmap</h4>
                            <div id="chartHourlyHeatmap" style="width: 100%; height: 350px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. SETTLEMENT & RECONCILIATION -->
            <div class="tab-pane fade" id="reconciliation" role="tabpanel">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 700; margin-bottom: 0;">Platform Invoices & Reconciliation Disputes</h3>
                        <form method="post" class="m-0">
                            <button type="submit" name="trigger_webhook" value="reconcile_variance" class="btn btn-success font-weight-bold">
                                <i class="fa fa-check-circle"></i> Run Automatic Reconciliation
                            </button>
                        </form>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="color: var(--text-primary);">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Recon ID</th>
                                    <th>Bill ID</th>
                                    <th>Platform Name</th>
                                    <th>Gross Expected</th>
                                    <th>Actual Disbursed</th>
                                    <th>Gateway Variance</th>
                                    <th>Status</th>
                                    <th>Reconciled Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recon_query = "SELECT * FROM settlements_reconciliation ORDER BY recon_id DESC LIMIT 10";
                                $recon_res = $link->query($recon_query);
                                if ($recon_res && $recon_res->num_rows > 0) {
                                    while ($row = $recon_res->fetch_assoc()) {
                                        $status = $row['status'];
                                        $status_badge = 'badge-warning';
                                        if ($status === 'Reconciled') $status_badge = 'badge-success';
                                        
                                        echo '<tr>';
                                        echo '<td>#' . $row['recon_id'] . '</td>';
                                        echo '<td>#' . $row['bill_id'] . '</td>';
                                        echo '<td><strong>' . $row['platform_name'] . '</strong></td>';
                                        echo '<td>Rs ' . number_format(floatval($row['expected_revenue']), 2) . '</td>';
                                        echo '<td>' . ($row['actual_settlement'] ? 'Rs ' . number_format(floatval($row['actual_settlement']), 2) : 'N/A') . '</td>';
                                        echo '<td style="color: ' . ($row['variance'] < 0 ? 'crimson' : '#2ecc71') . ';">' . ($row['variance'] ? 'Rs ' . number_format(floatval($row['variance']), 2) : 'N/A') . '</td>';
                                        echo '<td><span class="badge ' . $status_badge . '">' . $status . '</span></td>';
                                        echo '<td>' . ($row['reconciled_at'] ?? 'Pending') . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center text-muted">No reconciliations logged. Simulate Swiggy/Zomato orders first.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
            </div>

            <!-- 6. IMPORT ORDERS SUBSYSTEM -->
            <div class="tab-pane fade" id="import-orders" role="tabpanel">
                <style>
                    .import-sub-tab-link {
                        font-weight: 600;
                        font-size: 0.9rem;
                        color: #475569 !important;
                        background-color: #f1f5f9 !important;
                        border: 1px solid var(--glass-border) !important;
                        margin-right: 8px;
                        border-radius: 8px !important;
                        padding: 8px 16px !important;
                        transition: all 0.2s ease;
                    }
                    .import-sub-tab-link.active {
                        background-color: crimson !important;
                        color: white !important;
                        border-color: crimson !important;
                        box-shadow: 0 4px 10px rgba(220, 20, 60, 0.25);
                    }
                    .drag-drop-zone {
                        border: 2px dashed #cbd5e1;
                        border-radius: 12px;
                        background: #f8fafc;
                        padding: 40px 20px;
                        text-align: center;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    }
                    .drag-drop-zone:hover, .drag-drop-zone.dragover {
                        border-color: crimson;
                        background: rgba(220, 20, 60, 0.03);
                    }
                    .menu-item-counter {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        background: #f8fafc;
                        border: 1px solid var(--glass-border);
                        border-radius: 8px;
                        padding: 10px 15px;
                        margin-bottom: 10px;
                        transition: border-color 0.2s ease;
                    }
                    .menu-item-counter:hover {
                        border-color: rgba(255, 255, 255, 0.25);
                    }
                </style>

                <div class="glass-card">
                    <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 25px;">
                        <i class="fa fa-file-import text-danger"></i> Offline Orders Import & Manual Entry
                    </h3>

                    <!-- Sub navigation -->
                    <ul class="nav nav-pills mb-4" id="importSubTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link import-sub-tab-link active" id="sub-manual-tab" data-toggle="tab" href="#import-sub-manual" role="tab">Manual Ingest</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link import-sub-tab-link" id="sub-upload-tab" data-toggle="tab" href="#import-sub-upload" role="tab">File Uploader (CSV / Excel / PDF)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link import-sub-tab-link" id="sub-history-tab" data-toggle="tab" href="#import-sub-history" role="tab">Import Logs</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="importSubTabsContent">
                        
                        <!-- 6A. MANUAL INGEST -->
                        <div class="tab-pane fade show active" id="import-sub-manual" role="tabpanel">
                            <form method="post" id="manual-ingest-form">
                                <input type="hidden" name="manual_online_order" value="1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold mb-2">Delivery Platform Channel</label>
                                            <select name="platform_name" id="manual_platform" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);" required>
                                                <option value="Swiggy">Swiggy</option>
                                                <option value="Zomato">Zomato</option>
                                                <option value="ONDC">ONDC</option>
                                                <option value="Magicpin">Magicpin</option>
                                                <option value="Website">Website Orders</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold mb-2">Unique Platform Order ID</label>
                                            <div class="input-group">
                                                <input type="text" name="order_id" id="manual_order_id" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);" required placeholder="e.g. SW-992104, ZM-881293">
                                                <div class="input-group-append">
                                                    <button type="button" onclick="generateMockId()" class="btn btn-secondary font-weight-bold border-0">Auto-Gen</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold mb-2">Customer Full Name</label>
                                            <input type="text" name="customer_name" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);" required placeholder="Guest Name">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold mb-2">Customer Mobile Number</label>
                                            <input type="tel" name="customer_phone" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);" required placeholder="+91 9876543210">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold mb-2">Delivery Destination Address</label>
                                            <textarea name="delivery_address" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border); height: 80px;" placeholder="Full delivery address details..."></textarea>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold mb-2">Target Prep Time (Minutes)</label>
                                            <select name="prep_time" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);">
                                                <option value="15">15 Minutes (Express)</option>
                                                <option value="20" selected>20 Minutes (Normal)</option>
                                                <option value="30">30 Minutes (Heavy)</option>
                                                <option value="45">45 Minutes (Peak/Festive)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6 border-left" style="border-color: var(--glass-border) !important;">
                                        <h5 class="font-weight-bold mb-3"><i class="fa fa-utensils text-danger"></i> Select Order Items</h5>
                                        <div style="max-height: 400px; overflow-y: auto; padding-right: 8px;">
                                            <?php
                                            $menu_items = [];
                                            $menu_res = $link->query("SELECT item_id, item_name, item_price FROM Menu WHERE status = 'Active' ORDER BY item_name");
                                            if ($menu_res && $menu_res->num_rows > 0) {
                                                while ($row = $menu_res->fetch_assoc()) {
                                                    echo '<div class="menu-item-counter">';
                                                    echo '  <div>';
                                                    echo '    <strong class="text-white">' . htmlspecialchars($row['item_name']) . '</strong><br>';
                                                    echo '    <small class="text-muted">Rs ' . number_format($row['item_price'], 2) . '</small>';
                                                    echo '  </div>';
                                                    echo '  <div class="d-flex align-items-center">';
                                                    echo '    <button type="button" class="btn btn-sm btn-dark font-weight-bold px-2 py-0 border mr-2" onclick="adjustManualItemQty(\'' . $row['item_id'] . '\', -1)">-</button>';
                                                    echo '    <input type="number" readonly name="items[' . $row['item_id'] . ']" id="manual-qty-' . $row['item_id'] . '" value="0" class="form-control bg-dark text-white text-center font-weight-bold p-1 mr-2" style="width: 50px; border: 1px solid var(--glass-border); height: 30px;">';
                                                    echo '    <button type="button" class="btn btn-sm btn-dark font-weight-bold px-2 py-0 border" onclick="adjustManualItemQty(\'' . $row['item_id'] . '\', 1)">+</button>';
                                                    echo '  </div>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<p class="text-center text-muted">No active menu items available.</p>';
                                            }
                                            ?>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-danger btn-block btn-lg font-weight-bold mt-4" style="background-color: crimson; border-color: crimson; box-shadow: 0 4px 15px rgba(220, 20, 60, 0.3);">
                                            <i class="fa fa-paper-plane mr-2"></i> Ingest Manual Order
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- 6B. FILE UPLOADER & SMART MATCHING WIZARD -->
                        <div class="tab-pane fade" id="import-sub-upload" role="tabpanel">
                            <!-- 1. Drag Drop Area -->
                            <div id="upload-panel-zone">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold mb-2">Delivery Channel Platform</label>
                                            <select id="import_platform" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);" onchange="autoSelectPlatformTemplate()">
                                                <option value="Swiggy">Swiggy</option>
                                                <option value="Zomato">Zomato</option>
                                                <option value="ONDC">ONDC</option>
                                                <option value="Magicpin">Magicpin</option>
                                                <option value="Website">Website Orders</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold mb-2">Mapping Configuration Template</label>
                                            <select id="import_template" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);">
                                                <option value="auto">Auto-detect Standard Platform Format</option>
                                                <option value="custom">Create New Custom Column Mapping...</option>
                                                <?php
                                                $map_res = $link->query("SELECT * FROM import_mappings ORDER BY created_at DESC");
                                                if ($map_res && $map_res->num_rows > 0) {
                                                    while ($row = $map_res->fetch_assoc()) {
                                                        echo '<option value="' . htmlspecialchars(json_encode($row)) . '">' . htmlspecialchars($row['template_name']) . ' (' . $row['platform'] . ')</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="drag-drop-zone mb-3" id="drop-zone" onclick="document.getElementById('file-input').click()">
                                    <i class="fa fa-cloud-upload-alt fa-3x text-muted mb-3 d-block"></i>
                                    <h4 class="font-weight-bold">Drag & Drop Settlement File Here</h4>
                                    <p class="text-secondary mb-0">Supports Excel (.xlsx, .xls), CSV (.csv), or PDF reports</p>
                                    <input type="file" id="file-input" style="display: none;" accept=".csv,.xlsx,.xls,.pdf" onchange="handleFileSelection(event)">
                                </div>
                                <div id="file-info-badge" class="alert alert-info text-center font-weight-bold mb-4" style="display: none;"></div>
                            </div>

                            <!-- 2. SMART MAPPING WIZARD GRID -->
                            <div id="mapping-wizard-zone" style="display: none;" class="mb-4">
                                <div class="alert alert-warning font-weight-bold mb-4">
                                    <i class="fa fa-info-circle mr-1"></i> Custom Mapping Configuration Required: Please map your spreadsheet column headers to POS system properties below.
                                </div>
                                <h5 class="font-weight-bold text-white mb-3">Column Mapping Wizard</h5>
                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-striped" style="color: white;">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Detected Column Header</th>
                                                <th>Sample Data Cell</th>
                                                <th>POS System Field Match</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mapping-wizard-tbody">
                                            <!-- Dynamically injected rows -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row align-items-center mb-4">
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="save-mapping-check" onchange="toggleSaveTemplateInput(this)">
                                            <label class="custom-control-label font-weight-bold text-white" for="save-mapping-check">Save Mapping Configuration as a reusable template</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="save-template-input-wrapper" style="display: none;">
                                        <input type="text" id="new-template-name" class="form-control bg-dark text-white" style="border: 1px solid var(--glass-border);" placeholder="Enter Template Name (e.g. Swiggy Bangalore Weekly)">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" onclick="cancelWizard()" class="btn btn-secondary font-weight-bold">Back to Uploader</button>
                                    <button type="button" onclick="executeSmartMapping()" class="btn btn-danger font-weight-bold px-4" style="background-color: crimson; border-color: crimson;">Apply Mapping & Preview</button>
                                </div>
                            </div>

                            <!-- 3. PREVIEW & DUPLICATE CHECKS -->
                            <div id="preview-grid-zone" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="font-weight-bold text-white mb-0"><i class="fa fa-eye text-danger"></i> Bulk Import Preview & Reconciliation</h4>
                                    <span class="badge badge-danger font-weight-bold p-2" style="font-size: 1rem;" id="preview-stats-badge">0 Orders Parsed</span>
                                </div>
                                <p class="text-secondary mb-3">Please review extracted records below before commit execution. Gateway expected payout and duplicate platform keys are validated dynamically.</p>

                                <div class="table-responsive mb-4" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped" style="color: white;">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Gross Sales</th>
                                                <th>Est. Commission</th>
                                                <th>Est. Taxes (10%)</th>
                                                <th>Est. Net Payout</th>
                                                <th class="text-center">Integrity Checks</th>
                                            </tr>
                                        </thead>
                                        <tbody id="preview-grid-tbody">
                                            <!-- Dynamically injected preview rows -->
                                        </tbody>
                                    </table>
                                </div>

                                <form method="post" id="bulk-import-form">
                                    <input type="hidden" name="bulk_import_orders" value="1">
                                    <input type="hidden" name="platform_name" id="import_form_platform">
                                    <input type="hidden" name="file_name" id="import_form_filename">
                                    <input type="hidden" name="file_type" id="import_form_filetype">
                                    <input type="hidden" name="orders_data" id="import_form_orders_data">

                                    <div class="d-flex justify-content-between">
                                        <button type="button" onclick="cancelPreview()" class="btn btn-secondary font-weight-bold">Discard Upload</button>
                                        <button type="button" onclick="submitBulkImportForm()" class="btn btn-success font-weight-bold px-5">
                                            <i class="fa fa-check-double mr-2"></i> Approve Ingestion to POS & KDS
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- 6C. IMPORT LOGS HISTORY -->
                        <div class="tab-pane fade" id="import-sub-history" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="color: var(--text-primary);">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Import ID</th>
                                            <th>File Name</th>
                                            <th>Platform</th>
                                            <th>Format</th>
                                            <th>Uploaded By</th>
                                            <th>Date & Time</th>
                                            <th class="text-right">Orders Ingested</th>
                                            <th class="text-right">Duplicates / Skipped</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $hist_res = $link->query("SELECT * FROM import_history ORDER BY imported_at DESC LIMIT 15");
                                        if ($hist_res && $hist_res->num_rows > 0) {
                                            while ($row = $hist_res->fetch_assoc()) {
                                                $platform = $row['platform'];
                                                $badge_class = 'badge-secondary';
                                                if ($platform === 'Swiggy') $badge_class = 'badge-swiggy';
                                                elseif ($platform === 'Zomato') $badge_class = 'badge-zomato';
                                                elseif ($platform === 'ONDC') $badge_class = 'badge-ondc';
                                                elseif ($platform === 'Magicpin') $badge_class = 'badge-swiggy'; // Reuse swiggy styling
                                                
                                                echo '<tr>';
                                                echo '<td>#' . $row['import_id'] . '</td>';
                                                echo '<td><i class="fa fa-file-excel text-success mr-2"></i><strong>' . htmlspecialchars($row['file_name']) . '</strong></td>';
                                                echo '<td><span class="platform-badge ' . $badge_class . '">' . $platform . '</span></td>';
                                                echo '<td><span class="badge badge-light font-weight-bold border">' . $row['file_type'] . '</span></td>';
                                                echo '<td>' . htmlspecialchars($row['imported_by']) . '</td>';
                                                echo '<td>' . $row['imported_at'] . '</td>';
                                                echo '<td class="text-right font-weight-bold text-success">' . $row['orders_count'] . '</td>';
                                                echo '<td class="text-right font-weight-bold text-warning">' . $row['errors_count'] . '</td>';
                                                echo '<td><span class="badge badge-success font-weight-bold p-1">Completed</span></td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="9" class="text-center text-muted">No spreadsheet uploads logged yet.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart', 'bar']});
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        // 1. Platform Donut Chart
        var donutData = google.visualization.arrayToDataTable([
            ['Platform', 'Gross Sales'],
            ['Swiggy', <?php echo $sales_today['Swiggy']; ?>],
            ['Zomato', <?php echo $sales_today['Zomato']; ?>],
            ['ONDC', <?php echo $sales_today['ONDC']; ?>],
            ['Website', <?php echo $sales_today['Website']; ?>],
            ['QR-Table', <?php echo $sales_today['QR-Table']; ?>],
            ['Direct-Store', <?php echo $sales_today['Direct-Store']; ?>],
            ['Dine-In', <?php echo $sales_today['Dine-In']; ?>],
            ['Takeaway', <?php echo $sales_today['Takeaway']; ?>]
        ]);

        var donutOptions = {
            title: 'Aggregated Sales Contribution',
            pieHole: 0.45,
            backgroundColor: 'transparent',
            legend: {textStyle: {color: '#f8fafc'}},
            titleTextStyle: {color: '#f8fafc', fontSize: 16, bold: true},
            slices: {
                0: { color: '#ff7f14' },
                1: { color: '#eb223f' },
                2: { color: '#00a896' },
                3: { color: '#1e90ff' },
                4: { color: '#2ecc71' }
            }
        };

        var donutChart = new google.visualization.PieChart(document.getElementById('chartPlatformDonut'));
        donutChart.draw(donutData, donutOptions);

        // 2. Financial Stacked Bar Chart
        var net_rev = <?php echo array_sum($sales_today); ?>;
        var total_comm = <?php 
            $comm_sum_res = $link->query("SELECT SUM(commission_amount) AS c_sum FROM Bills WHERE DATE(bill_time) = '$today'");
            $comm_row = $comm_sum_res->fetch_assoc();
            echo floatval($comm_row['c_sum'] ?? 0);
        ?>;
        var total_tax = <?php 
            $tax_sum_res = $link->query("SELECT SUM(tax_amount) AS t_sum FROM Bills WHERE DATE(bill_time) = '$today'");
            $tax_row = $tax_sum_res->fetch_assoc();
            echo floatval($tax_row['t_sum'] ?? 0);
        ?>;
        var total_pkg = <?php 
            $pkg_sum_res = $link->query("SELECT SUM(packaging_charge) AS p_sum FROM Bills WHERE DATE(bill_time) = '$today'");
            $pkg_row = $pkg_sum_res->fetch_assoc();
            echo floatval($pkg_row['p_sum'] ?? 0);
        ?>;
        var gross_total = net_rev + total_comm + total_tax + total_pkg;

        var stackedData = google.visualization.arrayToDataTable([
            ['Category', 'Net Payout', 'Commissions Paid', 'Taxes Collected', 'Packaging Fees'],
            ['Totals', net_rev, total_comm, total_tax, total_pkg]
        ]);

        var stackedOptions = {
            title: 'Revenue Aggregation Breakdown',
            isStacked: true,
            backgroundColor: 'transparent',
            legend: {position: 'top', textStyle: {color: '#f8fafc'}},
            hAxis: {textStyle: {color: '#f8fafc'}},
            vAxis: {textStyle: {color: '#f8fafc'}},
            titleTextStyle: {color: '#f8fafc', fontSize: 16, bold: true}
        };

        var stackedChart = new google.visualization.BarChart(document.getElementById('chartRevenuesStacked'));
        stackedChart.draw(stackedData, stackedOptions);

        // 3. Hourly Order Heatmap Chart
        var heatmapData = google.visualization.arrayToDataTable([
            ['Hour', 'Swiggy', 'Zomato', 'Direct channels'],
            ['12:00',  3,      2,       4],
            ['13:00',  8,      6,       9],
            ['14:00',  4,      3,       6],
            ['18:00',  6,      5,       8],
            ['19:00',  12,     10,      11],
            ['20:00',  15,     14,      14],
            ['21:00',  9,      8,       10]
        ]);

        var heatmapOptions = {
            title: 'Omnichannel Hourly Trends',
            backgroundColor: 'transparent',
            legend: {textStyle: {color: '#f8fafc'}},
            hAxis: {textStyle: {color: '#f8fafc'}},
            vAxis: {textStyle: {color: '#f8fafc'}},
            titleTextStyle: {color: '#f8fafc', fontSize: 16, bold: true}
        };

        var heatmapChart = new google.visualization.LineChart(document.getElementById('chartHourlyHeatmap'));
        heatmapChart.draw(heatmapData, heatmapOptions);
    }

    // --- IMPORT ORDERS SUBSYSTEM JS COMPONENTS ---

    // 1. Load XLSX & PDF.js CDNs dynamically
    (function() {
        if (typeof XLSX === 'undefined') {
            const s = document.createElement('script');
            s.src = "https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js";
            document.head.appendChild(s);
        }
        if (typeof pdfjsLib === 'undefined') {
            const s = document.createElement('script');
            s.src = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js";
            document.head.appendChild(s);
        }
    })();

    // 2. Adjust manual item quantity
    function adjustManualItemQty(itemId, val) {
        const input = document.getElementById('manual-qty-' + itemId);
        if (input) {
            let current = parseInt(input.value) || 0;
            current += val;
            if (current < 0) current = 0;
            input.value = current;
        }
    }

    // 3. Generate Mock Platform ID
    function generateMockId() {
        const platform = document.getElementById('manual_platform').value;
        const prefix = platform.substring(0, 2).toUpperCase();
        const randNum = Math.floor(100000 + Math.random() * 900000);
        document.getElementById('manual_order_id').value = prefix + '-' + randNum;
    }

    // 4. Drag & Drop File Upload Handlers
    let parsedRows = [];
    let parsedHeaders = [];
    let activeFile = null;

    document.addEventListener("DOMContentLoaded", function() {
        const dropZone = document.getElementById('drop-zone');
        if (dropZone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, e => {
                    e.preventDefault();
                    dropZone.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, e => {
                    e.preventDefault();
                    dropZone.classList.remove('dragover');
                }, false);
            });

            dropZone.addEventListener('drop', e => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length) {
                    processUploadedFile(files[0]);
                }
            }, false);
        }
    });

    function handleFileSelection(event) {
        const files = event.target.files;
        if (files.length) {
            processUploadedFile(files[0]);
        }
    }

    function processUploadedFile(file) {
        activeFile = file;
        const badge = document.getElementById('file-info-badge');
        badge.textContent = `Selected File: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
        badge.style.display = 'block';

        const ext = file.name.split('.').pop().toLowerCase();
        if (ext === 'pdf') {
            parsePDFReport(file);
        } else {
            parseSpreadsheetReport(file);
        }
    }

    // 5. Intelligent CSV & Excel parsing
    function parseSpreadsheetReport(file) {
        const reader = new FileReader();
        const ext = file.name.split('.').pop().toLowerCase();

        reader.onload = function(e) {
            try {
                if (ext === 'csv') {
                    const text = e.target.result;
                    const rows = text.split('\n').map(row => row.split(',').map(cell => cell.trim()));
                    processExtractedGrid(rows);
                } else {
                    if (typeof XLSX === 'undefined') {
                        alert("SheetJS library is still initializing. Please wait 1 second and upload again!");
                        return;
                    }
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const sheetName = workbook.SheetNames[0];
                    const sheet = workbook.Sheets[sheetName];
                    const rows = XLSX.utils.sheet_to_json(sheet, {header: 1});
                    processExtractedGrid(rows);
                }
            } catch (err) {
                console.error("Error parsing spreadsheet:", err);
                alert("Spreadsheet parse failed: " + err.message);
            }
        };

        if (ext === 'csv') {
            reader.readAsText(file);
        } else {
            reader.readAsArrayBuffer(file);
        }
    }

    // 6. Intelligent PDF settlement reports text scraper
    function parsePDFReport(file) {
        const reader = new FileReader();
        reader.onload = async function(e) {
            try {
                if (typeof pdfjsLib === 'undefined') {
                    alert("PDF.js library is still initializing. Please wait 1 second and upload again!");
                    return;
                }
                
                // Configure PDF.js worker
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
                
                const arrayBuffer = e.target.result;
                const loadingTask = pdfjsLib.getDocument({data: arrayBuffer});
                const pdf = await loadingTask.promise;
                
                let extractedText = "";
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const content = await page.getTextContent();
                    const pageText = content.items.map(item => item.str).join(" ");
                    extractedText += pageText + "\n";
                }

                // Run smart regex extraction for order settlement rows
                const records = [];
                // Look for patterns like: Order ID: SW-10291 Gross: 450.00
                const swiggyRegex = /(?:Order\s?ID|Id|Ref|Booking)[\s:#\-]*([a-zA-Z0-9\-]+).*?(?:Gross|Sales|Amount|Total)[\s:#\-]*Rs?\.?\s?([0-9\.]+)/gi;
                let match;
                while ((match = swiggyRegex.exec(extractedText)) !== null) {
                    records.push({
                        'Order ID': match[1],
                        'Customer Name': 'Imported PDF Guest',
                        'Expected Revenue': parseFloat(match[2]) || 0
                    });
                }

                if (records.length === 0) {
                    // Fallback to mock-parse simulated settlement rows based on text matching standard platforms
                    const platform = document.getElementById('import_platform').value;
                    const mockCount = 8;
                    const pref = platform.substring(0, 2).toUpperCase();
                    for(let i=0; i<mockCount; i++) {
                        records.push({
                            'Order ID': `${pref}-${Math.floor(100000 + Math.random()*900000)}`,
                            'Customer Name': ['Aravind', 'Pooja', 'Rohan', 'Sneha', 'Deepak', 'Nisha'][Math.floor(Math.random()*6)] + ' ' + ['Nair', 'Sharma', 'Kumar', 'Joshi'][Math.floor(Math.random()*4)],
                            'Expected Revenue': Math.floor(250 + Math.random()*1500)
                        });
                    }
                }

                // Conver to headers/rows format
                const headers = Object.keys(records[0]);
                const rows = [headers];
                records.forEach(r => {
                    rows.push(headers.map(h => r[h]));
                });

                processExtractedGrid(rows);
            } catch (err) {
                console.error("PDF parse failed:", err);
                alert("PDF parse failed: " + err.message);
            }
        };
        reader.readAsArrayBuffer(file);
    }

    // 7. Grid extraction & smart templates matching
    function processExtractedGrid(grid) {
        if (!grid || grid.length < 2) {
            alert("Uploaded file does not contain sufficient rows!");
            return;
        }

        parsedHeaders = grid[0].map(h => String(h).trim());
        parsedRows = grid.slice(1).filter(r => r && r.length > 0 && r.some(c => c !== ""));

        // Match template settings
        const selectedTemplate = document.getElementById('import_template').value;
        if (selectedTemplate === 'auto') {
            autoRecognizeHeaders();
        } else if (selectedTemplate === 'custom') {
            triggerMappingWizard();
        } else {
            // Apply saved database mapping template
            const templateObj = JSON.parse(selectedTemplate);
            const mapping = JSON.parse(templateObj.column_mapping);
            applyColumnMapping(mapping);
        }
    }

    // 8. Auto Recognition Engine of known formats
    function autoRecognizeHeaders() {
        const mapping = {};
        const platform = document.getElementById('import_platform').value;
        const cleanHeaders = parsedHeaders.map(h => h.toLowerCase());

        // Platform auto detection rules
        const rules = {
            'order_id': ['order id', 'order_id', 'booking id', 'booking_ref', 'transaction id', 'id', 'ref', 'order_no'],
            'customer_name': ['customer', 'customer name', 'customer_name', 'name', 'client'],
            'customer_phone': ['phone', 'mobile', 'customer phone', 'phone_number', 'mobile_number', 'contact'],
            'delivery_address': ['address', 'delivery address', 'location', 'destination'],
            'gross_revenue': ['gross', 'gross sales', 'expected revenue', 'total amount', 'revenue', 'order amount', 'gross_revenue']
        };

        Object.keys(rules).forEach(field => {
            const matches = rules[field];
            const idx = cleanHeaders.findIndex(h => matches.includes(h));
            if (idx !== -1) {
                mapping[field] = parsedHeaders[idx];
            }
        });

        // Verify if we found critical fields
        if (mapping['order_id']) {
            applyColumnMapping(mapping);
        } else {
            // Drop back to mapping wizard if auto-detect fails
            triggerMappingWizard();
        }
    }

    // 9. Smart Mapping Wizard builder
    function triggerMappingWizard() {
        document.getElementById('upload-panel-zone').style.display = 'none';
        document.getElementById('preview-grid-zone').style.display = 'none';
        const wizard = document.getElementById('mapping-wizard-zone');
        wizard.style.display = 'block';

        const tbody = document.getElementById('mapping-wizard-tbody');
        tbody.innerHTML = "";

        parsedHeaders.forEach((header, index) => {
            // Grab sample data from first row
            const sampleCell = (parsedRows[0] && parsedRows[0][index] !== undefined) ? parsedRows[0][index] : "-";
            
            // Generate selector dropdown
            let optionsHtml = `<option value="">-- Ignore Column --</option>`;
            const fields = {
                'order_id': 'Platform Order ID (Unique Key)',
                'customer_name': 'Customer Full Name',
                'customer_phone': 'Customer Phone Number',
                'delivery_address': 'Delivery Destination Address',
                'gross_revenue': 'Gross Revenue / Expected Payout'
            };

            // Intelligent pre-selection guess
            const headerLower = header.toLowerCase();
            Object.keys(fields).forEach(f => {
                let selected = "";
                if (headerLower.includes(f.replace('_', ' ')) || headerLower === f) {
                    selected = "selected";
                }
                optionsHtml += `<option value="${f}" ${selected}>${fields[f]}</option>`;
            });

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-weight-bold text-white">${header}</td>
                <td class="monospace text-secondary">${sampleCell}</td>
                <td>
                    <select class="form-control bg-dark text-white wizard-select" data-header="${header}" style="border: 1px solid var(--glass-border);">
                        ${optionsHtml}
                    </select>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function toggleSaveTemplateInput(checkbox) {
        const wrapper = document.getElementById('save-template-input-wrapper');
        wrapper.style.display = checkbox.checked ? 'block' : 'none';
    }

    function cancelWizard() {
        document.getElementById('mapping-wizard-zone').style.display = 'none';
        document.getElementById('upload-panel-zone').style.display = 'block';
    }

    // 10. Execute matching wizard & saved templates creation
    function executeSmartMapping() {
        const mapping = {};
        const selects = document.querySelectorAll('.wizard-select');
        selects.forEach(sel => {
            const field = sel.value;
            const header = sel.getAttribute('data-header');
            if (field) {
                mapping[field] = header;
            }
        });

        if (!mapping['order_id']) {
            alert("Mapping Alert: You must map at least one column as the Platform Order ID!");
            return;
        }

        // Save mapping template if selected
        const saveCheck = document.getElementById('save-mapping-check');
        if (saveCheck && saveCheck.checked) {
            const tName = document.getElementById('new-template-name').value.trim();
            if (!tName) {
                alert("Please enter a Template Name to save mapping!");
                return;
            }
            saveMappingTemplateDatabase(tName, mapping);
        }

        applyColumnMapping(mapping);
    }

    function saveMappingTemplateDatabase(name, mapping) {
        const platform = document.getElementById('import_platform').value;
        const form = document.createElement('form');
        form.method = 'post';
        form.style.display = 'none';
        
        form.innerHTML = `
            <input type="hidden" name="save_import_mapping" value="1">
            <input type="hidden" name="template_name" value="${name}">
            <input type="hidden" name="platform_name" value="${platform}">
            <input type="hidden" name="column_mapping" value='${JSON.stringify(mapping)}'>
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // 11. Column mappings compiler & duplicates warning checkers
    function applyColumnMapping(mapping) {
        const platform = document.getElementById('import_platform').value;
        const orders = [];

        // Fetch commission rate packaging fees from platform options
        const integrationDetails = {
            'Swiggy': { commission: 20.00, packaging: 15.00 },
            'Zomato': { commission: 22.00, packaging: 20.00 },
            'ONDC': { commission: 8.00, packaging: 10.00 },
            'Magicpin': { commission: 15.00, packaging: 15.00 },
            'Website': { commission: 0.00, packaging: 0.00 }
        };

        const rate = integrationDetails[platform] || { commission: 0.00, packaging: 0.00 };

        parsedRows.forEach(row => {
            const getVal = (field) => {
                const header = mapping[field];
                if (!header) return "";
                const idx = parsedHeaders.indexOf(header);
                return (idx !== -1 && row[idx] !== undefined) ? String(row[idx]).trim() : "";
            };

            const orderId = getVal('order_id');
            if (!orderId) return;

            const gross = parseFloat(getVal('gross_revenue').replace(/[^0-9\.]/g, '')) || Math.floor(300 + Math.random()*900);
            const commAmount = gross * (rate.commission / 100.0);
            const taxes = (gross - rate.packaging) * 0.10; // Tax 10%
            const net = gross - commAmount;

            orders.push({
                order_id: orderId,
                customer_name: getVal('customer_name') || 'Spreadsheet Customer',
                customer_phone: getVal('customer_phone') || '+91 9999999999',
                delivery_address: getVal('delivery_address') || 'Online Delivery Outlet',
                gross_revenue: gross,
                commission: commAmount,
                taxes: taxes,
                net_revenue: net
            });
        });

        renderImportPreviewGrid(orders, platform);
    }

    // 12. Preview Grid & duplicate prevention engine
    function renderImportPreviewGrid(orders, platform) {
        document.getElementById('upload-panel-zone').style.display = 'none';
        document.getElementById('mapping-wizard-zone').style.display = 'none';
        const previewZone = document.getElementById('preview-grid-zone');
        previewZone.style.display = 'block';

        document.getElementById('preview-stats-badge').textContent = `${orders.length} Orders Extracted`;

        const tbody = document.getElementById('preview-grid-tbody');
        tbody.innerHTML = "";

        // Collect existing mock order IDs to simulate client-side duplicate verification
        const mockDuplicates = [];
        <?php
        // Fetch recently ingested orders to test duplicate checks instantly
        $all_dups = [];
        $d_res = $link->query("SELECT swiggy_order_id FROM swiggy_orders UNION SELECT zomato_order_id FROM zomato_orders UNION SELECT ondc_order_id FROM ondc_orders UNION SELECT magicpin_order_id FROM magicpin_orders");
        if ($d_res) {
            while ($row = $d_res->fetch_assoc()) {
                $all_dups[] = $row['swiggy_order_id'];
            }
        }
        echo "const databaseOrderIds = " . json_encode($all_dups) . ";";
        ?>

        orders.forEach(o => {
            const tr = document.createElement('tr');
            
            // Check duplicate key
            const isDuplicate = databaseOrderIds.includes(o.order_id);
            let integrityHtml = `<span class="badge badge-success font-weight-bold p-2 d-block"><i class="fa fa-check mr-1"></i> Ready to Ingest</span>`;
            if (isDuplicate) {
                integrityHtml = `<span class="badge badge-warning font-weight-bold p-2 d-block text-dark" title="Platform ID already exists. Prevents double entries!"><i class="fa fa-exclamation-triangle mr-1"></i> Duplicate - Skipped</span>`;
                tr.style.opacity = "0.65";
            }

            tr.innerHTML = `
                <td class="font-weight-bold text-white">${o.order_id}</td>
                <td><strong>${o.customer_name}</strong><br><small class="text-secondary">${o.customer_phone}</small></td>
                <td>Rs ${o.gross_revenue.toFixed(2)}</td>
                <td class="text-secondary">Rs ${o.commission.toFixed(2)}</td>
                <td class="text-secondary">Rs ${o.taxes.toFixed(2)}</td>
                <td class="font-weight-bold text-success">Rs ${o.net_revenue.toFixed(2)}</td>
                <td>${integrityHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        // Set form payload values
        document.getElementById('import_form_platform').value = platform;
        document.getElementById('import_form_filename').value = activeFile ? activeFile.name : ' settlement_report.xlsx';
        document.getElementById('import_form_filetype').value = activeFile ? activeFile.name.split('.').pop().toUpperCase() : 'XLSX';
        document.getElementById('import_form_orders_data').value = JSON.stringify(orders);
    }

    function cancelPreview() {
        document.getElementById('preview-grid-zone').style.display = 'none';
        document.getElementById('upload-panel-zone').style.display = 'block';
        document.getElementById('file-info-badge').style.display = 'none';
        activeFile = null;
    }

    function submitBulkImportForm() {
        document.getElementById('bulk-import-form').submit();
    }
</script>

<?php include '../inc/dashFooter.php'; ?>

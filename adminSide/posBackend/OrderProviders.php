<?php
// adminSide/posBackend/OrderProviders.php
// Extensible omnichannel provider-based architecture for restaurant orders

require_once __DIR__ . '/../config.php';

class OrderDTO {
    public $orderId;
    public $customerName;
    public $customerPhone;
    public $deliveryAddress;
    public $items = []; // Array of ['item_id' => X, 'quantity' => Y]
    public $platform; // 'Swiggy', 'Zomato', 'ONDC', 'Website', 'QR-Table', 'Direct-Store'
    public $priority = 'Normal';
    public $prepTimeMinutes = 20;
    public $deliveryTimeMinutes = 0;
    public $cancellationReason = null;
}

abstract class OrderProvider {
    protected $link;
    protected $platformName;
    protected $commissionRate;
    protected $packagingCharge;

    public function __construct($link) {
        $this->link = $link;
        $this->initializeSettings();
    }

    protected function initializeSettings() {
        $stmt = $this->link->prepare("SELECT commission_rate, packaging_charge FROM delivery_integrations WHERE platform_name = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $this->platformName);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $this->commissionRate = floatval($row['commission_rate']);
                $this->packagingCharge = floatval($row['packaging_charge']);
            }
            $stmt->close();
        }
        if (!isset($this->commissionRate)) $this->commissionRate = 0.00;
        if (!isset($this->packagingCharge)) $this->packagingCharge = 0.00;
    }

    public function isConnected() {
        $stmt = $this->link->prepare("SELECT status FROM delivery_integrations WHERE platform_name = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $this->platformName);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                return $row['status'] === 'Connected';
            }
            $stmt->close();
        }
        return false;
    }

    public function toggleConnection($status) {
        $stmt = $this->link->prepare("UPDATE delivery_integrations SET status = ? WHERE platform_name = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $status, $this->platformName);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
        return false;
    }

    // Process and ingest order into database systems
    public function ingestOrder(OrderDTO $order) {
        if (!$this->isConnected() && !in_array($this->platformName, ['Website', 'QR-Table', 'Direct-Store'])) {
            throw new Exception("Platform {$this->platformName} is disconnected. Please connect account first.");
        }

        $this->link->begin_transaction();
        try {
            // 1. Calculate Financial Breakdown
            $cartTotal = 0;
            foreach ($order->items as $item) {
                $itemId = $item['item_id'];
                $qty = intval($item['quantity']);
                $res = $this->link->query("SELECT item_price FROM Menu WHERE item_id = '" . $this->link->real_escape_string($itemId) . "'");
                if ($row = $res->fetch_assoc()) {
                    $cartTotal += floatval($row['item_price']) * $qty;
                }
            }

            $taxAmount = $cartTotal * 0.10; // 10% tax
            $grossRevenue = $cartTotal + $taxAmount + $this->packagingCharge;
            $commissionAmount = $grossRevenue * ($this->commissionRate / 100.0);
            $netRevenue = $grossRevenue - $commissionAmount;

            // Priority and deadlines
            $priority = $order->priority;
            if ($this->platformName === 'Swiggy' || $this->platformName === 'Zomato') {
                $priority = 'Urgent';
            }
            
            $prepTime = $order->prepTimeMinutes;
            $deliveryTime = ($this->platformName === 'Dine-In' || $this->platformName === 'QR-Table') ? 0 : 25; // Delivery transit simulation
            
            $pickupDeadline = date('Y-m-d H:i:s', strtotime("+" . $prepTime . " minutes"));

            // 2. Insert into Bills Table
            $currentTime = date('Y-m-d H:i:s');
            // Check if there is an active reservation
            $res_id_val = "NULL";
            
            $bill_insert_query = "INSERT INTO Bills (
                bill_time, payment_time, payment_method, order_source, 
                commission_rate, commission_amount, tax_amount, packaging_charge, 
                net_revenue, prep_time_minutes, delivery_time_minutes, 
                priority_level, pickup_deadline, non_member_name, non_member_mobile
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, ?, ?
            )";

            $stmt = $this->link->prepare($bill_insert_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare bills statement: " . $this->link->error);
            }

            $payTime = ($this->platformName === 'Dine-In') ? null : $currentTime; // Online/takeaway orders pre-paid
            $payMethod = ($this->platformName === 'Dine-In') ? null : 'Online';
            
            $stmt->bind_param(
                "ssssdddddiissss",
                $currentTime, $payTime, $payMethod, $this->platformName,
                $this->commissionRate, $commissionAmount, $taxAmount, $this->packagingCharge,
                $netRevenue, $prepTime, $deliveryTime,
                $priority, $pickupDeadline, $order->customerName, $order->customerPhone
            );

            if (!$stmt->execute()) {
                throw new Exception("Error inserting bill: " . $stmt->error);
            }
            $billId = $stmt->insert_id;
            $stmt->close();

            // 3. Insert Bill Items
            foreach ($order->items as $item) {
                $itemId = $item['item_id'];
                $qty = intval($item['quantity']);
                $item_stmt = $this->link->prepare("INSERT INTO Bill_Items (bill_id, item_id, quantity) VALUES (?, ?, ?)");
                if ($item_stmt) {
                    $item_stmt->bind_param("isi", $billId, $itemId, $qty);
                    $item_stmt->execute();
                    $item_stmt->close();
                }
            }

            // 4. Ingest into platform-specific tables
            $this->ingestPlatformSpecific($billId, $order, $currentTime);

            // 5. Ingest into Kitchen Queue
            foreach ($order->items as $item) {
                $itemId = $item['item_id'];
                $qty = intval($item['quantity']);
                // Use table 0 for online delivery orders
                $tableNo = 0;
                $kitchen_stmt = $this->link->prepare("INSERT INTO Kitchen (table_no, item_id, quantity, time_submitted) VALUES (?, ?, ?, ?)");
                if ($kitchen_stmt) {
                    $kitchen_stmt->bind_param("isis", $tableNo, $itemId, $qty, $currentTime);
                    $kitchen_stmt->execute();
                    $kitchen_stmt->close();
                }
            }

            // 6. Ingest Mock Reconciliation Settlement Entry
            $recon_stmt = $this->link->prepare("INSERT INTO settlements_reconciliation (bill_id, platform_name, expected_revenue, status) VALUES (?, ?, ?, 'Unreconciled')");
            if ($recon_stmt) {
                $recon_stmt->bind_param("isd", $billId, $this->platformName, $grossRevenue);
                $recon_stmt->execute();
                $recon_stmt->close();
            }

            $this->link->commit();
            return $billId;
        } catch (Exception $e) {
            $this->link->rollback();
            throw $e;
        }
    }

    abstract protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime);
}

// 1. Swiggy Provider
class SwiggyProvider extends OrderProvider {
    protected $platformName = 'Swiggy';

    protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime) {
        $stmt = $this->link->prepare("INSERT INTO swiggy_orders (swiggy_order_id, bill_id, customer_name, customer_phone, delivery_address, order_status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
        if ($stmt) {
            $stmt->bind_param("sissss", $order->orderId, $billId, $order->customerName, $order->customerPhone, $order->deliveryAddress, $currentTime);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// 2. Zomato Provider
class ZomatoProvider extends OrderProvider {
    protected $platformName = 'Zomato';

    protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime) {
        $stmt = $this->link->prepare("INSERT INTO zomato_orders (zomato_order_id, bill_id, customer_name, customer_phone, delivery_address, order_status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
        if ($stmt) {
            $stmt->bind_param("sissss", $order->orderId, $billId, $order->customerName, $order->customerPhone, $order->deliveryAddress, $currentTime);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// 3. ONDC Provider Foundation
class ONDCProvider extends OrderProvider {
    protected $platformName = 'ONDC';

    protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime) {
        $stmt = $this->link->prepare("INSERT INTO ondc_orders (ondc_order_id, bill_id, customer_name, customer_phone, delivery_address, order_status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
        if ($stmt) {
            $stmt->bind_param("sissss", $order->orderId, $billId, $order->customerName, $order->customerPhone, $order->deliveryAddress, $currentTime);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// 4. Website Provider (Direct Channel)
class WebsiteProvider extends OrderProvider {
    protected $platformName = 'Website';

    protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime) {
        $channel = 'Website';
        $stmt = $this->link->prepare("INSERT INTO direct_orders (bill_id, customer_name, customer_phone, channel_type, order_status, created_at) VALUES (?, ?, ?, ?, 'Completed', ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $billId, $order->customerName, $order->customerPhone, $channel, $currentTime);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// 5. QR Table Provider (Direct Channel)
class QRTableProvider extends OrderProvider {
    protected $platformName = 'QR-Table';

    protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime) {
        $channel = 'QR-Table';
        $stmt = $this->link->prepare("INSERT INTO direct_orders (bill_id, customer_name, customer_phone, channel_type, order_status, created_at) VALUES (?, ?, ?, ?, 'Completed', ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $billId, $order->customerName, $order->customerPhone, $channel, $currentTime);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// 6. Direct Store Provider (Direct Channel)
class DirectStoreProvider extends OrderProvider {
    protected $platformName = 'Direct-Store';

    protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime) {
        $channel = 'Direct-Store';
        $stmt = $this->link->prepare("INSERT INTO direct_orders (bill_id, customer_name, customer_phone, channel_type, order_status, created_at) VALUES (?, ?, ?, ?, 'Completed', ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $billId, $order->customerName, $order->customerPhone, $channel, $currentTime);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// 7. Magicpin Provider (Future Platform Integration)
class MagicpinProvider extends OrderProvider {
    protected $platformName = 'Magicpin';

    protected function ingestPlatformSpecific($billId, OrderDTO $order, $currentTime) {
        $stmt = $this->link->prepare("INSERT INTO magicpin_orders (magicpin_order_id, bill_id, customer_name, customer_phone, delivery_address, order_status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
        if ($stmt) {
            $stmt->bind_param("sissss", $order->orderId, $billId, $order->customerName, $order->customerPhone, $order->deliveryAddress, $currentTime);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>

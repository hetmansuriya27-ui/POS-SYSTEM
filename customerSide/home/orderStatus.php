<?php
session_start();
require_once '../config.php';

// Check if customer session is registered
if (!isset($_SESSION['customer_table_id'])) {
    header("Location: customerOrder.php");
    exit();
}

$table_id = $_SESSION['customer_table_id'];
$customer_name = $_SESSION['customer_name'];
$customer_mobile = $_SESSION['customer_mobile'];

// Re-establish DB connection
$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}

// Fetch all orders for this customer at this table
$sql = "SELECT co.*, m.item_name, m.item_price 
        FROM customer_orders co 
        JOIN Menu m ON co.item_id = m.item_id 
        WHERE co.table_id = ? AND co.customer_name = ? AND co.customer_mobile = ? 
        ORDER BY co.order_time DESC, co.request_group_id DESC";

$stmt = $link->prepare($sql);
$stmt->bind_param("iss", $table_id, $customer_name, $customer_mobile);
$stmt->execute();
$result = $stmt->get_result();

$orders_by_group = [];
while ($row = $result->fetch_assoc()) {
    $group_id = $row['request_group_id'];
    if (!isset($orders_by_group[$group_id])) {
        $orders_by_group[$group_id] = [
            'order_time' => $row['order_time'],
            'status' => $row['status'],
            'items' => []
        ];
    }
    $orders_by_group[$group_id]['items'][] = [
        'item_name' => $row['item_name'],
        'item_price' => $row['item_price'],
        'quantity' => $row['quantity']
    ];
}
?>

<?php include_once('../components/header.php'); ?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
    body {
        font-family: 'Montserrat', sans-serif !important;
        background-color: #121212;
        color: #e0e0e0;
    }
    .status-card {
        background: rgba(31, 30, 30, 0.95);
        border: 1px solid #333;
        border-radius: 12px;
        border-left: 5px solid crimson;
        box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.4);
        transition: transform 0.2s, border-color 0.2s;
    }
    .status-card:hover {
        transform: translateY(-2px);
        border-color: crimson;
        box-shadow: 0px 4px 15px rgba(220, 20, 60, 0.15);
    }
    .status-badge-Pending {
        background-color: #f8de22;
        color: black;
        font-weight: bold;
    }
    .status-badge-Accepted {
        background-color: #17594a;
        color: white;
        font-weight: bold;
    }
    .status-badge-Rejected {
        background-color: #d80032;
        color: white;
        font-weight: bold;
    }
    .cta-btn-sm {
        background-color: transparent;
        color: white;
        border: 2px solid crimson;
        font-size: 1.2rem;
        text-transform: uppercase;
        font-weight: bold;
        letter-spacing: 0.05rem;
        transition: 0.3s ease;
        border-radius: 4px;
        display: inline-block;
        text-decoration: none !important;
    }
    .cta-btn-sm:hover {
        background-color: crimson;
        color: white;
        box-shadow: 0px 0px 8px rgba(220, 20, 60, 0.4);
    }
</style>

<div class="container" style="margin-top: 10rem; margin-bottom: 5rem;">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-3" style="border-color: #333 !important;">
            <div>
                <h2 style="font-family: 'Montserrat', sans-serif; font-size: 2.6rem; font-weight: 700; letter-spacing: 0.1rem; text-transform: uppercase;">
                    Track <span style="color: crimson;">Orders</span>
                </h2>
                <h5 class="text-muted" style="font-size: 1.4rem;">Table ID: <?php echo $table_id; ?> | Customer: <?php echo htmlspecialchars($customer_name); ?></h5>
            </div>
            <div>
                <a href="customerMenu.php" class="btn cta-btn-sm py-2 px-3"><i class="fa fa-plus"></i> Order More Food</a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" style="background-color: #17594a; color: white; border: none; font-size: 1.4rem;">
            <i class="fa fa-check-circle"></i> Your order request has been submitted successfully and is waiting for staff acceptance!
        </div>
    <?php endif; ?>

    <?php if (empty($orders_by_group)): ?>
        <div class="text-center py-5">
            <h4 class="text-muted" style="font-size: 1.6rem;">You haven't placed any orders yet.</h4>
            <a href="customerMenu.php" class="btn cta-btn-sm mt-3 px-4 py-2" style="font-size: 1.3rem;">Order Now</a>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-9">
                <?php foreach ($orders_by_group as $group_id => $group): 
                    $badge_class = "status-badge-" . $group['status'];
                    $total_amount = 0;
                ?>
                    <?php
                    $border_color = 'crimson';
                    if ($group['status'] === 'Pending') {
                        $border_color = '#f8de22';
                    } elseif ($group['status'] === 'Accepted') {
                        $border_color = '#17594a';
                    } elseif ($group['status'] === 'Rejected') {
                        $border_color = '#d80032';
                    }
                    ?>
                    <div class="card status-card p-4 mb-4 shadow-sm" style="border-left: 5px solid <?php echo $border_color; ?> !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="text-white font-weight-bold mb-0" style="font-size: 1.6rem;">Order Ref: #<?php echo $group_id; ?></h5>
                                <small class="text-muted"><i class="fa fa-clock-o"></i> Placed at: <?php echo $group['order_time']; ?></small>
                            </div>
                            <div>
                                <span class="badge <?php echo $badge_class; ?> px-3 py-2" style="font-size: 1.1rem; border-radius: 20px;">
                                    <?php 
                                    if ($group['status'] === 'Rejected') {
                                        echo 'Cancelled';
                                    } else {
                                        echo htmlspecialchars($group['status']);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-items border-top pt-2" style="border-color: #2b2b2b !important;">
                            <table class="table table-borderless table-sm text-muted mb-0" style="font-size: 1.3rem;">
                                <thead>
                                    <tr style="color: #888;">
                                        <th>Item Name</th>
                                        <th class="text-center" style="width: 80px;">Qty</th>
                                        <th class="text-right" style="width: 100px;">Price</th>
                                        <th class="text-right" style="width: 120px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['items'] as $item): 
                                        $subtotal = $item['item_price'] * $item['quantity'];
                                        $total_amount += $subtotal;
                                    ?>
                                        <tr>
                                            <td style="color: #fff; font-weight: bold;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-right">Rs <?php echo number_format($item['item_price'], 2); ?></td>
                                            <td class="text-right text-white">Rs <?php echo number_format($subtotal, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center" style="border-color: #2b2b2b !important;">
                            <span class="text-muted" style="font-size: 1.4rem;">Total Amount:</span>
                            <h4 class="font-weight-bold mb-0" style="color: crimson; font-size: 2rem;">Rs <?php echo number_format($total_amount, 2); ?></h4>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
$stmt->close();
mysqli_close($link);
include_once('../components/footer.php'); 
?>

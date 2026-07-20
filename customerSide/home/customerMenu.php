<?php
session_start();
require_once '../config.php';

// Handle exiting the active table session
if (isset($_GET['exit_session']) && $_GET['exit_session'] === 'true') {
    unset($_SESSION['customer_table_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_mobile']);
    unset($_SESSION['customer_cart']);
    header("Location: customerOrder.php");
    exit();
}

// Check if customer session is registered
if (!isset($_SESSION['customer_table_id'])) {
    header("Location: customerOrder.php");
    exit();
}

$table_id = $_SESSION['customer_table_id'];
$customer_name = $_SESSION['customer_name'];
$customer_mobile = $_SESSION['customer_mobile'];

// Re-establish DB connection since components/header.php will close the connection
$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}

// Handle adding to cart
if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $qty = intval($_POST['quantity']);
    if ($qty > 0) {
        if (!isset($_SESSION['customer_cart'])) {
            $_SESSION['customer_cart'] = [];
        }
        if (isset($_SESSION['customer_cart'][$item_id])) {
            $_SESSION['customer_cart'][$item_id] += $qty;
        } else {
            $_SESSION['customer_cart'][$item_id] = $qty;
        }
    }
}

// Handle updating/removing from cart
if (isset($_POST['update_cart'])) {
    $item_id = $_POST['item_id'];
    $qty = intval($_POST['quantity']);
    if ($qty <= 0) {
        unset($_SESSION['customer_cart'][$item_id]);
    } else {
        $_SESSION['customer_cart'][$item_id] = $qty;
    }
}

if (isset($_POST['remove_item'])) {
    $item_id = $_POST['item_id'];
    unset($_SESSION['customer_cart'][$item_id]);
}

// Handle placing order
$order_error = "";
if (isset($_POST['place_order'])) {
    if (!empty($_SESSION['customer_cart'])) {
        $link->begin_transaction();
        try {
            // Get the next available request group ID
            $grp_query = "SELECT IFNULL(MAX(request_group_id), 0) + 1 AS next_group FROM customer_orders";
            $grp_res = $link->query($grp_query);
            $grp_row = $grp_res->fetch_assoc();
            $request_group_id = $grp_row['next_group'];
            
            $order_time = date('Y-m-d H:i:s');
            
            $stmt = $link->prepare("INSERT INTO customer_orders (request_group_id, table_id, customer_name, customer_mobile, item_id, quantity, order_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            
            foreach ($_SESSION['customer_cart'] as $item_id => $quantity) {
                $stmt->bind_param("iisssis", $request_group_id, $table_id, $customer_name, $customer_mobile, $item_id, $quantity, $order_time);
                if (!$stmt->execute()) {
                    throw new Exception("Error saving order: " . $stmt->error);
                }
            }
            
            $link->commit();
            $_SESSION['customer_cart'] = []; // Clear cart
            
            header("Location: customerMenu.php?success=1");
            exit();
        } catch (Exception $e) {
            $link->rollback();
            $order_error = "Error placing order: " . $e->getMessage();
        }
    } else {
        $order_error = "Your cart is empty!";
    }
}

// Fetch menu items
$sqlmainDishes = "SELECT * FROM Menu WHERE item_category = 'Main Dish' AND status = 'Active' ORDER BY LENGTH(item_id) ASC, item_id ASC;";
$resultmainDishes = mysqli_query($link, $sqlmainDishes);
$mainDishes = mysqli_fetch_all($resultmainDishes, MYSQLI_ASSOC);

$sqldrinks = "SELECT * FROM Menu WHERE item_category = 'Drinks' AND status = 'Active' ORDER BY LENGTH(item_id) ASC, item_id ASC;";
$resultdrinks = mysqli_query($link, $sqldrinks);
$drinks = mysqli_fetch_all($resultdrinks, MYSQLI_ASSOC);

$sqlsides = "SELECT * FROM Menu WHERE item_category = 'Side Snacks' AND status = 'Active' ORDER BY LENGTH(item_id) ASC, item_id ASC;";
$resultsides = mysqli_query($link, $sqlsides);
$sides = mysqli_fetch_all($resultsides, MYSQLI_ASSOC);
?>

<?php include_once('../components/header.php'); ?>
<?php
// Re-open DB connection since components/header.php automatically closes it at its end
$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}

// Fetch placed orders for this customer at this table to display below the cart
$placed_orders_sql = "SELECT co.*, m.item_name, m.item_price 
                      FROM customer_orders co 
                      JOIN Menu m ON co.item_id = m.item_id 
                      WHERE co.table_id = ? AND co.customer_name = ? AND co.customer_mobile = ? 
                      ORDER BY co.order_time DESC, co.request_group_id DESC";
$placed_orders_stmt = $link->prepare($placed_orders_sql);
$placed_orders_stmt->bind_param("iss", $table_id, $customer_name, $customer_mobile);
$placed_orders_stmt->execute();
$placed_orders_res = $placed_orders_stmt->get_result();

$placed_orders_by_group = [];
if ($placed_orders_res) {
    while ($row = $placed_orders_res->fetch_assoc()) {
        $group_id = $row['request_group_id'];
        if (!isset($placed_orders_by_group[$group_id])) {
            $placed_orders_by_group[$group_id] = [
                'order_time' => $row['order_time'],
                'status' => $row['status'],
                'items' => []
            ];
        }
        $placed_orders_by_group[$group_id]['items'][] = [
            'item_name' => $row['item_name'],
            'item_price' => $row['item_price'],
            'quantity' => $row['quantity']
        ];
    }
}
$placed_orders_stmt->close();
?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
    body {
        font-family: 'Montserrat', sans-serif !important;
        background-color: #121212;
        color: #e0e0e0;
    }
    .menu-card {
        background: rgba(31, 30, 30, 0.95);
        border: 1px solid #333;
        border-radius: 10px;
        transition: transform 0.2s, border-color 0.2s;
    }
    .menu-card:hover {
        transform: translateY(-2px);
        border-color: crimson;
        box-shadow: 0px 4px 10px rgba(220, 20, 60, 0.15);
    }
    .cart-card {
        background: rgba(31, 30, 30, 0.95);
        border: 2px solid crimson;
        border-radius: 12px;
        position: sticky;
        top: 8rem;
        box-shadow: 0px 0px 15px rgba(220, 20, 60, 0.1);
    }
    .placed-orders-card {
        background: rgba(31, 30, 30, 0.95);
        border: 2px solid crimson;
        border-radius: 12px;
        box-shadow: 0px 0px 15px rgba(220, 20, 60, 0.1);
    }
    .nav-tabs {
        border-bottom: 2px solid #333;
    }
    .nav-tabs .nav-link {
        color: #aaa;
        border: none;
        font-size: 1.4rem;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.05rem;
    }
    .nav-tabs .nav-link.active {
        background-color: transparent;
        color: crimson;
        border-bottom: 3px solid crimson;
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
    }
    .cta-btn-sm:hover {
        background-color: crimson;
        color: white;
        box-shadow: 0px 0px 8px rgba(220, 20, 60, 0.4);
    }
    .cta-btn-block {
        background-color: transparent;
        color: white;
        border: 2px solid crimson;
        font-size: 1.6rem;
        text-transform: uppercase;
        font-weight: bold;
        letter-spacing: 0.1rem;
        transition: 0.3s ease;
        border-radius: 6px;
        width: 100%;
        display: block;
    }
    .cta-btn-block:hover {
        background-color: crimson;
        color: white;
        box-shadow: 0px 0px 12px rgba(220, 20, 60, 0.5);
    }
    /* Complete Form Control Dark Theme Overrides to prevent White Boxes and Autofill Blocks */
    .form-control, 
    .form-control:focus,
    input, 
    input:focus,
    select,
    select:focus {
        background-color: #121212 !important;
        color: #ffffff !important;
        border: 1px solid #444 !important;
    }
    .form-control:focus, 
    input:focus, 
    select:focus {
        border-color: crimson !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 20, 60, 0.25) !important;
    }
    /* Webkit browser autofill overrides to prevent white background */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px #121212 inset !important;
        -webkit-text-fill-color: #ffffff !important;
        transition: background-color 5000s ease-in-out 0s;
    }
</style>

<div class="container-fluid" style="margin-top: 10rem; margin-bottom: 5rem; padding-left: 5%; padding-right: 5%;">
    <div class="row mb-4">
        <div class="col-12 border-bottom pb-3 d-flex justify-content-between align-items-center" style="border-color: #333 !important;">
            <div>
                <h2 style="font-size: 2.4rem;">Welcome, <span style="color: crimson; font-weight: bold;"><?php echo htmlspecialchars($customer_name); ?></span></h2>
                <h5 class="text-muted" style="font-size: 1.4rem;">Table ID: <?php echo $table_id; ?> | Mobile: <?php echo htmlspecialchars($customer_mobile); ?></h5>
            </div>
            <div>
                <a href="customerMenu.php?exit_session=true" class="btn cta-btn-sm py-2 px-3" onclick="return confirm('Are you sure you want to exit your table session?')"><i class="fa fa-sign-out"></i> Exit Table</a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" style="background-color: #17594a; color: white; border: none; font-size: 1.4rem; margin-bottom: 2rem;">
            <i class="fa fa-check-circle"></i> Your order request has been submitted successfully and is waiting for staff acceptance!
        </div>
    <?php endif; ?>

    <?php if (!empty($order_error)): ?>
        <div class="alert alert-danger" style="background-color: rgba(220, 20, 60, 0.2); color: #f8d7da; border: 1px solid crimson; font-size: 1.4rem;"><?php echo $order_error; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Side: Menu Items -->
        <div class="col-lg-8">
            <ul class="nav nav-tabs mb-4" id="menuTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="drinks-tab" data-toggle="tab" href="#drinks" role="tab">Drinks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="sides-tab" data-toggle="tab" href="#sides" role="tab">Side Snacks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="main-tab" data-toggle="tab" href="#mainDishes" role="tab">Main Dish</a>
                </li>
            </ul>

            <div class="tab-content" id="menuTabsContent">
                <!-- Drinks Tab -->
                <div class="tab-pane fade show active" id="drinks" role="tabpanel">
                    <div class="row">
                        <?php foreach ($drinks as $item): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card menu-card h-100 p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title text-white font-weight-bold" style="font-size: 1.6rem;"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                        <span class="badge badge-dark p-2" style="font-size: 1.2rem; color: crimson;">Rs <?php echo number_format($item['item_price'], 2); ?></span>
                                    </div>
                                    <p class="card-text text-muted my-2" style="font-size: 1.3rem;"><?php echo htmlspecialchars($item['item_description']); ?></p>
                                    <div class="mt-auto pt-3 border-top" style="border-color: #333 !important;">
                                        <form action="" method="post" class="d-flex align-items-center justify-content-between">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <div class="d-flex align-items-center">
                                                <label class="mr-2 mb-0" style="font-size: 1.2rem; color: #aaa;">Qty:</label>
                                                <input type="number" name="quantity" value="1" min="1" max="20" class="form-control text-center" style="width: 60px; background: #121212; border: 1px solid #444; color: white; font-size: 1.3rem; height: 35px;">
                                            </div>
                                            <button type="submit" name="add_to_cart" class="btn cta-btn-sm py-1 px-3"><i class="fa fa-shopping-cart"></i> Add</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Side Snacks Tab -->
                <div class="tab-pane fade" id="sides" role="tabpanel">
                    <div class="row">
                        <?php foreach ($sides as $item): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card menu-card h-100 p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title text-white font-weight-bold" style="font-size: 1.6rem;"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                        <span class="badge badge-dark p-2" style="font-size: 1.2rem; color: crimson;">Rs <?php echo number_format($item['item_price'], 2); ?></span>
                                    </div>
                                    <p class="card-text text-muted my-2" style="font-size: 1.3rem;"><?php echo htmlspecialchars($item['item_description']); ?></p>
                                    <div class="mt-auto pt-3 border-top" style="border-color: #333 !important;">
                                        <form action="" method="post" class="d-flex align-items-center justify-content-between">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <div class="d-flex align-items-center">
                                                <label class="mr-2 mb-0" style="font-size: 1.2rem; color: #aaa;">Qty:</label>
                                                <input type="number" name="quantity" value="1" min="1" max="20" class="form-control text-center" style="width: 60px; background: #121212; border: 1px solid #444; color: white; font-size: 1.3rem; height: 35px;">
                                            </div>
                                            <button type="submit" name="add_to_cart" class="btn cta-btn-sm py-1 px-3"><i class="fa fa-shopping-cart"></i> Add</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Main Dish Tab -->
                <div class="tab-pane fade" id="mainDishes" role="tabpanel">
                    <div class="row">
                        <?php foreach ($mainDishes as $item): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card menu-card h-100 p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title text-white font-weight-bold" style="font-size: 1.6rem;"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                        <span class="badge badge-dark p-2" style="font-size: 1.2rem; color: crimson;">Rs <?php echo number_format($item['item_price'], 2); ?></span>
                                    </div>
                                    <p class="card-text text-muted my-2" style="font-size: 1.3rem;"><?php echo htmlspecialchars($item['item_description']); ?></p>
                                    <div class="mt-auto pt-3 border-top" style="border-color: #333 !important;">
                                        <form action="" method="post" class="d-flex align-items-center justify-content-between">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <div class="d-flex align-items-center">
                                                <label class="mr-2 mb-0" style="font-size: 1.2rem; color: #aaa;">Qty:</label>
                                                <input type="number" name="quantity" value="1" min="1" max="20" class="form-control text-center" style="width: 60px; background: #121212; border: 1px solid #444; color: white; font-size: 1.3rem; height: 35px;">
                                            </div>
                                            <button type="submit" name="add_to_cart" class="btn cta-btn-sm py-1 px-3"><i class="fa fa-shopping-cart"></i> Add</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Active Cart -->
        <div class="col-lg-4">
            <div class="card cart-card p-4">
                <h4 class="card-title text-white border-bottom pb-2" style="border-color: #333 !important; font-weight: bold; font-size: 1.8rem;"><i class="fa fa-shopping-bag" style="color: crimson;"></i> Table Cart</h4>
                
                <?php if (empty($_SESSION['customer_cart'])): ?>
                    <p class="text-muted my-4 text-center" style="font-size: 1.3rem;">Your cart is empty. Select delicious items from the menu to get started!</p>
                <?php else: ?>
                    <div class="cart-items-list my-3" style="max-height: 300px; overflow-y: auto;">
                        <?php 
                        $total_price = 0;
                        foreach ($_SESSION['customer_cart'] as $item_id => $qty): 
                            // Fetch item details from DB
                            $item_details_sql = "SELECT item_name, item_price FROM Menu WHERE item_id = '$item_id'";
                            $item_details_res = mysqli_query($link, $item_details_sql);
                            $item_details = mysqli_fetch_assoc($item_details_res);
                            $subtotal = $item_details['item_price'] * $qty;
                            $total_price += $subtotal;
                        ?>
                            <div class="cart-item-row mb-3 d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: #222 !important;">
                                <div style="max-width: 60%;">
                                    <h6 class="text-white mb-0 font-weight-bold" style="font-size: 1.3rem;"><?php echo htmlspecialchars($item_details['item_name']); ?></h6>
                                    <small class="text-muted" style="font-size: 1.1rem;">Rs <?php echo number_format($item_details['item_price'], 2); ?> each</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <form action="" method="post" class="mr-2 d-flex align-items-center">
                                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                        <input type="number" name="quantity" value="<?php echo $qty; ?>" min="0" max="20" class="form-control text-center text-white" style="width: 50px; background: #121212; border: 1px solid #444; height: 30px; font-size: 1.2rem;" onchange="this.form.submit()">
                                        <input type="hidden" name="update_cart" value="1">
                                    </form>
                                    <form action="" method="post">
                                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-link p-0 text-danger" style="font-size: 1.3rem;"><i class="fa fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-top pt-3" style="border-color: #333 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-white mb-0" style="font-size: 1.5rem;">Total Price:</h5>
                            <h4 style="color: crimson; font-weight: bold; font-size: 2rem;">Rs <?php echo number_format($total_price, 2); ?></h4>
                        </div>
                        <form action="" method="post">
                            <button type="submit" name="place_order" class="btn cta-btn-block py-2"><i class="fa fa-check-circle"></i> Place Order</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Placed Orders card -->
            <div class="card placed-orders-card p-4 mt-4" style="max-height: 400px; overflow-y: auto;">
                <h4 class="card-title text-white border-bottom pb-2" style="border-color: #333 !important; font-weight: bold; font-size: 1.8rem;"><i class="fa fa-list-alt" style="color: crimson;"></i> Placed Orders</h4>
                
                <?php if (empty($placed_orders_by_group)): ?>
                    <p class="text-muted my-3 text-center" style="font-size: 1.3rem;">No orders placed yet. Add items to cart and place an order!</p>
                <?php else: ?>
                    <div class="placed-orders-list">
                        <?php foreach ($placed_orders_by_group as $group_id => $group): 
                            $status = $group['status'];
                            $badge_color = '#dc143c'; // default crimson
                            $display_status = htmlspecialchars($status);
                            
                            if ($status === 'Pending') {
                                $badge_color = '#f8de22'; // yellow
                            } elseif ($status === 'Accepted') {
                                $badge_color = '#17594a'; // green
                            } elseif ($status === 'Rejected') {
                                $badge_color = '#d80032'; // red
                                $display_status = 'Cancelled';
                            }
                            
                            $order_total = 0;
                        ?>
                            <div class="placed-order-item mb-3 pb-2 border-bottom" style="border-color: #222 !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-weight: bold; font-size: 1.3rem; color: white;">Ref: #<?php echo $group_id; ?></span>
                                    <span class="badge" style="background-color: <?php echo $badge_color; ?>; color: <?php echo ($status === 'Pending' ? 'black' : 'white'); ?>; font-size: 1.1rem; padding: 3px 8px; border-radius: 10px;">
                                        <?php echo $display_status; ?>
                                    </span>
                                </div>
                                <div style="font-size: 1.2rem; color: #888;" class="mb-2">
                                    <i class="fa fa-clock-o"></i> <?php echo date('H:i', strtotime($group['order_time'])); ?>
                                </div>
                                
                                <div class="placed-items-sublist pl-2 border-left" style="border-left: 2px solid <?php echo $badge_color; ?> !important;">
                                    <?php foreach ($group['items'] as $item): 
                                        $subtotal = $item['item_price'] * $item['quantity'];
                                        $order_total += $subtotal;
                                    ?>
                                        <div class="d-flex justify-content-between text-muted" style="font-size: 1.2rem; line-height: 1.6rem;">
                                            <span><?php echo htmlspecialchars($item['item_name']); ?> x<?php echo $item['quantity']; ?></span>
                                            <span>Rs <?php echo number_format($subtotal, 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex justify-content-between mt-1 text-white font-weight-bold" style="font-size: 1.2rem;">
                                    <span>Total:</span>
                                    <span style="color: <?php echo ($status === 'Rejected' ? '#d80032' : 'crimson'); ?>;">Rs <?php echo number_format($order_total, 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_close($link);
include_once('../components/footer.php'); 
?>

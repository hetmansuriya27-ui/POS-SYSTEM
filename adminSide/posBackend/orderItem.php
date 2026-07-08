<?php
session_start();
require_once 'checkIfLoggedIn.php'; // Ensure session is started
?>
<?php
require_once '../config.php';
include '../inc/dashHeader.php'; 

$bill_id = isset($_GET['bill_id']) ? $_GET['bill_id'] : '';
$table_id = isset($_GET['table_id']) ? $_GET['table_id'] : '';

function createNewBillRecord($table_id) {
    global $link; // Assuming $link is your database connection
    
    // Check if there are active self-service orders for this table to automatically sync customer name & mobile
    $name = NULL;
    $mobile = NULL;
    $cust_sql = "SELECT customer_name, customer_mobile FROM customer_orders WHERE table_id = '$table_id' ORDER BY order_time DESC LIMIT 1";
    $cust_res = mysqli_query($link, $cust_sql);
    if ($cust_res && mysqli_num_rows($cust_res) > 0) {
        $cust_row = mysqli_fetch_assoc($cust_res);
        $name = mysqli_real_escape_string($link, $cust_row['customer_name']);
        $mobile = mysqli_real_escape_string($link, $cust_row['customer_mobile']);
    }
    
    $bill_time = date('Y-m-d H:i:s');
    
    if ($name !== NULL && $mobile !== NULL) {
        $insert_query = "INSERT INTO Bills (table_id, bill_time, non_member_name, non_member_mobile) VALUES ('$table_id', '$bill_time', '$name', '$mobile')";
    } else {
        $insert_query = "INSERT INTO Bills (table_id, bill_time) VALUES ('$table_id', '$bill_time')";
    }
    
    if ($link->query($insert_query) === TRUE) {
        return $link->insert_id; // Return the newly inserted bill_id
    } else {
        return false;
    }
}

// Automatically detect missing, null, or empty bill ID and redirect with a valid active bill
if (empty($bill_id) || $bill_id === 'null' || $bill_id === 'NULL' || $bill_id == 0) {
    // Check if there is already an active (unpaid) bill for this table
    $check_bill_query = "SELECT bill_id FROM Bills WHERE table_id = '$table_id' AND payment_time IS NULL ORDER BY bill_time DESC LIMIT 1";
    $check_result = mysqli_query($link, $check_bill_query);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $check_row = mysqli_fetch_assoc($check_result);
        $bill_id = $check_row['bill_id'];
        header("Location: orderItem.php?bill_id=" . urlencode($bill_id) . "&table_id=" . urlencode($table_id));
        exit();
    } else {
        // No active bill exists, create a new one automatically
        $new_bill_id = createNewBillRecord($table_id);
        if ($new_bill_id) {
            header("Location: orderItem.php?bill_id=" . urlencode($new_bill_id) . "&table_id=" . urlencode($table_id));
            exit();
        }
    }
}

$unpaid_bills_query = "SELECT bill_id, bill_time FROM Bills WHERE table_id = '$table_id' AND (payment_time IS NULL OR payment_time = '') ORDER BY bill_id DESC";
$unpaid_bills_result = mysqli_query($link, $unpaid_bills_query);
?>
<!DOCTYPE html>
<html>
<head>
    <link href="../css/pos.css" rel="stylesheet" />
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 order-md-1 m-1" id="item-select-section ">
                <div class="container-fluid pt-4 pl-500 row" style=" margin-left: 10rem;width: 81% ;">
                    <div class="mt-5 mb-2 d-flex justify-content-between align-items-center" style="width: 100%;">
                        <h3 class="m-0">Food & Drinks</h3>
                        <a href="posTable.php" class="btn btn-outline-dark"><i class="fas fa-arrow-left"></i> Back to Tables</a>
                    </div>
                    <div class="mb-3">
                        <form method="POST" action="#">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" required="" id="search" name="search" class="form-control" placeholder="Search Food & Drinks">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-dark">Search</button>
                                </div>
                                <div class="col" style="text-align: right;" >
                                    <a href="orderItem.php?bill_id=<?php echo $bill_id; ?>&table_id=<?php echo $table_id; ?>" class="btn btn-light">Show All</a>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div style="max-height: 45rem;overflow-y: auto;">
                        <?php
                        // Include config file
                        
                        require_once "../config.php";
                        if (isset($_POST['search'])) {
                            if (!empty($_POST['search'])) {
                                $search = $_POST['search'];

                                $query = "SELECT * FROM Menu WHERE status = 'Active' AND (item_category LIKE '%$search%' OR item_name LIKE '%$search%' OR item_id LIKE '%$search%') ORDER BY CASE WHEN item_category = 'Drinks' THEN 1 WHEN item_category = 'Side Snacks' THEN 2 WHEN item_category = 'Main Dish' THEN 3 ELSE 4 END ASC, LENGTH(item_id) ASC, item_id ASC;";
                                $result = mysqli_query($link, $query);
                            }else{
                                // Default query to fetch all active menu items
                                $query = "SELECT * FROM Menu WHERE status = 'Active' ORDER BY CASE WHEN item_category = 'Drinks' THEN 1 WHEN item_category = 'Side Snacks' THEN 2 WHEN item_category = 'Main Dish' THEN 3 ELSE 4 END ASC, LENGTH(item_id) ASC, item_id ASC;";
                                $result = mysqli_query($link, $query);
                            }
                        } else {
                            // Default query to fetch all active menu items
                            $query = "SELECT * FROM Menu WHERE status = 'Active' ORDER BY CASE WHEN item_category = 'Drinks' THEN 1 WHEN item_category = 'Side Snacks' THEN 2 WHEN item_category = 'Main Dish' THEN 3 ELSE 4 END ASC, LENGTH(item_id) ASC, item_id ASC;";
                            $result = mysqli_query($link, $query);
                        }
                        $bill_id = $_GET['bill_id'];
                        if ($result) {
                            if (mysqli_num_rows($result) > 0) {
                                echo '<table class="table table-bordered table-striped">';
                                echo "<thead>";
                                echo "<tr>";
                                echo "<th>ID</th>";
                                echo "<th>Item Name</th>";
                                echo "<th>Category</th>";
                                echo "<th>Price</th>";
                                echo "<th>Add</th>";
                                echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                // ...

                                while ($row = mysqli_fetch_array($result)) {
                                    echo "<tr>";
                                    echo "<td>" . $row['item_id'] . "</td>";
                                    echo "<td>" . $row['item_name'] . "</td>";
                                    echo "<td>" . $row['item_category'] . "</td>";
                                    echo "<td>" . number_format($row['item_price'],2) . "</td>";

                                    // Check if the bill has been paid
                                    $payment_time_query = "SELECT payment_time FROM Bills WHERE bill_id = '$bill_id'";
                                    $payment_time_result = mysqli_query($link, $payment_time_query);
                                    $has_payment_time = false;

                                    if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
                                        $payment_time_row = mysqli_fetch_assoc($payment_time_result);
                                        if (!empty($payment_time_row['payment_time'])) {
                                            $has_payment_time = true;
                                        }
                                    }

                                    // Display the "Add to Cart" button if the bill hasn't been paid
                                    if (!$has_payment_time) {
                                        echo '<td><form method="get" action="addItem.php">'
                                            . '<input type="text" hidden name= "table_id" value="' . $table_id . '">'
                                            . '<input type="text" name= "item_id" value=' . $row['item_id'] . ' hidden>'
                                            . '<input type="number" name= "bill_id" value=' . $bill_id . ' hidden>'
                                            . '<input type="number" name="quantity" style="width:120px" placeholder="1 to 1000" required min="1" max="1000" value="1">'
                                            . '<input type="hidden" name="addToCart" value="1">'
                                            . '<button type="submit" class="btn btn-primary">Add to Cart</button>';
                                        echo "</form></td>";
                                    } else {
                                        echo '<td>Bill Paid</td>';
                                    }

                                    echo "</tr>";
                                }

                                // ...

                                echo "</tbody>";
                                echo "</table>";
                            } else {
                                echo '<div class="alert alert-danger"><em>No menu items were found.</em></div>';
                            }
                        } else {
                            echo "Oops! Something went wrong. Please try again later.";
                        }
                        // Close connection
                        
                        ?>
                     </div>

                </div>
            </div>
            <div class="col-md-4 order-md-2 m-1" id="cart-section" >
                <div class="container-fluid pt-5 pl-600 pr-6 row mt-3" style="max-width: 200%; width:150%;">
                    <div class="cart-section" >
                        <h3 class="d-flex justify-content-between align-items-center mb-3">
                            <span>Cart</span>
                            <a href="newCustomer.php?new_customer=true&table_id=<?php echo $table_id; ?>" class="btn btn-sm btn-warning font-weight-bold" onclick="return confirm('Start new customer session for this table? The current unpaid bill will be saved.')"><i class="fa fa-plus"></i> Next Guest</a>
                        </h3>

                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Item ID</th>
                                <th>Item Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            
                            <div style="max-height: 40rem;overflow-y: auto;">
                                <tbody>
                                <?php
                                // Query to fetch cart items for the given bill_id
                                $cart_query = "SELECT bi.*, m.item_name, m.item_price FROM bill_items bi
                                               JOIN Menu m ON bi.item_id = m.item_id
                                               WHERE bi.bill_id = '$bill_id'";
                                $cart_result = mysqli_query($link, $cart_query);
                                $cart_total = 0;//cart total
                                $tax = 0.1; // 10% tax rate

                                if ($cart_result && mysqli_num_rows($cart_result) > 0) {
                                    while ($cart_row = mysqli_fetch_assoc($cart_result)) {
                                        $item_id = $cart_row['item_id'];
                                        $item_name = $cart_row['item_name'];
                                        $item_price = $cart_row['item_price'];
                                        $quantity = $cart_row['quantity'];
                                        $total = $item_price * $quantity;
                                        $bill_item_id = $cart_row['bill_item_id'];
                                        $cart_total += $total;
                                        echo '<tr>';
                                        echo '<td>' . $item_id . '</td>';
                                        echo '<td>' . $item_name . '</td>';
                                        echo '<td>Rs ' . number_format($item_price,2) . '</td>';
                                        echo '<td>' . $quantity . '</td>';
                                        echo '<td>Rs ' . number_format($total,2) . '</td>';
                                        // Check if the bill has been paid
                                        $payment_time_query = "SELECT payment_time FROM Bills WHERE bill_id = '$bill_id'";
                                        $payment_time_result = mysqli_query($link, $payment_time_query);
                                        $has_payment_time = false;

                                        if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
                                            $payment_time_row = mysqli_fetch_assoc($payment_time_result);
                                            if (!empty($payment_time_row['payment_time'])) {
                                                $has_payment_time = true;
                                            }
                                        }

                                        // Display the "Delete" button if the bill hasn't been paid
                                        if (!$has_payment_time) {
                                            echo '<td><a class="btn btn-dark" href="deleteItem.php?bill_id=' . $bill_id . '&table_id=' . $table_id . '&bill_item_id=' . $bill_item_id . '&item_id=' . $item_id .'">Delete</a></td>';
                                        } else {
                                            echo '<td>Bill Paid</td>';
                                        }
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6">No Items in Cart.</td></tr>';
                                }
                                ?>
                                </tbody>
                            </div>
                        </table>
                        <hr>
                        <div class="table-responsive">
    <table class="table table-bordered ">
        <tbody>
            <tr>
                <td><strong>Cart Total</strong></td>
                <td>Rs <?php echo number_format($cart_total, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Cart Taxed</strong></td>
                <td>Rs <?php echo number_format($cart_total * $tax, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Grand Total</strong></td>
                <td>Rs <?php echo number_format(($tax * $cart_total) + $cart_total, 2); ?></td>
            </tr>
        </tbody>
    </table>
</div>

                        <?php 
                        
                        //echo "Cart Total: Rs " . $cart_total;
                        //echo "<br>Cart Taxed: Rs " . $cart_total * $tax;
                        //echo "<br>Grand Total: Rs " . $tax * $cart_total + $cart_total;
                      
                        // Check if the payment time record exists for the bill
                        $payment_time_query = "SELECT payment_time FROM Bills WHERE bill_id = '$bill_id'";
                        $payment_time_result = mysqli_query($link, $payment_time_query);
                        $has_payment_time = false;

                        if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
                            $payment_time_row = mysqli_fetch_assoc($payment_time_result);
                            if (!empty($payment_time_row['payment_time'])) {
                                $has_payment_time = true;
                            }
                        }

                        // If payment time record exists, show the "Print Receipt" button
                        if ($has_payment_time) {
                            echo '<div>';
                            echo '<div class="alert alert-success" role="alert">
                                    Bill has already been paid.
                                  </div>';
                            echo '<br><a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a></div>';
                            

                            
                        } elseif(($tax * $cart_total + $cart_total) > 0) {
                            echo '<br><a href="idValidity.php?bill_id=' . $bill_id . '" class="btn btn-success">Pay Bill</a>';
                        } else {
                            echo '<br><h3>Add Item To Cart to Proceed</h3>';
                        }

                        
                        
                        ?>
                    </div>
                    <?php 
                        echo '<div class="d-flex justify-content-end mt-3" style="width: 100%;">';
                        echo '<a href="posTable.php" class="btn btn-dark"><i class="fas fa-arrow-left"></i> Back to Tables</a>';
                        echo '</div>';
                    ?>
                </div>

            </div>
        </div>
    </div>
<?php include '../inc/dashFooter.php'; ?>

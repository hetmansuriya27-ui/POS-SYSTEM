<?php
session_start();
require_once 'checkIfLoggedIn.php'; // Ensure session is started
?>
<?php
require_once '../config.php';
include '../inc/dashHeader.php'; 
$bill_id = $_GET['bill_id'];
$staff_id = $_GET['staff_id'];
$order_source = !empty($_GET['order_source']) ? $_GET['order_source'] : 'Dine-In';
$reservation_id = $_GET['reservation_id'];
$non_member_name = $_GET['non_member_name'] ?? '';
$non_member_mobile = $_GET['non_member_mobile'] ?? '';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Bill (Cash Payment)</h3>
                </div>
                <div class="card-body">
                    <h5>Bill ID: <?php echo $bill_id; ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item ID</th>
                                    <th>Item Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
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
                    echo '<td>Rs ' . $item_price . '</td>';
                    echo '<td>' . $quantity . '</td>';
                    echo '<td>Rs ' . number_format($total,2) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6">No Items in Cart.</td></tr>';
            }
            ?>
        </tbody>
                        </table>
                    </div>
                    <hr>
                    <div class="text-right">
                        <?php 
                        echo "<strong>Total:</strong> Rs " . number_format($cart_total, 2) . "<br>";
                        echo "<strong>Tax (10%):</strong> Rs " . number_format($cart_total * $tax, 2) . "<br>";
                        $GRANDTOTAL = $tax * $cart_total + $cart_total;
                        echo "<strong>Grand Total:</strong> Rs " . number_format($GRANDTOTAL, 2);
                        ?>
                    </div>

                </div>
            </div>
            
            

<div id="cash-payment" class="container-fluid mt-5 pt-5 pl-5 pr-5 mb-5">
    <div class="row">
        <div class="col-md-6">
            <h1>Cash Payment</h1>
            <form action="" method="get">
                <div class="form-group">
                    <label for="payment_amount">Payment Amount</label>
                    <input type="number" min="0" id="payment_amount" name="payment_amount" class="form-control" required>
                </div>

                <!-- Add hidden input fields for bill_id, staff_id, member_id, and reservation_id -->
                <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
                <input type="hidden" name="order_source" value="<?php echo htmlspecialchars($order_source); ?>">
                <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
                <input type="hidden" name="GRANDTOTAL" value="<?php echo $tax * $cart_total + $cart_total; ?>">
                <input type="hidden" name="non_member_name" value="<?php echo htmlspecialchars($non_member_name); ?>">
                <input type="hidden" name="non_member_mobile" value="<?php echo htmlspecialchars($non_member_mobile); ?>">

                <button type="submit" id="cardSubmit" class="btn btn-dark mt-2">Pay</button>
            </form>
        </div>
        <div class="col-md-6">
        <?php
        function calculateChange(float $paymentAmount, float $GrandTotal) {
            return $paymentAmount - $GrandTotal;
        }
        
        

        if (isset($_GET['payment_amount'])) {
            $payment_amount = isset($_GET['payment_amount']) ? floatval($_GET['payment_amount']) : 0.0;

            $billCheckQuery = "SELECT payment_time FROM Bills WHERE bill_id = $bill_id";
            $billCheckResult = $link->query($billCheckQuery);

            if ($billCheckResult) {
                if ($billCheckResult->num_rows > 0) {
                    $billRow = $billCheckResult->fetch_assoc();
                    if ($billRow['payment_time'] !== null) {
                        echo '<div class="alert alert-warning" role="alert">';
                        echo "Bill with ID $bill_id has already been paid.</div>";
                        echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
                        echo '<br><a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>';
                        exit; // Stop further execution
                    }
                }
            } else {
                echo "Error checking bill: " . $link->error;
                exit; // Stop further execution
            }

            if ($payment_amount >= $GRANDTOTAL) {
                echo '<div class="alert alert-dark" role="alert">';
                echo "Change is Rs" . number_format(calculateChange($payment_amount, $GRANDTOTAL),2);
                echo '</div>';

                // Update the payment method, bill time, and other details in the Bills table
                $currentTime = date('Y-m-d H:i:s');
                
                $reservation_id_val = (!empty($reservation_id)) ? intval($reservation_id) : "NULL";
                $non_member_name_val = (!empty($non_member_name)) ? "'" . mysqli_real_escape_string($link, $non_member_name) . "'" : "NULL";
                $non_member_mobile_val = (!empty($non_member_mobile)) ? "'" . mysqli_real_escape_string($link, $non_member_mobile) . "'" : "NULL";
                $order_source_val = "'" . mysqli_real_escape_string($link, $order_source) . "'";

                $updateQuery = "UPDATE Bills SET payment_method = 'cash', payment_time = '$currentTime',
                                staff_id = $staff_id, reservation_id = $reservation_id_val,
                                non_member_name = $non_member_name_val, non_member_mobile = $non_member_mobile_val,
                                order_source = $order_source_val, net_revenue = $GRANDTOTAL
                                WHERE bill_id = $bill_id;";
                
                if ($link->query($updateQuery) === TRUE) {
                    // Clean up old self-service orders for this table upon successful bill payment
                    $table_id_query = "SELECT table_id FROM Bills WHERE bill_id = $bill_id";
                    $table_id_res = $link->query($table_id_query);
                    if ($table_id_res && $table_row = $table_id_res->fetch_assoc()) {
                        $t_id = $table_row['table_id'];
                        $delete_orders_sql = "DELETE FROM customer_orders WHERE table_id = $t_id";
                        $link->query($delete_orders_sql);
                    }

                    echo '<div class="alert alert-success" role="alert">
                            Bill successfully Paid!
                          </div>';
                    echo '<a href="posTable.php" class="btn btn-dark ">Back to Tables</a>';
                    echo '<a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>';
                } else {
                    echo "Error updating bill: " . $link->error;
                }
            } else {
                echo '<div class="alert alert-warning" role="alert">
                        Payment amount is not sufficient
                      </div>';
                echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
            }
        }
        ?>

    </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>

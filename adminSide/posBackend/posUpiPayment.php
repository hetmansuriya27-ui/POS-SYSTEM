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

<style>
    .wrapper {
        width: 80%;
        padding-left: 220px;
        padding-top: 50px;
    }
</style>

<div class="wrapper">
    <div class="container-fluid pt-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card shadow-lg border-0" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-header bg-dark text-white py-3">
                        <h3 class="card-title mb-0 font-weight-bold text-center"><i class="fa fa-mobile text-danger"></i> UPI Payment Settlement</h3>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php
                        if (isset($_GET['confirm_upi'])) {
                            $billCheckQuery = "SELECT payment_time FROM Bills WHERE bill_id = $bill_id";
                            $billCheckResult = $link->query($billCheckQuery);
                            $billRow = $billCheckResult ? $billCheckResult->fetch_assoc() : null;

                            if ($billRow && $billRow['payment_time'] !== null) {
                                echo '<div class="alert alert-warning text-center font-weight-bold" role="alert"><i class="fa fa-exclamation-triangle"></i> Bill with ID ' . $bill_id . ' has already been paid.</div>';
                                echo '<div class="text-center mt-4">';
                                echo '<a href="posTable.php" class="btn btn-dark btn-lg w-50 mb-2 font-weight-bold">Back to Tables</a><br>';
                                echo '<a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-outline-dark btn-lg w-50 font-weight-bold">Print Receipt <i class="fa fa-receipt"></i></a>';
                                echo '</div>';
                            } else {
                                // Calculate total
                                $cart_query = "SELECT bi.*, m.item_price FROM bill_items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = '$bill_id'";
                                $cart_result = mysqli_query($link, $cart_query);
                                $cart_total = 0;
                                if ($cart_result) {
                                    while ($cart_row = mysqli_fetch_assoc($cart_result)) {
                                        $cart_total += $cart_row['item_price'] * $cart_row['quantity'];
                                    }
                                }
                                $tax = 0.1;
                                $GRANDTOTAL = $tax * $cart_total + $cart_total;

                                $currentTime = date('Y-m-d H:i:s');
                                $reservation_id_val = (!empty($reservation_id)) ? intval($reservation_id) : "NULL";
                                $non_member_name_val = (!empty($non_member_name)) ? "'" . mysqli_real_escape_string($link, $non_member_name) . "'" : "NULL";
                                $non_member_mobile_val = (!empty($non_member_mobile)) ? "'" . mysqli_real_escape_string($link, $non_member_mobile) . "'" : "NULL";
                                $order_source_val = "'" . mysqli_real_escape_string($link, $order_source) . "'";

                                $updateQuery = "UPDATE Bills SET payment_method = 'UPI', payment_time = '$currentTime',
                                                staff_id = $staff_id, reservation_id = $reservation_id_val,
                                                non_member_name = $non_member_name_val, non_member_mobile = $non_member_mobile_val,
                                                order_source = $order_source_val, net_revenue = $GRANDTOTAL
                                                WHERE bill_id = $bill_id;";

                                if ($link->query($updateQuery) === TRUE) {
                                    // Clean up self-service orders for this table
                                    $table_id_query = "SELECT table_id FROM Bills WHERE bill_id = $bill_id";
                                    $table_id_res = $link->query($table_id_query);
                                    if ($table_id_res && $table_row = $table_id_res->fetch_assoc()) {
                                        $t_id = $table_row['table_id'];
                                        $delete_orders_sql = "DELETE FROM customer_orders WHERE table_id = $t_id";
                                        $link->query($delete_orders_sql);
                                    }

                                    echo '<div class="alert alert-success text-center font-weight-bold py-3" style="font-size: 1.2em;" role="alert"><i class="fa fa-check-circle fa-2x d-block mb-2"></i> UPI Payment Successful!</div>';
                                    echo '<div class="text-center mt-4">';
                                    echo '<a href="posTable.php" class="btn btn-dark btn-lg w-50 mb-2 font-weight-bold">Back to Tables</a><br>';
                                    echo '<a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-outline-dark btn-lg w-50 font-weight-bold">Print Receipt <i class="fa fa-receipt"></i></a>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-danger" role="alert">Error updating bill: ' . $link->error . '</div>';
                                }
                            }
                        } else {
                            // Show bill details and Confirm button
                            ?>
                            <div class="mb-4">
                                <h5 class="font-weight-bold text-muted">Bill Details</h5>
                                <p class="mb-1"><strong>Bill ID:</strong> <?php echo $bill_id; ?></p>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Item ID</th>
                                            <th>Item Name</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Query to fetch cart items for the given bill_id
                                        $cart_query = "SELECT bi.*, m.item_name, m.item_price FROM bill_items bi
                                                       JOIN Menu m ON bi.item_id = m.item_id
                                                       WHERE bi.bill_id = '$bill_id'";
                                        $cart_result = mysqli_query($link, $cart_query);
                                        $cart_total = 0;
                                        $tax = 0.1; // 10% tax rate

                                        if ($cart_result && mysqli_num_rows($cart_result) > 0) {
                                            while ($cart_row = mysqli_fetch_assoc($cart_result)) {
                                                $item_id = $cart_row['item_id'];
                                                $item_name = $cart_row['item_name'];
                                                $item_price = $cart_row['item_price'];
                                                $quantity = $cart_row['quantity'];
                                                $total = $item_price * $quantity;
                                                $cart_total += $total;
                                                echo '<tr>';
                                                echo '<td>' . $item_id . '</td>';
                                                echo '<td>' . $item_name . '</td>';
                                                echo '<td class="text-center">Rs ' . number_format($item_price, 2) . '</td>';
                                                echo '<td class="text-center">' . $quantity . '</td>';
                                                echo '<td class="text-right">Rs ' . number_format($total, 2) . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="5" class="text-center">No Items in Cart.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded text-muted mb-3 mb-md-0" style="border-left: 5px solid crimson;">
                                        <i class="fa fa-info-circle text-danger mr-1"></i> Verify that the customer has successfully paid via UPI on the guest device or counter scanner before confirming.
                                    </div>
                                </div>
                                <div class="col-md-6 text-right" style="font-size: 1.15em;">
                                    <strong>Subtotal:</strong> Rs <?php echo number_format($cart_total, 2); ?><br>
                                    <strong>Tax (10%):</strong> Rs <?php echo number_format($cart_total * $tax, 2); ?><br>
                                    <?php $GRANDTOTAL = $tax * $cart_total + $cart_total; ?>
                                    <span style="color: crimson; font-size: 1.35em;"><strong>Grand Total: Rs <?php echo number_format($GRANDTOTAL, 2); ?></strong></span>
                                </div>
                            </div>

                            <hr class="my-4">

                            <form action="" method="get" class="text-center">
                                <!-- Hidden params -->
                                <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                                <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
                                <input type="hidden" name="order_source" value="<?php echo htmlspecialchars($order_source); ?>">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
                                <input type="hidden" name="non_member_name" value="<?php echo htmlspecialchars($non_member_name); ?>">
                                <input type="hidden" name="non_member_mobile" value="<?php echo htmlspecialchars($non_member_mobile); ?>">
                                <input type="hidden" name="confirm_upi" value="true">

                                <div class="d-flex justify-content-center gap-3">
                                    <button type="submit" class="btn btn-danger btn-lg font-weight-bold px-5 mr-3" style="background-color: crimson; border-color: crimson; box-shadow: 0 4px 10px rgba(220, 20, 60, 0.3);">
                                        <i class="fa fa-check"></i> Confirm UPI Payment
                                    </button>
                                    <a href="posTable.php" class="btn btn-outline-secondary btn-lg font-weight-bold px-4">Back to Tables</a>
                                </div>
                            </form>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>

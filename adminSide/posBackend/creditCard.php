<?php
session_start();
require_once 'checkIfLoggedIn.php'; // Ensure session is started
?>
<?php
require_once '../config.php';
include '../inc/dashHeader.php';
$bill_id = $_GET['bill_id'];
?>

<div class="container" style="margin-top: 15rem; margin-left: 4rem;">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bill (Card Payment)</h3>
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
                    echo '<td>Rs ' . number_format($item_price,2) . '</td>';
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
        </div>
    </div>
</div>



<div id="card-payment" class="col-md-6 order-md-2" style="margin-top: 10rem; margin-right: 5rem;max-width: 40rem;">
    <div class="container-fluid pt-5 pl-3 pr-3">

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from the form
    $account_holder_name = $_POST['cardName'];
    $card_number = $_POST['cardNumber'];
    $expiry_date = $_POST['expiryDate'];
    $security_code = $_POST['securityCode'];
    $bill_id = $_GET['bill_id'];
    $staff_id = $_POST['staff_id'];
    $order_source = !empty($_POST['order_source']) ? $_POST['order_source'] : 'Dine-In';
    $reservation_id = $_POST['reservation_id'];
    $GRANDTOTAL = $_POST['GRANDTOTAL'];
    $non_member_name = $_POST['non_member_name'] ?? '';
    $non_member_mobile = $_POST['non_member_mobile'] ?? '';

    // Check if the bill has already been paid for
    $check_payment_sql = "SELECT card_id FROM Bills WHERE bill_id = '$bill_id'";
    $check_payment_result = $link->query($check_payment_sql);

    if ($check_payment_result) {
        $row = $check_payment_result->fetch_assoc();

        if ($row['card_id'] !== null) {
            echo '<div class="alert alert-warning" role="alert">';
            echo "Bill has already been paid for.</div>";
            
            echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
            echo '<br><a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>';
        } else {
            $currentTime = date('Y-m-d H:i:s'); // Current time

            // Prepare and execute the SQL query to insert into card_payments table
            $insert_card_sql = "INSERT INTO card_payments (account_holder_name, card_number, expiry_date, security_code) 
                                VALUES (?, ?, ?, ?)";
            
            $stmt = $link->prepare($insert_card_sql);
            $stmt->bind_param("ssss", $account_holder_name, $card_number, $expiry_date, $security_code);

            if ($stmt->execute()) {
                // Retrieve the generated card_id
                $card_id = $stmt->insert_id;
                
                // Prepare and execute the SQL query to update Bills table with payment details
                $reservation_id_val = (!empty($reservation_id)) ? intval($reservation_id) : "NULL";
                $non_member_name_val = (!empty($non_member_name)) ? "'" . mysqli_real_escape_string($link, $non_member_name) . "'" : "NULL";
                $non_member_mobile_val = (!empty($non_member_mobile)) ? "'" . mysqli_real_escape_string($link, $non_member_mobile) . "'" : "NULL";
                $order_source_val = "'" . mysqli_real_escape_string($link, $order_source) . "'";

                $update_bill_sql = "UPDATE Bills SET card_id = $card_id, payment_method = 'card', payment_time = '$currentTime',
                                    staff_id = $staff_id, reservation_id = $reservation_id_val,
                                    non_member_name = $non_member_name_val, non_member_mobile = $non_member_mobile_val,
                                    order_source = $order_source_val, net_revenue = $GRANDTOTAL
                                    WHERE bill_id = $bill_id;";

                if ($link->query($update_bill_sql) === TRUE) {
                    // Clean up old self-service orders for this table upon successful bill payment
                    $table_id_query = "SELECT table_id FROM Bills WHERE bill_id = $bill_id";
                    $table_id_res = $link->query($table_id_query);
                    if ($table_id_res && $table_row = $table_id_res->fetch_assoc()) {
                        $t_id = $table_row['table_id'];
                        $delete_orders_sql = "DELETE FROM customer_orders WHERE table_id = $t_id";
                        $link->query($delete_orders_sql);
                    }

                    echo '<div class="alert alert-success" role="alert">
                    Payment successful!</div>';
                    echo '<br><a href="posTable.php" class="btn btn-dark">Back to Tables</a>';
                    echo '<br><a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>';
                } else {
                    echo "Error updating Bills table: " . $link->error;
                }
            } else {
                echo "Error inserting data into card_payments table: " . $stmt->error;
            }
        }
    } else {
        echo "Error checking payment status: " . $link->error;
    }
}
?>
    </div>
    </div><!-- comment -->


<?php include '../inc/dashFooter.php'; ?>
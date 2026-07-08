<?php
session_start();
require_once 'checkIfLoggedIn.php'; // Ensure session is started
require_once('../config.php');

$bill_id = isset($_GET['bill_id']) ? $_GET['bill_id'] : '';
$pre_name = '';
$pre_mobile = '';
$pre_member_id = '';
$pre_res_id = '';

if (!empty($bill_id)) {
    $db_bill_query = "SELECT * FROM Bills WHERE bill_id = '$bill_id'";
    $db_bill_res = mysqli_query($link, $db_bill_query);
    if ($db_bill_res && $db_bill_row = mysqli_fetch_assoc($db_bill_res)) {
        $pre_name = $db_bill_row['non_member_name'] ?? '';
        $pre_mobile = $db_bill_row['non_member_mobile'] ?? '';
        $pre_res_id = $db_bill_row['reservation_id'] ?? '';
        $table_id = $db_bill_row['table_id'] ?? '';

        // Auto-sync customer name and mobile from customer_orders if they are empty
        if ((empty($pre_name) || empty($pre_mobile)) && !empty($table_id)) {
            $cust_orders_query = "SELECT customer_name, customer_mobile FROM customer_orders WHERE table_id = '$table_id' ORDER BY order_time DESC LIMIT 1";
            $cust_orders_res = mysqli_query($link, $cust_orders_query);
            if ($cust_orders_res && $cust_orders_row = mysqli_fetch_assoc($cust_orders_res)) {
                if (empty($pre_name)) {
                    $pre_name = $cust_orders_row['customer_name'];
                    mysqli_query($link, "UPDATE Bills SET non_member_name = '" . mysqli_real_escape_string($link, $pre_name) . "' WHERE bill_id = '$bill_id'");
                }
                if (empty($pre_mobile)) {
                    $pre_mobile = $cust_orders_row['customer_mobile'];
                    mysqli_query($link, "UPDATE Bills SET non_member_mobile = '" . mysqli_real_escape_string($link, $pre_mobile) . "' WHERE bill_id = '$bill_id'");
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Staff Member Reservation Validity</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Check Staff Member Reservation Validity</h2>
        <form action="" method="post">
            <div class="form-group">
                <?php
                    $currentStaffId = $_SESSION['logged_account_id'] ?? "Please Login"; 
                ?>
                <label for="staffId">Staff ID:</label>
                <input type="text" id="staffId" name="staffId" class="form-control" 
                       value="<?= $currentStaffId ?>" readonly required>
            </div>
            <div class="form-group">
                <label for="orderSource">Order Source:</label>
                <select id="orderSource" name="orderSource" class="form-control">
                    <option value="Dine-In" selected>Dine-In (Default)</option>
                    <option value="Takeaway">Takeaway</option>
                    <option value="Direct-Store">Direct Store Order</option>
                    <option value="QR-Table">QR Table Order</option>
                    <option value="Website">Website Direct Order</option>
                </select>
            </div>
            <div class="border p-3 mb-3 bg-light rounded">
                <h5 class="mb-2">If Not a Member:</h5>
                <div class="form-group">
                    <label for="customerName">Customer Name:</label>
                    <input type="text" id="customerName" name="customerName" class="form-control" placeholder="Enter Customer Name" value="<?php echo htmlspecialchars($pre_name); ?>">
                </div>
                <div class="form-group mb-0">
                    <label for="customerMobile">Mobile Number:</label>
                    <input type="text" id="customerMobile" name="customerMobile" class="form-control" placeholder="Enter Mobile Number" value="<?php echo htmlspecialchars($pre_mobile); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="reservationId">Reservation ID:</label>
                <input type="text" id="reservationId" name="reservationId" class="form-control" value="<?php echo htmlspecialchars($pre_res_id); ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-dark">Check Validity</button>
                <a class="btn btn-danger" href="javascript:window.history.back();">Cancel</a>
                <a class="btn btn-link" href="posTable.php">Tables Page</a>
            </div>
        </form>
    </div>

<div class="container mt-3">
    <?php
    // Include your database connection configuration
    require_once('../config.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $staffId = $_POST['staffId'];
        $orderSource = !empty($_POST['orderSource']) ? $_POST['orderSource'] : 'Dine-In';
        $customerName = !empty($_POST['customerName']) ? $_POST['customerName'] : '';
        $customerMobile = !empty($_POST['customerMobile']) ? $_POST['customerMobile'] : '';
        $reservationId = !empty($_POST['reservationId']) ? $_POST['reservationId'] : 1111111;
        $bill_id = $_GET['bill_id'];

        // Check if the staff ID exists in the database
        $query = "SELECT * FROM Staffs WHERE staff_id = '$staffId'";
        $result = mysqli_query($link, $query);

        if (!$result) {
            echo "Error: " . mysqli_error($link);
        } else {
            $staffExists = mysqli_num_rows($result) > 0;

            $reservationExists = true; // Assume reservation is valid if ID is not provided
            if (!empty($reservationId)) {
                $query = "SELECT * FROM Reservations WHERE reservation_id = '$reservationId'";
                $result = mysqli_query($link, $query);
                if (!$result) {
                    echo "Error: " . mysqli_error($link);
                } else {
                    $reservationExists = mysqli_num_rows($result) > 0;
                }
            }

            if ($staffExists && $reservationExists) {
                echo '<div class="alert alert-success" role="alert">';
                echo "Staff and reservation are valid.";
                echo '</div>';
                echo '<div class="mt-3">';
                echo '<a href="posCashPayment.php?bill_id=' . $bill_id . '&staff_id=' . $staffId . '&order_source=' . urlencode($orderSource) . '&reservation_id=' . $reservationId . '&non_member_name=' . urlencode($customerName) . '&non_member_mobile=' . urlencode($customerMobile) . '" class="btn btn-success">Cash</a>';
                echo '<a href="posCardPayment.php?bill_id=' . $bill_id . '&staff_id=' . $staffId . '&order_source=' . urlencode($orderSource) . '&reservation_id=' . $reservationId . '&non_member_name=' . urlencode($customerName) . '&non_member_mobile=' . urlencode($customerMobile) . '" class="btn btn-primary ml-2">Card</a>';
                echo '<a href="posUpiPayment.php?bill_id=' . $bill_id . '&staff_id=' . $staffId . '&order_source=' . urlencode($orderSource) . '&reservation_id=' . $reservationId . '&non_member_name=' . urlencode($customerName) . '&non_member_mobile=' . urlencode($customerMobile) . '" class="btn btn-info ml-2">UPI</a>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger" role="alert">Invalid staff or reservation.</div>';
            }
        }
    }
    ?>
</div>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

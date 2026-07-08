<?php
session_start(); // Ensure session is started
require_once 'checkIfLoggedIn.php';
?>
<?php
include '../inc/dashHeader.php';
require_once '../config.php'; // Include your database configuration
?>

<!DOCTYPE html>
<html>
<head>
    <link href="../css/pos.css" rel="stylesheet" />
    <style>
        @keyframes blinker {
            50% { opacity: 0.3; }
        }
        .pos-content-wrapper {
            padding-left: 225px;
            transition: padding-left 0.15s ease-in-out;
            width: 100%;
        }
        .sb-sidenav-toggled .pos-content-wrapper {
            padding-left: 0;
        }
    </style>
</head>
<body>

<div class="pos-content-wrapper">
    <div class="container-fluid" style="text-align: center; margin-top:3rem;">
        <div id="POS-Content" class="row justify-content-center" >
            <div class="col-md-10" style="margin-top: 0rem;max-height: 700px; overflow-y: auto;">
                <div class="row justify-content-center">
                    <?php
                    // Fetch all tables from the database
                    $query = "SELECT * FROM Restaurant_Tables ORDER BY table_id;";
                    $result = mysqli_query($link, $query);
                    $table = array("", "", "");
                    if ($result) {
                        $table_count = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            if ($table_count % 5 == 0) {
                                echo '</div><div class="row justify-content-center">';
                            }
                            $table_id = $row['table_id'];
                            $capacity = $row['capacity'];
                            

                            $sqlBill = "SELECT bill_id FROM Bills WHERE table_id = $table_id ORDER BY bill_time DESC LIMIT 1";
                            $result1 = $link->query($sqlBill);
                            $latestBillData = $result1->fetch_assoc();
                            
                             // Check if the table is reserved for the selected time
                             date_default_timezone_set('Asia/Kolkata'); // Set the time zone to India (matching +05:30)

                            $selectedDate = date("Y-m-d"); // Get the current date, 
                            $endTime = date("H:i:s"); // Get the current time
                            
                            // Check if there's a reservation starting 30 minutes before up to 15 minutes after now
                            $startTime = date("H:i:s", strtotime($endTime) - (15 * 60)); // 15 mins after reservation start (since reservation_time <= current_time + 15 mins)
                            $maxTime = date("H:i:s", strtotime($endTime) + (30 * 60)); // 30 mins before reservation start (since reservation_time >= current_time - 30 mins)
                            $reservationQuery = "SELECT * FROM reservations WHERE table_id = $table_id AND reservation_date = '$selectedDate' AND reservation_time BETWEEN '$startTime' AND '$maxTime'";
                            $reservationResult = mysqli_query($link, $reservationQuery);
                            
                            if ($latestBillData) {
                                $latestBillID = $latestBillData['bill_id'];

                                $sqlBillItems = "SELECT * FROM bill_items WHERE bill_id = $latestBillID";
                                $result2 = $link->query($sqlBillItems);
                                if ($result2 && mysqli_num_rows($result2) > 0) {
                                    $billItemColor = 'rgb(216, 0, 50)'; // Bill has associated bill items
                                    $billItemColor = 'rgb(216, 0, 50)'; // Bill has associated bill items
                                } else {
                                    $billItemColor = 'rgb(23, 89, 74)';
                                }

                                $paymentTimeQuery = "SELECT payment_time FROM Bills WHERE bill_id = $latestBillID";
                                $paymentTimeResult = $link->query($paymentTimeQuery);
                                $hasPaymentTime = false;

                                if ($paymentTimeResult && $paymentTimeResult->num_rows > 0) {
                                    $paymentTimeRow = $paymentTimeResult->fetch_assoc();
                                    if (!empty($paymentTimeRow['payment_time'])) {
                                        $hasPaymentTime = true;
                                    }
                                }

                                $box_color = $hasPaymentTime ? 'rgb(23, 89, 74)' : $billItemColor;
                            } else {
                                $latestBillID = null;
                                $box_color = 'gray'; // No bill for the table (gray)
                            }

                            // Check if there are any pending orders for this specific table
                            $pendingOrderQuery = "SELECT COUNT(*) AS pending_count FROM customer_orders WHERE table_id = $table_id AND status = 'Pending'";
                            $pendingOrderResult = mysqli_query($link, $pendingOrderQuery);
                            $hasPendingOrder = false;
                            if ($pendingOrderResult) {
                                $pendingOrderRow = mysqli_fetch_assoc($pendingOrderResult);
                                $hasPendingOrder = $pendingOrderRow && $pendingOrderRow['pending_count'] > 0;
                            }

                            echo '<div class="col-md-2 mb-3" style="position: relative;">';
                            if ($hasPendingOrder) {
                                echo '<span class="badge badge-danger" style="position: absolute; top: -5px; right: 10px; z-index: 10; background-color: crimson; color: white; border-radius: 50%; padding: 6px 8px; font-size: 0.9em; box-shadow: 0 0 8px crimson; animation: blinker 1.5s linear infinite;" title="Pending self-service order request!"><i class="fa fa-bell"></i></span>';
                            }
                            if ($reservationResult && mysqli_num_rows($reservationResult) > 0) {
                                    // The table is reserved for the selected time, so set the color accordingly
                                echo '<a href="orderItem.php?bill_id=' . $latestBillID . '&table_id=' . $table_id . '"class="btn btn-primary btn-block btn-lg" style="color:black; '
                                        . 'background-color: rgb(248, 222, 34);justify-content: center; align-items: center; display: flex; width: 9rem; height: 9rem;">'
                                        . 'Table: ' . $table_id /* . '<br>Bill ID: ' . $latestBillID */. '<br>Capacity: ' . $capacity;
                            } else{
                                echo '<a href="orderItem.php?bill_id=' . $latestBillID . '&table_id=' . $table_id . '"class="btn btn-primary btn-block btn-lg" '
                                        . 'style="background-color: ' . $box_color . ';justify-content: center; align-items: center; display: flex; width: 9rem; height: 9rem;">Table:'
                                        . ' ' . $table_id /*. '<br>Bill ID: ' . $latestBillID */. '<br>Capacity: ' . $capacity;
                            }
                            echo '</a></div>';
                            $table_count++;
                        }
                    } else {
                        echo "Error fetching tables: " . mysqli_error($link);
                    }
                    ?>
                </div>
            
              <div class="row d-flex justify-content-around"style="margin-top: 2rem;" >
                 <div class="col-md-3">
                    <div class="alert alert-success" role="alert" style="color:white;background-color: rgb(23, 89, 74);" data-toggle="tooltip" data-placement="top" title="Tables That are Free">
                        Available
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-danger" role="alert" style="color:white;background-color: rgb(216, 0, 50);" data-toggle="tooltip" data-placement="top" title="Tables That are Used">
                        Occupied
                    </div>
                </div>
                <!--
                <div class="col-md-3">
                    <div class="alert alert-dark" role="alert">
                        No Bill Id
                    </div>
                </div>
                -->
                <div class="col-md-3">
                    <div class="alert alert-warning" style="color:black;background-color: rgb(248, 222, 34);" role="alert" data-toggle="tooltip" data-placement="top" title="Tables That are Reserved">
                        Reserved
                    </div>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php' ?>

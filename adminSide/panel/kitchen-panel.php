<?php
session_start(); // Ensure session is started
require_once '../posBackend/checkIfLoggedIn.php';
?>
<?php
include '../inc/dashHeader.php';
require_once '../config.php';
$query = "SELECT * FROM Kitchen";
$result = mysqli_query($link, $query);
?>

    <link href="../css/pos.css" rel="stylesheet" />
    <meta http-equiv="refresh" content="5">

<div class="wrapper" style="width: 1300px; padding-left: 200px; padding-top: 20px">
    <div class="container-fluid pt-5 pl-600 mt-5">
          <div class="">
            <div class="col" style="text-align: left; display: flex; justify-content: space-between;">
                <h2 class="">Kitchen Orders</h2>
                <a href="../posBackend/kitchenBackend/undo.php?UndoUnshow=true" class="btn btn-warning mb-2">Undo</a>
            </div>
          </div>

        <table class="table table-bordered ">
            <thead>
                
                <tr>
                    <th>Kitchen ID</th>
                    <th>Table / Platform</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Time Submitted</th>
                    <th>Order Source</th>
                    <th>Pickup Deadline</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $kitchen_id = $row['kitchen_id'];
                        $table_no = $row['table_no'];
                        $item_id = $row['item_id'];
                        $quantity = $row['quantity'];
                        $time_submitted = $row['time_submitted'];

                        // Dynamic Bills Join to find order source, priority, and pickup deadline
                        $order_source = 'Dine-In';
                        $priority = 'Normal';
                        $deadline = '';
                        $table_display = $table_no;

                        $bill_sql = "SELECT order_source, priority_level, pickup_deadline FROM Bills 
                                     WHERE (table_id = '$table_no' AND payment_time IS NULL) 
                                        OR ('$table_no' = 0 AND ABS(TIMESTAMPDIFF(SECOND, bill_time, '$time_submitted')) < 10)
                                     ORDER BY bill_id DESC LIMIT 1";
                        $bill_res = mysqli_query($link, $bill_sql);
                        if ($bill_res && $bill_row = mysqli_fetch_assoc($bill_res)) {
                            $order_source = $bill_row['order_source'];
                            $priority = $bill_row['priority_level'];
                            $deadline = $bill_row['pickup_deadline'];
                        }

                        if ($table_no == 0) {
                            $table_display = "<span class='badge bg-dark' style='color: white;'>Online Order</span>";
                        }

                        // Badges definition
                        $badge_class = 'bg-success';
                        if ($order_source === 'Swiggy') $badge_class = 'bg-warning text-dark';
                        elseif ($order_source === 'Zomato') $badge_class = 'bg-danger';
                        elseif ($order_source === 'ONDC') $badge_class = 'bg-info text-dark';
                        elseif ($order_source === 'Website') $badge_class = 'bg-primary';
                        elseif ($order_source === 'QR-Table') $badge_class = 'bg-secondary';
                        elseif ($order_source === 'Takeaway') $badge_class = 'bg-secondary';

                        // Priority row highlights
                        $row_style = "";
                        $urgent_badge = "";
                        if ($priority === 'Urgent') {
                            $row_style = "style='border-left: 5px solid crimson; background-color: rgba(220, 20, 60, 0.05);'";
                            $urgent_badge = "<span class='badge bg-danger ms-2' style='color: white; animation: blinker 1.5s linear infinite;'>URGENT</span>";
                        }

                        // Countdown formatting
                        $deadline_display = "N/A";
                        if (!empty($deadline)) {
                            $time_diff = strtotime($deadline) - time();
                            if ($time_diff > 0) {
                                $mins = ceil($time_diff / 60);
                                $deadline_display = "<span class='badge bg-dark' style='color: white;'>$mins mins left</span>";
                            } else {
                                $deadline_display = "<span class='badge bg-danger' style='color: white;'>OVERDUE</span>";
                            }
                        }

                        // Get item name from Menu table
                        $itemQuery = "SELECT item_name FROM Menu WHERE item_id = '$item_id'";
                        $itemResult = mysqli_query($link, $itemQuery);
                        $itemRow = mysqli_fetch_assoc($itemResult);
                        $item_name = $itemRow['item_name']??"Deleted";

                        echo "<tr $row_style>";
                        echo '<td>' . $kitchen_id . '</td>';
                        echo '<td>' . $table_display . '</td>';
                        echo '<td>' . $item_name . '</td>';
                        echo '<td>' . $quantity . '</td>';
                        echo '<td>' . $time_submitted . '</td>';
                        echo '<td><span class="badge ' . $badge_class . '" style="font-size: 0.9em; padding: 5px 10px;">' . $order_source . '</span> ' . $urgent_badge . '</td>';
                        echo '<td>' . $deadline_display . '</td>';
                        echo '<td>';
                        echo '<a href="../posBackend/kitchenBackend/kitchen-panel-back.php?action=done&kitchen_id=' . $kitchen_id . '" class="btn btn-danger">Done</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">No records in the Kitchen table.</td></tr>';
                }
                ?>
            </tbody>
        </table>

    </div>
</div>



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
        
        /* Premium luxury table card */
        .pos-table-card {
            background-color: #FFFFFF !important;
            border: 1px solid #E7E2DA !important;
            border-radius: 20px !important;
            box-shadow: 0 12px 40px rgba(25, 30, 40, 0.05) !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            width: 10rem !important;
            height: 10rem !important;
            color: #23252F !important;
            text-decoration: none !important;
            transition: all 200ms ease !important;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif !important;
        }
        
        .pos-table-card:hover {
            box-shadow: 0 20px 50px rgba(25, 30, 40, 0.1) !important;
            transform: translateY(-4px);
            border-color: #C8A96A !important; /* Gold hover border */
        }
        
        .pos-table-card .card-title {
            font-family: 'Playfair Display', serif !important;
            font-weight: 700 !important;
            font-size: 1.45rem !important;
            color: #23252F !important;
            margin-bottom: 4px;
        }
        
        .pos-table-card .card-capacity {
            font-size: 0.85rem !important;
            color: #7C8798 !important;
            font-weight: 500 !important;
        }
        
        /* Status indicator line on top of card */
        .pos-table-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background-color: #7C8798; /* Default Inactive */
        }
        
        .pos-table-card.status-available::before {
            background-color: #2D9D78 !important; /* Available Green */
        }
        
        .pos-table-card.status-occupied::before {
            background-color: #C0392B !important; /* Occupied Red */
        }
        
        .pos-table-card.status-reserved::before {
            background-color: #C68A00 !important; /* Reserved Amber */
        }
    </style>
</head>
<body>

<div class="pos-content-wrapper">
    <div class="container-fluid" style="text-align: center; margin-top:3rem;">
        <div id="POS-Content" class="row justify-content-center" >
            <div class="col-md-10" style="margin-top: 0rem; max-height: 700px; overflow-y: auto; padding: 20px;">
                <div class="row justify-content-center">
                    <?php
                    // Fetch all tables from the database
                    $query = "SELECT * FROM Restaurant_Tables ORDER BY table_id;";
                    $result = mysqli_query($link, $query);
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
                            
                            // Check if there's a reservation starting 15 minutes before up to 30 minutes after now
                            $startTime = date("H:i:s", strtotime($endTime) - (15 * 60)); 
                            $maxTime = date("H:i:s", strtotime($endTime) + (30 * 60)); 
                            $reservationQuery = "SELECT * FROM reservations WHERE table_id = $table_id AND reservation_date = '$selectedDate' AND reservation_time BETWEEN '$startTime' AND '$maxTime'";
                            $reservationResult = mysqli_query($link, $reservationQuery);
                            
                            $latestBillID = null;
                            $hasPaymentTime = false;
                            $result2 = null;
                            
                            if ($latestBillData) {
                                $latestBillID = $latestBillData['bill_id'];

                                $sqlBillItems = "SELECT * FROM bill_items WHERE bill_id = $latestBillID";
                                $result2 = $link->query($sqlBillItems);

                                $paymentTimeQuery = "SELECT payment_time FROM Bills WHERE bill_id = $latestBillID";
                                $paymentTimeResult = $link->query($paymentTimeQuery);

                                if ($paymentTimeResult && $paymentTimeResult->num_rows > 0) {
                                    $paymentTimeRow = $paymentTimeResult->fetch_assoc();
                                    if (!empty($paymentTimeRow['payment_time'])) {
                                        $hasPaymentTime = true;
                                    }
                                }
                            }

                            // Compute status class
                            $status_class = 'status-inactive';
                            if ($reservationResult && mysqli_num_rows($reservationResult) > 0) {
                                $status_class = 'status-reserved';
                            } else {
                                if ($latestBillData) {
                                    if ($hasPaymentTime) {
                                        $status_class = 'status-available';
                                    } else {
                                        $status_class = ($result2 && mysqli_num_rows($result2) > 0) ? 'status-occupied' : 'status-available';
                                    }
                                } else {
                                    $status_class = 'status-inactive';
                                }
                            }

                            // Check if there are any pending orders for this specific table
                            $pendingOrderQuery = "SELECT COUNT(*) AS pending_count FROM customer_orders WHERE table_id = $table_id AND status = 'Pending'";
                            $pendingOrderResult = mysqli_query($link, $pendingOrderQuery);
                            $hasPendingOrder = false;
                            if ($pendingOrderResult) {
                                $pendingOrderRow = mysqli_fetch_assoc($pendingOrderResult);
                                $hasPendingOrder = $pendingOrderRow && $pendingOrderRow['pending_count'] > 0;
                            }

                            echo '<div class="col-md-2 mb-4 text-center" style="position: relative; min-width: 12rem;">';
                            if ($hasPendingOrder) {
                                echo '<span class="badge badge-danger" style="position: absolute; top: -10px; right: 15px; z-index: 10; background-color: #C0392B; color: white; border-radius: 50%; padding: 6px 8px; font-size: 0.9em; box-shadow: 0 0 8px #C0392B; animation: blinker 1.5s linear infinite;" title="Pending order request!"><i class="fa fa-bell"></i></span>';
                            }
                            echo '<a href="orderItem.php?bill_id=' . $latestBillID . '&table_id=' . $table_id . '" class="pos-table-card ' . $status_class . '">';
                            echo '  <span class="card-title">Table ' . $table_id . '</span>';
                            echo '  <span class="card-capacity"><i class="fa fa-users mr-1"></i> ' . $capacity . ' seats</span>';
                            echo '</a>';
                            echo '</div>';
                            
                            $table_count++;
                        }
                    } else {
                        echo "Error fetching tables: " . mysqli_error($link);
                    }
                    ?>
                </div>
            
              <!-- Premium Legend indicators -->
              <div class="row justify-content-center align-items-center" style="margin-top: 3rem; gap: 16px; margin-bottom: 2rem;">
                  <div class="d-inline-flex align-items-center px-3 py-2 shadow-sm border" style="background: white; border-radius: 12px !important; font-family: 'Poppins', sans-serif !important; font-weight: 500; font-size: 0.9rem;">
                      <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #2D9D78; margin-right: 8px;"></span>
                      <span style="color: #555F70;">Available</span>
                  </div>
                  <div class="d-inline-flex align-items-center px-3 py-2 shadow-sm border" style="background: white; border-radius: 12px !important; font-family: 'Poppins', sans-serif !important; font-weight: 500; font-size: 0.9rem;">
                      <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #C0392B; margin-right: 8px;"></span>
                      <span style="color: #555F70;">Occupied</span>
                  </div>
                  <div class="d-inline-flex align-items-center px-3 py-2 shadow-sm border" style="background: white; border-radius: 12px !important; font-family: 'Poppins', sans-serif !important; font-weight: 500; font-size: 0.9rem;">
                      <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #C68A00; margin-right: 8px;"></span>
                      <span style="color: #555F70;">Reserved</span>
                  </div>
                  <div class="d-inline-flex align-items-center px-3 py-2 shadow-sm border" style="background: white; border-radius: 12px !important; font-family: 'Poppins', sans-serif !important; font-weight: 500; font-size: 0.9rem;">
                      <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #7C8798; margin-right: 8px;"></span>
                      <span style="color: #555F70;">Inactive / Clean</span>
                  </div>
              </div>
            </div>
        </div>
    </div>

<?php include '../inc/dashFooter.php' ?>

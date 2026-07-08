<?php
if (!isset($link)) {
    // Dynamically detect and include the correct config.php path relative to the active panel script
    if (file_exists('../config.php')) {
        require_once '../config.php';
    } elseif (file_exists('../../config.php')) {
        require_once '../../config.php';
    } elseif (file_exists('config.php')) {
        require_once 'config.php';
    }
}

$sidebar_pending_count = 0;
if (isset($link) && !$link->connect_error) {
    $sidebar_pending_query = "SELECT COUNT(DISTINCT request_group_id) AS pending_count FROM customer_orders WHERE status = 'Pending'";
    $sidebar_pending_res = mysqli_query($link, $sidebar_pending_query);
    if ($sidebar_pending_res) {
        $sidebar_pending_row = mysqli_fetch_assoc($sidebar_pending_res);
        $sidebar_pending_count = $sidebar_pending_row['pending_count'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <script>
            (function() {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.classList.add('preload');
            })();
        </script>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Dashboard - X Hotel Admin</title>
        <link href="../css/styles.css?v=1.4" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> 
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark justify-content-between">
            <div class="d-flex align-items-center">
                <!-- Navbar Brand-->
                <a class="navbar-brand ps-3" href="../panel/pos-panel.php">X Hotel Staff Panel</a>
            </div>
            <!-- Right side notifications link -->
            <div class="d-flex align-items-center pe-3" style="position: relative; z-index: 1050;">
                <a id="navbar-notification-btn" href="../panel/notifications.html" style="color: #64748B; font-size: 1.25rem; position: relative; text-decoration: none; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: rgba(0, 0, 0, 0.03); transition: background-color 0.2s;" title="View Notifications">
                    <i class="fas fa-bell"></i>
                    <span id="navbar-notification-badge" style="position: absolute; top: 10px; right: 10px; width: 8px; height: 8px; background-color: #EF4444; border-radius: 50%; display: none; box-shadow: 0 0 8px #EF4444;"></span>
                </a>
            </div>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Main</div>
                            <a class="nav-link" href="../panel/pos-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>
                                Point of Sale (POS)
                            </a>
                            <a class="nav-link" href="../panel/kitchen-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-kitchen-set"></i></div>
                                Kitchen
                            </a>
                            <a class="nav-link" href="../panel/bill-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-receipt"></i></div>
                                Bills
                            </a>
                            <a class="nav-link" href="../panel/table-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-table-cells"></i></div>
                                Tables
                            </a>
                            <a class="nav-link" href="../panel/reservation-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-book"></i></div>
                                Reservations
                            </a>
                            <a class="nav-link" href="../panel/menu-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-utensils"></i></div>
                                Menu
                            </a>
                            <a class="nav-link" href="../panel/account-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-eye"></i></div>
                                Staff Account Details
                            </a>
                            
                            <div class="sb-sidenav-menu-heading">Report & Analytics</div>
                            <a class="nav-link" href="../panel/statistics-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Revenue Statistics
                            </a>
                            <a class="nav-link" href="../panel/sales-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-fire"></i></div>
                                Item Sales
                            </a>

                            <a class="nav-link" href="../StaffLogin/logout.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-key"></i></div>
                                Log Out
                            </a>
                            
                            
                            
                        </div>
                    </div>
                        <div class="sb-sidenav-footer">
                            <div class="small">Logged in as:</div>
                                <?php
                                // Check if the session variables are set
                                if (isset($_SESSION['logged_account_id']) && isset($_SESSION['logged_staff_name'])) {
                                    // Display the logged-in staff ID and name
                                    echo "Staff ID: " . $_SESSION['logged_account_id'] . "<br>";
                                    echo "Staff Name: " . $_SESSION['logged_staff_name'];
                                    
                                } else {
                                    // If session variables are not set, display a default message or handle as needed
                                    echo "Not logged in";
                                }
                                ?>
                        </div>
                </nav>
            </div>
        </<div>
            <div id="content-for-template">Content</div> 
        
        <script src="../js/scripts.js" type="text/javascript"></script>
        
        <!-- Preload transition manager -->
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(() => {
                document.documentElement.classList.remove('preload');
            }, 100);
        });
        </script>

<?php
include_once('../components/header.php');

// Reconnect to DB since header.php automatically closes the connection
$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}

$reservationStatus = $_GET['reservation'] ?? null;
$message = '';
if ($reservationStatus === 'success') {
    $message = "Reservation successful";
    $reservation_id = $_GET['reservation_id'] ?? null;
    echo '<script>alert("Table Successfully Reserved. Click OK to view your reservation receipt."); window.location.href = "reservationReceipt.php?reservation_id=' . $reservation_id . '";</script>';
}
$head_count = $_GET['head_count'] ?? 1;

$auto_table_id = null;
$auto_capacity = null;
$show_reservation = false;

if (isset($_GET['reserved_table_id'])) {
    $table_id_list = $_GET['reserved_table_id'];
    $reserved_table_ids = explode(',', $table_id_list);
    
    // Find available tables order by capacity asc, so we pick the smallest matching capacity first
    $select_query_tables = "SELECT * FROM restaurant_tables WHERE capacity >= '$head_count'";
    if (!empty($reserved_table_ids) && $table_id_list !== '0') {
        $reserved_table_ids_string = implode(',', $reserved_table_ids);
        $select_query_tables .= " AND table_id NOT IN ($reserved_table_ids_string)";
    }
    $select_query_tables .= " ORDER BY capacity ASC, table_id ASC LIMIT 1";
    $result_tables = mysqli_query($link, $select_query_tables);
    if ($result_tables && mysqli_num_rows($result_tables) > 0) {
        $row = mysqli_fetch_assoc($result_tables);
        $auto_table_id = $row['table_id'];
        $auto_capacity = $row['capacity'];
        $show_reservation = true;
    } else {
        echo '<script>alert("No reservation tables found for the selected time. Please choose another time.");</script>';
    }
}
?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
    body {
        font-family: 'Poppins', sans-serif !important;
        background-image: linear-gradient(rgba(250, 248, 244, 0.65), rgba(250, 248, 244, 0.65)), url('../image/reservation_bg.jpg') !important;
        background-size: cover !important;
        background-position: center !important;
        background-attachment: fixed !important;
        background-repeat: no-repeat !important;
        color: #555F70 !important; /* Body Text */
    }
    
    .section-title {
        color: #23252F !important; /* Luxury Charcoal */
        font-family: 'Playfair Display', serif !important;
        font-weight: 700 !important;
    }
    
    .section-title span {
        color: #8C1D2C !important; /* Primary Accent */
    }
    
    .reserve-card {
        background: #FFFFFF !important; /* Card Background */
        border: 1px solid #E7E2DA !important; /* Border */
        border-radius: 20px;
        padding: 35px;
        box-shadow: 0 12px 40px rgba(25, 30, 40, 0.08) !important;
        transition: all 200ms ease;
    }
    
    .reserve-card:hover {
        border-color: #C8A96A !important; /* Accent Gold */
        box-shadow: 0 20px 50px rgba(25, 30, 40, 0.12) !important;
        transform: translateY(-4px);
    }
    
    .reserve-card h3 {
        color: #23252F !important; /* Luxury Charcoal */
        font-family: 'Playfair Display', serif !important;
        font-weight: 700 !important;
    }
    
    .text-crimson {
        color: #8C1D2C !important; /* Primary Accent */
    }
    
    .cta-btn-sm {
        background-color: #8C1D2C !important; /* Primary Accent */
        color: #FFFFFF !important;
        border: 1px solid #8C1D2C !important;
        font-size: 1.3rem;
        text-transform: uppercase;
        font-weight: bold;
        letter-spacing: 0.05rem;
        transition: all 200ms ease;
        border-radius: 14px;
        padding: 14px 20px;
    }
    
    .cta-btn-sm:hover {
        background-color: #6E1623 !important; /* Hover Accent */
        border-color: #6E1623 !important;
        color: #FFFFFF !important;
        box-shadow: 0px 4px 12px rgba(140, 29, 44, 0.25) !important;
    }
    
    .cta-btn-sm:active {
        background-color: #6E1623 !important; /* Active Accent */
        border-color: #6E1623 !important;
    }
    
    label {
        color: #555F70 !important; /* Body Text */
        font-weight: 500;
        font-family: 'Poppins', sans-serif !important;
    }
    
    /* Elegant standard inputs */
    input, select, textarea {
        background-color: #FFFFFF !important; /* Input Background */
        border: 1px solid #E7E2DA !important; /* Input Border */
        border-radius: 14px !important;
        color: #23252F !important; /* Primary Text */
        padding: 12px 18px !important;
        transition: all 200ms ease !important;
    }
    
    input:focus, select:focus, textarea:focus {
        background-color: #FFFFFF !important;
        border-color: #8C1D2C !important; /* Primary Accent */
        box-shadow: 0 0 0 3px rgba(140, 29, 44, 0.12) !important;
        color: #23252F !important;
        outline: none !important;
    }
    
    input::placeholder, textarea::placeholder {
        color: #7C8798 !important; /* Placeholder */
    }
</style>


<div class="container" style="margin-top: 12rem; margin-bottom: 5rem;">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <h1 class="text-center mb-5 section-title">Book A <span>Table</span></h1>
            
            <div class="row">
                <!-- Search Column -->
                <div class="col-md-5 mb-4" style="display: <?php echo $show_reservation ? 'none' : 'block'; ?>;">
                    <div class="reserve-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h3 class="mb-4 font-weight-bold" style="font-family: 'Outfit', sans-serif;"><i class="fa fa-search text-crimson mr-2"></i> Search Time</h3>
                            
                            <form id="reservation-form" method="GET" action="availability.php"><br>
                                <div class="form-group mb-4">
                                    <label for="reservation_date" style="color: var(--text-secondary) !important; font-size: 1.2rem;">Select Date</label>
                                    <input class="form-control" type="date" id="reservation_date" name="reservation_date" required>
                                </div>
                                <div class="form-group mb-4">
                                    <label for="reservation_time" style="color: var(--text-secondary) !important; font-size: 1.2rem;">Available Reservation Times</label>
                                    <div id="availability-table">
                                        <?php
                                        $availableTimes = array();
                                        for ($hour = 10; $hour <= 20; $hour++) {
                                            for ($minute = 0; $minute < 60; $minute += 60) {
                                                $time = sprintf('%02d:%02d:00', $hour, $minute);
                                                $availableTimes[] = $time;
                                            }
                                        }
                                        echo '<select name="reservation_time" id="reservation_time" class="form-control" required>';
                                        echo '<option value="" selected disabled>Select a Time</option>';
                                        foreach ($availableTimes as $time) {
                                            echo "<option value='$time'>$time</option>";
                                        }
                                        echo '</select>';
                                        if (isset($_GET['message'])) {
                                            $message = $_GET['message'];
                                            echo "<p class='text-crimson mt-2'>$message</p>";
                                        }
                                        ?>
                                    </div>
                                </div>
                      
                                <div class="form-group mb-4">
                                    <label for="head_count" style="color: var(--text-secondary) !important; font-size: 1.2rem;">Number of People</label>
                                    <input class="form-control" type="number" id="head_count" name="head_count" min="1" max="12" value="<?php echo htmlspecialchars($head_count); ?>" required>
                                </div>
                        </div>
                        <button type="submit" class="btn cta-btn-sm w-100" name="submit" >Search Availability</button>
                            </form>
                    </div>
                </div>

                <!-- Make Reservation Column -->
                <div class="<?php echo $show_reservation ? 'col-md-12' : 'col-md-7'; ?> mb-4" style="display: <?php echo $show_reservation ? 'block' : 'none'; ?>;">
                    <div class="reserve-card h-100">
                        <h3 class="mb-4 font-weight-bold" style="font-family: 'Outfit', sans-serif;"><i class="fa fa-calendar-check-o text-crimson mr-2"></i> Make Reservation</h3>
                        <form id="reservation-form" method="POST" action="insertReservation.php">
                            <div class="form-group mb-4">
                                <label for="customer_name" style="color: var(--text-secondary) !important; font-size: 1.2rem;">Customer Name</label>
                                <input class="form-control" type="text" id="customer_name" name="customer_name" required placeholder="Enter full name">
                            </div>
                            <div class="form-group mb-4">
                                <label for="phone_number" style="color: var(--text-secondary) !important; font-size: 1.2rem;">Mobile Number</label>
                                <input class="form-control" type="text" id="phone_number" name="phone_number" required placeholder="e.g. 0123456789">
                            </div>
                            <?php
                            $defaultReservationDate = $_GET['reservation_date'] ?? date("Y-m-d");
                            $defaultReservationTime = $_GET['reservation_time'] ?? "13:00:00";
                            ?>
                       
                            <div class="row">
                                <div class="col-md-6 form-group mb-4">
                                    <label style="color: var(--text-secondary) !important; font-size: 1.2rem;">Reservation Date</label>
                                    <input class="form-control" type="date" name="reservation_date" value="<?= $defaultReservationDate ?>" readonly required style="opacity: 0.8;">
                                </div>
                                <div class="col-md-6 form-group mb-4">
                                    <label style="color: var(--text-secondary) !important; font-size: 1.2rem;">Reservation Time</label>
                                    <input class="form-control" type="time" name="reservation_time" value="<?= $defaultReservationTime ?>" readonly required style="opacity: 0.8;">
                                </div>
                            </div>
                     
                            <input type="hidden" name="table_id" value="<?php echo $auto_table_id; ?>">
                            <input type="hidden" name="head_count" value="<?php echo htmlspecialchars($head_count); ?>">
                            <div class="form-group mb-4">
                                <label style="color: var(--text-secondary) !important; font-size: 1.2rem;">Assigned Table</label>
                                <input class="form-control" type="text" readonly value="Table ID: <?php echo $auto_table_id; ?> (For up to <?php echo $auto_capacity; ?> people)" style="opacity: 0.8;">
                            </div>
                     
                            <div class="form-group mb-4">
                                <label for="special_request" style="color: var(--text-secondary) !important; font-size: 1.2rem;">Special Request (Optional)</label>
                                <textarea class="form-control" id="special_request" name="special_request" rows="2" placeholder="e.g., anniversary, window seat..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn cta-btn-sm w-100" name="submit">Make Reservation</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const viewDateInput = document.getElementById("reservation_date");
    const makeDateInput = document.getElementById("reservation_date");

    viewDateInput.addEventListener("change", function () {
        makeDateInput.value = this.value;
    });
</script>

<?php
mysqli_close($link);
include_once('../components/footer.php');
?>


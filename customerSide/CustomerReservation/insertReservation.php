<?php
// reservation.php
require_once '../config.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the values from the form
    $customer_name = $_POST["customer_name"];
    $phone_number = $_POST["phone_number"];
    $table_id = intval($_POST["table_id"]);
    $reservation_time = $_POST["reservation_time"];
    $reservation_date = $_POST["reservation_date"];
    $special_request = $_POST["special_request"];
    
    $select_query_capacity = "SELECT capacity FROM restaurant_tables WHERE table_id='$table_id';";
    $results_capacity = mysqli_query($link, $select_query_capacity);

    if ($results_capacity) {
        $row = mysqli_fetch_assoc($results_capacity);
        $head_count = $row['capacity'];

        // Prepare the SQL query for insertion (let MySQL auto-generate reservation_id)
        $insert_query1 = "INSERT INTO Reservations (customer_name, phone_number, table_id, reservation_time, reservation_date, head_count, special_request) 
                        VALUES ('$customer_name', '$phone_number', '$table_id', '$reservation_time', '$reservation_date', '$head_count', '$special_request');";
        
        if (mysqli_query($link, $insert_query1)) {
            $reservation_id = mysqli_insert_id($link);
            
            $insert_query2 = "INSERT INTO Table_Availability (availability_id, table_id, reservation_date, reservation_time, status) 
                            VALUES ('$reservation_id', '$table_id', '$reservation_date', '$reservation_time',  'no');";
            mysqli_query($link, $insert_query2);

            $_SESSION['customer_name'] = $customer_name;
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Syncing Reservation...</title>
                <!-- Firebase SDK Compat CDN -->
                <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
                <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore-compat.js"></script>
                <script src="../js/firebase-config.js"></script>
            </head>
            <body>
                <div style="text-align: center; margin-top: 50px; font-family: sans-serif;">
                    <h2>Processing your reservation...</h2>
                    <p>Please do not close this window.</p>
                </div>
                <script>
                    document.addEventListener("DOMContentLoaded", async function () {
                        if (typeof firebase !== 'undefined' && window.db) {
                            try {
                                const reservationId = "<?php echo $reservation_id; ?>";
                                const newReservation = {
                                    reservation_id: reservationId,
                                    customer_name: "<?php echo addslashes($customer_name); ?>",
                                    phone_number: "<?php echo addslashes($phone_number); ?>",
                                    table_id: parseInt("<?php echo $table_id; ?>"),
                                    reservation_time: "<?php echo $reservation_time; ?>",
                                    reservation_date: "<?php echo $reservation_date; ?>",
                                    head_count: parseInt("<?php echo $head_count; ?>"),
                                    special_request: "<?php echo addslashes($special_request); ?>" || "None"
                                };

                                await window.db.collection("reservations").doc(reservationId).set(newReservation);

                                const newAvailability = {
                                    availability_id: reservationId,
                                    table_id: parseInt("<?php echo $table_id; ?>"),
                                    reservation_date: "<?php echo $reservation_date; ?>",
                                    reservation_time: "<?php echo $reservation_time; ?>",
                                    status: "no"
                                };
                                await window.db.collection("table_availability").doc(reservationId).set(newAvailability);

                            } catch (err) {
                                console.error("Firestore sync failed:", err);
                            }
                        }
                        window.location.href = "reservePage.php?reservation=success&reservation_id=<?php echo $reservation_id; ?>";
                    });
                </script>
            </body>
            </html>
            <?php
            exit();
        } else {
            echo "Error inserting reservation: " . mysqli_error($link);
        }
    } else {
        // Handle the case where the query failed
        echo "Error fetching table capacity: " . mysqli_error($link);
    }
}
?>

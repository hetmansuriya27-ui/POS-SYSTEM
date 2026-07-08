<?php
// reservation.php
require_once '../config.php';
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
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
        } else {
            echo "Error inserting reservation: " . mysqli_error($link);
            exit();
        }
    } else {
        // Handle the case where the query failed
        echo "Error fetching table capacity: " . mysqli_error($link);
        exit();
    }
}
?>

<?php
// Define the variables
$iconClass = 'fa-check-circle'; // This value indicates success; you can adjust it as needed
$cardClass = 'alert-success';   // This value indicates a success message card; adjust as needed
$bgColor = "#D4F4DD";
// Rest of your PHP code ...

?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
    <!-- Firebase SDK Compat CDN -->
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore-compat.js"></script>
    <script src="../js/firebase-config.js"></script>
    <style>
        /* Your custom CSS styles for the success message card here */
        body {
            text-align: center;
            padding: 40px 0;
            background: #EBF0F5;
        }
        h1 {
            color: #88B04B;
            font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
            font-weight: 900;
            font-size: 40px;
            margin-bottom: 10px;
        }
        p {
            color: #404F5E;
            font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
            font-size: 20px;
            margin: 0;
        }
        i.checkmark {
            color: #9ABC66;
            font-size: 100px;
            line-height: 200px;
            margin-left: -15px;
        }
        .card {
            background: white;
            padding: 60px;
            border-radius: 4px;
            box-shadow: 0 2px 3px #C8D0D8;
            display: inline-block;
            margin: 0 auto;
        }
        /* Additional CSS styles based on success/error message */
        .alert-success {
            /* Customize the styles for the success message card */
            background-color: <?php echo $bgColor; ?>;
        }
        .alert-success i {
            color: #5DBE6F; /* Customize the checkmark icon color for success */
        }
        .alert-danger {
            /* Customize the styles for the error message card */
            background-color: #FFA7A7; /* Custom background color for error */
        }
        .alert-danger i {
            color: #F25454; /* Customize the checkmark icon color for error */
        }
        .custom-x {
            color: #F25454; /* Customize the "X" symbol color for error */
            font-size: 100px;
            line-height: 200px;
        }
    </style>
</head>
<body>
    <div class="card <?php echo $cardClass; ?>" style="display: none;">
        <div style="border-radius: 200px; height: 200px; width: 200px; background: #F8FAF5; margin: 0 auto;">
            <?php if ($iconClass === 'fa-check-circle'): ?>
                <i class="checkmark">✓</i>
            <?php else: ?>
                <i class="custom-x" style="font-size: 100px; line-height: 200px;">✘</i>
            <?php endif; ?>
        </div>
        <h1><?php echo ($cardClass === 'alert-success') ? 'Success' : 'Error'; ?></h1>
        <p>Reservation Created Successfully!</p>
    </div>

    <div style="text-align: center; margin-top: 20px;">Redirecting back in <span id="countdown">3</span></div>

    <script>
        // Function to show the message card as a pop-up and start the countdown
        async function showPopup() {
            var messageCard = document.querySelector(".card");
            messageCard.style.display = "block";

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
                } catch(err) {
                    console.error("Firestore sync failed:", err);
                }
            }

            var i = 3;
            var countdownElement = document.getElementById("countdown");
            var countdownInterval = setInterval(function() {
                i--;
                countdownElement.textContent = i;
                if (i <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = "../panel/reservation-panel.html";
                }
            }, 1000); // 1000 milliseconds = 1 second
        }

        // Show the message card and start the countdown when the page is loaded
        window.onload = showPopup;

        // Function to hide the message card after a delay
        function hidePopup() {
            var messageCard = document.querySelector(".card");
            messageCard.style.display = "none";
            // Redirect to another page after hiding the pop-up (adjust the delay as needed)
            setTimeout(function () {
                window.location.href = "../panel/reservation-panel.html"; // Replace with your desired URL
            }, 3000); // 3000 milliseconds = 3 seconds
        }

        // Hide the message card after 3 seconds (adjust the delay as needed)
        setTimeout(hidePopup, 3000);
    </script>
</body>
</html>
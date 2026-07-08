<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';


$sqlmainDishes = "SELECT * FROM Menu WHERE item_category = 'Main Dish' AND status = 'Active' ORDER BY LENGTH(item_id) ASC, item_id ASC; ";
$resultmainDishes = mysqli_query($link, $sqlmainDishes);
$mainDishes = mysqli_fetch_all($resultmainDishes, MYSQLI_ASSOC);

$sqldrinks = "SELECT * FROM Menu WHERE item_category = 'Drinks' AND status = 'Active' ORDER BY LENGTH(item_id) ASC, item_id ASC; ";
$resultdrinks = mysqli_query($link, $sqldrinks);
$drinks = mysqli_fetch_all($resultdrinks, MYSQLI_ASSOC);

$sqlsides = "SELECT * FROM Menu WHERE item_category = 'Side Snacks' AND status = 'Active' ORDER BY LENGTH(item_id) ASC, item_id ASC; ";
$resultsides = mysqli_query($link, $sqlsides);
$sides = mysqli_fetch_all($resultsides, MYSQLI_ASSOC);



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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.theme.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <title>Home</title>
</head>

<body>
    <!-- Header -->

    <section id="header">
        <div class="header container">
            <div class="nav-bar">
                <div class="brand">
                    <a class="nav-link" href="../home/home.php#hero">
                        <h1 class="text-center" style="font-family:Copperplate; color:whitesmoke;"> X HOTEL</h1><span
                            class="sr-only"></span>
                    </a>
                </div>
                <div class="nav-list">
                    <div class="hamburger">
                        <div class="bar"></div>
                    </div>
                    <div class="navbar-container">

                        <div class="navbar">
                            <ul style="display: flex; align-items: center;">
<?php
$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$is_home = strpos($current_url, "localhost/customerSide/home/home.php") !== false || strpos($current_url, "customerSide/home/home.php") !== false;
?>
                                <li><a href="<?= $is_home ? "#hero" : "/customerSide/home/home.php" ?>" data-after="Home">Home</a></li>
                                <li><a href="<?= $is_home ? "#projects" : "/customerSide/home/home.php#projects" ?>" data-after="Projects">Menu</a></li>
                                <li><a href="../CustomerReservation/reservePage.php" data-after="Service">Reservation</a></li>
                                <li><a href="<?= $is_home ? "#about" : "/customerSide/home/home.php#about" ?>" data-after="About">About</a></li>
                                <li><a href="<?= $is_home ? "#contact" : "/customerSide/home/home.php#contact" ?>" data-after="Contact">Contact</a></li>





<?php
// Close the database connection
mysqli_close($link);
?>


                                    </div>
                                </div>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Header -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Enable smooth transitions post render
        setTimeout(() => {
            document.documentElement.classList.remove('preload');
        }, 100);

        // 4-click shortcut on X HOTEL brand logo redirects to the staff login/admin page
        const brandLogo = document.querySelector('.brand .nav-link');
        if (brandLogo) {
            let clickCount = 0;
            let clickTimer = null;

            brandLogo.addEventListener('click', function (e) {
                e.preventDefault();
                clickCount++;

                if (clickCount === 4) {
                    clearTimeout(clickTimer);
                    clickCount = 0;
                    window.location.href = '../../adminSide/StaffLogin/login.php';
                } else {
                    clearTimeout(clickTimer);
                    clickTimer = setTimeout(function () {
                        clickCount = 0;
                        window.location.href = brandLogo.href;
                    }, 300); // 300ms window to detect 4 clicks
                }
            });
        }
    });
</script>
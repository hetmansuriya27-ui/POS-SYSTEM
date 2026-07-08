<?php
session_start();
require_once '../config.php';

$table_id_err = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table_id = intval($_POST['table_id']);
    $customer_name = trim($_POST['customer_name']);
    $customer_mobile = trim($_POST['customer_mobile']);

    // Check if table exists
    $sql = "SELECT * FROM Restaurant_Tables WHERE table_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                // Table exists! Save to session
                $_SESSION['customer_table_id'] = $table_id;
                $_SESSION['customer_name'] = $customer_name;
                $_SESSION['customer_mobile'] = $customer_mobile;
                
                // Clear any existing customer cart to start fresh
                $_SESSION['customer_cart'] = [];
                
                header("Location: customerMenu.php");
                exit();
            } else {
                $table_id_err = "Table number does not exist. Please enter a valid Table ID.";
            }
        } else {
            $table_id_err = "Oops! Something went wrong. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include_once('../components/header.php'); ?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
    body {
        font-family: 'Montserrat', sans-serif !important;
        background-color: #121212;
    }
    .custom-card {
        background: rgba(31, 30, 30, 0.95);
        color: white;
        border: 2px solid crimson;
        border-radius: 12px;
        box-shadow: 0px 0px 20px rgba(220, 20, 60, 0.15);
    }
    .cta-btn {
        background-color: transparent;
        color: white;
        border: 2px solid crimson;
        font-size: 1.6rem;
        text-transform: uppercase;
        letter-spacing: 0.1rem;
        transition: 0.3s ease;
        font-weight: bold;
    }
    .cta-btn:hover {
        background-color: crimson;
        color: white;
        box-shadow: 0px 0px 10px rgba(220, 20, 60, 0.4);
    }
    .form-control-custom {
        background: #121212 !important;
        border: 1px solid #444 !important;
        color: white !important;
        font-size: 1.4rem;
        height: 45px;
    }
    .form-control-custom:focus {
        background: #181818 !important;
        border-color: crimson !important;
        color: white !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 20, 60, 0.25) !important;
    }
    /* Complete Form Control Dark Theme Overrides to prevent White Boxes and Autofill Blocks */
    .form-control, 
    .form-control:focus,
    input, 
    input:focus,
    select,
    select:focus {
        background-color: #121212 !important;
        color: #ffffff !important;
        border: 1px solid #444 !important;
    }
    .form-control:focus, 
    input:focus, 
    select:focus {
        border-color: crimson !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 20, 60, 0.25) !important;
    }
    /* Webkit browser autofill overrides to prevent white background */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px #121212 inset !important;
        -webkit-text-fill-color: #ffffff !important;
        transition: background-color 5000s ease-in-out 0s;
    }
</style>

<div class="container" style="margin-top: 12rem; margin-bottom: 8rem; min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div class="row justify-content-center w-100">
        <div class="col-md-6 col-lg-5">
            <div class="card custom-card p-4">
                <div class="text-center pb-3 mb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                    <h2 class="my-2" style="font-family: 'Montserrat', sans-serif; font-size: 2.6rem; font-weight: 700; letter-spacing: 0.1rem; text-transform: uppercase;">
                        Order <span style="color: crimson;">At Table</span>
                    </h2>
                    <p class="text-muted mb-0" style="font-size: 1.3rem;">Please fill in your details to begin</p>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($table_id_err)): ?>
                        <div class="alert alert-danger" style="background-color: rgba(220, 20, 60, 0.2); color: #f8d7da; border: 1px solid crimson; font-size: 1.3rem;"><?php echo $table_id_err; ?></div>
                    <?php endif; ?>
                    <form action="" method="post">
                        <div class="form-group mb-3">
                            <label class="form-label" style="font-weight: 600; color: #f3f3f3; font-size: 1.3rem;">Table Number:</label>
                            <input type="number" min="1" name="table_id" class="form-control form-control-custom" placeholder="e.g. 1" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label" style="font-weight: 600; color: #f3f3f3; font-size: 1.3rem;">Your Name:</label>
                            <input type="text" name="customer_name" class="form-control form-control-custom" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label" style="font-weight: 600; color: #f3f3f3; font-size: 1.3rem;">Mobile Number:</label>
                            <input type="text" name="customer_mobile" class="form-control form-control-custom" placeholder="e.g. +60123456789" required>
                        </div>
                        <button type="submit" class="btn btn-block cta-btn py-2">Enter Menu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../components/footer.php'); ?>

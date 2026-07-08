<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
 
// Include config file
require_once '../config.php';
 
// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Please enter the new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "Password must have atleast 6 characters.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm the password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
        
    // Check input errors before updating the database
    if(empty($new_password_err) && empty($confirm_password_err)){
        // Prepare an update statement
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
            
            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $_SESSION["id"];
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Password updated successfully. Destroy the session, and redirect to login page
                session_destroy();
                header("location: login.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - X Hotel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #06080c;
            background-image: linear-gradient(rgba(6, 8, 12, 0.8), rgba(6, 8, 12, 0.85)), url('../image/loginBackground.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #f8fafc;
        }

        .reset-container {
            width: 100%;
            max-width: 460px;
            padding: 40px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            transition: border-color 0.3s ease;
        }

        .reset-container:hover {
            border-color: rgba(220, 20, 60, 0.3);
        }

        h1.brand-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 2.6rem;
            letter-spacing: -0.03em;
            color: #f8fafc;
            text-transform: uppercase;
        }

        h1.brand-title span {
            color: crimson;
        }

        h2.page-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 1.8rem;
            color: white;
            text-align: center;
            margin-bottom: 5px;
        }

        p.page-desc {
            font-size: 1.15rem;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group label {
            color: #94a3b8;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Premium inputs */
        .form-control {
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            color: #f8fafc !important;
            padding: 12px 18px !important;
            height: auto !important;
            transition: all 0.2s ease !important;
        }

        .form-control:focus {
            background-color: rgba(0, 0, 0, 0.45) !important;
            border-color: crimson !important;
            box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.25) !important;
            color: white !important;
            outline: none !important;
        }

        .input-group-append button {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-left: none !important;
            background-color: rgba(0, 0, 0, 0.3) !important;
            color: #94a3b8 !important;
            border-top-right-radius: 12px !important;
            border-bottom-right-radius: 12px !important;
            padding-left: 15px;
            padding-right: 15px;
            transition: all 0.2s ease;
        }

        .input-group-append button:hover {
            color: white;
            background-color: rgba(220, 20, 60, 0.2) !important;
        }

        .btn-submit {
            background: crimson;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: bold;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(220, 20, 60, 0.3);
            width: 100%;
        }

        .btn-submit:hover {
            background: #b30e2f;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(220, 20, 60, 0.45);
            color: white;
        }

        .btn-cancel {
            background: transparent;
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-cancel:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
            text-decoration: none;
        }

        .invalid-feedback {
            font-size: 1.1rem;
            margin-top: 5px;
            display: block;
            color: #f87171 !important;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <a class="nav-link text-center p-0" href="../home/home.php#hero">
            <h1 class="brand-title text-center mb-1">X <span>HOTEL</span></h1>
        </a>
        <h2 class="page-title mt-2">Reset Password</h2>
        <p class="page-desc">Please fill out this form to reset your password.</p>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
            <div class="form-group mb-4">
                <label>New Password</label>
                <div class="input-group">
                    <input type="password" id="new_password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" placeholder="••••••••" value="<?php echo $new_password; ?>">
                    <div class="input-group-append">
                        <button class="toggle-password-btn" type="button" data-target="new_password">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
            </div>
            
            <div class="form-group mb-5">
                <label>Confirm Password</label>
                <div class="input-group">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" placeholder="••••••••">
                    <div class="input-group-append">
                        <button class="toggle-password-btn" type="button" data-target="confirm_password">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-submit mb-3">Submit</button>
                <a class="btn btn-cancel d-block text-center" href="profile.php">Cancel</a>
            </div>
        </form>
    </div>    

    <script>
    document.querySelectorAll('.toggle-password-btn').forEach(button => {
        button.addEventListener('click', function() {
            const inputId = this.getAttribute('data-target');
            const input = document.getElementById(inputId);
            if (input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            }
        });
    });
    </script>
</body>
</html>
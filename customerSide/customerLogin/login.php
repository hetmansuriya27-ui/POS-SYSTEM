<?php
// Include your database connection code here
require_once '../config.php';
session_start();

// Define variables for email and password
$email = $password = "";
$email_err = $password_err = "";

// Check if the form was submitted.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Check input errors before checking authentication
    if (empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT * FROM Accounts WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            // Set parameters
            $param_email = $email;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Get the result
                $result = mysqli_stmt_get_result($stmt);

                // Check if a matching record was found.
                if (mysqli_num_rows($result) == 1) {
                    // Fetch the result row
                    $row = mysqli_fetch_assoc($result);

                    
                   // Verify the password
                    if ($password === $row["password"]) {
                        // Password is correct, start a new session and redirect the user to a dashboard or home page.
                        $_SESSION["loggedin"] = true;
                        $_SESSION["email"] = $email;
                        $_SESSION["account_id"] = $row["account_id"];
                        $_SESSION["member_name"] = $row["name"] ?? $email;
                        header("location: ../home/home.php"); // Redirect to the home page
                        exit;
                    } else {
                        // Password is incorrect
                        $password_err = "Invalid password. Please try again.";
                    }


                } else {
                    // No matching records found
                    $email_err = "No account found with this email.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close the statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - X Hotel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
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

        .login-container {
            width: 100%;
            max-width: 480px;
            padding: 40px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            transition: border-color 0.3s ease;
        }

        .login-container:hover {
            border-color: rgba(220, 20, 60, 0.3);
        }

        h1.brand-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 2.8rem;
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
            color: #94a3b8;
            margin-bottom: 30px;
            text-align: center;
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
        .form-control, select {
            background-color: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            color: #f8fafc !important;
            padding: 12px 18px !important;
            height: auto !important;
            transition: all 0.2s ease !important;
        }

        .form-control:focus, select:focus {
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
            font-size: 1.3rem;
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

        .link-text {
            color: #94a3b8;
            font-size: 1.2rem;
            margin-top: 25px;
            text-align: center;
        }

        .link-text a {
            color: crimson;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .link-text a:hover {
            color: #ff3355;
            text-decoration: none;
        }

        .text-danger {
            font-size: 1.1rem;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a class="nav-link text-center p-0" href="../home/home.php#hero">
            <h1 class="brand-title text-center mb-1">X <span>HOTEL</span></h1>
        </a>
        <h2 class="page-title">Artisan Dining</h2>
        
        <form action="login.php" method="post">
            <div class="form-group mb-4">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
                <span class="text-danger"><?php echo $email_err; ?></span>
            </div>

            <div class="form-group mb-4">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <div class="input-group-append">
                        <button type="button" id="togglePassword">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <span class="text-danger"><?php echo $password_err; ?></span>
            </div>
            
            <button class="btn btn-submit mt-2" type="submit" name="submit" value="Login">Log In</button>
        </form>

        <p class="link-text mb-0">Don't have an account? <a href="register.php">Proceed to Register</a></p>
    </div>

    <script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
    </script>
</body>
</html>


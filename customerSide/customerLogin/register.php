<?php
// Include your database connection code here (not shown in this example).
require_once '../config.php';
session_start();

// Define variables and initialize them to empty values
$email = $member_name = $password = $phone_number = "";
$email_err = $member_name_err = $password_err = $phone_number_err = "";

// Check if the form was submitted.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else if (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email. Ex: johndoe@email.com";
    } else {
        $email = trim($_POST["email"]);
    }

    $selectCreatedEmail = "SELECT email from Accounts WHERE email = ?";

    if($stmt = $link->prepare($selectCreatedEmail)){
        $stmt->bind_param("s", $_POST['email']);

        $stmt->execute();

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Email already exists
            $email_err = "This email is already registered.";
        } else {
            $email = trim($_POST["email"]);
        }
        $stmt->close();
    }

    // Validate member name
    if (empty(trim($_POST["member_name"]))) {
        $member_name_err = "Please enter your full name.";
    } else {
        $member_name = trim($_POST["member_name"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate phone number
    if (empty(trim($_POST["phone_number"]))) {
        $phone_number_err = "Please enter your phone number.";
    } else if(!is_numeric(trim($_POST['phone_number']))){
        $phone_number_err = "Only enter numeric values!";
    } else {
        $phone_number = trim($_POST["phone_number"]);
    }

    // Check input errors before inserting into the database
    if (empty($email_err) && empty($member_name_err) && empty($password_err) && empty($phone_number_err)) {
        // Start a transaction
        mysqli_begin_transaction($link);

        // Prepare an insert statement for Accounts table
      // Prepare an insert statement for Accounts table
        // Prepare an insert statement for Accounts table
        $sql_accounts = "INSERT INTO Accounts (name, email, password, phone_number, register_date) VALUES (?, ?, ?, ?, NOW())";
        if ($stmt_accounts = mysqli_prepare($link, $sql_accounts)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt_accounts, "ssss", $param_name, $param_email, $param_password, $param_phone_number);

            // Set parameters
            $param_name = $member_name;
            $param_email = $email;
            // Store the password as plain text (not recommended for production)
            $param_password = $password;
            $param_phone_number = $phone_number;

            // Attempt to execute the prepared statement for Accounts table
            if (mysqli_stmt_execute($stmt_accounts)) {
                // Commit the transaction
                mysqli_commit($link);

                // Registration successful, redirect to the login page
                header("location: register_process.php");
                exit;
            } else {
                // Rollback the transaction if there was an error
                mysqli_rollback($link);
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close the statement for Accounts table
            mysqli_stmt_close($stmt_accounts);
        }
        }
    }


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - X Hotel</title>
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

        .register-container {
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

        .register-container:hover {
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
    <div class="register-container">
        <a class="nav-link text-center p-0" href="../home/home.php#hero">
            <h1 class="brand-title text-center mb-1">X <span>HOTEL</span></h1>
        </a>
        <h2 class="page-title">Create Account</h2>
       
        <form action="register.php" method="post">
            <div class="form-group mb-3">
                <label>Email Address</label>
                <input type="text" name="email" class="form-control" placeholder="name@domain.com" value="<?php echo $email; ?>">
                <span class="text-danger"><?php echo $email_err; ?></span>
            </div>

            <div class="form-group mb-3">
                <label>Full Name</label>
                <input type="text" name="member_name" class="form-control" placeholder="Full Name" value="<?php echo $member_name; ?>">
                <span class="text-danger"><?php echo $member_name_err; ?></span>
            </div>

            <div class="form-group mb-3">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    <div class="input-group-append">
                        <button class="toggle-password-btn" type="button" data-target="password">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <span class="text-danger"><?php echo $password_err; ?></span>
            </div>

            <div class="form-group mb-4">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" placeholder="e.g. 0123456789" value="<?php echo $phone_number; ?>">
                <span class="text-danger"><?php echo $phone_number_err; ?></span>
            </div>

            <button class="btn btn-submit mt-2" type="submit" name="register" value="Register">Register</button>
        </form>

        <p class="link-text mb-0">Already have an account? <a href="../customerLogin/login.php">Proceed to Login</a></p>
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
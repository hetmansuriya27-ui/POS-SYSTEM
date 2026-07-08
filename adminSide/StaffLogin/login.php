<?php 
session_start();
if(isset($_SESSION['logged_account_id'])) {
    header("Location: ../panel/pos-panel.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Login - X Hotel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #0f172a;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.05) 0px, transparent 45%),
                radial-gradient(at 100% 0%, rgba(79, 70, 229, 0.05) 0px, transparent 45%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 40px;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
            transition: all 0.3s ease;
        }

        .login-container:hover {
            border-color: rgba(37, 99, 235, 0.15);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.08), 0 0 30px rgba(37, 99, 235, 0.03);
        }

        h1.brand-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 2.6rem;
            letter-spacing: -0.03em;
            color: #0f172a;
            text-transform: uppercase;
        }

        h1.brand-title span {
            color: crimson;
        }

        h2.page-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 1.6rem;
            color: #475569;
            margin-bottom: 30px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group label {
            color: #475569;
            font-size: 1.05rem;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Premium inputs */
        .form-control {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            color: #0f172a !important;
            padding: 12px 18px !important;
            height: auto !important;
            transition: all 0.2s ease !important;
        }

        .form-control:focus {
            background-color: #ffffff !important;
            border-color: #2563EB !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
            color: #0f172a !important;
            outline: none !important;
        }

        .input-group-append button {
            border: 1px solid #e2e8f0 !important;
            border-left: none !important;
            background-color: #f8fafc !important;
            color: #475569 !important;
            border-top-right-radius: 12px !important;
            border-bottom-right-radius: 12px !important;
            padding-left: 15px;
            padding-right: 15px;
            transition: all 0.2s ease;
        }

        .input-group-append button:hover {
            color: #0f172a;
            background-color: rgba(37, 99, 235, 0.08) !important;
        }

        .btn-submit {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: bold;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            width: 100%;
        }

        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.2);
            color: white;
        }

        .btn-cancel {
            background: transparent;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-cancel:hover {
            color: #0f172a;
            background-color: #f1f5f9;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a class="nav-link text-center p-0" href="../../customerSide/home/home.php"> 
            <h1 class="brand-title mb-1">X <span>HOTEL</span></h1>
        </a>
        <h2 class="page-title">Staff Portal</h2>
        
        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger" style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; border-radius: 12px;">' . $login_err . '</div>';
        }        
        ?>

        <form action="login_process.php" method="post" >
            <div class="form-group mb-4">
                <label for="account_id">Staff Account ID</label>
                <input type="number" id="account_id" name="account_id" placeholder="Enter Account ID" required class="form-control <?php echo (!empty($account_id)) ? 'is-invalid' : ''; ?>" value="<?php echo $account_id; ?>">
                <span class="invalid-feedback"><?php echo $account_id; ?></span>
            </div>
            
            <div class="form-group mb-4">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" placeholder="••••••••" required class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <div class="input-group-append">
                        <button type="button" id="togglePassword">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
                
            <div class="form-group mt-2">
                <button class="btn btn-submit mb-3" type="submit" name="submit" value="Login">Login</button>
                <a href="../../customerSide/home/home.php" class="btn btn-cancel d-block text-center">Back to Site</a>
            </div>
        </form>
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


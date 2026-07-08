<?php
require_once '../posBackend/checkIfLoggedIn.php'; include '../inc/dashHeader.php'; ?>
<?php
require_once '../posBackend/checkIfLoggedIn.php';
// Include config file
require_once "../config.php";

$input_account_id = $account_iderr = $account_id = "";
$input_email = $email_err = $email = "";
$input_register_date = $register_date_err = $register_date = "";
$input_phone_number = $phone_number_err = $phone_number = "";
$input_password = $password_err = $password = "";

?>
<head>
    <meta charset="UTF-8">
    <title>Create New Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .admin-form-container {
            font-family: 'Inter', sans-serif;
            padding-left: 260px; /* Offset for SB Admin Sidebar */
            padding-top: 50px;
            padding-bottom: 50px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .form-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
        }
        .card-header-dark {
            background-color: #1e293b;
            color: #ffffff;
            padding: 30px;
            border-bottom: none;
        }
        .card-header-dark h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            margin: 0;
        }
        .form-label-custom {
            font-weight: 600;
            color: #475569;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-control-custom {
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 12px 16px !important;
            font-size: 1rem !important;
            height: auto !important;
            background-color: #ffffff !important;
            color: #1e293b !important;
            transition: all 0.2s ease !important;
        }
        .form-control-custom:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
            outline: none !important;
        }
        .btn-submit-custom {
            background-color: #1e293b;
            border: none;
            color: #ffffff;
            font-weight: 700;
            padding: 14px;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
        }
        .btn-submit-custom:hover {
            background-color: crimson;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(220, 20, 60, 0.25);
        }
        .btn-cancel-custom {
            background-color: transparent;
            border: 1px solid #cbd5e1;
            color: #64748b;
            font-weight: 600;
            padding: 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-cancel-custom:hover {
            background-color: #f1f5f9;
            color: #334155;
            text-decoration: none;
        }
        .input-group-append button {
            border: 1px solid #cbd5e1 !important;
            border-left: none !important;
            background-color: #ffffff !important;
            color: #64748b !important;
            border-top-right-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
            padding-left: 15px;
            padding-right: 15px;
            transition: all 0.2s ease;
        }
        .input-group-append button:hover {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
        }
    </style>
</head>

<div class="admin-form-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card form-card">
                    <div class="card-header card-header-dark">
                        <h3><i class="fa fa-user-plus mr-2"></i> Create Staff Account</h3>
                        <p class="text-muted mb-0 mt-1" style="color: #94a3b8 !important;">Please fill in Account Information Properly</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="success_create_staff_Account.php">
                            
                            <div class="form-group mb-4">
                                <label for="account_id" class="form-label-custom">Account ID</label>
                                <input min="1" type="number" name="account_id" placeholder="99" class="form-control form-control-custom <?php
require_once '../posBackend/checkIfLoggedIn.php'; echo !$account_idErr ?: 'is-invalid'; ?>" id="account_id" required value="<?php
require_once '../posBackend/checkIfLoggedIn.php'; echo $account_id; ?>">
                                <div class="invalid-feedback">Please provide a valid account ID.</div>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="email" class="form-label-custom">Email Address</label>
                                <input type="text" name="email" placeholder="staff12@xhotel.com" class="form-control form-control-custom <?php
require_once '../posBackend/checkIfLoggedIn.php'; echo !$emailErr ?: 'is-invalid'; ?>" id="email" required value="<?php
require_once '../posBackend/checkIfLoggedIn.php'; echo $email; ?>">
                                <div class="invalid-feedback">Please provide a valid email.</div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="register_date" class="form-label-custom">Register Date</label>
                                <input type="date" name="register_date" id="register_date" required class="form-control form-control-custom <?php
require_once '../posBackend/checkIfLoggedIn.php'; echo !$register_date_err ?: 'is-invalid';?>" value="<?php
require_once '../posBackend/checkIfLoggedIn.php'; echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Please provide a valid register date.</div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="phone_number" class="form-label-custom">Phone Number</label>
                                <input type="text" name="phone_number" placeholder="+60101231234" class="form-control form-control-custom <?php
require_once '../posBackend/checkIfLoggedIn.php'; echo !$phone_numberErr ?: 'is-invalid'; ?>" id="phone_number" required value="<?php
require_once '../posBackend/checkIfLoggedIn.php'; echo $phone_number; ?>">
                                <div class="invalid-feedback">Please provide a valid phone number.</div>
                            </div>

                            <div class="form-group mb-5">
                                <label for="password" class="form-label-custom">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" placeholder="password1234@" id="password" required class="form-control form-control-custom <?php
require_once '../posBackend/checkIfLoggedIn.php'; echo !$password_err ?: 'is-invalid' ; ?>" value="<?php
require_once '../posBackend/checkIfLoggedIn.php'; echo $password; ?>">
                                    <div class="input-group-append">
                                        <button class="toggle-password-btn" type="button" data-target="password">
                                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="invalid-feedback">Please provide a valid password.</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <input type="submit" name="submit" class="btn btn-submit-custom w-100" value="Create Account">
                                </div>
                                <div class="col-md-6">
                                     <a href="../panel/account-panel.html" class="btn btn-cancel-custom w-100 d-block text-center">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

<?php
require_once '../posBackend/checkIfLoggedIn.php'; include '../inc/dashFooter.php'; ?>

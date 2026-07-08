<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php'; // Ensure session is started
require_once "../config.php";

$account_id = $email = $phone_number = $password = "";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $account_id = intval($_GET['id']);

    // Retrieve account details
    $sql = "SELECT * FROM Accounts WHERE account_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $account_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $email = $row['email'];
                $phone_number = $row['phone_number'];
                $password = $row['password'];
            } else {
                echo "Account not found.";
                exit();
            }
        } else {
            echo "Error retrieving account details.";
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_id = intval($_POST["account_id"]);
    $email = trim($_POST["email"]);
    $phone_number = trim($_POST["phone_number"]);
    $password = trim($_POST["password"]);

    // Update account details
    $update_sql = "UPDATE Accounts SET email=?, phone_number=?, password=? WHERE account_id=?";
    if ($stmt = mysqli_prepare($link, $update_sql)) {
        mysqli_stmt_bind_param($stmt, "sssi", $email, $phone_number, $password, $account_id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: ../panel/account-panel.html");
            exit();
        } else {
            echo "Error updating account details: " . mysqli_error($link);
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($link);
}
?>

<?php include '../inc/dashHeader.php'; ?>
<head>
    <meta charset="UTF-8">  
    <title>Update Account</title>
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
    </style>
</head>

<div class="admin-form-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card form-card">
                    <div class="card-header card-header-dark">
                        <h3><i class="fa fa-key mr-2"></i> Update Account</h3>
                        <p class="text-muted mb-0 mt-1" style="color: #94a3b8 !important;">Modify Account Details Properly</p>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="post">
                            <input type="hidden" name="account_id" value="<?php echo $account_id; ?>">
                            
                            <div class="form-group mb-4">
                                <label class="form-label-custom">Account ID</label>
                                <input type="text" class="form-control form-control-custom" value="<?php echo $account_id; ?>" readonly style="background-color: #f1f5f9 !important;">
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="email" class="form-label-custom">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="phone_number" class="form-label-custom">Phone Number</label>
                                <input type="text" name="phone_number" id="phone_number" class="form-control form-control-custom" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                            </div>
                            
                            <div class="form-group mb-5">
                                <label for="password" class="form-label-custom">Password</label>
                                <input type="text" name="password" id="password" class="form-control form-control-custom" value="<?php echo htmlspecialchars($password); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <button class="btn btn-submit-custom w-100" type="submit">Update</button>
                                </div>
                                <div class="col-md-6">
                                    <a class="btn btn-cancel-custom w-100 d-block text-center" href="../panel/account-panel.html">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>

<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php'; // Ensure session is started
?>
<?php
// Include config file
require_once "../config.php";

// Initialize variables for form validation and item data
$item_id = $item_name = $status = $item_category = $item_price = $item_description = "";
$item_id_err = "";

// Check if item_id is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $item_id = $_GET['id'];

    // Retrieve item details based on item_id
    $sql = "SELECT * FROM Menu WHERE item_id = ?";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_item_id);
        $param_item_id = $item_id;
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $item_name = $row['item_name'];
                $item_category = $row['item_category'];
                $item_price = $row['item_price'];
                $item_description = $row['item_description'];
                $status = $row['status'];
            } else {
                echo "Item not found.";
                exit();
            }
        } else {
            echo "Error retrieving item details.";
            exit();
        }
     
    }
}

// Process form submission when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   // echo "Received POST data: <pre>";
//print_r($_POST);
//echo "</pre>";
    // Validate and sanitize input
    $item_name = trim($_POST["item_name"]);
    $item_category = trim($_POST["item_category"]);
    $item_price = floatval($_POST["item_price"]); // Convert to float
    $item_description = $_POST["item_description"];
    $status = $_POST["status"];

    // Update the item in the database
    $update_sql = "UPDATE Menu SET item_name='$item_name', item_category='$item_category', item_price='$item_price', item_description='$item_description', status='$status' WHERE item_id='$item_id'";
    $resultItems = mysqli_query($link, $update_sql);
    
        if ($resultItems) {
            // Item updated successfully
          
           header("Location: ../panel/menu-panel.php");
           echo 'success';
            exit();
        } else {
            echo "Error updating item: ";
        }

       
    }
    
    /*
     $result_tables = mysqli_query($link, $select_query_tables);
                                $resultCheckTables = mysqli_num_rows($result_tables);
                                if ($resultCheckTables > 0) {
                                    while ($row = mysqli_fetch_assoc($result_tables)) {
                                        echo '<option value="' . $row['table_id'] . '">For ' . $row['capacity'] . ' people. (Table Id: ' . $row['table_id'] . ')</option>';
                                    }
                                }  else {
                                    echo '<option disabled>No tables available, please choose another time.</option>';
                                    echo '<script>alert("No reservation tables found for the selected time. Please choose another time.");</script>';
                                }
     */

    // Close the database connection
    

?>

<?php include '../inc/dashHeader.php'; ?>
<head>
    <meta charset="UTF-8">  
    <title>Update Menu Item</title>
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
                        <h3><i class="fa fa-utensils mr-2"></i> Update Menu Item</h3>
                        <p class="text-muted mb-0 mt-1" style="color: #94a3b8 !important;">Modify Menu Item Details Properly</p>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="post">
                            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                            
                            <div class="form-group mb-4">
                                <label class="form-label-custom">Item ID</label>
                                <input type="text" class="form-control form-control-custom" value="<?php echo $item_id; ?>" readonly style="background-color: #f1f5f9 !important;">
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="item_name" class="form-label-custom">Item Name</label>
                                <input type="text" name="item_name" id="item_name" class="form-control form-control-custom" placeholder="Spaghetti" value="<?php echo htmlspecialchars($item_name); ?>" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="status" class="form-label-custom">Status</label>
                                <select name="status" id="status" class="form-control form-control-custom" required>
                                    <option value="Active" <?php echo ($status == 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo ($status == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="item_category" class="form-label-custom">Item Category</label>
                                <input type="text" name="item_category" id="item_category" class="form-control form-control-custom" placeholder="Main Dish/ Side Dish/ Drinks" value="<?php echo htmlspecialchars($item_category); ?>" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="item_price" class="form-label-custom">Item Price (Rs)</label>
                                <input type="number" min="0.01" step="0.01" name="item_price" id="item_price" placeholder="Enter Item Price" class="form-control form-control-custom" value="<?php echo htmlspecialchars($item_price); ?>" required>
                            </div>
                            
                            <div class="form-group mb-5">
                                <label for="item_description" class="form-label-custom">Item Description</label>
                                <textarea name="item_description" id="item_description" placeholder="The dish...." required class="form-control form-control-custom" rows="3"><?php echo htmlspecialchars($item_description); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <button class="btn btn-submit-custom w-100" type="submit" name="submit" value="submit">Update</button>
                                </div>
                                <div class="col-md-6">
                                    <a class="btn btn-cancel-custom w-100 d-block text-center" href="../panel/menu-panel.php">Cancel</a>
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
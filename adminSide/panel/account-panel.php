<?php
session_start(); // Ensure session is started
require_once '../posBackend/checkIfLoggedIn.php';
?>
<?php include '../inc/dashHeader.php'; ?>
    <style>
        .wrapper{ width: 1300px; padding-left: 200px; padding-top: 20px  }
    </style>

<div class="wrapper">
    <div class="container-fluid pt-5 pl-600">
        <div class="row">
            <div class="m-50">
                <div class="mt-5 mb-3">
                    <h2 class="pull-left">Staff Account Details</h2>
                </div>
                
                <div class="mb-3">
                    <form method="POST" action="#">
                        <div class="row">
                            <div class="col-md-6">
                                <input required type="text" id="search" name="search" class="form-control" placeholder="Enter Account ID, Email">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-dark">Search</button>
                            </div>
                            <div class="col" style="text-align: right;" >
                                <a href="../accountCrud/createStaffAccount.php" class="btn btn-warning font-weight-bold"><i class="fa fa-plus"></i> Add Staff</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php
                // Include config file
                require_once "../config.php";

                if (isset($_POST['search'])) {
                    if (!empty($_POST['search'])) {
                        $search = $_POST['search'];

                        $sql = "SELECT A.*, S.staff_name, S.role AS staff_role 
                                FROM Accounts A 
                                LEFT JOIN Staffs S ON A.account_id = S.account_id 
                                WHERE A.email LIKE '%$search%' OR A.account_id LIKE '%$search%' OR S.staff_name LIKE '%$search%' OR A.name LIKE '%$search%' 
                                ORDER BY A.account_id;";
                    } else {
                        // Default query to fetch all accounts
                        $sql = "SELECT A.*, S.staff_name, S.role AS staff_role 
                                FROM Accounts A 
                                LEFT JOIN Staffs S ON A.account_id = S.account_id 
                                ORDER BY A.account_id;";
                    }
                } else {
                    // Default query to fetch all accounts
                    $sql = "SELECT A.*, S.staff_name, S.role AS staff_role 
                            FROM Accounts A 
                            LEFT JOIN Staffs S ON A.account_id = S.account_id 
                            ORDER BY A.account_id;";
                }

                if ($result = mysqli_query($link, $sql)) {
                    if (mysqli_num_rows($result) > 0) {
                        echo '<table class="table table-bordered table-striped">';
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>Account ID</th>";
                        echo "<th>Owner Name</th>";
                        echo "<th>Account Type</th>";
                        echo "<th>Email ID</th>";
                        echo "<th style='width: 160px;'>Contact No</th>";
                        echo "<th style='width: 100px;'>Salary</th>";
                        echo "<th style='width: 220px;'>Password</th>";
                        echo "<th style='width:5em;'>Edit</th>";
                        echo "<th style='width:5em;'>Delete</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        while ($row = mysqli_fetch_array($result)) {
                            $owner = 'N/A';
                            $type = 'Customer';
                            if (!empty($row['staff_name'])) {
                                $owner = $row['staff_name'];
                                $type = 'Staff (' . $row['staff_role'] . ')';
                            } elseif (!empty($row['name'])) {
                                $owner = $row['name'];
                                $type = 'Customer';
                            }
                            
                            echo "<tr>";
                            echo "<td>" . $row['account_id'] . "</td>";
                            echo "<td>" . htmlspecialchars($owner) . "</td>";
                            echo "<td>" . htmlspecialchars($type) . "</td>";
                            $salaryDisplay = ($row['salary'] !== null) ? "Rs " . number_format($row['salary'], 2) : "-";
                            echo "<td>" . $row['email'] . "</td>";
                            echo "<td>" . $row['phone_number'] . "</td>";
                            echo "<td class='font-weight-bold text-success'>" . $salaryDisplay . "</td>";
                            echo "<td>" . $row['password'] . "</td>";
                            echo "<td>";
                            echo '<a href="../accountCrud/updateAccountVerify.php?id=' . $row['account_id'] . '" title="Modify Record" data-toggle="tooltip" onclick="return confirm(\'Admin permission Required!\n\nAre you sure you want to Edit this Account?\')"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
                            echo "</td>";
                            echo "<td>";
                            echo '<a href="../accountCrud/deleteAccountVerify.php?id=' . $row['account_id'] . '" title="Delete Record" data-toggle="tooltip" onclick="return confirm(\'Admin permission Required!\n\nAre you sure you want to delete this Account?\n\nThis will alter other modules related to this Account!\n\')"><span class="fa fa-trash text-black"></span></a>';
                            echo "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody>";
                        echo "</table>";
                        // Free result set
                        mysqli_free_result($result);
                    } else {
                        echo '<div class="alert alert-danger"><em>No records were found.</em></div>';
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close connection
                mysqli_close($link);
                ?>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>

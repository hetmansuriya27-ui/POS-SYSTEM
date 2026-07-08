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
                    <h2 class="pull-left">Items Details</h2>
                    <a href="../menuCrud/createItemVerify.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Add Item</a>
                </div>
                <div class="mb-3">
                    <form method="POST" action="#">
                        <div class="row">
                            <div class="col-md-6">
                                <select name="search" id="search" class="form-control">
                                    <option value="">Select Item Category or Status</option>      
                                    <option value="Main Dish">Main Dish</option>
                                    <option value="Side Snacks">Side Snacks</option>
                                    <option value="Drinks">Drinks</option>                                    
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-dark">Search</button>
                            </div>
                            <div class="col" style="text-align: right;" >
                                <a href="menu-panel.php" class="btn btn-light">Show All</a>
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

                        $sql = "SELECT * FROM Menu WHERE item_category LIKE '%$search%' OR item_name LIKE '%$search%' OR item_id LIKE '%$search%' OR status LIKE '%$search%' ORDER BY CASE WHEN item_category = 'Drinks' THEN 1 WHEN item_category = 'Side Snacks' THEN 2 WHEN item_category = 'Main Dish' THEN 3 ELSE 4 END ASC, LENGTH(item_id) ASC, item_id ASC;";
                    } else {
                        // Default query to fetch all items
                        $sql = "SELECT * FROM Menu ORDER BY CASE WHEN item_category = 'Drinks' THEN 1 WHEN item_category = 'Side Snacks' THEN 2 WHEN item_category = 'Main Dish' THEN 3 ELSE 4 END ASC, LENGTH(item_id) ASC, item_id ASC;";
                    }
                } else {
                    // Default query to fetch all items
                    $sql = "SELECT * FROM Menu ORDER BY CASE WHEN item_category = 'Drinks' THEN 1 WHEN item_category = 'Side Snacks' THEN 2 WHEN item_category = 'Main Dish' THEN 3 ELSE 4 END ASC, LENGTH(item_id) ASC, item_id ASC;";
                }

                if ($result = mysqli_query($link, $sql)) {
                    if (mysqli_num_rows($result) > 0) {
                        echo '<table class="table table-bordered table-striped">';
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>Item ID</th>";
                        echo "<th>Name</th>";
                        echo "<th>Category</th>";
                        echo "<th>Price</th>";
                        echo "<th>Description</th>";
                        echo "<th>Status</th>";
                        echo "<th>Edit</th>";
                        echo "<th>Delete</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        while ($row = mysqli_fetch_array($result)) {
                            echo "<tr>";
                            echo "<td>" . $row['item_id'] . "</td>";
                            echo "<td>" . $row['item_name'] . "</td>";
                            echo "<td>" . $row['item_category'] . "</td>";
                            echo "<td>" . $row['item_price'] . "</td>";
                            echo "<td>" . $row['item_description'] . "</td>";
                            
                            $status = $row['status'] ?? 'Active';
                            $badgeClass = ($status == 'Active') ? 'badge-success' : 'badge-danger';
                            echo "<td><a href='../menuCrud/toggleStatusVerify.php?id=" . $row['item_id'] . "&status=" . $status . "' class='badge " . $badgeClass . "' style='padding: 8px 12px; color: black; text-decoration: none;' onclick='return confirm(\"Are you sure you want to change the status of this item?\")'>" . $status . "</a></td>";
                            
                            echo "<td>";
                            echo '<a href="../menuCrud/updateItemVerify.php?id='. $row['item_id'] .'" title="Modify Record" data-toggle="tooltip"'
                                    . 'onclick="return confirm(\'Admin permission Required!\n\nAre you sure you want to Edit this Item?\')">'
                                     . '<i class="fa fa-pencil" aria-hidden="true"></i></a>';
                            echo "</td>";
                            
                            echo "<td>";
                            echo '<a href="../menuCrud/deleteMenuVerify.php?id='. $row['item_id'] .'" title="Delete Record" data-toggle="tooltip" '
                                    . 'onclick="return confirm(\'Admin permission Required!\n\nAre you sure you want to delete this Item?\n\nThis will alter other modules related to this Item!\n\nYou see unwanted changes in bills.\')"><span class="fa fa-trash text-black"></span></a>';
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

<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php'; // Ensure session is started
?>
<?php  include '../inc/dashHeader.php'?>
<?php
// Include config file
require_once "../config.php";
 
// Item ID is automatically generated sequentially based on item category
?>
<head>
    <meta charset="UTF-8">
    <title>Create New Item</title>
    <style>
        .wrapper{ width: 1300px; padding-left: 200px; padding-top: 80px  }
    </style>
</head>

 <div class="wrapper" >
    <h3>Create New Item</h1>
    <p>Please fill Items Information Properly </p>
    
<form method="POST" action="success_create.php" class="ht-600 w-50">
    
        <!-- Item ID is automatically calculated on submission -->
    
        <div class="form-group"> 
            <label for="item_name">Item Name :</label>
            <input type="text" name="item_name" id="item_name" placeholder="Spaghetti" required class="form-control <?php echo (!empty($itemname_err)) ? 'is-invalid' : ''; ?>" ><br>
            <span class="invalid-feedback"></span>
        </div>
        
        <div class="form-group">
            <label for="status">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Active" selected>Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <span class="invalid-feedback"></span>
        </div><br>
        
        <div class="form-group">
            <label for="item_category">Item Category:</label>
            <select name="item_category" id="item_category" class="form-control <?php echo (!empty($itemcategory_err)) ? 'is-invalid' : ''; ?>" required>
                <option value="">Select Item Category</option>
                <option value="Main Dish">Main Dish</option>
                <option value="Side Snacks">Side Snacks</option>
                <option value="Drinks">Drinks</option>
            </select>
            <span class="invalid-feedback"></span>
        </div><br>
        
        <div class="form-group">
            <label for="item_price">Item Price :</label>
            <input min='0.01' type="number" name="item_price" id="item_price" placeholder="12.34" step="0.01" required class="form-control <?php echo (!empty($itemprice_err)) ? 'is-invalid' : ''; ?>" ><br>
            <span class="invalid-feedback"></span>
        </div>
        
        <div class="form-group">
            <label for="item_description">Item Description :</label>
            <textarea name="item_description" id="item_description" rows="4" placeholder="The dish...." required class="form-control <?php echo (!empty($itemdescription_err)) ? 'is-invalid' : ''; ?>" ></textarea><br>
            <span class="invalid-feedback"></span>
        </div>
        
        <div class="form-group">
            <input type="submit" class="btn btn-dark" value="Create Item">
        </div>    
        
    
 </form>
 </div>
 

<?php
include("connect.php");
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if ID is set
if(isset($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // Fetch product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        // Product not found
        header("Location: products.php");
        exit();
    }
} else {
    // No ID provided
    header("Location: products.php");
    exit();
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_name = $_POST['prod_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $expiry_date = $_POST['expiry_date'];
    
    // Update product
    $stmt = $conn->prepare("UPDATE products SET prod_name = ?, category = ?, quantity = ?, unit_price = ?, expiry_date = ? WHERE product_id = ?");
    $stmt->bind_param("ssiisi", $prod_name, $category, $quantity, $unit_price, $expiry_date, $product_id);
    
    if($stmt->execute()) {
        // Redirect to products page with success message
        header("Location: products.php?update=success");
        exit();
    } else {
        $error = "Error updating product: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="stylesheet" href="supermarket.css">
</head>
<style>
    .form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="date"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-group button {
    padding: 10px 15px;
    background-color: #8a2be2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.cancel-btn {
    display: inline-block;
    padding: 10px 15px;
    background-color: #f44336;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin-left: 10px;
}

.error {
    color: red;
    margin-bottom: 15px;
    padding: 10px;
    background-color: #ffebee;
    border-radius: 4px;
}
</style>
<body>
    
    <div class="layout-container">
        <!-- Side Navigation Bar -->
        <div class="side-nav">
        <!-- Logo/Brand -->
        <div class="brand">
            <h1>Grocery Store</h1>
        </div>
        
        <!-- Navigation Links -->
        <nav class="nav-links">
            <a href="supermarket.php" style="text-decoration: none; color:inherit;">
            <div class="nav-item active" data-id="home">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Dashboard</span>
            </div>
            </a>
            

             <!-- Inventory -->
              <a href="products.php" style="text-decoration: none; color:inherit;">
              <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'):?>
             <div class="nav-item" data-id="inventory">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Inventory</span>
            </div>
            <?php endif; ?>
              </a>
             

            <!-- Purchases -->
             <a href="purchases.php" style="text-decoration: none; color:inherit;">
             <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'):?>
             <div class="nav-item" data-id="purchases">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Purchases</span>
            </div>
            <?php endif; ?>
             </a>
           
   
            <!-- Sales -->
             <a href="add_transaction.php" style="text-decoration: none; color:inherit;">
             <div class="nav-item" data-id="sales">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                </svg>
                <span>Sales</span>
            </div>
             </a>
           
            
            <!-- Transactions History -->
             <a href="account_info.php" style="text-decoration: none; color:inherit;">
             <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'):?>
             <div class="nav-item" data-id="transactions">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <span>Transactions History</span>
            </div>
            <?php endif; ?>
             </a>
            
            
            <!-- Reports -->
            <a href="sales_report.php" style="text-decoration: none; color:inherit;">
            <?php if ($_SESSION['role'] == 'admin'):?>
            <div class="nav-item" data-id="reports">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Reports</span>
            </div>
            <?php endif; ?>
            </a>
           
            
               <!-----Users------------->   
               <a href="manage_users.php" style="text-decoration: none; color:inherit;">
               <?php if ($_SESSION['role'] == 'admin'):?>
               <div class="nav-item" data-id="users">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>User Accounts</span>
            </div>
            <?php endif; ?>
               </a>  
           

               <!------------Settings---------->
               <a href="account_settings.php" style="text-decoration: none; color:inherit;">
               <?php if ($_SESSION['role'] == 'admin'):?>
               <div class="nav-item" data-id="settings">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Settings</span>
            </div>
            <?php endif; ?>
               </a>
          
        </nav>
        
        <!-- Logout Button - Positioned at the bottom -->
         <a href="login.php" style="text-decoration: none; color:inherit;">
         <div class="logout-container">
            <button class="logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </button>
        </div>
         </a>
       
    </div>
        <div class="container">
            <div class="main-content">
                <h2>Edit Product</h2>
                
                <?php if(isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="prod_name">Product Name:</label>
                        <input type="text" id="prod_name" name="prod_name" value="<?php echo $product['item']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <input type="text" id="category" name="category" value="<?php echo $product['category']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="<?php echo $product['quantity']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit_price">Unit Price:</label>
                        <input type="number" step="0.01" id="unit_price" name="unit_price" value="<?php echo $product['unit_price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date:</label>
                        <input type="date" id="expiry_date" name="expiry_date" value="<?php echo $product['expiry_date']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit">Update Product</button>
                        <a href="purchases.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
                <div class="footer">
    <div class="copyright">
        &copy; <?php echo date('Y'); ?> Grocery Store Inventory System. All rights reserved.
    </div>
    <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="#">Help Center</a>
        <a href="#">Contact</a>
    </div>
</div>
            </div>
        </div>
    </div>
    
      <!------------Header section------------->
      <div class="header">
    <div class="search-container">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="search" placeholder="Search..." />
    </div>
    
    <div class="header-actions">
        <div class="notification-bell">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notification-count">3</span>
        </div>
        
        <div class="user-profile">
            <div class="avatar">
               <p>GS</p>
            </div>
            <div class="user-info">
                <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
            </div>
            </div>
    </div>
</div>
</body>
</html>
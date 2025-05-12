<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include("connect.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start transaction
// Start transaction
$conn->begin_transaction();

try {
    // Get form data
    $transaction_type = $_POST['transaction_type'];
    $sales_date = $_POST['transaction_date'];
    $prod_name = $_POST['prod_name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $payment_mode = $_POST['payment_mode'];
    $payment_status = $_POST['payment_status'];
    $total = $quantity * $price;
    
    // Get current user ID if available
    $created_by = isset($_SESSION['role']) ? $_SESSION['role'] : NULL;

    // First insert into sales table
    $stmt_sales = $conn->prepare("INSERT INTO sales (transaction_type, sales_date, prod_name, price, quantity, payment_mode, payment_status, total, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt_sales) {
        throw new Exception("Prepare failed for sales: " . $conn->error);
    }

    // Bind parameters
    $stmt_sales->bind_param("sssdissds", 
        $transaction_type, 
        $sales_date, 
        $prod_name, 
        $price, 
        $quantity, 
        $payment_mode, 
        $payment_status, 
        $total,
        $created_by
    );

    // Execute sales insertion
    if (!$stmt_sales->execute()) {
        throw new Exception("Error inserting into sales: " . $stmt_sales->error);
    }
    
    // Get the last inserted ID from sales to use as transaction_id
    $last_sales_id = $conn->insert_id;
    
    // Prepare the SQL statement for transactions table WITH transaction_id explicitly included
    $stmt_transaction = $conn->prepare("INSERT INTO transactions (transaction_id, transaction_type, transaction_date, prod_name, quantity, total, payment_mode, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt_transaction) {
        throw new Exception("Prepare failed for transactions: " . $conn->error);
    }
    
    // Bind parameters for transactions table, including transaction_id
    $stmt_transaction->bind_param("isssdsss", 
        $last_sales_id,  // Use the sales_id as the transaction_id
        $transaction_type, 
        $sales_date, 
        $prod_name,
        $quantity,
        $total, 
        $payment_mode, 
        $payment_status
    );
    
    // Rest of your code remains the same...
    
    // Execute transaction insertion
    if (!$stmt_transaction->execute()) {
        throw new Exception("Error inserting into transactions: " . $stmt_transaction->error);
    }
    
    // Update product inventory if it's a sale
    if ($transaction_type == 'sale') {
        // Reduce inventory
        $stmt_inventory = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE prod_name = ?");
        if (!$stmt_inventory) {
            throw new Exception("Prepare failed for inventory update: " . $conn->error);
        }
        
        $stmt_inventory->bind_param("is", $quantity, $prod_name);
        
        if (!$stmt_inventory->execute()) {
            throw new Exception("Error updating inventory: " . $stmt_inventory->error);
        }
        
        $stmt_inventory->close();
    } elseif ($transaction_type == 'refund') {
        // Add back to inventory
        $stmt_inventory = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE prod_name = ?");
        if (!$stmt_inventory) {
            throw new Exception("Prepare failed for inventory update: " . $conn->error);
        }
        
        $stmt_inventory->bind_param("is", $quantity, $prod_name);
        
        if (!$stmt_inventory->execute()) {
            throw new Exception("Error updating inventory: " . $stmt_inventory->error);
        }
        
        $stmt_inventory->close();
    }
    
    // If we get here, commit the transaction
    $conn->commit();
    
    // Close statements
    $stmt_sales->close();
    $stmt_transaction->close();
    
    // Redirect to add_transaction.php (no output before this)
    header("Location: add_transaction.php");
    exit();
    
} catch (Exception $e) {
    // An error occurred, rollback the transaction
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

// Close connection
$conn->close();
?>
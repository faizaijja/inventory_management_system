<?php
// This code should be in your add_products.php file

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    include("connect.php");
    
    // Get form data
    $item = isset($_POST['new_item']) && !empty($_POST['new_item']) ? $_POST['new_item'] : $_POST['item'];
    $category = isset($_POST['new_category']) && !empty($_POST['new_category']) ? $_POST['new_category'] : $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $payment_method = $_POST['payment_method'];
    $payment_status = $_POST['payment_status'];
    $expiry_date = $_POST['expiry_date'] ?? null;
    $purchase_date = $_POST['purchase_date'];
    
    // Prepare SQL statement to check if product exists
    $check_stmt = $conn->prepare("SELECT product_id, quantity FROM products WHERE prod_name = ?");
    $check_stmt->bind_param("s", $item);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Product exists, update quantity
        $row = $result->fetch_assoc();
        $product_id = $row['product_id'];
        $new_quantity = $row['quantity'] + $quantity;
        
        $update_stmt = $conn->prepare("UPDATE products SET quantity = ?, unit_price = ?, expiry_date = ? WHERE product_id = ?");
        $update_stmt->bind_param("idsi", $new_quantity, $unit_price, $expiry_date, $product_id);
        
        if ($update_stmt->execute()) {
            // Insert the purchase record
            $purchase_stmt = $conn->prepare("INSERT INTO purchases (product_id, quantity, unit_price, payment_method, payment_status, purchase_date) VALUES (?, ?, ?, ?, ?, ?)");
            $purchase_stmt->bind_param("iidsss", $product_id, $quantity, $unit_price, $payment_method, $payment_status, $purchase_date);
            $purchase_stmt->execute();
            
            header("Location: products.php?success=updated");
            exit();
        } else {
            header("Location: products.php?error=update_failed");
            exit();
        }
    } else {
        // Product doesn't exist, insert new product
        $insert_stmt = $conn->prepare("INSERT INTO products (prod_name, category, quantity, unit_price, expiry_date) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssids", $item, $category, $quantity, $unit_price, $expiry_date);
        
        if ($insert_stmt->execute()) {
            $product_id = $conn->insert_id;
            
            // Insert the purchase record
            $purchase_stmt = $conn->prepare("INSERT INTO purchases (product_id, quantity, unit_price, payment_method, payment_status, purchase_date) VALUES (?, ?, ?, ?, ?, ?)");
            $purchase_stmt->bind_param("iidsss", $product_id, $quantity, $unit_price, $payment_method, $payment_status, $purchase_date);
            $purchase_stmt->execute();
            
            header("Location: products.php?success=added");
            exit();
        } else {
            header("Location: products.php?error=insert_failed");
            exit();
        }
    }
    
    $conn->close();
}
?>
<?php
include("connect.php");

// Check if product ID is provided
if(isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    
    // Prepare DELETE statement
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    // Execute the statement
    if($stmt->execute()) {
        echo "Product deleted successfully";
    } else {
        echo "Error deleting product: " . $conn->error;
    }
    
    $stmt->close();
} else {
    echo "Product ID not provided";
}

$conn->close();
?>
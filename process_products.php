<?php
// Start the session to check for admin privileges
session_start();

// Only allow admin to manage products
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: products.php?error=unauthorized");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    include("connect.php");

    // Add new product
    $prod_name = $_POST['prod_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $restock_date = $_POST['restock_date']; // Current date as restock date
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : null;


    // Check if product already exists
    $checkStmt = $conn->prepare("SELECT * FROM products WHERE prod_name = ? AND category = ?");
    $checkStmt->bind_param("ss", $prod_name, $category);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Product exists - update quantity and price
        $updateStmt = $conn->prepare("UPDATE products SET 
            quantity = quantity + ?,
            unit_price = ?,
            restock_date =?
            WHERE prod_name = ? AND category = ?");

        $updateStmt->bind_param(
            "idsss",
            $quantity,
            $unit_price,
            $restock_date,
            $prod_name,
            $category
        );

        if ($updateStmt->execute()) {
            $_SESSION['success'] = true;
            $_SESSION['success_message'] = 'Product updated successfully!';
            $updateStmt->close();
            header("Location: products.php?success=updated");
            exit();
        } else {
            $error = urlencode($updateStmt->error);
            $updateStmt->close();
            header("Location: products.php?error=$error");
            exit();
        }
    } else {
        // Product doesn't exist - insert new product
        if ($expiry_date) {
            // With expiry date
            $stmt = $conn->prepare("INSERT INTO products 
                (restock_date, prod_name, category, quantity, unit_price, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "sssids",
                $restock_date,
                $prod_name,
                $category,
                $quantity,
                $unit_price,
                $expiry_date
            );
        } else {
            // Without expiry date
            $stmt = $conn->prepare("INSERT INTO products 
                (restock_date, prod_name, category, quantity, unit_price)
                VALUES (?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "sssid",
                $restock_date,
                $prod_name,
                $category,
                $quantity,
                $unit_price
            );
        }

        // Execute insert
        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['success'] = true;
            $_SESSION['success_message'] = 'Product added successfully!';
            header("Location: products.php?success=added");
            exit();
        } else {
            $error = urlencode($stmt->error);
            $stmt->close();
            header("Location: products.php?error=$error");
            exit();
        }
    }
}

// Close the connection if we somehow get here
if (isset($conn)) {
    $conn->close();
}
?>
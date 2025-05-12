<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $conn = mysqli_connect("localhost", "root", "", "supermarket");
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Get and sanitize form data
    $purchase_date   = mysqli_real_escape_string($conn, $_POST['purchase_date']);
    $item            = mysqli_real_escape_string($conn, $_POST['item']);
    $category        = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity        = intval($_POST['quantity']);
    $unit_price      = floatval($_POST['unit_price']);
    $payment_method  = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $payment_status  = mysqli_real_escape_string($conn, $_POST['payment_status']);
    $total_amount    = $quantity * $unit_price;
    
    // Check if expiry_date was submitted
    $expiry_date = null;
    if(isset($_POST['expiry_date']) && !empty($_POST['expiry_date'])) {
        $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    }

    // First, check if the item already exists in the same category
    $checkStmt = $conn->prepare("SELECT purchase_id FROM purchases WHERE item = ? AND category = ?");
    if (!$checkStmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $checkStmt->bind_param("ss", $item, $category);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $checkStmt->close();

    if ($result->num_rows > 0) {
        // Item exists - update the purchase
        $updateStmt = $conn->prepare("UPDATE purchases SET 
            quantity = quantity + ?, 
            total_amount = total_amount + ?, 
            purchase_date = ?,
            unit_price = ?,
            payment_method = ?,
            payment_status = ?
            WHERE item = ? AND category = ?");
            
        if (!$updateStmt) {
            die("Prepare failed: " . $conn->error);
        }
        
        $updateStmt->bind_param("idssssss", 
            $quantity, $total_amount, $purchase_date, 
            $unit_price, $payment_method, $payment_status, 
            $item, $category);
            
        if ($updateStmt->execute()) {
            $updateStmt->close();
            $conn->close();
            header("Location: purchases.php?success=1");
            exit();
        } else {
            $error = urlencode($updateStmt->error);
            $updateStmt->close();
            $conn->close();
            header("Location: purchases.php?error=$error");
            exit();
        }
    } else {
        // Item doesn't exist - insert new purchase
        if ($expiry_date) {
            // With expiry date
            $stmt = $conn->prepare("INSERT INTO purchases 
                (purchase_date, item, category, quantity, unit_price, payment_method, payment_status, total_amount, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssidssds", 
                $purchase_date, $item, $category, 
                $quantity, $unit_price, $payment_method, $payment_status, $total_amount, $expiry_date);
        } else {
            // Without expiry date
            $stmt = $conn->prepare("INSERT INTO purchases 
                (purchase_date, item, category, quantity, unit_price, payment_method, payment_status, total_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssidssd", 
                $purchase_date, $item, $category, 
                $quantity, $unit_price, $payment_method, $payment_status, $total_amount);
        }

        // Execute insert
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: purchases.php?success=1");
            exit();
        } else {
            $error = urlencode($stmt->error);
            $stmt->close();
            $conn->close();
            header("Location: purchases.php?error=$error");
            exit();
        }
    }
}
?>
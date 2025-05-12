<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No invoice ID provided.";
    exit();
}

$invoice_id = $_GET['id'];

// Connect to database
include("connect.php");

// Get the sale information
$sql = "SELECT s.*, u.role 
        FROM sales s 
        LEFT JOIN users u ON s.created_by = u.role
        WHERE s.sales_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Invoice not found.";
    exit();
}

$sale = $result->fetch_assoc();

// Get store information (you might want to store this in a settings table)
$store_name = "Grocery Store";
$store_address = "Rwanda";
$store_phone = "0786061493";
$store_email = "contact@grocerystore.com";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice_id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            padding-bottom: 20px;
            border-bottom: 2px solid black;
            margin-bottom: 20px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            color: black;
            margin: 0;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .invoice-details-col {
            flex-basis: 33%;
        }
        .invoice-details-col p {
            margin: 4px 0;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-table th {
            background-color: lightgray;
            padding: 10px;
            text-align: left;
        }
        .invoice-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .invoice-total {
            text-align: right;
            margin-top: 30px;
            margin-bottom: 40px;
        }
        .invoice-total table {
            width: 300px;
            margin-left: auto;
        }
        .invoice-total table td {
            padding: 5px;
        }
        .total-row {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #c8e682;
        }
        .invoice-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
        }
        .btn-print {
            background-color: linear-gradient(90deg, #e9e6ff 0%, #ffe1f0 100%);
            color: #333;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 20px;
            display: block;
        }
        @media print {
            .btn-print {
                display: none;
            }
            body {
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <button onclick="window.print();" class="btn-print">Print Invoice</button>
        
        <div class="invoice-header">
            <div class="company-info">
                <h2><?php echo $store_name; ?></h2>
                <p><?php echo $store_address; ?></p>
                <p>Phone: <?php echo $store_phone; ?></p>
                <p>Email: <?php echo $store_email; ?></p>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p>Invoice #: <?php echo $invoice_id; ?></p>
                <p>TIN(if any):</p>
                <p>Date: <?php echo date('F j, Y', strtotime($sale['sales_date'])); ?></p>
            </div>
        </div>
        
        <div class="invoice-details">
            <div class="invoice-details-col">
                <h3>Transaction Details</h3>
                <p><strong>Transaction Type:</strong> <?php echo ucfirst($sale['transaction_type']); ?></p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($sale['payment_mode']); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($sale['payment_status']); ?></p>
            </div>
            
            <div class="invoice-details-col">
                <h3>Customer</h3>
                <p><strong>Walk-in Customer</strong></p>
                <p>Thank you for your purchase!</p>
            </div>
            
            <div class="invoice-details-col">
                <h3>Issued By</h3>
                <p><strong>Cashier:</strong> <?php echo isset($sale['username']) ? $sale['username'] : 'System'; ?></p>
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $sale['prod_name']; ?></td>
                    <td><?php echo $sale['quantity']; ?></td>
                    <td>$<?php echo number_format($sale['price'], 2); ?></td>
                    <td>$<?php echo number_format($sale['quantity'] * $sale['price'], 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="invoice-total">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td>$<?php echo number_format($sale['quantity'] * $sale['price'], 2); ?></td>
                </tr>
                <tr>
                    <td>Tax (0%):</td>
                    <td>$0.00</td>
                </tr>
                <tr class="total-row">
                    <td>Total:</td>
                    <td>$<?php echo number_format($sale['quantity'] * $sale['price'], 2); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="invoice-footer">
            <p>Thank you for shopping with us!</p>
            <p>For any inquiries, please contact our customer service.</p>
        </div>
    </div>
</body>
</html>
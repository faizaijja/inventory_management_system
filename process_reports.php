<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Only allow admin and manager roles to access reports data
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Database connection
include("connect.php");

// Get report type and date range parameters
$report = isset($_GET['report']) ? $_GET['report'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default to first day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default to today

// Initialize response array
$data = [];

try {
    // Based on report type, execute the appropriate query
    switch ($report) {
        case 'sales-summary':
            // Daily Sales Summary Report
            $sql = "SELECT 
                    DATE(s.sales_date) AS sale_date,
                    COUNT(s.sales_id) AS total_transactions,
                    SUM(s.quantity * s.price) AS total_sales,
                    s.payment_mode,
                    s.payment_status
                FROM 
                    sales s
                WHERE 
                    s.sales_date BETWEEN ? AND ?
                GROUP BY 
                    DATE(s.sales_date), s.payment_mode, s.payment_status
                ORDER BY 
                    sale_date DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            break;
            
        case 'product-performance':
            // Product Performance Report
            $sql = "SELECT 
                    p.prod_name,
                    SUM(s.quantity) AS total_quantity_sold,
                    SUM(s.quantity * s.price) AS total_revenue,
                    COUNT(s.sales_id) AS number_of_sales
                FROM 
                    sales s
                JOIN 
                    products p ON s.prod_name = p.prod_name
                WHERE 
                    s.transaction_type = 'sale' AND
                    s.sales_date BETWEEN ? AND ?
                GROUP BY 
                    p.prod_name
                ORDER BY 
                    total_revenue DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            break;
            
        case 'payment-analysis':
            // Payment Method Analysis
            $sql = "SELECT 
                    s.payment_mode,
                    COUNT(s.sales_id) AS transaction_count,
                    SUM(s.quantity * s.price) AS total_amount
                FROM 
                    sales s
                WHERE 
                    s.sales_date BETWEEN ? AND ?
                GROUP BY 
                    s.payment_mode
                ORDER BY
                    total_amount DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            break;
            
            case 'user-performance':
                // Sales by User Performance Report
                $sql = "SELECT 
                        s.added_by AS user,
                        COUNT(s.sales_id) AS transaction_count,
                        SUM(s.quantity * s.price) AS total_sales
                    FROM 
                        sales s
                    WHERE 
                        s.sales_date BETWEEN ? AND ?
                    GROUP BY 
                        s.added_by
                    ORDER BY 
                        total_sales DESC";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $startDate, $endDate);
                break;
                
            case 'inventory-levels':
                // Current Inventory Levels
                $sql = "SELECT 
                        p.prod_name,
                        p.quantity AS current_stock,
                        p.reorder_level,
                        p.last_restock_date
                    FROM 
                        products p
                    ORDER BY 
                        (CASE WHEN p.quantity <= p.reorder_level THEN 0 ELSE 1 END), 
                        p.quantity ASC";
                
                $stmt = $conn->prepare($sql);
                break;
                
            default:
                throw new Exception("Invalid report type specified");
        }
        
        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch all data into the array
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        // Return the data as JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (Exception $e) {
        // Return error message
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } finally {
        // Close the database connection
        if (isset($conn)) {
            $conn->close();
        }
    }
    ?>
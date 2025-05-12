<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "supermarket"); // Replace with your actual credentials
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch purchase data
$purchaseQuery = "SELECT * FROM purchases ORDER BY purchase_date DESC";
$purchaseResult = mysqli_query($conn, $purchaseQuery);

$purchaseData = array();
if ($purchaseResult) {
    while ($row = mysqli_fetch_assoc($purchaseResult)) {
        $purchaseData[] = $row;
    }
}

$notificationCount = getUnreadNotificationCount($_SESSION['user_id']);
$userNotifications = getUserNotifications($_SESSION['user_id']);

// Convert to JSON for JavaScript
$purchaseDataJson = json_encode($purchaseData);

?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $_SESSION['theme']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link rel="stylesheet" href="supermarket.css">
    <script src="notifications.js"></script>
    <style>
        /* Search Box Styling */
        .search-box {
            display: flex;
            align-items: center;
            margin-left: 10px;
        }

        .search-box input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            width: 200px;
            background-color: white;
        }

        .search-box .search-btn {
            padding: 8px 12px;
            background-color: #f0f0ff;
            color: #333;
            font-weight: bold;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-box .search-btn:hover {
            background-color: #e0e0ff;
        }

        /* Pagination Styling */
        .pagination-container {
            text-align: center;
            margin: 20px 0;
            clear: both;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .pagination-container a,
        .pagination-container .pagination-nav {
            padding: 6px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .pagination-container a:hover {
            background-color: #f2f2f2;
        }

        .pagination-container a.active {
            background-color: #f0f0ff;
            color: black;
            font-weight: bold;
        }

        .pagination-container .pagination-nav {
            background-color: #f0f0ff;
            font-weight: bold;
        }

        .pagination-container .pagination-nav:hover {
            background-color: #e0e0ff;
        }

        .pagination-container .disabled {
            opacity: 0.5;
            cursor: default;
            pointer-events: none;
        }

        /* Table Container Styling */
        .inventory {
            width: 100%;
            margin-bottom: 20px;
            overflow-x: auto;
            /* Enable horizontal scrolling for small screens */
        }

        /* Filter Container Styling */
        .add-purchase {
            padding: 8px;
            border: 1px solid #ddd;
            background-color: #8a2be2;
            color: white;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }

        .filter-container h2 {
            margin: 0;
            flex-grow: 1;
        }

        .filter-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-container select {
            padding: 8px;
            border: 1px solid #ddd;
            background-color: #f0f0ff;
            color: #333;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-container select:hover {
            background-color: #e0e0ff;
        }

        /* Action Buttons Styling */
        .product-display td button {
            padding: 6px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
            transition: all 0.2s ease;
        }

        .edit-btn {
            background-color: #9370db;
        }

        .delete-btn {
            background-color: #f44336;
        }

        .product-display td button:hover {
            opacity: 0.8;
        }

        /* Empty table message styling */
        .no-products {
            text-align: center;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-style: italic;
            color: #666;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 700px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            gap: 15px;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-checkout {
            background-color: #8a2be2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            display: block;
            margin: 20px auto 0;
        }

        .btn-checkout:hover {
            background-color: #7b24d3;
        }

        .purchase-table {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
            width: 98%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #f0f0ff;
            color: black;
            font-weight: bolder;
        }

        /* Title and add button styling */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .table-header h2 {
            margin: 0;
        }

        .btn-add {
            background-color: #9370db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .btn-add:hover {
            background-color: #8a2be2;
        }

        /* Button styling */
        .btn-purchase {
            background-color: #9370db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-purchase:hover {
            background-color: lightpurple;
            /* Add hover effect */
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            gap: 10px;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1000;
            opacity: 1;
            transition: opacity 0.6s ease;
            display: block;
        }

        .btn-checkout {
            background-color: #f0f0ff;
            color: purple;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        .total-amount {
            margin-left: 950px;
            font-weight: bold;
        }

        /* Notification Bell Container */
        .notification-bell-container {
            position: relative;
            display: inline-block;
        }

        /* Notification Bell */
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-bell svg {
            width: 24px;
            height: 24px;
            color: #555;
            transition: color 0.3s;
        }

        .notification-bell:hover svg {
            color: #333;
        }

        /* Notification Count Badge */
        .notification-count {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #ff4b4b;
            color: white;
            font-size: 12px;
            height: 18px;
            width: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
        }

        /* Notifications Dropdown */
        .notifications-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            max-height: 400px;
            display: none;
            overflow: hidden;
            border: 1px solid #eee;
        }

        /* Show dropdown when notification bell is clicked */
        .notification-bell-container.active .notifications-dropdown {
            display: block;
        }

        /* Notifications Header */
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
        }

        .notifications-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .mark-all-read {
            color: #4a90e2;
            text-decoration: none;
            font-size: 13px;
        }

        .mark-all-read:hover {
            text-decoration: underline;
        }

        /* Notifications List */
        .notifications-list {
            max-height: 300px;
            overflow-y: auto;
        }

        /* Individual Notification Item */
        .notification-item {
            display: flex;
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f5;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f9f9f9;
        }

        .notification-item.unread {
            background-color: #f0f7ff;
        }

        .notification-item.unread:hover {
            background-color: #e6f1ff;
        }

        /* Notification Icon */
        .notification-icon {
            margin-right: 12px;
            display: flex;
            align-items: flex-start;
        }

        .notification-icon svg {
            width: 18px;
            height: 18px;
            color: #666;
        }

        /* Notification Content */
        .notification-content {
            flex: 1;
        }

        .notification-content p {
            margin: 0 0 4px 0;
            font-size: 14px;
            color: #333;
        }

        .notification-time {
            font-size: 12px;
            color: #999;
        }

        /* No Notifications State */
        .no-notifications {
            padding: 24px 16px;
            text-align: center;
            color: #888;
        }

        /* Notifications Footer */
        .notifications-footer {
            padding: 12px 16px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .notifications-footer a {
            color: #4a90e2;
            text-decoration: none;
            font-size: 14px;
        }

        .notifications-footer a:hover {
            text-decoration: underline;
        }
    </style>

<body>
    <!--------------
    <div class="nav-container">
        <h3>GROCERY STORE</h3>
    </div>
    ----------------->
    <div class="layout-container">
        <!-- Side Navigation Bar -->
        <div class="side-nav">
            <!-- Logo/Brand -->
            <div class="brand">
                <h1>Grocery Store</h1>
                <form method="post" style="display: inline;">
                    <button type="submit" name="toggle_theme" class="theme-toggle" title="Toggle Theme">
                        <?php if ($_SESSION['theme'] === 'light'): ?>
                            <!-- Moon icon for dark mode -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        <?php else: ?>
                            <!-- Sun icon for light mode -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="5"></circle>
                                <line x1="12" y1="1" x2="12" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="23"></line>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                <line x1="1" y1="12" x2="3" y2="12"></line>
                                <line x1="21" y1="12" x2="23" y2="12"></line>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                            </svg>
                        <?php endif; ?>
                    </button>
                </form>
            </div>

            <!-- Navigation Links -->
            <nav class="nav-links">
                <a href="supermarket.php" style="text-decoration: none; color:inherit;">
                    <div class="nav-item" data-id="home">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </a>


                <!-- Inventory -->
                <a href="products.php" style="text-decoration: none; color:inherit;">
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                        <div class="nav-item active" data-id="inventory">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Inventory</span>
                        </div>
                    <?php endif; ?>
                </a>


                <!-- Purchases -->
                <a href="purchases.php" style="text-decoration: none; color:inherit;">
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                        <div class="nav-item" data-id="purchases">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        <span>Sales</span>
                    </div>
                </a>


                <!-- Transactions History -->
                <a href="account_info.php" style="text-decoration: none; color:inherit;">
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                        <div class="nav-item" data-id="transactions">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <div class="nav-item" data-id="reports">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <div class="nav-item" data-id="users">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <div class="nav-item" data-id="settings">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                            </path>
                        </svg>
                        <span>Settings</span>
                    </div>
                </a>

            </nav>

            <!-- Logout Button - Positioned at the bottom -->
            <a href="login.php" style="text-decoration: none; color:inherit;">
                <div class="logout-container">
                    <button class="logout-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <?php if (isset($_SESSION['success']) && $_SESSION['success'] === true): ?>
                    <div class="success-message" id="success-alert">
                        <?php echo $_SESSION['success_message']; ?>
                    </div>

                    <?php
                    // Clear the message so it doesn't show again on page refresh
                    unset($_SESSION['success']);
                    unset($_SESSION['success_message']);
                    ?>
                <?php endif; ?>
                <div class="inventory">
                    <form method="GET" id="filterForm">
                        <div class="filter-container">
                            <h2>Inventory</h2>
                            <div class="filter-controls">
                                <div class="addition">
                                    <!-- Changed from button to input type button with id for JavaScript access -->
                                    <input type="button" id="purchaseBtn" class="add-purchase" value="Add Item">
                                </div>

                                <select name="category" id="categoryFilter"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Categories</option>

                                    <?php
                                    $sql = "SELECT DISTINCT category FROM products";
                                    $result = $conn->query($sql);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $selected = (isset($_GET['category']) && $_GET['category'] == $row['category']) ? 'selected' : '';
                                            echo '<option value ="' . $row['category'] . '" ' . $selected . '>' . $row['category'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>

                                <!-- Search Input and Button -->
                                <div class="search-box">
                                    <input type="text" name="search" id="searchInput" placeholder="Search products..."
                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    <button type="submit" class="search-btn">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div style="overflow-x: auto;">
                        <table class="product-display">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Restock Date</th>
                                    <th>Expiry Date</th>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Filter category
                                $categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
                                $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

                                // Arrange pages 
                                $limit = 10;
                                $page = isset($_GET['page']) ? $_GET['page'] : 1;
                                $start_from = ($page - 1) * $limit;

                                // SQL query with filters
                                if (!empty($categoryFilter) && !empty($searchTerm)) {
                                    // Both category and search term provided
                                    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND prod_name LIKE ? LIMIT ?, ?");
                                    $searchParam = "%$searchTerm%";
                                    $stmt->bind_param("ssii", $categoryFilter, $searchParam, $start_from, $limit);
                                } elseif (!empty($categoryFilter)) {
                                    // Only category filter provided
                                    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT ?, ?");
                                    $stmt->bind_param("sii", $categoryFilter, $start_from, $limit);
                                } elseif (!empty($searchTerm)) {
                                    // Only search term provided
                                    $stmt = $conn->prepare("SELECT * FROM products WHERE prod_name LIKE ? LIMIT ?, ?");
                                    $searchParam = "%$searchTerm%";
                                    $stmt->bind_param("sii", $searchParam, $start_from, $limit);
                                } else {
                                    // No filters provided
                                    $stmt = $conn->prepare("SELECT * FROM products LIMIT ?, ?");
                                    $stmt->bind_param("ii", $start_from, $limit);
                                }

                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>" . $row['product_id'] . "</td>
                                                <td>" . $row['prod_name'] . "</td>
                                                <td>" . $row['category'] . "</td>
                                                <td>" . $row['quantity'] . "</td>
                                                <td>$" . $row['unit_price'] . "</td>
                                                 <td>" . $row['restock_date'] . "</td>
                                                <td>" . $row['expiry_date'] . "</td>";

                                        // Add action column only for admin users
                                        if ($_SESSION['role'] == 'admin') {
                                            echo "<td>
                                                   <div class='action-buttons'>
                                                        <button class='edit-btn' data-id='" . $row['product_id'] . "'>Edit</button>
                                                        <button class='delete-btn' data-id='" . $row['product_id'] . "'>Delete</button>
                                                    </div>
                                                </td>";
                                        }

                                        echo "</tr>";
                                    }
                                } else {
                                    $colspan = ($_SESSION['role'] == 'admin') ? '11' : '10';
                                    echo "<tr><td colspan='" . $colspan . "' class='no-products'>No products available.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-----display pagination-------->
                <div class="pagination-container">
                    <?php
                    // Recalculate total_pages since it's in a new PHP block
                    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
                    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

                    if (!empty($categoryFilter) && !empty($searchTerm)) {
                        $stmt_total = $conn->prepare("SELECT COUNT(*) FROM products WHERE category = ? AND prod_name LIKE ?");
                        $searchParam = "%$searchTerm%";
                        $stmt_total->bind_param("ss", $categoryFilter, $searchParam);
                    } elseif (!empty($categoryFilter)) {
                        $stmt_total = $conn->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
                        $stmt_total->bind_param("s", $categoryFilter);
                    } elseif (!empty($searchTerm)) {
                        $stmt_total = $conn->prepare("SELECT COUNT(*) FROM products WHERE prod_name LIKE ?");
                        $searchParam = "%$searchTerm%";
                        $stmt_total->bind_param("s", $searchParam);
                    } else {
                        $stmt_total = $conn->prepare("SELECT COUNT(*) FROM products");
                    }

                    $stmt_total->execute();
                    $stmt_total->bind_result($total_records);
                    $stmt_total->fetch();
                    $stmt_total->close();
                    $total_pages = ceil($total_records / $limit);

                    // Previous button code (was already in the file)
                    if ($page > 1) {
                        echo "<a href='replica.php?page=" . ($page - 1) . "&category=" . $categoryFilter . "&search=" . urlencode($searchTerm) . "' class='pagination-nav'>&laquo; Previous</a> ";
                    }

                    // Page numbers
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active = ($i == $page) ? "active" : "";
                        echo "<a href='replica.php?page=" . $i . "&category=" . $categoryFilter . "&search=" . urlencode($searchTerm) . "' class='" . $active . "'>" . $i . "</a> ";
                    }

                    // Next button
                    if ($page < $total_pages) {
                        echo "<a href='replica.php?page=" . ($page + 1) . "&category=" . $categoryFilter . "&search=" . urlencode($searchTerm) . "' class='pagination-nav'>Next &raquo;</a>";
                    } else {
                        echo "<span class='pagination-nav disabled'>Next &raquo;</span>";
                    }
                    ?>
                </div>

            </div>
        </div>

    </div>

    <!------------Header section------------->
    <div class="header">
        <div class="search-container">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>

        <div class="header-actions">
            <div class="notification-bell-container">
                <div class="notification-bell" id="notification-bell">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-count">
                        <?php echo $notificationCount; ?>
                    </span>
                </div>

                <!-- Dropdown for notifications -->
                <div class="notifications-dropdown">
                    <div class="notifications-header">
                        <h4>Notifications</h4>
                        <a href="#" class="mark-all-read">Mark all as read</a>
                    </div>
                    <div class="notifications-list">
                        <?php if (count($userNotifications) > 0): ?>
                            <?php foreach ($userNotifications as $notification): ?>
                                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                    <div class="notification-icon">
                                        <?php if ($notification['type'] == 'low_stock'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path
                                                    d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34">
                                                </path>
                                                <path d="M14 3v4a2 2 0 0 0 2 2h4"></path>
                                                <path d="M16 16L22 10"></path>
                                            </svg>
                                        <?php elseif ($notification['type'] == 'order'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <circle cx="9" cy="21" r="1"></circle>
                                                <circle cx="20" cy="21" r="1"></circle>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-content">
                                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <span
                                            class="notification-time"><?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-notifications">
                                <p>No notifications yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="notifications-footer">
                        <a href="notifications.php">View all notifications</a>
                    </div>
                </div>
            </div>
            <div class="user-profile">
                <div class="avatar">
                    <p>GS</p>
                </div>
                <div class="user-info">
                    <span class="user-role">
                        <?php echo ucfirst($_SESSION['role']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Modal -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Item</h2>
            <form id="purchaseForm" method="POST" action="process_products.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="prod_name">Item:</label>
                        <select id="prod_name" name="prod_name" required>
                            <option value="">Select Item</option>
                            <?php
                            $sql = "SELECT * FROM products";
                            $cat_result = $conn->query($sql);
                            while ($cat_row = $cat_result->fetch_assoc()) {
                                ?>
                                <option value="<?php echo htmlspecialchars($cat_row['prod_name']); ?>"
                                    data-price="<?php echo htmlspecialchars($cat_row['unit_price']); ?>">
                                    <?php echo htmlspecialchars($cat_row['prod_name']); ?>
                                </option>
                            <?php } ?>

                            <option value="Other">Other</option>
                        </select>
                        <div id="new_item_container" style="display: none; margin-top: 10px;">
                            <label for="new_item">New Item Name:</label>
                            <input type="text" id="new_item" name="new_item">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php
                            $sql = "SELECT DISTINCT category FROM products";
                            $cat_result = $conn->query($sql);
                            if ($cat_result->num_rows > 0) {
                                while ($cat_row = $cat_result->fetch_assoc()) {
                                    echo '<option value="' . $cat_row['category'] . '">' . $cat_row['category'] . '</option>';
                                }
                            }
                            ?>
                            <option value="Other">Other</option>
                        </select>
                        <div id="new_category_container" style="display: none; margin-top: 10px;">
                            <label for="new_category">New Category Name:</label>
                            <input type="text" id="new_category" name="new_category">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="unit_price">Unit Price (rwf):</label>
                        <input type="number" id="unit_price" name="unit_price" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Momo</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Check">Check</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_status">Payment Status:</label>
                        <select id="payment_status" name="payment_status" required>
                            <option value="">Select Status</option>
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date:</label>
                        <input type="date" id="expiry_date" name="expiry_date">
                    </div>
                    <div class="form-group">
                        <label for="restock_date">Restock Date:</label>
                        <input type="date" id="restock_date" name="restock_date" required
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button type="submit" class="btn-checkout">Save item</button>
            </form>
        </div>
    </div>

    <!-- Edit Purchase Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Purchase</h2>
            <form id="editForm" method="POST" action="edit_product.php">
                <input type="hidden" id="edit_id" name="purchase_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_item">Item:</label>
                        <input type="text" id="edit_item" name="item" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_category">Category:</label>
                        <select id="edit_category" name="category" required>
                            <option value="">Select Category</option>
                            <?php
                            // Reset the result pointer
                            $cat_result->data_seek(0);
                            if ($cat_result->num_rows > 0) {
                                while ($cat_row = $cat_result->fetch_assoc()) {
                                    echo '<option value="' . $cat_row['category'] . '">' . $cat_row['category'] . '</option>';
                                }
                            }
                            ?>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_quantity">Quantity:</label>
                        <input type="number" id="edit_quantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_unit_price">Unit Price ($):</label>
                        <input type="number" id="edit_unit_price" name="unit_price" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_payment_method">Payment Method:</label>
                        <select id="edit_payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Check">Check</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_payment_status">Payment Status:</label>
                        <select id="edit_payment_status" name="payment_status" required>
                            <option value="">Select Status</option>
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_expiry_date">Expiry Date:</label>
                        <input type="date" id="edit_expiry_date" name="expiry_date">
                    </div>
                    <div class="form-group">
                        <label for="edit_restock_date">Restock Date:</label>
                        <input type="date" id="edit_restock_date" name="restock_date" required>
                    </div>
                </div>
                <button type="submit" class="btn-checkout">Update Product</button>
            </form>
        </div>
    </div>

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

    <?php

    function getDbConnection()
    {
        // Update with your actual database credentials
        $host = "localhost";
        $dbname = "supermarket";
        $username = "root";
        $password = "";

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            // For production, log this error instead of displaying
            die("Connection failed: " . $e->getMessage());
        }
    }

    // Get pending notifications for the user
    function getUserNotifications($userId)
    {
        $conn = getDbConnection();

        $query = "SELECT n.id, n.type, n.message, n.created_at, n.is_read
              FROM notifications n
              WHERE n.user_id = ? OR n.user_id IS NULL
              ORDER BY n.is_read ASC, n.created_at DESC
              LIMIT 5";

        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get unread notification count
    function getUnreadNotificationCount($userId)
    {
        $conn = getDbConnection();

        $query = "SELECT COUNT(*) FROM notifications 
              WHERE (user_id = ? OR user_id IS NULL) AND message = 0";

        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);

        return $stmt->fetchColumn();
    }
    ?>

    <!-- JavaScript for modal functionality -->
    <script>
        // Get the modals
        var purchaseModal = document.getElementById("purchaseModal");
        var editModal = document.getElementById("editModal");

        // Get the button that opens the purchase modal
        var purchaseBtn = document.getElementById("purchaseBtn");


        // Get the <span> elements that close the modals
        var purchaseSpan = purchaseModal.getElementsByClassName("close")[0];
        var editSpan = editModal.getElementsByClassName("close")[0];

        // When the user clicks the button, open the purchase modal
        purchaseBtn.onclick = function () {
            purchaseModal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modals
        purchaseSpan.onclick = function () {
            purchaseModal.style.display = "none";
        }

        editSpan.onclick = function () {
            editModal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modals, close them
        window.onclick = function (event) {
            if (event.target == purchaseModal) {
                purchaseModal.style.display = "none";
            }
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
        }

        // Get all edit buttons
        const editButtons = document.querySelectorAll('.edit-btn');
        // Get all delete buttons
        const deleteButtons = document.querySelectorAll('.delete-btn');

        // Add click event listeners to edit buttons
        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.getAttribute('data-id');
                editProduct(productId);
            });
        });

        // Add click event listeners to delete buttons
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.getAttribute('data-id');
                deleteProduct(productId);
            });
        });

        // Function to handle edit operation
        function editProduct(productId) {
            // Redirect to edit page with product ID
            window.location.href = 'edit_product.php?id=' + productId;
        }

        // Function to handle delete operation
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                // Use AJAX to send delete request
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_product.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (this.status === 200) {
                        // Reload the page or remove the row from the table
                        location.reload();
                    }
                };
                xhr.send('product_id=' + productId);
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            // Get references to the select element and the price input field
            const productSelect = document.getElementById('prod_name');
            const priceField = document.getElementById('unit_price'); // Assuming you have a price input with id="price"


            // Add event listener for when the selection changes
            productSelect.addEventListener('change', function () {
                // Get the selected option
                const selectedOption = this.options[this.selectedIndex];

                // Check if an item is selected (not the empty option)
                if (this.value !== '') {
                    // Get the price from the data-price attribute
                    const price = selectedOption.getAttribute('data-price');

                    // If it's "Other", you might want to clear the price field or handle differently
                    if (this.value === 'Other') {
                        priceField.value = ''; // Clear the price field
                        priceField.removeAttribute('readonly'); // Make it editable
                    } else {
                        // Update the price field with the selected item's price
                        priceField.value = price;
                        priceField.setAttribute('readonly', 'readonly'); // Optional: make it read-only
                    }
                } else {
                    // Clear price if nothing is selected
                    priceField.value = '';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            var successAlert = document.getElementById('success-alert');
            successAlert.style.display = 'block';
            successAlert.style.opacity = '1';

            // Auto-hide the message after 5 seconds
            setTimeout(function () {
                successAlert.style.opacity = '0';
                setTimeout(function () {
                    successAlert.style.display = 'none';
                }, 600);
            }, 5000);
        });


        document.addEventListener('DOMContentLoaded', function () {

            initializeNotifications();
        });

        function initializeNotifications() {
            const notificationBell = document.querySelector('.notification-bell');
            const notificationContainer = document.querySelector('.notification-bell-container');

            // Toggle dropdown when clicking the bell
            notificationBell.addEventListener('click', function (e) {
                e.stopPropagation();
                notificationContainer.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (!notificationContainer.contains(e.target)) {
                    notificationContainer.classList.remove('active');
                }
            });
            // Mark all as read functionality
            const markAllReadBtn = document.querySelector('.mark-all-read');
            markAllReadBtn.addEventListener('click', function (e) {
                e.preventDefault();

                // Get all unread notifications
                const unreadNotifications = document.querySelectorAll('.notification-item.unread');

                // Remove unread class
                unreadNotifications.forEach(function (notification) {
                    notification.classList.remove('unread');
                });
            });
        }

        function markNotificationAsRead(notificationId) {
            // Mark specific notification as read
            fetch('api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: notificationId })
            })
                .then(response => response.json())
                .then(data => {
                    // Update UI
                    const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                    if (notificationItem) {
                        notificationItem.classList.remove('unread');
                    }

                    // Update count
                    updateNotificationCount();
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
        }

        function markAllNotificationsAsRead() {
            // Mark all notifications as read
            fetch('api/mark_all_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => response.json())
                .then(data => {
                    // Update UI - remove unread class from all items
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });

                    // Update notification count to zero
                    const countElement = document.querySelector('.notification-count');
                    if (countElement) {
                        countElement.textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);
                });
        }

        function updateNotificationCount() {
            // Fetch updated count
            fetch('api/notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const countElement = document.querySelector('.notification-count');
                    if (countElement) {
                        countElement.textContent = data.count;
                    }
                })
                .catch(error => {
                    console.error('Error updating notification count:', error);
                });
        }

        document.addEventListener("DOMContentLoaded", function () {
            const bell = document.getElementById("notification-bell");
            const container = bell.closest(".notification-bell-container");

            if (bell && container) {
                bell.addEventListener("click", function () {
                    container.classList.toggle("active");
                });
            }
        });

    </script>

</body>

</html>
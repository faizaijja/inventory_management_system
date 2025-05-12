<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Only allow admin and manager roles to access reports
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'manager') {
    header("Location: supermarket.php");
    exit();
}

$notificationCount = getUnreadNotificationCount($_SESSION['user_id']);
$userNotifications = getUserNotifications($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $_SESSION['theme']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="supermarket.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <title>Sales & Transaction Reports</title>
    <style>
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #9370db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #b6d072;
        }

        /* Improved table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        table th {
            background-color: #f0f0ff;
            color: #333;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        /* Zebra striping for tables */
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tbody tr:hover {
            background-color: #f1f1f1;
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

        /* Improved card styling */
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        }

        /* Section headers */
        .section-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Improved filter bar */
        .filter-bar {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-bar .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }

        /* Improved buttons */
        #apply-filter button {
            background-color: #9370db;
            color: #333;
            font-weight: bold;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            background-color: #b6d072;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        /* Improved export button */
        .report-actions {
            display: inline-flex;
            align-items: center;
            width: 100%;
            margin-bottom: 15px;
            background-color: whitesmoke;
            padding: 10px 15px;
            border-radius: 4px;
        }

        .report-title {
            display: flex;
            align-items: center;

            /* separates title and button */
            gap: 5px;
            margin: 0;
            width: 100%;
            /* ensure it uses full width */
        }

        .report-title h3 {
            margin: 0;
            font-size: 18px;
            flex: 1;
            /* allows h3 to grow and avoid being squeezed */
            white-space: nowrap;
            /* optional: remove if you want the title to wrap */

            text-overflow: ellipsis;
        }


        .btn-export {
            background-color: #9370db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
        }

        .btn-export:hover {
            background-color: #9370db;
        }

        /* Dashboard Styles */
        .dashboard-container {
            margin-bottom: 30px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .metric-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .metric-change {
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
        }

        .metric-change.positive {
            background-color: rgba(200, 230, 130, 0.2);
            color: #6b8e23;
        }

        .metric-change.negative {
            background-color: rgba(255, 99, 132, 0.2);
            color: #e74c3c;
        }

        /* Progress Bar Styles */
        .goal-progress-container,
        .top-products-container,
        .payment-methods-container,
        .user-performance-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .goal-progress-container h4,
        .top-products-container h4,
        .payment-methods-container h4,
        .user-performance-container h4,
        .sales-heatmap-container h4,
        .product-heatmap-container h4,
        .payment-heatmap-container h4,
        .user-heatmap-container h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }

        .goal-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .progress-bar-container {
            height: 20px;
            background-color: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 100%;
            background-color: #c8e682;
            border-radius: 10px;
            transition: width 1s ease;
        }

        .goal-percentage {
            text-align: right;
            font-weight: bold;
            color: #333;
        }

        /* Product Performance Progress Bars */
        .product-performance-grid,
        .payment-methods-grid,
        .user-performance-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .product-performance-row,
        .payment-method-row,
        .user-performance-row {
            display: grid;
            grid-template-columns: 1fr 3fr 1fr;
            align-items: center;
            gap: 10px;
        }

        .product-name,
        .payment-method,
        .user-name {
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-progress-container,
        .payment-progress-container,
        .user-progress-container {
            height: 15px;
            background-color: #f1f1f1;
            border-radius: 7px;
            overflow: hidden;
        }

        .product-progress-bar {
            height: 100%;
            background-color: #36a2eb;
            border-radius: 7px;
            transition: width 1s ease;
        }

        .payment-progress-bar {
            height: 100%;
            background-color: #9966ff;
            border-radius: 7px;
            transition: width 1s ease;
        }

        /* User progress bar continued */
        .user-progress-bar {
            height: 100%;
            background-color: #4bc0c0;
            border-radius: 7px;
            transition: width 1s ease;
        }

        .product-stats,
        .payment-stats,
        .user-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
        }

        /* Chart Styles */
        #sales-trend-chart {
            background-color: var(--card-bg);
            border-radius: 8px;
            width: 100%;
            height: 700px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        .chart-card {
            background-color: #f4f4f4;
            border-radius: 8px;
            margin-top: 30px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-export {
            background-color: #9370db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .btn-export:hover {
            background-color: #7a52b3;
        }

        .sales-summary {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-value {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .summary-label {
            font-size: 12px;
            color: #666;
        }

        /* Heatmap Styles */
        .sales-heatmap-container,
        .product-heatmap-container,
        .payment-heatmap-container,
        .user-heatmap-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .heatmap-grid,
        .product-heatmap-grid,
        .payment-heatmap-grid,
        .user-heatmap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }

        .heatmap-cell {
            padding: 15px;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #333;
            min-height: 90px;
            transition: transform 0.2s ease;
        }

        .heatmap-cell:hover {
            transform: scale(1.05);
        }

        .heatmap-date,
        .heatmap-product,
        .heatmap-payment,
        .heatmap-user {
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 5px;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
        }

        .heatmap-value {
            font-size: 1rem;
            text-align: center;
        }

        .heatmap-amount {
            font-size: 0.85rem;
            text-align: center;
            color: #666;
            margin-top: 5px;
        }

        .no-data {
            padding: 20px;
            text-align: center;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            color: #666;
        }

        /* Tab content active styles */
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <!-------------
    <div class="nav-container">
        <h3>GROCERY STORE INVENTORY MANAGEMENT SYSTEM</h3>
    </div>
    ------------>

    <div class="layout-container">
        <button class="mobile-toggle" id="mobileNavToggle">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12H21M3 6H21M3 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </button>
        <div class="nav-overlay" id="navOverlay"></div>
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
                        <div class="nav-item" data-id="inventory">
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
                        <div class="nav-item active" data-id="reports">
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
                <div class="card">
                    <div class="sales-card">
                        <h2 style="text-align: center;">Sales & Transaction Reports</h2>
                        <!-- Date Range Filter (applies to all reports) -->
                        <div class="filter-bar">
                            <div class="form-group">
                                <label for="start-date">Start Date</label>
                                <input type="date" id="start-date" name="start_date"
                                    value="<?php echo date('Y-m-01'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="end-date">End Date</label>
                                <input type="date" id="end-date" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group" style="align-self: flex-end;">
                                <button id="apply-filter">Apply Filter</button>
                            </div>

                        </div>
                    </div>

                    <!-- Sales Summary Report Tab Content -->
                    <div class="tab-content active" id="sales-summary">
                        <div class="report-actions">
                            <div class="report-title">
                                <h3>Daily Sales Summary </h3>
                                <button class="btn-export"
                                    onclick="exportTableToCSV('sales-summary-table', 'sales_summary_report.csv')">Export
                                    to CSV</button>
                            </div>

                        </div>

                        <!-- Dashboard Container - Replace the chart-container -->
                        <div class="dashboard-container">
                            <!-- Dashboard content will be loaded dynamically -->
                        </div>

                        <table id="sales-summary-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transactions</th>
                                    <th>Total Sales (RWF)</th>
                                    <th>Payment Mode</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="sales-summary-body">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="sales-trend-chart">
                    <div class="chart-card">
                        <h3>Weekly Sales Summary
                            <button class="btn-export"
                                onclick="exportTableToCSV('sales-summary-table', 'sales_summary_report.csv')">
                                Export to CSV
                            </button>
                        </h3>
                        <div class="dashboard-container">
                            <div style="width: 80%; margin: 0 auto;">
                                <canvas id="weeklySalesChart"></canvas>
                            </div>
                        </div>

                    </div>
                </div>


                <div class="product-card">
                    <!-- Product Performance Report Tab Content -->
                    <div class="tab-content" id="product-performance">
                        <div class="report-actions">
                            <div class="report-title">
                                <h3>Product Performance Analysis</h3>
                                <button class="btn-export"
                                    onclick="exportTableToCSV('product-performance-table', 'product_performance_report.csv')">Export
                                    to CSV</button>
                            </div>

                        </div>



                        <table id="product-performance-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity Sold</th>
                                    <th>Total Revenue (RWF)</th>
                                    <th>Number of Sales</th>
                                </tr>
                            </thead>
                            <tbody id="product-performance-body">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="payment-card">
                    <!-- Payment Analysis Report Tab Content -->
                    <div class="tab-content" id="payment-analysis">
                        <div class="report-actions">
                            <div class="report-title">
                                <h3>Payment Method Analysis</h3>
                                <button class="btn-export"
                                    onclick="exportTableToCSV('payment-analysis-table', 'payment_analysis_report.csv')">Export
                                    to CSV</button>
                            </div>

                        </div>

                        <!-- Dashboard Container - Replace the chart-container -->
                        <div class="dashboard-container">
                            <!-- Dashboard content will be loaded dynamically -->
                        </div>

                        <table id="payment-analysis-table">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Transaction Count</th>
                                    <th>Total Amount (RWF)</th>
                                </tr>
                            </thead>
                            <tbody id="payment-analysis-body">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                </div>


                <!-- User Performance Report Tab Content -->
                <div class="performance-card">
                    <div class="tab-content" id="user-performance">
                        <div class="report-actions">
                            <div class="report-title">
                                <h3>Sales by User/Employee</h3>
                                <button class="btn-export"
                                    onclick="exportTableToCSV('user-performance-table', 'user_performance_report.csv')">Export
                                    to CSV</button>

                            </div>
                        </div>

                        <!-- Dashboard Container - Replace the chart-container -->
                        <div class="dashboard-container">
                            <!-- Dashboard content will be loaded dynamically -->
                        </div>

                        <table id="user-performance-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Transaction Count</th>
                                    <th>Total Sales (RWF)</th>
                                </tr>
                            </thead>
                            <tbody id="user-performance-body">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                </div>
                </di>
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
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6">
                                                        </path>
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
                            <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
                        </div>
                    </div>
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
          WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0";

                $stmt = $conn->prepare($query);
                $stmt->execute([$userId]);

                return $stmt->fetchColumn();
            }
            ?>

            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    initializeNotifications();
                    initializeTabs();
                    initializeCharts();
                    initializeReportFilters();
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

                function initializeNotifications() {
                    const notificationBell = document.querySelector('.notification-bell');
                    const notificationContainer = document.querySelector('.notification-bell-container');

                    if (notificationBell && notificationContainer) {
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
                        if (markAllReadBtn) {
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
                    }
                }


                // Add these variable declarations at the beginning of your script
                function initializeTabs() {
                    // Tab switching functionality
                    const tabs = document.querySelectorAll('.tab');
                    tabs.forEach(tab => {
                        tab.addEventListener('click', function () {
                            // Remove active class from all tabs and content
                            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                            // Add active class to clicked tab
                            this.classList.add('active');

                            // Show corresponding content
                            const tabId = this.getAttribute('data-tab');
                            document.getElementById(tabId).classList.add('active');

                            // Load data for the selected tab
                            const startDate = document.getElementById('start-date').value;
                            const endDate = document.getElementById('end-date').value;
                            loadReportData(tabId, startDate, endDate);
                        });
                    });

                }


                // Add chart functionality for sales trends
                function initializeCharts() {
                    // Chart.js Initialization Script
                    if (typeof Chart !== 'undefined') {
                        const ctx = document.getElementById('weeklySalesChart');
                        if (ctx) {
                            const weeklySalesChart = new Chart(ctx.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                                    datasets: [{
                                        label: 'Sales (RWF)',
                                        data: [120000, 150000, 180000, 200000, 170000, 220000, 190000],
                                        backgroundColor: 'rgba(147, 112, 219, 0.7)',
                                        borderColor: 'rgba(147, 112, 219, 1)',
                                        borderWidth: 1,
                                        borderRadius: 5
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            display: true
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function (context) {
                                                    return `RWF ${context.raw.toLocaleString()}`;
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                callback: function (value) {
                                                    return `RWF ${value.toLocaleString()}`;
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    } else {
                        console.error('Chart.js library is not loaded');
                    }
                }

                document.getElementById('apply-filter').addEventListener('click', function () {
                    const startDate = document.getElementById('start-date').value;
                    const endDate = document.getElementById('end-date').value;

                    // Load data for all report types with new date filters
                    loadReportData('sales-summary', startDate, endDate);
                    loadReportData('product-performance', startDate, endDate);
                    loadReportData('payment-analysis', startDate, endDate);
                    loadReportData('user-performance', startDate, endDate);
                });

                // Function to load report data via AJAX
                function loadReportData(reportType, startDate, endDate) {
                    // Show loading indicator
                    const reportContainer = document.getElementById(reportType);
                    const dashboardContainer = reportContainer.querySelector('.dashboard-container');
                    dashboardContainer.innerHTML = '<div style="text-align: center; padding: 20px;">Loading dashboard data...</div>';

                    // Create query parameters
                    const params = new URLSearchParams({
                        report: reportType,
                        start_date: startDate,
                        end_date: endDate
                    });

                    // Fetch data
                    fetch('get_report_data.php?' + params.toString())
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(responseObj => {
                            // Get salesData from the response
                            const salesData = responseObj.salesData || [];

                            // Clear loading indicator
                            dashboardContainer.innerHTML = ''; // Clear the container for new dashboard elements

                            // Handle the data based on report type
                            switch (reportType) {
                                case 'sales-summary':
                                    renderSalesSummaryDashboard(salesData, dashboardContainer);
                                    break;
                                case 'product-performance':
                                    renderProductPerformanceDashboard(salesData, dashboardContainer);
                                    break;
                                case 'payment-analysis':
                                    renderPaymentAnalysisDashboard(salesData, dashboardContainer);
                                    break;
                                case 'user-performance':
                                    renderUserPerformanceDashboard(salesData, dashboardContainer);
                                    break;
                            }

                            // Also update the table
                            updateReportTable(reportType, salesData);
                        })
                        .catch(error => {
                            console.error('Error loading report data:', error);
                            dashboardContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: red;">Error loading dashboard data. Please try again.</div>';

                            // Display error in table as well
                            const tableBody = document.getElementById(reportType + '-body');
                            if (tableBody) {
                                tableBody.innerHTML = '<tr><td colspan="5">Error loading data. Please try again.</td></tr>';
                            }
                        });
                }

                // Function to update table data
                function updateReportTable(reportType, data) {
                    const tableBody = document.getElementById(reportType + '-body');
                    tableBody.innerHTML = '';

                    if (!data || data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5">No data available for the selected date range</td></tr>';
                        return;
                    }

                    switch (reportType) {
                        case 'sales-summary':
                            data.forEach(row => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                        <td>${row.sale_date}</td>
                        <td>${row.total_transactions}</td>
                        <td>${parseFloat(row.total_sales).toFixed(2)}</td>
                        <td>${row.payment_mode}</td>
                        <td>${row.payment_status}</td>
                    `;
                                tableBody.appendChild(tr);
                            });
                            break;

                        case 'product-performance':
                            data.forEach(row => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                        <td>${row.prod_name}</td>
                        <td>${row.total_quantity_sold}</td>
                        <td>${parseFloat(row.total_revenue).toFixed(2)}</td>
                        <td>${row.number_of_sales}</td>
                    `;
                                tableBody.appendChild(tr);
                            });
                            break;

                        case 'payment-analysis':
                            data.forEach(row => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                        <td>${row.payment_mode}</td>
                        <td>${row.transaction_count}</td>
                        <td>${parseFloat(row.total_amount).toFixed(2)}</td>
                    `;
                                tableBody.appendChild(tr);
                            });
                            break;

                        case 'user-performance':
                            data.forEach(row => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                        <td>${row.created_by || 'Unknown'}</td>
                        <td>${row.transaction_count}</td>
                        <td>${parseFloat(row.total_sales).toFixed(2)}</td>
                    `;
                                tableBody.appendChild(tr);
                            });
                            break;
                    }
                }

                // Function to render Sales Summary Dashboard
                function renderSalesSummaryDashboard(data, container) {
                    if (!data || data.length === 0) {
                        container.innerHTML = '<div class="no-data">No data available for the selected date range</div>';
                        return;
                    }

                    // Calculate key metrics
                    let totalSales = 0;
                    let totalTransactions = 0;
                    let avgTransactionValue = 0;
                    let dailySalesData = {};
                    let maxDailySale = 0;

                    // Process the data
                    data.forEach(row => {
                        totalSales += parseFloat(row.total_sales || 0);
                        totalTransactions += parseInt(row.total_transactions || 0);

                        // Aggregate sales by date for heatmap
                        if (!dailySalesData[row.sale_date]) {
                            dailySalesData[row.sale_date] = 0;
                        }
                        dailySalesData[row.sale_date] += parseFloat(row.total_sales || 0);

                        // Track maximum daily sale for heatmap intensity
                        maxDailySale = Math.max(maxDailySale, dailySalesData[row.sale_date]);
                    });

                    // Calculate average transaction value
                    avgTransactionValue = totalTransactions > 0 ? totalSales / totalTransactions : 0;

                    // Determine if sales goal is met (example: 10000 RWF)
                    const salesGoal = 10000; // This should come from your settings or database
                    const goalProgress = Math.min(100, (totalSales / salesGoal) * 100);

                    // Create dashboard HTML
                    const dashboardHTML = `
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-title">Total Sales</div>
                    <div class="metric-value">${totalSales.toFixed(2)} RWF</div>
                    <div class="metric-change positive">+${(Math.random() * 10).toFixed(2)}% from previous period</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Total Transactions</div>
                    <div class="metric-value">${totalTransactions}</div>
                    <div class="metric-change ${Math.random() > 0.5 ? 'positive' : 'negative'}">
                        ${Math.random() > 0.5 ? '+' : '-'}${(Math.random() * 5).toFixed(2)}% from previous period
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Average Transaction Value</div>
                    <div class="metric-value">${avgTransactionValue.toFixed(2)} RWF</div>
                    <div class="metric-change ${Math.random() > 0.5 ? 'positive' : 'negative'}">
                        ${Math.random() > 0.5 ? '+' : '-'}${(Math.random() * 8).toFixed(2)}% from previous period
                    </div>
                </div>
            </div>
            
            <div class="goal-progress-container">
                <h4>Sales Goal Progress</h4>
                <div class="goal-info">
                    <span>Current: ${totalSales.toFixed(2)} RWF</span>
                    <span>Goal: ${salesGoal.toFixed(2)} RWF</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: ${goalProgress}%"></div>
                </div>
                <div class="goal-percentage">${goalProgress.toFixed(1)}%</div>
            </div>
            
            <div class="sales-heatmap-container">
                <h4>Daily Sales Heatmap</h4>
                <div class="sales-heatmap" id="sales-heatmap"></div>
            </div>
        `;

                    // Add the dashboard to the container
                    container.innerHTML = dashboardHTML;

                    // Create heatmap
                    createSalesHeatmap(dailySalesData, maxDailySale);
                }

                // Function to create a sales heatmap
                function createSalesHeatmap(dailyData, maxValue) {
                    const heatmapContainer = document.getElementById('sales-heatmap');
                    if (!heatmapContainer) return;

                    const dates = Object.keys(dailyData).sort();

                    let heatmapHTML = '<div class="heatmap-grid">';

                    dates.forEach(date => {
                        const value = dailyData[date];
                        const intensity = maxValue > 0 ? (value / maxValue) * 100 : 0;
                        const colorIntensity = Math.min(100, Math.max(0, intensity));

                        heatmapHTML += `
                <div class="heatmap-cell" style="background-color: rgba(200, 230, 130, ${colorIntensity / 100})">
                    <div class="heatmap-date">${date}</div>
                    <div class="heatmap-value">${value.toFixed(2)}</div>
                </div>
            `;
                    });

                    heatmapHTML += '</div>';
                    heatmapContainer.innerHTML = heatmapHTML;
                }

                // Function to render Product Performance Dashboard
                function renderProductPerformanceDashboard(data, container) {
                    if (!data || data.length === 0) {
                        container.innerHTML = '<div class="no-data">No data available for the selected date range</div>';
                        return;
                    }

                    // Sort products by revenue
                    const sortedProducts = [...data].sort((a, b) =>
                        parseFloat(b.total_revenue) - parseFloat(a.total_revenue)
                    );

                    // Get top 5 products
                    const topProducts = sortedProducts.slice(0, 5);

                    // Calculate total revenue for all products
                    const totalRevenue = data.reduce((sum, product) =>
                        sum + parseFloat(product.total_revenue || 0), 0
                    );

                    // Calculate total quantity sold
                    const totalQuantity = data.reduce((sum, product) =>
                        sum + parseInt(product.total_quantity_sold || 0), 0
                    );

                    // Create dashboard HTML
                    let dashboardHTML = `
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-title">Total Revenue</div>
                    <div class="metric-value">${totalRevenue.toFixed(2)} RWF</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Total Units Sold</div>
                    <div class="metric-value">${totalQuantity}</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Total Products Sold</div>
                    <div class="metric-value">${data.length}</div>
                </div>
            </div>
            
            <div class="top-products-container">
                <h4>Top 5 Products by Revenue</h4>
                <div class="product-performance-grid">
        `;

                    // Add top products with progress bars
                    topProducts.forEach(product => {
                        const percentage = (parseFloat(product.total_revenue) / totalRevenue * 100).toFixed(1);
                        dashboardHTML += `
                <div class="product-performance-row">
                    <div class="product-name" title="${product.prod_name}">${product.prod_name}</div>
                    <div class="product-progress-container">
                        <div class="product-progress-bar" style="width: ${percentage}%"></div>
                    </div>
                    <div class="product-stats">
                        <span>${parseFloat(product.total_revenue).toFixed(2)} RWF</span>
                        <span>${percentage}%</span>
                    </div>
                </div>
            `;
                    });

                    dashboardHTML += `
                </div>
            </div>
            
            <div class="product-heatmap-container">
                <h4>Product Quantity Heatmap</h4>
                <div class="product-heatmap" id="product-heatmap"></div>
            </div>
        `;

                    // Add the dashboard to the container
                    container.innerHTML = dashboardHTML;

                    // Create product heatmap
                    createProductHeatmap(data);
                }

                // Function to create a product heatmap
                function createProductHeatmap(data) {
                    const heatmapContainer = document.getElementById('product-heatmap');
                    if (!heatmapContainer) return;

                    // Get max quantity for scaling
                    const maxQuantity = data.reduce((max, product) =>
                        Math.max(max, parseInt(product.total_quantity_sold || 0)), 0
                    );

                    // Sort products by quantity sold (descending)
                    const sortedProducts = [...data].sort((a, b) =>
                        parseInt(b.total_quantity_sold) - parseInt(a.total_quantity_sold)
                    ).slice(0, 10); // Limit to top 10 products

                    let heatmapHTML = '<div class="product-heatmap-grid">';

                    sortedProducts.forEach(product => {
                        const quantity = parseInt(product.total_quantity_sold || 0);
                        const intensity = maxQuantity > 0 ? (quantity / maxQuantity) * 100 : 0;

                        heatmapHTML += `
                <div class="heatmap-cell" style="background-color: rgba(54, 162, 235, ${intensity / 100})">
                    <div class="heatmap-product" title="${product.prod_name}">${product.prod_name}</div>
                    <div class="heatmap-value">${quantity} units</div>
                </div>
            `;
                    });

                    heatmapHTML += '</div>';
                    heatmapContainer.innerHTML = heatmapHTML;
                }

                // Function to render Payment Analysis Dashboard
                function renderPaymentAnalysisDashboard(data, container) {
                    if (!data || data.length === 0) {
                        container.innerHTML = '<div class="no-data">No data available for the selected date range</div>';
                        return;
                    }

                    // Calculate total transaction amount
                    const totalAmount = data.reduce((sum, payment) =>
                        sum + parseFloat(payment.total_amount || 0), 0
                    );

                    // Calculate total transaction count
                    const totalCount = data.reduce((sum, payment) =>
                        sum + parseInt(payment.transaction_count || 0), 0
                    );

                    // Create dashboard HTML
                    let dashboardHTML = `
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-title">Total Payment Volume</div>
                    <div class="metric-value">${totalAmount.toFixed(2)} RWF</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Total Transactions</div>
                    <div class="metric-value">${totalCount}</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Average Transaction</div>
                    <div class="metric-value">${(totalAmount / totalCount).toFixed(2)} RWF</div>
                </div>
            </div>
            
            <div class="payment-methods-container">
                <h4>Payment Method Distribution</h4>
                <div class="payment-methods-grid">
        `;

                    // Add payment methods with progress bars
                    data.forEach(payment => {
                        const percentage = (parseFloat(payment.total_amount) / totalAmount * 100).toFixed(1);
                        dashboardHTML += `
                <div class="payment-method-row">
                    <div class="payment-method">${payment.payment_mode}</div>
                    <div class="payment-progress-container">
                        <div class="payment-progress-bar" style="width: ${percentage}%"></div>
                    </div>
                    <div class="payment-stats">
                        <span>${parseFloat(payment.total_amount).toFixed(2)} RWF</span>
                        <span>${percentage}%</span>
                    </div>
                </div>
            `;
                    });

                    dashboardHTML += `
                </div>
            </div>
            
            <div class="payment-heatmap-container">
                <h4>Payment Method Heatmap</h4>
                <div class="payment-heatmap" id="payment-heatmap"></div>
            </div>
        `;

                    // Add the dashboard to the container
                    container.innerHTML = dashboardHTML;

                    // Create payment method heatmap
                    createPaymentHeatmap(data);
                }

                // Function to create a payment method heatmap
                function createPaymentHeatmap(data) {
                    const heatmapContainer = document.getElementById('payment-heatmap');
                    if (!heatmapContainer) return;

                    // Get max transaction count for scaling
                    const maxCount = data.reduce((max, payment) =>
                        Math.max(max, parseInt(payment.transaction_count || 0)), 0
                    );

                    let heatmapHTML = '<div class="payment-heatmap-grid">';

                    data.forEach(payment => {
                        const count = parseInt(payment.transaction_count || 0);
                        const intensity = maxCount > 0 ? (count / maxCount) * 100 : 0;

                        heatmapHTML += `
                <div class="heatmap-cell" style="background-color: rgba(153, 102, 255, ${intensity / 100})">
                    <div class="heatmap-payment">${payment.payment_mode}</div>
                    <div class="heatmap-value">${count} transactions</div>
                    <div class="heatmap-amount">${parseFloat(payment.total_amount).toFixed(2)} RWF</div>
                </div>
            `;
                    });

                    heatmapHTML += '</div>';
                    heatmapContainer.innerHTML = heatmapHTML;
                }

                // Function to render User Performance Dashboard
                function renderUserPerformanceDashboard(data, container) {
                    if (!data || data.length === 0) {
                        container.innerHTML = '<div class="no-data">No data available for the selected date range</div>';
                        return;
                    }

                    // Sort users by sales volume
                    const sortedUsers = [...data].sort((a, b) =>
                        parseFloat(b.total_sales) - parseFloat(a.total_sales)
                    );

                    // Calculate total sales
                    const totalSales = data.reduce((sum, user) =>
                        sum + parseFloat(user.total_sales || 0), 0
                    );

                    // Calculate total transactions
                    const totalTransactions = data.reduce((sum, user) =>
                        sum + parseInt(user.transaction_count || 0), 0
                    );

                    // Create dashboard HTML
                    let dashboardHTML = `
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-title">Total User Sales</div>
                    <div class="metric-value">${totalSales.toFixed(2)} RWF</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Active Users</div>
                    <div class="metric-value">${data.length}</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">Average Sales per User</div>
                    <div class="metric-value">${(totalSales / data.length).toFixed(2)} RWF</div>
                </div>
            </div>
            
            <div class="user-performance-container">
                <h4>User Performance Comparison</h4>
                <div class="user-performance-grid">
        `;

                    // Add users with progress bars
                    sortedUsers.forEach(user => {
                        const percentage = (parseFloat(user.total_sales) / totalSales * 100).toFixed(1);
                        dashboardHTML += `
                <div class="user-performance-row">
                    <div class="user-name">${user.created_by || 'Unknown'}</div>
                    <div class="user-progress-container">
                        <div class="user-progress-bar" style="width: ${percentage}%"></div>
                    </div>
                    <div class="user-stats">
                        <span>${parseFloat(user.total_sales).toFixed(2)} RWF</span>
                        <span>${percentage}%</span>
                    </div>
                </div>
            `;
                    });

                    dashboardHTML += `
                </div>
            </div>
            
            <div class="user-heatmap-container">
                <h4>User Transaction Heatmap</h4>
                <div class="user-heatmap" id="user-heatmap"></div>
            </div>
        `;

                    // Add the dashboard to the container
                    container.innerHTML = dashboardHTML;

                    // Create user heatmap
                    createUserHeatmap(sortedUsers);
                }

                // Function to create a user heatmap
                function createUserHeatmap(data) {
                    const heatmapContainer = document.getElementById('user-heatmap');
                    if (!heatmapContainer) return;

                    // Get max transaction count for scaling
                    const maxCount = data.reduce((max, user) =>
                        Math.max(max, parseInt(user.transaction_count || 0)), 0
                    );

                    let heatmapHTML = '<div class="user-heatmap-grid">';

                    data.forEach(user => {
                        const count = parseInt(user.transaction_count || 0);
                        const intensity = maxCount > 0 ? (count / maxCount) * 100 : 0;

                        heatmapHTML += `
                <div class="heatmap-cell" style="background-color: rgba(75, 192, 192, ${intensity / 100})">
                    <div class="heatmap-user">${user.created_by || 'Unknown'}</div>
                    <div class="heatmap-value">${count} transactions</div>
                    <div class="heatmap-amount">${parseFloat(user.total_sales).toFixed(2)} RWF</div>
                </div>
            `;
                    });

                    heatmapHTML += '</div>';
                    heatmapContainer.innerHTML = heatmapHTML;
                }

                // Function to export table to CSV (unchanged)
                function exportTableToCSV(tableId, filename) {
                    const table = document.getElementById(tableId);
                    let csv = [];

                    // Get all rows including header
                    const rows = table.querySelectorAll('tr');

                    for (let i = 0; i < rows.length; i++) {
                        const row = [], cols = rows[i].querySelectorAll('td, th');

                        for (let j = 0; j < cols.length; j++) {
                            // Add quotes around the value to handle commas
                            row.push('"' + cols[j].innerText + '"');
                        }

                        csv.push(row.join(','));
                    }

                    // Create CSV file and download
                    const csvContent = csv.join('\n');
                    const blob = new Blob([csvContent], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.setAttribute('hidden', '');
                    a.setAttribute('href', url);
                    a.setAttribute('download', filename);
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }

                // Add exportTableToCSV to window object so it can be called from HTML
                window.exportTableToCSV = exportTableToCSV;

                // Load initial data for the default active tab

                document.addEventListener('DOMContentLoaded', function () {
                    const startDate = document.getElementById('start-date').value;
                    const endDate = document.getElementById('end-date').value;
                    loadReportData('sales-summary', startDate, endDate);
                    loadReportData('product-performance', startDate, endDate);
                    loadReportData('payment-analysis', startDate, endDate);
                    loadReportData('user-performance', startDate, endDate);

                });
            </script>
</body>

</html>
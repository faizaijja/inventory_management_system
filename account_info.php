<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Set default theme if not set
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

// Toggle theme if requested
if (isset($_POST['toggle_theme'])) {
    $_SESSION['theme'] = ($_SESSION['theme'] === 'light') ? 'dark' : 'light';
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
    <title>Transactions</title>
    <style>
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
        }

        .transactiom-card {
            background-color: #f4f7f9;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;

        }

        .transaction-history {
            margin-right: 5px;
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
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        #clear-filters {
            background-color: #9370db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #c8e682;
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

        .transactions-list tr:hover {
            background-color: #f5f5f5;
        }

        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-bar input,
        .filter-bar select {
            width: auto;
        }

        .status-paid {
            color: #27ae60;
            font-weight: bold;
        }

        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
        }

        .stat-card h3 {
            margin-top: 0;
            color: #7f8c8d;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
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
</head>

<body>
    <!------------------
    <div class="nav-container">
        <h3>GROCERY STORE INVENTORY MANAGEMENT SYSTEM</h3>
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
                        <div class="nav-item active" data-id="transactions">
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
                <div class="card">
                    <div class="transaction-history">
                        <h2>Recent Transactions</h2>
                        <form method="GET" id="filterForm">
                            <div class="filter-bar">
                                <select name="payment_method" id="filter-payment-method"
                                    onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Payment Methods</option>

                                    <?php
                                    include("connect.php");
                                    $sql = "SELECT DISTINCT payment_method FROM transactions";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $selected = (isset($_GET['payment_method']) && $_GET['payment_method'] == $row['payment_method']) ? 'selected' : '';
                                            echo '<option value ="' . $row['payment_method'] . '" ' . $selected . '>' . $row['payment_method'] . '</option>';
                                        }
                                    }
                                    $conn->close();

                                    ?>
                                </select>
                                <input type="date" id="filter-date" name="transaction_date"
                                    value="<?php echo isset($_GET['transaction_date']) ? htmlspecialchars($_GET['transaction_date']) : ''; ?>"
                                    onchange="document.getElementById('filterForm').submit()">
                                <button id="clear-filters" type="button">Clear Filters</button>
                            </div>

                        </form>

                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Amount(RWF)</th>
                                    <th>Mode of payment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                include("connect.php");

                                // Get filter values
                                $paymentFilter = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
                                $dateFilter = isset($_GET['transaction_date']) ? $_GET['transaction_date'] : '';

                                // Build SQL query
                                $sql = "SELECT * FROM transactions";

                                // Add WHERE clause if filters are applied
                                $whereClause = [];
                                if (!empty($paymentFilter)) {
                                    $whereClause[] = "payment_method = '" . $conn->real_escape_string($paymentFilter) . "'";
                                }
                                if (!empty($dateFilter)) {
                                    $whereClause[] = "DATE(transaction_date) = '" . $conn->real_escape_string($dateFilter) . "'";
                                }

                                // Combine all filters if any exist
                                if (!empty($whereClause)) {
                                    $sql .= " WHERE " . implode(" AND ", $whereClause);
                                }

                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                    <td>{$row['transaction_id']}</td>
                                    <td>{$row['transaction_type']}</td>
                                    <td>{$row['transaction_date']}</td>
                                    <td>{$row['prod_name']}</td>
                                    <td>{$row['quantity']}</td>
                                    <td>{$row['total']}</td>
                                    <td>{$row['payment_mode']}</td>
                                    <td>{$row['payment_status']}</td>
                                </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8'>No transactions found</td></tr>";
                                }

                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                    </div>

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

        document.addEventListener('DOMContentLoaded', function () {
            // Get filter elements
            const paymentFilter = document.getElementById('filter-payment-method');
            const dateFilter = document.getElementById('filter-date');
            const clearFiltersBtn = document.getElementById('clear-filters');
            const filterForm = document.getElementById('filterForm');

            // Add event listener for clear filters button
            clearFiltersBtn.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent default form submission

                // Reset all filters
                paymentFilter.value = '';
                dateFilter.value = '';

                // Submit the form with cleared filters
                filterForm.submit();
            });
        });
    </script>
</body>

</html>

<?php
include("connect.php");
// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form data
    $transactionDate = $conn->real_escape_string($_POST['transaction_date']);
    $transactionType = $conn->real_escape_string($_POST['transaction_type']);
    $prod_name = (string) $_POST['prod_name'];
    $quantity = (int) $_POST['quantity'];
    $price = (float) $_POST['price'];
    $total = (float) $_POST['total'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $payment_status = $conn->real_escape_string($_POST['payment_status']);

    // Set default status based on transaction type
    $status = ($transactionType == 'purchase' || $transactionType == 'expense') ? 'pending' : 'paid';

    // Insert into transactions table
    $sql = "INSERT INTO `transactions`(`transaction_id`, `sales_id`, `transaction_date`, `transaction_type`, `prod_name`, `quantity`, `total`, `payment_method`, `payment_status`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]')";

    if ($conn->query($sql) === TRUE) {
        // Update inventory based on transaction type
        if ($transactionType == 'sale') {
            // Reduce inventory
            $updateSql = "UPDATE products SET 
                          quantity = quantity - $quantity, s
                          last_updated = NOW() 
                          WHERE id = $itemId";
            $conn->query($updateSql);
        } elseif ($transactionType == 'purchase') {
            // Increase inventory
            $updateSql = "UPDATE products SET 
                          quantity = quantity + $quantity, 
                          last_updated = NOW() 
                          WHERE id = $itemId";
            $conn->query($updateSql);
        }

        // Redirect back to accounting page with success message
        header("Location: transactions.php?status=success&message=Transaction added successfully");
        exit();
    } else {
        // Redirect back with error
        header("Location: transactions.php?status=error&message=Error: " . $conn->error);
        exit();
    }
}
$conn->close();
?>
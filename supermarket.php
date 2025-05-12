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

$productStats = getProductStats();
$featuredProducts = getFeaturedProducts(10);
$notificationCount = getUnreadNotificationCount($_SESSION['user_id']);
$userNotifications = getUserNotifications($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $_SESSION['theme']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="supermarket.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="market.js"></script>
    <title>IMS</title>

    <style>
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

</head>

<body>

    <div class="layout-container">
        <button class="mobile-toggle" id="mobileNavToggle">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12H21M3 6H21M3 18H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </button>
        <div class="nav-overlay" id="navOverlay"></div>

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
                    <div class="nav-item active" data-id="home">
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

        <!------------Header section------------->
        <div class="header">
            <div class="search-container"></div>

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
                                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                                        data-id="<?php echo $notification['id']; ?>">
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

        <div class="container">
            <div class="main-content">
                <!-- Featured Products Section -->
                <div class="content-area">
                    <div class="featured-section">
                        <h3>Top picks</h3>
                        <div class="product-carousel">
                            <?php foreach ($featuredProducts as $product): ?>
                                <?php

                                // Determine stock status
                                $stockStatus = 'In Stock';
                                $stockPercentage = 100;

                                if ($product['quantity'] <= 0) {
                                    $stockStatus = 'Out of Stock';
                                    $stockPercentage = 0;
                                } elseif ($product['quantity'] <= $product['reorder_level']) {
                                    $stockStatus = 'Low Stock';
                                    $stockPercentage = 30; // This could be calculated as a percentage of optimal stock
                                }

                                // Handle image path more robustly
                                $imagePath = 'images/placeholder.jpg'; // Default placeholder
                                if (!empty($product['image_path'])) {
                                    // Check if the file exists and is in the correct directory
                                    $fullPath = 'images/' . basename($product['image_path']);
                                    if (file_exists($fullPath)) {
                                        $imagePath = $fullPath;
                                    }
                                }
                                ?>

                                <div class="product-card" data-id="<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                                    <h4 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p class="price"><?php echo number_format($product['price'], 0); ?> rwf</p>
                                    <span
                                        class="stock-badge <?php echo strtolower(str_replace(' ', '-', $stockStatus)); ?>"><?php echo $stockStatus; ?></span>

                                    <!-- Stock indicator will be added by JS -->
                                    <div class="stock-indicator">
                                        <div class="stock-bar">
                                            <div class="stock-progress" style="width: <?php echo $stockPercentage; ?>%; 
                            background-color: <?php echo ($stockPercentage === 0) ? 'var(--danger)' :
                                ($stockPercentage <= 30 ? 'var(--warning)' :
                                    'var(--success)'); ?>">
                                            </div>
                                        </div>
                                        <span class="stock-text"><?php echo $stockPercentage; ?>%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>

    <!------footer---->
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

    // Get product statistics
    function getProductStats()
    {
        $conn = getDbConnection();

        $stats = [
            'total_products' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'sales_today' => 0
        ];
        // Get total products
        $stmt = $conn->query("SELECT COUNT(*) FROM products");
        $stats['total_products'] = $stmt->fetchColumn();

        // Get low stock items (threshold can be defined in a settings table or hardcoded)
        $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE quantity <= reorder_level AND quantity > 0");
        $stats['low_stock'] = $stmt->fetchColumn();

        // Get out of stock items
        $stmt = $conn->query("SELECT COUNT(*) FROM products WHERE quantity = 0");
        $stats['out_of_stock'] = $stmt->fetchColumn();

        // Get sales today
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) AS sales_today FROM transactions WHERE DATE(transaction_date) = ?");
        $stmt->execute([$today]);
        $stats['sales_today'] = $stmt->fetchColumn();

        return $stats;
    }

    // Get featured products for the carousel
    // Update the getFeaturedProducts function to include image_path
    function getFeaturedProducts($limit = 10)
    {
        $conn = getDbConnection();

        // This query assumes you have an image_path column in your products table
        $query = "SELECT 
                p.product_id as id, 
                p.prod_name as name, 
                p.category, 
                p.unit_price as price, 
                p.quantity, 
                p.image_path, 
                COALESCE(p.reorder_level, 10) as reorder_level 
              FROM products p
              ORDER BY p.product_id DESC
              LIMIT " . intval($limit);

        $stmt = $conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initializeNotifications();

            // Fetch notifications immediately on page load
            fetchNotifications();

            // Refresh notifications periodically (every 30 seconds)
            setInterval(fetchNotifications, 30000);
        });

        function initializeNotifications() {
            const notificationBell = document.querySelector('.notification-bell');
            const notificationContainer = document.querySelector('.notification-bell-container');

            if (!notificationBell || !notificationContainer) {
                console.error('Notification elements not found');
                return;
            }

            // Toggle dropdown when clicking the bell
            notificationBell.addEventListener('click', function (e) {
                e.stopPropagation();
                notificationContainer.classList.toggle('active');

                // Mark notifications as seen when opening the dropdown
                if (notificationContainer.classList.contains('active')) {
                    markNotificationsAsSeen();
                }
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
                    markAllNotificationsAsRead();
                });
            }

            // Add click events to individual notifications
            const notificationItems = document.querySelectorAll('.notification-item');
            notificationItems.forEach(function (item) {
                item.addEventListener('click', function () {
                    const notificationId = this.getAttribute('data-id');
                    if (notificationId && this.classList.contains('unread')) {
                        markNotificationAsRead(notificationId);
                    }
                });
            });
        }

        function fetchNotifications() {
            fetch('api/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    updateNotificationUI(data.notifications);
                    updateNotificationCount(data.unread_count);
                })
                .catch(error => {
                    console.error('Error fetching notifications:', error);
                });
        }

        function markNotificationAsRead(notificationId) {
            fetch('api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: notificationId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                        if (notificationItem) {
                            notificationItem.classList.remove('unread');
                        }

                        // Update count
                        updateNotificationCount(data.unread_count);
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
        }

        function markNotificationsAsSeen() {

            fetch('api/mark_notifications_seen.php', {
                method: 'POST'
            })
                .catch(error => {
                    console.error('Error marking notifications as seen:', error);
                });
        }
        function markAllNotificationsAsRead() {
            // Show loading state
            const markAllBtn = document.querySelector('.mark-all-read');
            if (markAllBtn) {
                markAllBtn.textContent = 'Updating...';
            }

            fetch('api/mark_all_notifications_read.php', {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI - remove unread class from all items
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                        });

                        // Update notification count
                        updateNotificationCount(0);
                    }
                    if (markAllBtn) {
                        markAllBtn.textContent = 'Mark all as read';
                    }
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);

                    // Reset button text on error
                    if (markAllBtn) {
                        markAllBtn.textContent = 'Mark all as read';
                    }
                });
        }

        function updateNotificationUI(notifications) {
            const notificationsList = document.querySelector('.notifications-list');

            if (!notificationsList) return;

            if (notifications.length === 0) {
                notificationsList.innerHTML = `
            <div class="no-notifications">
                <p>No notifications yet</p>
            </div>
        `;
                return;
            }
            let notificationsHTML = '';

            notifications.forEach(notification => {
                let iconSVG = '';

                // Set icon based on notification type
                if (notification.type === 'low_stock') {
                    iconSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" 
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 16v1a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2m5.66 0H14a2 2 0 0 1 2 2v3.34"></path>
                        <path d="M14 3v4a2 2 0 0 0 2 2h4"></path>
                        <path d="M16 16L22 10"></path>
                        </svg>`;
                } else if (notification.type === 'order') {
                    iconSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" 
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>`;
                } else {
                    iconSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" 
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>`;
                }
                const date = new Date(notification.created_at);
                const formattedDate = date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });

                notificationsHTML += `
            <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-icon">
                    ${iconSVG}
                </div>
                <div class="notification-content">
                    <p>${notification.message}</p>
                    <span class="notification-time">${formattedDate}</span>
                </div>
            </div>
        `;
            });
            notificationsList.innerHTML = notificationsHTML;

            // Add click event to each notification
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function () {
                    const notificationId = this.getAttribute('data-id');
                    markNotificationAsRead(notificationId);
                });
            });
        }
        function updateNotificationCount(count) {
            const countElement = document.querySelector('.notification-count');
            if (!countElement) return;

            countElement.textContent = count;

            // Show/hide badge based on count
            if (count > 0) {
                countElement.style.display = 'flex';
            } else {
                countElement.style.display = 'none';
            }
        }
    </script>

</body>

</html>
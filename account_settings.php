<?php
// Include database connection
include("connect.php");
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

// Process form submission for profile updates
if (isset($_POST['save_profile'])) {
    $store_name = $_POST['store_name'];
    $location = $_POST['location'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];
    $update_success = false;
    
    // Update email in users table
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
    $stmt->bind_param("si", $email, $user_id);
    $update_success = $stmt->execute();
    
    // Check if the store already exists for this user
    $stmt = $conn->prepare("SELECT store_id FROM stores WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Store exists, update it
        $store = $result->fetch_assoc();
        $store_id = $store['store_id'];
        
        $stmt = $conn->prepare("UPDATE stores SET store_name = ?, store_location = ? WHERE store_id = ?");
        $stmt->bind_param("ssi", $store_name, $location, $store_id);
        $stmt->execute();
    } else {
        // Store doesn't exist, insert new store
        $stmt = $conn->prepare("INSERT INTO stores (store_name, store_location, user_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $store_name, $location, $user_id);
        $stmt->execute();
    }
    
    // Update password if provided and passwords match
    if (!empty($password) && $password === $confirm_password) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
    }
    
    // Fetch updated user information
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Save success message to display
    $_SESSION['update_message'] = "Profile updated successfully!";
}

// Get current user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.*, s.store_name, s.store_location 
                      FROM users u 
                      LEFT JOIN stores s ON u.user_id = s.user_id 
                      WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $_SESSION['theme']; ?>">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link rel="stylesheet" href="supermarket.css">
    <script src="script.js"></script>
    <title>GROCERY STORE INVENTORY</title>
    <style>
        .success-message {
    background-color: #dff0d8;
    color: #3c763d;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
}

        .form-group {
            margin-bottom: 20px;
        }
        
        .form-section-title {
            margin-top: 20px;
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: var(--text-color);
            border-bottom: 1px solid var(--input-border);
            padding-bottom: 8px;
        }
        
        /* Profile section styling */
        .profile-section {
            padding: 30px;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .profile-section h2 {
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            margin-right: 20px;
        }
        
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--profile-avatar-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            color: var(--profile-avatar-text);
        }
        
        .profile-details h1 {
            font-size: 1.6rem;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .profile-details p {
            color: #666;
            margin-bottom: 15px;
        }
        
        .profile-details-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .profile-section-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .profile-section-card h3 {
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: var(--text-color);
            border-bottom: 1px solid var(--input-border);
            padding-bottom: 8px;
        }
        
        .profile-field {
            display: flex;
            margin-bottom: 12px;
        }
        
        .profile-field label {
            min-width: 140px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .profile-field span {
            color: var(--text-color);
        }
        
        /* Edit profile form styling */
        #edit-profile-form {
            display: none; /* Hidden by default */
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: var(--card-shadow);
        }
        
        #edit-profile-form h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--text-color);
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            font-size: 14px;
            background-color: var(--input-bg);
            color: var(--text-color);
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
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .primary-btn, .secondary-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .primary-btn {
            background-color: var(--btn-primary-bg);
            color: var(--btn-primary-text);
        }
        
        .primary-btn:hover {
            filter: brightness(1.1);
        }
        
        .secondary-btn {
            background-color: var(--btn-secondary-bg);
            color: var(--btn-secondary-text);
        }
        
        .secondary-btn:hover {
            filter: brightness(1.1);
        }
        
        .cancel-btn {
            background-color: var(--btn-cancel-bg);
            border: 1px solid var(--input-border);
        }

        /* For very small screens */
        @media (max-width: 768px) {
            .side-nav {
                width: 60px;
                overflow: hidden;
            }
            
            .side-nav .brand h1,
            .side-nav .nav-item span {
                display: none;
            }
            
            .side-nav .nav-item svg {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 60px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .profile-field {
                flex-direction: column;
            }
            
            .profile-field label {
                margin-bottom: 5px;
            }
        }
        
        @media (max-width: 480px) {
            .profile-section {
                padding: 15px;
            }
            
            #edit-profile-form {
                padding: 15px;
            }
            
            .form-group label {
                font-size: 14px;
            }
            
            .form-group input,
            .form-group select {
                padding: 8px 10px;
            }
            
            h2 {
                font-size: 1.4em;
            }
            
            .form-section-title {
                font-size: 1.1em;
            }
            
            .button-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="layout-container">
        <!-- Side Navigation Bar -->
        <div class="side-nav">
            <!-- Logo/Brand with Theme Toggle -->
            <div class="brand">
                <h1>Grocery Store</h1>
                <form method="post" style="display: inline;">
                    <button type="submit" name="toggle_theme" class="theme-toggle" title="Toggle Theme">
                        <?php if($_SESSION['theme'] === 'light'): ?>
                        <!-- Moon icon for dark mode -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                        <?php else: ?>
                        <!-- Sun icon for light mode -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Dashboard</span>
                </div>
                </a>
                
                <!-- Inventory -->
                <a href="products.php" style="text-decoration: none; color:inherit;">
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'):?>
                <div class="nav-item" data-id="inventory">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Inventory</span>
                </div>
                <?php endif; ?>
                </a>
                
                <!-- Purchases -->
                <a href="purchases.php" style="text-decoration: none; color:inherit;">
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'):?>
                <div class="nav-item" data-id="purchases">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                    <span>Sales</span>
                </div>
                </a>
                
                <!-- Transactions History -->
                <a href="account_info.php" style="text-decoration: none; color:inherit;">
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'):?>
                <div class="nav-item" data-id="transactions">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <?php if ($_SESSION['role'] == 'admin'):?>
                <div class="nav-item" data-id="reports">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <?php if ($_SESSION['role'] == 'admin'):?>
                <div class="nav-item" data-id="users">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <div class="nav-item active" data-id="settings">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>Settings</span>
                </div>
                </a>
            </nav>
            
            <!-- Logout Button - Positioned at the bottom -->
            <a href="login.php" style="text-decoration: none; color:inherit;">
            <div class="logout-container">
                <button class="logout-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <div class="content-area">
                <!-- Profile Section -->
                <div id="profile-section" class="profile-section">
                    <h2>Your Profile</h2>
                    
                    <div class="profile-info">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <div class="avatar-placeholder">
                                    <span id="profile-initials">GS</span>
                                </div>
                            </div>
                            <div class="profile-details">
                                <h1 id="profile-name"><?php echo isset($user['store_name']) ? $user['store_name'] : 'Grocery Store'; ?></h1>
                                <p id="profile-email"><?php echo isset($user['email']) ? $user['email'] : 'supermarket@gmail.com'; ?></p>

                                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                                <button id="edit-profile-btn" class="secondary-btn"><b>Edit Profile</b></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="profile-details-section">
                            <div class="profile-section-card">
                                <h3>Store Information</h3>
                                <div class="profile-field">
                                    <label>Store Name:</label>
                                    <span id="profile-gender"><?php echo isset($user['store_name']) ? $user['store_name'] : 'Grocery Store'; ?></span>
                                </div>
                                <div class="profile-field">
                                    <label>Location:</label>
                                    <span id="profile-dob"><?php echo isset($user['store_location']) ? $user['store_location'] : 'Gacuriro'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Edit Profile Form -->
                         <?php if($_SESSION['role'] == 'admin'):?>
                        <div id="edit-profile-form">
                            <h2>Edit Profile</h2>
                            <?php if(isset($_SESSION['update_message'])): ?>
        <div class="success-message"><?php echo $_SESSION['update_message']; ?></div>
        <?php unset($_SESSION['update_message']); ?>
    <?php endif; ?>
                           
                            
    <form method="POST" action="">
        <!-- Store Information Section -->
        <h3 class="form-section-title">Store Information</h3>
        <div class="form-group">
            <label for="store_name">Store Name</label>
            <input type="text" id="store_name" name="store_name" value="<?php echo isset($user['store_name']) ? $user['store_name'] : 'Grocery Store'; ?>">
        </div>
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo isset($stores['store_location']) ? $stores['store_location'] : 'Gacuriro'; ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo isset($user['email']) ? $user['email'] : 'supermarket@gmail.com'; ?>">
        </div>
        
        <!-- Security Section -->
        <h3 class="form-section-title">Security</h3>
        <div class="form-group">
            <label for="password">Change Password</label>
            <input type="password" id="password" name="password" placeholder="Enter new password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
        </div>
        <div class="button-group">
            <button type="submit" name="save_profile" class="primary-btn">Save Changes</button>
            <button type="button" id="cancel-edit-btn" class="secondary-btn cancel-btn">Cancel</button>
        </div>
    </form>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
          
        </div>

        </div>
       
    </div>
      <!------------Header section------------->
      <div class="header">
    <div class="search-container">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        document.addEventListener('DOMContentLoaded', function() {
   
   initializeNotifications();
});

function initializeNotifications() {
   const notificationBell = document.querySelector('.notification-bell');
   const notificationContainer = document.querySelector('.notification-bell-container');
   
   // Toggle dropdown when clicking the bell
   notificationBell.addEventListener('click', function(e) {
       e.stopPropagation();
       notificationContainer.classList.toggle('active');
   });
   
   // Close dropdown when clicking outside
   document.addEventListener('click', function(e) {
       if (!notificationContainer.contains(e.target)) {
           notificationContainer.classList.remove('active');
       }
   });
   // Mark all as read functionality
   const markAllReadBtn = document.querySelector('.mark-all-read');
   markAllReadBtn.addEventListener('click', function(e) {
       e.preventDefault();
       
       // Get all unread notifications
       const unreadNotifications = document.querySelectorAll('.notification-item.unread');
       
       // Remove unread class
       unreadNotifications.forEach(function(notification) {
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
        // DOM Elements
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const editProfileForm = document.getElementById('edit-profile-form');
        const saveProfileBtn = document.getElementById('save-profile-btn');
        const cancelEditBtn = document.getElementById('cancel-edit-btn');
        
        // Edit Profile functionality - Toggle form visibility
        editProfileBtn.addEventListener('click', () => {
            try {
                // Helper function to safely set form values
                const safeSetValue = (inputId, profileId, transformFn = (val) => val) => {
                    const inputElement = document.getElementById(inputId);
                    const profileElement = document.getElementById(profileId);
                    
                    if (inputElement && profileElement) {
                        let value = profileElement.innerText;
                        if (transformFn) {
                            value = transformFn(value);
                        }
                        inputElement.value = value;
                    }
                };
                
                // Personal Information
                safeSetValue('edit-name', 'profile-name');
                safeSetValue('edit-gender', 'profile-gender', (val) => val.toLowerCase());
                
                // Show the edit form
                editProfileForm.style.display = 'block';
                editProfileBtn.style.display = 'none';
            } catch (error) {
                console.error('Error opening edit profile form:', error);
                // Still attempt to show the form even if there was an error
                editProfileForm.style.display = 'block';
                editProfileBtn.style.display = 'none';
            }
        });

        // Cancel button functionality
        cancelEditBtn.addEventListener('click', () => {
            editProfileForm.style.display = 'none';
            editProfileBtn.style.display = 'block';
        });

        // Save profile changes
        saveProfileBtn.addEventListener('click', () => {
            try {
                // Helper functions
                const safeGetValue = (id) => {
                    const element = document.getElementById(id);
                    return element ? element.value : null;
                };
                
                const safeSetText = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.innerText = value;
                };
                
                // Get all values
                const formData = {
                    // Personal Information
                    name: safeGetValue('edit-name'),
                    gender: safeGetValue('edit-gender'),
                    password: safeGetValue('edit-password'),
                    confirmPassword: safeGetValue('edit-confirm-password')
                };
                
                // Check if required fields have values
                const requiredFields = ['name', 'gender'];
                const missingRequired = requiredFields.some(field => 
                    document.getElementById('edit-' + field.replace(/([A-Z])/g, '-$1').toLowerCase()) && 
                    !formData[field]
                );
                
                // Check if passwords match if both are filled
                const passwordsMatch = !formData.password || 
                                      (formData.password && formData.password === formData.confirmPassword);
                
                if (!missingRequired && passwordsMatch) {
                    // Update Personal Information
                    if (formData.name) {
                        safeSetText('profile-name', formData.name);
                        safeSetText('profile-initials', 
                            formData.name.split(' ').map(n => n[0]).join('').toUpperCase());
                    }
                    
                    if (formData.gender) {
                        safeSetText('profile-gender', 
                            formData.gender.charAt(0).toUpperCase() + formData.gender.slice(1));
                    }
                    
                    // Handle password change if provided
                    if (formData.password) {
                        // In a real application, you would send this to the server
                        console.log('Password changed');
                    }
                    
                    // Hide the form and show edit button
                    editProfileForm.style.display = 'none';
                    editProfileBtn.style.display = 'block';
                } else {
                    if (!passwordsMatch) {
                        alert('Passwords do not match. Please try again.');
                    } else {
                        alert('Please fill in all required fields.');
                    }
                }
            } catch (error) {
                console.error('Error saving profile:', error);
                alert('There was an error saving your profile. Please try again.');
            }
        });
        </script>
        </body>
        </html>
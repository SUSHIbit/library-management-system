<?php
/**
 * Header Template
 * Library Management System
 * 
 * Common header template included in all pages.
 * Contains navigation, user menu, and basic page structure.
 * 
 * @author Final Year Student
 * @version 1.0
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

// Get current user information
$current_user = getCurrentUser();
$page_title = $page_title ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo APP_DESCRIPTION; ?>">
    <meta name="author" content="<?php echo APP_AUTHOR; ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <!-- Additional CSS -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_URL . '/css/' . $css_file; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body class="<?php echo $body_class ?? ''; ?>">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner" style="display: none;">
        <div class="spinner"></div>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="nav-container">
                <!-- Logo and Brand -->
                <div class="nav-brand">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </button>
                    <a href="<?php echo BASE_URL; ?>/index.php" class="brand-link">
                        <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="Logo" class="brand-logo">
                        <span class="brand-text"><?php echo APP_NAME; ?></span>
                    </a>
                </div>
                
                <!-- Search Bar -->
                <div class="nav-search">
                    <form class="search-form" action="<?php echo BASE_URL; ?>/search.php" method="GET">
                        <input 
                            type="text" 
                            name="q" 
                            class="search-input" 
                            placeholder="Search books, authors, users..."
                            value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                        >
                        <button type="submit" class="search-btn">
                            <i class="icon-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Right Navigation -->
                <div class="nav-right">
                    <!-- Notifications -->
                    <div class="nav-item dropdown">
                        <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown">
                            <i class="icon-bell"></i>
                            <span class="notification-badge" id="notificationCount">3</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right notification-dropdown">
                            <div class="dropdown-header">
                                <h6>Notifications</h6>
                                <a href="#" class="mark-all-read">Mark all as read</a>
                            </div>
                            <div class="dropdown-body">
                                <a href="#" class="dropdown-item notification-item unread">
                                    <div class="notification-icon">
                                        <i class="icon-book text-warning"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">Book Due Tomorrow</div>
                                        <div class="notification-text">Introduction to Algorithms is due tomorrow</div>
                                        <div class="notification-time">2 hours ago</div>
                                    </div>
                                </a>
                                <a href="#" class="dropdown-item notification-item">
                                    <div class="notification-icon">
                                        <i class="icon-user text-success"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">New User Registration</div>
                                        <div class="notification-text">John Doe has registered</div>
                                        <div class="notification-time">5 hours ago</div>
                                    </div>
                                </a>
                                <a href="#" class="dropdown-item notification-item">
                                    <div class="notification-icon">
                                        <i class="icon-dollar-sign text-danger"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">Fine Payment</div>
                                        <div class="notification-text">RM10.00 fine payment received</div>
                                        <div class="notification-time">1 day ago</div>
                                    </div>
                                </a>
                            </div>
                            <div class="dropdown-footer">
                                <a href="<?php echo BASE_URL; ?>/notifications.php">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="nav-item dropdown">
                        <button type="button" class="nav-link dropdown-toggle user-menu" data-toggle="dropdown">
                            <img 
                                src="<?php echo getUserAvatar($current_user['email']); ?>" 
                                alt="Avatar" 
                                class="user-avatar"
                            >
                            <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                            <i class="icon-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right user-dropdown">
                            <div class="dropdown-header">
                                <div class="user-info">
                                    <div class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                                    <div class="user-role"><?php echo ucfirst($current_user['role']); ?></div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo BASE_URL; ?>/users/profile.php" class="dropdown-item">
                                <i class="icon-user"></i> My Profile
                            </a>
                            <a href="<?php echo BASE_URL; ?>/users/change-password.php" class="dropdown-item">
                                <i class="icon-lock"></i> Change Password
                            </a>
                            <?php if ($current_user['role'] === 'student' || $current_user['role'] === 'staff'): ?>
                            <a href="<?php echo BASE_URL; ?>/borrowing/history.php" class="dropdown-item">
                                <i class="icon-history"></i> My Borrowing History
                            </a>
                            <a href="<?php echo BASE_URL; ?>/fines/index.php" class="dropdown-item">
                                <i class="icon-dollar-sign"></i> My Fines
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <?php if (hasRole(['admin'])): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/settings.php" class="dropdown-item">
                                <i class="icon-settings"></i> System Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="dropdown-item text-danger">
                                <i class="icon-log-out"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-content">
                <!-- User Info Panel -->
                <div class="sidebar-user">
                    <img 
                        src="<?php echo getUserAvatar($current_user['email']); ?>" 
                        alt="Avatar" 
                        class="sidebar-user-avatar"
                    >
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                        <div class="sidebar-user-role"><?php echo ucfirst($current_user['role']); ?></div>
                    </div>
                </div>
                
                <!-- Navigation Menu -->
                <nav class="sidebar-nav">
                    <ul class="nav-menu">
                        <?php
                        $current_page = basename($_SERVER['PHP_SELF']);
                        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
                        
                        foreach ($nav_menu as $key => $menu_item):
                            // Check if user has permission to access this menu
                            if (!hasRole($menu_item['roles'])) {
                                continue;
                            }
                            
                            // Determine if menu item is active
                            $is_active = false;
                            if ($key === 'dashboard' && $current_page === 'index.php') {
                                $is_active = true;
                            } elseif ($key === $current_dir) {
                                $is_active = true;
                            }
                            
                            $menu_url = $menu_item['url'];
                            // Adjust URL if it doesn't start with BASE_URL
                            if (!str_starts_with($menu_url, 'http')) {
                                $menu_url = BASE_URL . '/' . ltrim($menu_url, '/');
                            }
                        ?>
                        <li class="nav-item<?php echo $is_active ? ' active' : ''; ?>">
                            <a href="<?php echo $menu_url; ?>" class="nav-link">
                                <i class="nav-icon icon-<?php echo $menu_item['icon']; ?>"></i>
                                <span class="nav-text"><?php echo $menu_item['title']; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                
                <!-- Sidebar Footer -->
                <div class="sidebar-footer">
                    <div class="app-info">
                        <div class="app-name"><?php echo APP_NAME; ?></div>
                        <div class="app-version">v<?php echo APP_VERSION; ?></div>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-content">
                    <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    
                    <!-- Breadcrumb Navigation -->
                    <?php if (!empty($breadcrumbs)): ?>
                        <?php echo createBreadcrumb($breadcrumbs); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Page Actions -->
                <?php if (isset($page_actions)): ?>
                <div class="page-actions">
                    <?php echo $page_actions; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Flash Messages -->
            <div class="flash-messages">
                <?php
                $success_message = getSuccessMessage();
                $error_message = getErrorMessage();
                
                if ($success_message):
                ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                        <span>&times;</span>
                    </button>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                        <span>&times;</span>
                    </button>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Page Content Container -->
            <div class="page-content"><?php // Content will be inserted here by including pages ?>
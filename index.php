<?php
/**
 * Main Dashboard Page - Phase 2 Updated
 * Library Management System
 * 
 * This is the main dashboard page that displays different views based on user roles.
 * Updated for Phase 2 with complete book and category management integration.
 * 
 * @author Final Year Student
 * @version 2.0
 */

// Define library system constant
define('LIBRARY_SYSTEM', true);

// Include configuration
require_once 'config/config.php';

// Require user to be logged in
requireLogin();

// Get current user
$current_user = getCurrentUser();

// Set page variables
$page_title = 'Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'index.php']
];

// Initialize dashboard data
$dashboard_data = [];

try {
    // Get role-specific dashboard data
    if (hasRole(['admin', 'librarian'])) {
        // Admin/Librarian Dashboard Data
        $dashboard_data = [
            'total_books' => getSingleRow("SELECT COUNT(*) as count FROM books")['count'] ?? 0,
            'total_categories' => getSingleRow("SELECT COUNT(*) as count FROM categories")['count'] ?? 0,
            'total_users' => getSingleRow("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'] ?? 0,
            'active_borrowings' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'")['count'] ?? 0,
            'overdue_books' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed' AND due_date < CURDATE()")['count'] ?? 0,
            'books_added_today' => getSingleRow("SELECT COUNT(*) as count FROM books WHERE DATE(created_at) = CURDATE()")['count'] ?? 0,
            'available_books' => getSingleRow("SELECT SUM(available_quantity) as total FROM books")['total'] ?? 0,
            'borrowed_books' => getSingleRow("SELECT SUM(quantity - available_quantity) as total FROM books")['total'] ?? 0,
            'total_fines' => getSingleRow("SELECT COALESCE(SUM(amount - paid_amount), 0) as total FROM fines WHERE status != 'paid'")['total'] ?? 0,
            'new_users_this_month' => getSingleRow("SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['count'] ?? 0,
            'popular_categories' => getAllRows("
                SELECT c.name, c.id, COUNT(b.id) as book_count 
                FROM categories c 
                LEFT JOIN books b ON c.id = b.category_id 
                GROUP BY c.id, c.name 
                ORDER BY book_count DESC 
                LIMIT 5
            "),
            'recent_books' => getAllRows("
                SELECT b.*, c.name as category_name 
                FROM books b 
                JOIN categories c ON b.category_id = c.id 
                ORDER BY b.created_at DESC 
                LIMIT 8
            "),
            'low_stock_books' => getAllRows("
                SELECT b.*, c.name as category_name 
                FROM books b 
                JOIN categories c ON b.category_id = c.id 
                WHERE b.available_quantity <= 1 
                ORDER BY b.available_quantity ASC, b.title ASC 
                LIMIT 5
            "),
            'recent_borrowings' => getAllRows("
                SELECT br.*, b.title, b.author, u.full_name, u.username 
                FROM borrowings br 
                JOIN books b ON br.book_id = b.id 
                JOIN users u ON br.user_id = u.id 
                ORDER BY br.created_at DESC 
                LIMIT 6
            "),
            'category_stats' => getAllRows("
                SELECT c.name, COUNT(b.id) as book_count, SUM(b.quantity) as total_copies
                FROM categories c 
                LEFT JOIN books b ON c.id = b.category_id 
                GROUP BY c.id, c.name 
                ORDER BY book_count DESC
            ")
        ];
    } else {
        // Student/Staff Dashboard Data
        $user_id = $current_user['id'];
        $dashboard_data = [
            'my_borrowed_books' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'", "i", $user_id)['count'] ?? 0,
            'my_overdue_books' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed' AND due_date < CURDATE()", "i", $user_id)['count'] ?? 0,
            'my_total_fines' => getSingleRow("SELECT COALESCE(SUM(amount - paid_amount), 0) as total FROM fines WHERE user_id = ? AND status != 'paid'", "i", $user_id)['total'] ?? 0,
            'books_borrowed_this_month' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())", "i", $user_id)['count'] ?? 0,
            'available_books_count' => getSingleRow("SELECT COUNT(*) as count FROM books WHERE available_quantity > 0")['count'] ?? 0,
            'total_books_in_library' => getSingleRow("SELECT COUNT(*) as count FROM books")['count'] ?? 0,
            'my_current_borrowings' => getAllRows("
                SELECT br.*, b.title, b.author, b.isbn, c.name as category_name,
                       DATEDIFF(br.due_date, CURDATE()) as days_until_due
                FROM borrowings br 
                JOIN books b ON br.book_id = b.id 
                JOIN categories c ON b.category_id = c.id
                WHERE br.user_id = ? AND br.status = 'borrowed'
                ORDER BY br.due_date ASC
            ", "i", $user_id),
            'my_borrowing_history' => getAllRows("
                SELECT br.*, b.title, b.author, c.name as category_name
                FROM borrowings br 
                JOIN books b ON br.book_id = b.id 
                JOIN categories c ON b.category_id = c.id
                WHERE br.user_id = ? 
                ORDER BY br.created_at DESC 
                LIMIT 5
            ", "i", $user_id),
            'recommended_books' => getAllRows("
                SELECT b.*, c.name as category_name
                FROM books b 
                JOIN categories c ON b.category_id = c.id 
                WHERE b.available_quantity > 0 
                ORDER BY RAND() 
                LIMIT 6
            "),
            'popular_categories' => getAllRows("
                SELECT c.name, c.id, COUNT(b.id) as book_count 
                FROM categories c 
                LEFT JOIN books b ON c.id = b.category_id 
                WHERE b.available_quantity > 0
                GROUP BY c.id, c.name 
                ORDER BY book_count DESC 
                LIMIT 5
            ")
        ];
    }
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    showError("Failed to load dashboard data. Please try again.");
}

// Page actions for header
if (hasRole(['admin', 'librarian'])) {
    $page_actions = '
        <a href="books/add.php" class="btn btn-primary">
            <i class="icon-plus"></i> Add Book
        </a>
        <a href="categories/add.php" class="btn btn-secondary">
            <i class="icon-folder"></i> Add Category
        </a>
    ';
}

// Include header
include_once 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-container">
    
    <?php if (hasRole(['admin', 'librarian'])): ?>
        <!-- Admin/Librarian Dashboard -->
        
        <!-- Main Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="icon-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['total_books']); ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
                <div class="stat-footer">
                    <small><?php echo $dashboard_data['available_books']; ?> available</small>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="icon-folder"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['total_categories']); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-footer">
                    <small>+<?php echo $dashboard_data['books_added_today']; ?> books today</small>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="icon-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['total_users']); ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-footer">
                    <small>+<?php echo $dashboard_data['new_users_this_month']; ?> this month</small>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="icon-refresh-cw"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['active_borrowings']); ?></div>
                    <div class="stat-label">Active Borrowings</div>
                </div>
                <div class="stat-footer">
                    <small><?php echo $dashboard_data['borrowed_books']; ?> books out</small>
                </div>
            </div>
            
            <?php if ($dashboard_data['overdue_books'] > 0): ?>
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="icon-alert-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['overdue_books']); ?></div>
                    <div class="stat-label">Overdue Books</div>
                </div>
                <div class="stat-footer">
                    <small><?php echo formatCurrency($dashboard_data['total_fines']); ?> in fines</small>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Recent Books -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-book"></i> Recently Added Books
                    </h3>
                    <a href="books/index.php" class="card-action">View All Books</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['recent_books'])): ?>
                        <div class="recent-books-grid">
                            <?php foreach (array_slice($dashboard_data['recent_books'], 0, 4) as $book): ?>
                                <div class="recent-book-item">
                                    <div class="book-cover-small">
                                        <i class="icon-book"></i>
                                    </div>
                                    <div class="book-details">
                                        <h4 class="book-title"><?php echo htmlspecialchars(truncateText($book['title'], 40)); ?></h4>
                                        <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                        <span class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                        <div class="availability-info">
                                            <span class="available-count"><?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?> available</span>
                                        </div>
                                    </div>
                                    <div class="book-actions">
                                        <a href="books/view.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="icon-book"></i>
                            <p>No books added yet.</p>
                            <a href="books/add.php" class="btn btn-primary">Add First Book</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Popular Categories -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-folder"></i> Popular Categories
                    </h3>
                    <a href="categories/index.php" class="card-action">Manage Categories</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['popular_categories'])): ?>
                        <div class="category-list">
                            <?php foreach ($dashboard_data['popular_categories'] as $category): ?>
                                <div class="category-item">
                                    <div class="category-icon">
                                        <i class="icon-folder"></i>
                                    </div>
                                    <div class="category-info">
                                        <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                                        <span class="book-count"><?php echo $category['book_count']; ?> books</span>
                                    </div>
                                    <div class="category-actions">
                                        <a href="books/index.php?category=<?php echo $category['id']; ?>" class="btn btn-sm btn-secondary">Browse</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="icon-folder"></i>
                            <p>No categories created yet.</p>
                            <a href="categories/add.php" class="btn btn-primary">Add First Category</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <?php if (!empty($dashboard_data['low_stock_books'])): ?>
            <div class="dashboard-card alert-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-alert-triangle"></i> Low Stock Alert
                    </h3>
                    <span class="alert-badge"><?php echo count($dashboard_data['low_stock_books']); ?></span>
                </div>
                <div class="card-body">
                    <div class="low-stock-list">
                        <?php foreach ($dashboard_data['low_stock_books'] as $book): ?>
                            <div class="low-stock-item">
                                <div class="book-info">
                                    <h4><?php echo htmlspecialchars(truncateText($book['title'], 35)); ?></h4>
                                    <p><?php echo htmlspecialchars($book['author']); ?></p>
                                </div>
                                <div class="stock-info">
                                    <span class="stock-count <?php echo $book['available_quantity'] == 0 ? 'out-of-stock' : 'low-stock'; ?>">
                                        <?php echo $book['available_quantity']; ?> left
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recent Activity -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-activity"></i> Recent Borrowings
                    </h3>
                    <a href="borrowing/index.php" class="card-action">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['recent_borrowings'])): ?>
                        <div class="activity-list">
                            <?php foreach ($dashboard_data['recent_borrowings'] as $borrowing): ?>
                                <div class="activity-item">
                                    <div class="activity-avatar">
                                        <i class="icon-user"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <strong><?php echo htmlspecialchars($borrowing['full_name']); ?></strong>
                                            borrowed "<em><?php echo htmlspecialchars(truncateText($borrowing['title'], 30)); ?></em>"
                                        </div>
                                        <div class="activity-time">
                                            <?php echo timeAgo($borrowing['created_at']); ?> • Due: <?php echo formatDate($borrowing['due_date']); ?>
                                        </div>
                                    </div>
                                    <div class="activity-status">
                                        <?php echo createStatusBadge($borrowing['status']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="icon-refresh-cw"></i>
                            <p>No borrowing activity yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-zap"></i> Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="books/add.php" class="quick-action-btn">
                            <i class="icon-plus"></i>
                            <span>Add Book</span>
                        </a>
                        <a href="categories/add.php" class="quick-action-btn">
                            <i class="icon-folder-plus"></i>
                            <span>Add Category</span>
                        </a>
                        <a href="users/add.php" class="quick-action-btn">
                            <i class="icon-user-plus"></i>
                            <span>Add User</span>
                        </a>
                        <a href="borrowing/index.php" class="quick-action-btn">
                            <i class="icon-refresh-cw"></i>
                            <span>Manage Borrowing</span>
                        </a>
                        <a href="reports/index.php" class="quick-action-btn">
                            <i class="icon-bar-chart-2"></i>
                            <span>View Reports</span>
                        </a>
                        <a href="books/index.php" class="quick-action-btn">
                            <i class="icon-search"></i>
                            <span>Browse Books</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Student/Staff Dashboard -->
        
        <!-- User Statistics -->
        <div class="user-stats-grid">
            <div class="user-stat-card primary">
                <div class="stat-icon">
                    <i class="icon-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $dashboard_data['my_borrowed_books']; ?></div>
                    <div class="stat-label">Currently Borrowed</div>
                </div>
            </div>
            
            <div class="user-stat-card <?php echo $dashboard_data['my_overdue_books'] > 0 ? 'danger' : 'success'; ?>">
                <div class="stat-icon">
                    <i class="icon-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $dashboard_data['my_overdue_books']; ?></div>
                    <div class="stat-label">Overdue Books</div>
                </div>
            </div>
            
            <div class="user-stat-card <?php echo $dashboard_data['my_total_fines'] > 0 ? 'warning' : 'success'; ?>">
                <div class="stat-icon">
                    <i class="icon-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo formatCurrency($dashboard_data['my_total_fines']); ?></div>
                    <div class="stat-label">Outstanding Fines</div>
                </div>
            </div>
            
            <div class="user-stat-card info">
                <div class="stat-icon">
                    <i class="icon-calendar"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $dashboard_data['books_borrowed_this_month']; ?></div>
                    <div class="stat-label">Borrowed This Month</div>
                </div>
            </div>
        </div>
        
        <!-- Library Overview -->
        <div class="library-overview">
            <div class="overview-card">
                <h3><i class="icon-database"></i> Library Collection</h3>
                <div class="overview-stats">
                    <div class="overview-item">
                        <span class="overview-number"><?php echo number_format($dashboard_data['total_books_in_library']); ?></span>
                        <span class="overview-label">Total Books</span>
                    </div>
                    <div class="overview-item">
                        <span class="overview-number"><?php echo number_format($dashboard_data['available_books_count']); ?></span>
                        <span class="overview-label">Available Now</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Dashboard Grid -->
        <div class="user-dashboard-grid">
            
            <!-- My Current Books -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-book-open"></i> My Current Books
                    </h3>
                    <a href="borrowing/history.php" class="card-action">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['my_current_borrowings'])): ?>
                        <div class="my-books-list">
                            <?php foreach ($dashboard_data['my_current_borrowings'] as $borrowing): ?>
                                <div class="my-book-item">
                                    <div class="book-cover">
                                        <i class="icon-book"></i>
                                    </div>
                                    <div class="book-details">
                                        <h4 class="book-title"><?php echo htmlspecialchars(truncateText($borrowing['title'], 35)); ?></h4>
                                        <p class="book-author"><?php echo htmlspecialchars($borrowing['author']); ?></p>
                                        <span class="book-category"><?php echo htmlspecialchars($borrowing['category_name']); ?></span>
                                        <div class="due-info">
                                            <span class="due-date">Due: <?php echo formatDate($borrowing['due_date']); ?></span>
                                            <?php if ($borrowing['days_until_due'] < 0): ?>
                                                <span class="overdue-badge">Overdue</span>
                                            <?php elseif ($borrowing['days_until_due'] <= 3): ?>
                                                <span class="due-soon-badge">Due Soon</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="icon-book"></i>
                            <p>You have no books currently borrowed.</p>
                            <a href="books/index.php" class="btn btn-primary">Browse Books</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recommended Books -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-star"></i> Recommended Books
                    </h3>
                    <a href="books/index.php" class="card-action">Browse All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['recommended_books'])): ?>
                        <div class="recommended-books-grid">
                            <?php foreach (array_slice($dashboard_data['recommended_books'], 0, 6) as $book): ?>
                                <div class="recommended-book-card">
                                    <div class="book-cover">
                                        <i class="icon-book"></i>
                                    </div>
                                    <div class="book-info">
                                        <h4 class="book-title"><?php echo htmlspecialchars(truncateText($book['title'], 25)); ?></h4>
                                        <p class="book-author"><?php echo htmlspecialchars(truncateText($book['author'], 20)); ?></p>
                                        <span class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                    </div>
                                    <div class="book-actions">
                                        <a href="books/view.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-secondary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No books available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Browse by Category -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-folder"></i> Browse by Category
                    </h3>
                    <a href="books/index.php" class="card-action">All Categories</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['popular_categories'])): ?>
                        <div class="category-browse-list">
                            <?php foreach ($dashboard_data['popular_categories'] as $category): ?>
                                <a href="books/index.php?category=<?php echo $category['id']; ?>" class="category-browse-item">
                                    <div class="category-icon">
                                        <i class="icon-folder"></i>
                                    </div>
                                    <div class="category-info">
                                        <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                                        <span><?php echo $category['book_count']; ?> books available</span>
                                    </div>
                                    <div class="category-arrow">
                                        <i class="icon-chevron-right"></i>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No categories available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-zap"></i> Quick Links
                    </h3>
                </div>
                <div class="card-body">
                    <div class="quick-links-grid">
                        <a href="books/index.php" class="quick-link-item">
                            <i class="icon-search"></i>
                            <span>Search Books</span>
                        </a>
                        <a href="borrowing/history.php" class="quick-link-item">
                            <i class="icon-history"></i>
                            <span>My History</span>
                        </a>
                        <a href="fines/index.php" class="quick-link-item">
                            <i class="icon-dollar-sign"></i>
                            <span>My Fines</span>
                        </a>
                        <a href="users/profile.php" class="quick-link-item">
                            <i class="icon-user"></i>
                            <span>My Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
    
</div>

<!-- Dashboard-specific styles -->
<style>
/* Dashboard Container */
.dashboard-container {
    padding: 0;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    border-left: 4px solid #e4e4e7;
    transition: all 0.2s ease;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.stat-card.primary { border-left-color: #3f3f46; }
.stat-card.success { border-left-color: #16a34a; }
.stat-card.warning { border-left-color: #f59e0b; }
.stat-card.danger { border-left-color: #dc2626; }
.stat-card.info { border-left-color: #0ea5e9; }

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.stat-card.primary .stat-icon { background: #f4f4f5; color: #3f3f46; }
.stat-card.success .stat-icon { background: #f0fdf4; color: #16a34a; }
.stat-card.warning .stat-icon { background: #fffbeb; color: #f59e0b; }
.stat-card.danger .stat-icon { background: #fef2f2; color: #dc2626; }
.stat-card.info .stat-icon { background: #f0f9ff; color: #0ea5e9; }

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #18181b;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    color: #71717a;
    font-size: 14px;
    font-weight: 500;
}

.stat-footer {
    margin-top: 8px;
    font-size: 12px;
    color: #71717a;
}

/* User Stats Grid */
.user-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 30px;
}

.user-stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    border-left: 4px solid #e4e4e7;
}

.user-stat-card.primary { border-left-color: #3f3f46; }
.user-stat-card.success { border-left-color: #16a34a; }
.user-stat-card.warning { border-left-color: #f59e0b; }
.user-stat-card.danger { border-left-color: #dc2626; }
.user-stat-card.info { border-left-color: #0ea5e9; }

/* Library Overview */
.library-overview {
    margin-bottom: 30px;
}

.overview-card {
    background: linear-gradient(135deg, #3f3f46 0%, #52525b 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.overview-card h3 {
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.overview-stats {
    display: flex;
    gap: 32px;
}

.overview-item {
    text-align: center;
}

.overview-number {
    display: block;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 4px;
}

.overview-label {
    font-size: 14px;
    opacity: 0.9;
}

/* Dashboard Grid */
.dashboard-grid, .user-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border: 1px solid #f1f5f9;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #e4e4e7;
    background: #fafafa;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #18181b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-action {
    color: #3f3f46;
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.card-action:hover {
    color: #18181b;
}

.card-body {
    padding: 20px;
}

/* Alert Card */
.alert-card .card-header {
    background: #fef2f2;
    color: #dc2626;
}

.alert-badge {
    background: #dc2626;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Recent Books Grid */
.recent-books-grid {
    display: grid;
    gap: 16px;
}

.recent-book-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.book-cover-small {
    width: 60px;
    height: 80px;
    background: #e4e4e7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #71717a;
    flex-shrink: 0;
}

.book-details {
    flex: 1;
}

.book-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #18181b;
    line-height: 1.3;
}

.book-author {
    font-size: 13px;
    color: #52525b;
    margin-bottom: 4px;
    font-style: italic;
}

.book-category {
    display: inline-block;
    font-size: 11px;
    background: #3f3f46;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.availability-info {
    margin-top: 6px;
}

.available-count {
    font-size: 12px;
    color: #16a34a;
    font-weight: 500;
}

.book-actions {
    display: flex;
    align-items: center;
}

/* Category List */
.category-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.category-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.category-icon {
    width: 40px;
    height: 40px;
    background: #3f3f46;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.category-info {
    flex: 1;
}

.category-info h4 {
    margin: 0 0 2px 0;
    font-size: 14px;
    color: #18181b;
}

.book-count {
    font-size: 12px;
    color: #71717a;
}

/* Low Stock List */
.low-stock-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.low-stock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #fef2f2;
    border-radius: 8px;
    border: 1px solid #fecaca;
}

.low-stock-item .book-info h4 {
    margin: 0 0 2px 0;
    font-size: 14px;
    color: #18181b;
}

.low-stock-item .book-info p {
    margin: 0;
    font-size: 12px;
    color: #71717a;
}

.stock-count {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 12px;
}

.stock-count.low-stock {
    background: #fed7aa;
    color: #d97706;
}

.stock-count.out-of-stock {
    background: #fecaca;
    color: #dc2626;
}

/* Activity List */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
}

.activity-avatar {
    width: 32px;
    height: 32px;
    background: #e4e4e7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #71717a;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-size: 14px;
    color: #18181b;
    margin-bottom: 2px;
    line-height: 1.3;
}

.activity-time {
    font-size: 12px;
    color: #71717a;
}

/* My Books List */
.my-books-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.my-book-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.book-cover {
    width: 50px;
    height: 70px;
    background: #e4e4e7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #71717a;
    flex-shrink: 0;
}

.due-info {
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.due-date {
    font-size: 12px;
    color: #71717a;
}

.overdue-badge {
    background: #dc2626;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.due-soon-badge {
    background: #f59e0b;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

/* Recommended Books Grid */
.recommended-books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.recommended-book-card {
    background: #f9fafb;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.recommended-book-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.recommended-book-card .book-cover {
    width: 40px;
    height: 60px;
    margin: 0 auto 8px;
    background: #e4e4e7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #71717a;
}

.recommended-book-card .book-title {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #18181b;
    line-height: 1.2;
}

.recommended-book-card .book-author {
    font-size: 11px;
    color: #71717a;
    margin-bottom: 6px;
}

/* Category Browse List */
.category-browse-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.category-browse-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.category-browse-item:hover {
    background: #f4f4f5;
    transform: translateX(4px);
}

.category-browse-item .category-info h4 {
    margin: 0 0 2px 0;
    font-size: 14px;
    color: #18181b;
}

.category-browse-item .category-info span {
    font-size: 12px;
    color: #71717a;
}

.category-arrow {
    color: #71717a;
    transition: transform 0.2s ease;
}

.category-browse-item:hover .category-arrow {
    transform: translateX(4px);
}

/* Quick Actions Grid */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #3f3f46;
    transition: all 0.2s ease;
    border: 1px solid #f1f5f9;
    text-align: center;
}

.quick-action-btn:hover {
    background: #3f3f46;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.quick-action-btn i {
    font-size: 18px;
}

.quick-action-btn span {
    font-size: 12px;
    font-weight: 500;
}

/* Quick Links Grid */
.quick-links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
}

.quick-link-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #3f3f46;
    transition: all 0.2s ease;
    border: 1px solid #f1f5f9;
    text-align: center;
}

.quick-link-item:hover {
    background: #f4f4f5;
    color: #18181b;
    transform: translateY(-2px);
}

.quick-link-item i {
    font-size: 18px;
}

.quick-link-item span {
    font-size: 12px;
    font-weight: 500;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #71717a;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
    color: #a1a1aa;
}

.empty-state p {
    margin-bottom: 16px;
    color: #71717a;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .dashboard-grid, .user-dashboard-grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    }
    
    .overview-stats {
        gap: 24px;
    }
}

@media (max-width: 768px) {
    .stats-grid, .user-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .dashboard-grid, .user-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card, .user-stat-card {
        padding: 16px;
    }
    
    .stat-number {
        font-size: 24px;
    }
    
    .card-header, .card-body {
        padding: 15px;
    }
    
    .recent-books-grid {
        gap: 12px;
    }
    
    .recent-book-item {
        flex-direction: column;
        text-align: center;
    }
    
    .book-cover-small {
        margin: 0 auto;
    }
    
    .recommended-books-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
    
    .quick-actions-grid, .quick-links-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    }
    
    .overview-stats {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .stats-grid, .user-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .user-stat-card {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .quick-actions-grid, .quick-links-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .recommended-books-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
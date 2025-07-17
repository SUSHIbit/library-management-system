<?php
/**
 * Main Dashboard Page
 * Library Management System
 * 
 * This is the main dashboard page that displays different views based on user roles.
 * Provides overview statistics, recent activities, and quick access to common functions.
 * 
 * @author Final Year Student
 * @version 1.0
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
            'total_users' => getSingleRow("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'] ?? 0,
            'active_borrowings' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'")['count'] ?? 0,
            'overdue_books' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed' AND due_date < CURDATE()")['count'] ?? 0,
            'total_fines' => getSingleRow("SELECT COALESCE(SUM(amount - paid_amount), 0) as total FROM fines WHERE status != 'paid'")['total'] ?? 0,
            'books_borrowed_today' => getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE DATE(created_at) = CURDATE()")['count'] ?? 0,
            'new_users_this_month' => getSingleRow("SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['count'] ?? 0,
            'popular_books' => getAllRows("
                SELECT b.title, b.author, COUNT(br.id) as borrow_count 
                FROM books b 
                LEFT JOIN borrowings br ON b.id = br.book_id 
                GROUP BY b.id 
                ORDER BY borrow_count DESC 
                LIMIT 5
            "),
            'recent_borrowings' => getAllRows("
                SELECT br.*, b.title, b.author, u.full_name, u.username 
                FROM borrowings br 
                JOIN books b ON br.book_id = b.id 
                JOIN users u ON br.user_id = u.id 
                ORDER BY br.created_at DESC 
                LIMIT 10
            "),
            'overdue_list' => getAllRows("
                SELECT br.*, b.title, b.author, u.full_name, u.username,
                       DATEDIFF(CURDATE(), br.due_date) as days_overdue
                FROM borrowings br 
                JOIN books b ON br.book_id = b.id 
                JOIN users u ON br.user_id = u.id 
                WHERE br.status = 'borrowed' AND br.due_date < CURDATE()
                ORDER BY days_overdue DESC 
                LIMIT 10
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
            'my_current_borrowings' => getAllRows("
                SELECT br.*, b.title, b.author, b.isbn,
                       DATEDIFF(br.due_date, CURDATE()) as days_until_due
                FROM borrowings br 
                JOIN books b ON br.book_id = b.id 
                WHERE br.user_id = ? AND br.status = 'borrowed'
                ORDER BY br.due_date ASC
            ", "i", $user_id),
            'my_borrowing_history' => getAllRows("
                SELECT br.*, b.title, b.author 
                FROM borrowings br 
                JOIN books b ON br.book_id = b.id 
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
            ")
        ];
    }
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    showError("Failed to load dashboard data. Please try again.");
}

// Include header
include_once 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-container">
    
    <?php if (hasRole(['admin', 'librarian'])): ?>
        <!-- Admin/Librarian Dashboard -->
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="icon-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['total_books']); ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
                <div class="stat-change positive">
                    <small>+<?php echo $dashboard_data['books_borrowed_today']; ?> borrowed today</small>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="icon-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['total_users']); ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-change positive">
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
                <div class="stat-change">
                    <small>Currently borrowed</small>
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="icon-alert-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($dashboard_data['overdue_books']); ?></div>
                    <div class="stat-label">Overdue Books</div>
                </div>
                <div class="stat-change negative">
                    <small><?php echo formatCurrency($dashboard_data['total_fines']); ?> in fines</small>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Recent Borrowings -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Borrowings</h3>
                    <a href="borrowing/index.php" class="card-action">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['recent_borrowings'])): ?>
                        <div class="activity-list">
                            <?php foreach (array_slice($dashboard_data['recent_borrowings'], 0, 5) as $borrowing): ?>
                                <div class="activity-item">
                                    <div class="activity-avatar">
                                        <img src="<?php echo getUserAvatar('user@example.com'); ?>" alt="User">
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <strong><?php echo htmlspecialchars($borrowing['full_name']); ?></strong>
                                            borrowed "<em><?php echo htmlspecialchars($borrowing['title']); ?></em>"
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
                            <p>No recent borrowings found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Overdue Books -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Overdue Books</h3>
                    <a href="borrowing/overdue.php" class="card-action">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['overdue_list'])): ?>
                        <div class="overdue-list">
                            <?php foreach (array_slice($dashboard_data['overdue_list'], 0, 5) as $overdue): ?>
                                <div class="overdue-item">
                                    <div class="overdue-info">
                                        <div class="book-title"><?php echo htmlspecialchars($overdue['title']); ?></div>
                                        <div class="borrower-name"><?php echo htmlspecialchars($overdue['full_name']); ?></div>
                                    </div>
                                    <div class="overdue-days">
                                        <span class="days-count"><?php echo $overdue['days_overdue']; ?></span>
                                        <span class="days-label">days</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state success">
                            <i class="icon-check-circle"></i>
                            <p>No overdue books!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Popular Books -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Popular Books</h3>
                    <a href="reports/books.php" class="card-action">View Report</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['popular_books'])): ?>
                        <div class="popular-books-list">
                            <?php foreach ($dashboard_data['popular_books'] as $index => $book): ?>
                                <div class="popular-book-item">
                                    <div class="book-rank"><?php echo $index + 1; ?></div>
                                    <div class="book-info">
                                        <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                        <div class="book-author"><?php echo htmlspecialchars($book['author']); ?></div>
                                    </div>
                                    <div class="borrow-count">
                                        <span class="count"><?php echo $book['borrow_count']; ?></span>
                                        <span class="label">borrows</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No borrowing data available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="books/add.php" class="quick-action-btn">
                            <i class="icon-plus"></i>
                            <span>Add New Book</span>
                        </a>
                        <a href="users/add.php" class="quick-action-btn">
                            <i class="icon-user-plus"></i>
                            <span>Add New User</span>
                        </a>
                        <a href="borrowing/index.php" class="quick-action-btn">
                            <i class="icon-refresh-cw"></i>
                            <span>Borrow/Return</span>
                        </a>
                        <a href="reports/index.php" class="quick-action-btn">
                            <i class="icon-bar-chart-2"></i>
                            <span>View Reports</span>
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
        
        <!-- User Dashboard Grid -->
        <div class="user-dashboard-grid">
            
            <!-- My Current Books -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">My Current Books</h3>
                    <a href="borrowing/history.php" class="card-action">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['my_current_borrowings'])): ?>
                        <div class="my-books-list">
                            <?php foreach ($dashboard_data['my_current_borrowings'] as $borrowing): ?>
                                <div class="my-book-item">
                                    <div class="book-cover">
                                        <div class="book-placeholder">
                                            <i class="icon-book"></i>
                                        </div>
                                    </div>
                                    <div class="book-details">
                                        <div class="book-title"><?php echo htmlspecialchars($borrowing['title']); ?></div>
                                        <div class="book-author"><?php echo htmlspecialchars($borrowing['author']); ?></div>
                                        <div class="due-date">
                                            Due: <?php echo formatDate($borrowing['due_date']); ?>
                                            <?php if ($borrowing['days_until_due'] < 0): ?>
                                                <span class="overdue-badge">Overdue</span>
                                            <?php elseif ($borrowing['days_until_due'] <= 3): ?>
                                                <span class="due-soon-badge">Due Soon</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="book-actions">
                                        <?php if ($borrowing['days_until_due'] <= 7): ?>
                                            <a href="books/renew.php?id=<?php echo $borrowing['id']; ?>" class="btn btn-sm btn-primary">
                                                Renew
                                            </a>
                                        <?php endif; ?>
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
                    <h3 class="card-title">Recommended Books</h3>
                    <a href="books/index.php" class="card-action">Browse All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['recommended_books'])): ?>
                        <div class="recommended-books-grid">
                            <?php foreach (array_slice($dashboard_data['recommended_books'], 0, 6) as $book): ?>
                                <div class="recommended-book-card">
                                    <div class="book-cover">
                                        <div class="book-placeholder">
                                            <i class="icon-book"></i>
                                        </div>
                                    </div>
                                    <div class="book-info">
                                        <div class="book-title"><?php echo htmlspecialchars(truncateText($book['title'], 30)); ?></div>
                                        <div class="book-author"><?php echo htmlspecialchars(truncateText($book['author'], 25)); ?></div>
                                        <div class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></div>
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
            
            <!-- Recent Activity -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">My Recent Activity</h3>
                    <a href="borrowing/history.php" class="card-action">View Full History</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($dashboard_data['my_borrowing_history'])): ?>
                        <div class="activity-timeline">
                            <?php foreach ($dashboard_data['my_borrowing_history'] as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?php echo $activity['status']; ?>"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <?php if ($activity['status'] === 'borrowed'): ?>
                                                Borrowed "<?php echo htmlspecialchars($activity['title']); ?>"
                                            <?php else: ?>
                                                Returned "<?php echo htmlspecialchars($activity['title']); ?>"
                                            <?php endif; ?>
                                        </div>
                                        <div class="timeline-time">
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No recent activity found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Quick Links</h3>
                </div>
                <div class="card-body">
                    <div class="quick-links-grid">
                        <a href="books/index.php" class="quick-link-item">
                            <i class="icon-search"></i>
                            <span>Search Books</span>
                        </a>
                        <a href="borrowing/history.php" class="quick-link-item">
                            <i class="icon-history"></i>
                            <span>Borrowing History</span>
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
/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    border-left: 4px solid #e4e4e7;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.stat-card.primary { border-left-color: #3f3f46; }
.stat-card.success { border-left-color: #16a34a; }
.stat-card.warning { border-left-color: #f59e0b; }
.stat-card.danger { border-left-color: #dc2626; }

.stat-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-card.primary .stat-icon { background: #f4f4f5; color: #3f3f46; }
.stat-card.success .stat-icon { background: #f0fdf4; color: #16a34a; }
.stat-card.warning .stat-icon { background: #fffbeb; color: #f59e0b; }
.stat-card.danger .stat-icon { background: #fef2f2; color: #dc2626; }

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

.stat-change {
    margin-top: 8px;
    font-size: 12px;
}

.stat-change.positive { color: #16a34a; }
.stat-change.negative { color: #dc2626; }

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #e4e4e7;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #18181b;
    margin: 0;
}

.card-action {
    color: #3f3f46;
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
}

.card-action:hover {
    color: #18181b;
}

.card-body {
    padding: 20px;
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

/* Activity Lists */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
}

.activity-avatar img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-size: 14px;
    color: #18181b;
    margin-bottom: 2px;
}

.activity-time {
    font-size: 12px;
    color: #71717a;
}

/* Quick Actions */
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
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #3f3f46;
    transition: all 0.2s ease;
}

.quick-action-btn:hover {
    background: #3f3f46;
    color: white;
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 20px;
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
}

.empty-state.success {
    color: #16a34a;
}

.empty-state.success i {
    opacity: 1;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid,
    .user-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-grid,
    .user-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card,
    .user-stat-card {
        padding: 16px;
    }
    
    .stat-number {
        font-size: 24px;
    }
    
    .card-header,
    .card-body {
        padding: 15px;
    }
}
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
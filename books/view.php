<?php
/**
 * View Book Details Page
 * Library Management System
 */

define('LIBRARY_SYSTEM', true);
require_once '../config/config.php';

requireLogin();

$book_id = (int)($_GET['id'] ?? 0);
if ($book_id <= 0) {
    showError('Invalid book ID.');
    redirect('index.php');
}

// Get book details with category
$book = getSingleRow("
    SELECT b.*, c.name as category_name 
    FROM books b 
    JOIN categories c ON b.category_id = c.id 
    WHERE b.id = ?
", "i", $book_id);

if (!$book) {
    showError('Book not found.');
    redirect('index.php');
}

$page_title = htmlspecialchars($book['title']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Books', 'url' => 'index.php'],
    ['title' => $book['title']]
];

// Get borrowing history for this book
$borrowing_history = getAllRows("
    SELECT br.*, u.full_name, u.username 
    FROM borrowings br 
    JOIN users u ON br.user_id = u.id 
    WHERE br.book_id = ? 
    ORDER BY br.created_at DESC 
    LIMIT 10
", "i", $book_id);

// Check if current user can borrow this book
$can_borrow = false;
$borrow_message = '';

if (hasRole(['student', 'staff'])) {
    if ($book['available_quantity'] > 0) {
        // Check if user has reached borrowing limit
        $user_borrowed = getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'", "i", $current_user['id'])['count'];
        $max_books = getSystemSetting('max_books_per_user', MAX_BOOKS_PER_USER);
        
        if ($user_borrowed >= $max_books) {
            $borrow_message = "You have reached the maximum borrowing limit ($max_books books).";
        } else {
            // Check if user already borrowed this book
            $already_borrowed = getSingleRow("SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status = 'borrowed'", "ii", $current_user['id'], $book_id);
            if ($already_borrowed) {
                $borrow_message = "You have already borrowed this book.";
            } else {
                $can_borrow = true;
            }
        }
    } else {
        $borrow_message = "This book is currently not available.";
    }
}

include_once '../includes/header.php';
?>

<div class="book-details-container">
    <div class="book-details-grid">
        <!-- Book Cover and Actions -->
        <div class="book-cover-section">
            <div class="book-cover-large">
                <?php if ($book['book_cover']): ?>
                    <img src="<?php echo UPLOADS_URL . '/covers/' . $book['book_cover']; ?>" 
                         alt="Book Cover" class="cover-image">
                <?php else: ?>
                    <div class="cover-placeholder">
                        <i class="icon-book"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="availability-info">
                <div class="availability-status <?php echo $book['available_quantity'] > 0 ? 'available' : 'unavailable'; ?>">
                    <?php if ($book['available_quantity'] > 0): ?>
                        <i class="icon-check-circle"></i> Available
                    <?php else: ?>
                        <i class="icon-x-circle"></i> Not Available
                    <?php endif; ?>
                </div>
                <div class="quantity-info">
                    <strong><?php echo $book['available_quantity']; ?></strong> of 
                    <strong><?php echo $book['quantity']; ?></strong> copies available
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="book-actions">
                <?php if ($can_borrow): ?>
                    <a href="../borrowing/borrow.php?book_id=<?php echo $book['id']; ?>" class="btn btn-primary btn-block">
                        <i class="icon-plus"></i> Borrow This Book
                    </a>
                <?php elseif (hasRole(['student', 'staff']) && !empty($borrow_message)): ?>
                    <div class="alert alert-warning">
                        <?php echo $borrow_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (hasPermission($current_user['role'], 'books', 'update')): ?>
                    <a href="edit.php?id=<?php echo $book['id']; ?>" class="btn btn-secondary btn-block">
                        <i class="icon-edit"></i> Edit Book
                    </a>
                <?php endif; ?>

                <?php if (hasPermission($current_user['role'], 'books', 'delete')): ?>
                    <a href="delete.php?id=<?php echo $book['id']; ?>" 
                       class="btn btn-danger btn-block"
                       onclick="return confirm('Are you sure you want to delete this book?')">
                        <i class="icon-trash"></i> Delete Book
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Book Information -->
        <div class="book-info-section">
            <div class="book-header">
                <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                <div class="book-meta">
                    <span class="category-badge"><?php echo htmlspecialchars($book['category_name']); ?></span>
                    <?php if ($book['publication_year']): ?>
                        <span class="year-badge"><?php echo $book['publication_year']; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="book-details">
                <div class="details-grid">
                    <?php if ($book['isbn']): ?>
                    <div class="detail-item">
                        <label>ISBN:</label>
                        <span><?php echo htmlspecialchars($book['isbn']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($book['publisher']): ?>
                    <div class="detail-item">
                        <label>Publisher:</label>
                        <span><?php echo htmlspecialchars($book['publisher']); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="detail-item">
                        <label>Category:</label>
                        <span><?php echo htmlspecialchars($book['category_name']); ?></span>
                    </div>

                    <div class="detail-item">
                        <label>Total Copies:</label>
                        <span><?php echo $book['quantity']; ?></span>
                    </div>

                    <div class="detail-item">
                        <label>Available:</label>
                        <span><?php echo $book['available_quantity']; ?></span>
                    </div>

                    <div class="detail-item">
                        <label>Added:</label>
                        <span><?php echo formatDate($book['created_at'], 'd M Y'); ?></span>
                    </div>
                </div>

                <?php if ($book['description']): ?>
                <div class="book-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Borrowing History -->
    <?php if (hasRole(['admin', 'librarian'])): ?>
    <div class="borrowing-history-section">
        <h3>Borrowing History</h3>
        <?php if (!empty($borrowing_history)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowing_history as $borrowing): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($borrowing['full_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($borrowing['username']); ?></small>
                            </td>
                            <td><?php echo formatDate($borrowing['borrow_date']); ?></td>
                            <td><?php echo formatDate($borrowing['due_date']); ?></td>
                            <td><?php echo $borrowing['return_date'] ? formatDate($borrowing['return_date']) : '-'; ?></td>
                            <td><?php echo createStatusBadge($borrowing['status']); ?></td>
                            <td><?php echo $borrowing['fine_amount'] > 0 ? formatCurrency($borrowing['fine_amount']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No borrowing history for this book yet.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.book-details-container {
    max-width: 1200px;
    margin: 0 auto;
}

.book-details-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.book-cover-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.book-cover-large {
    width: 100%;
    height: 400px;
    background: #f9fafb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e4e4e7;
}

.cover-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.cover-placeholder {
    font-size: 80px;
    color: #71717a;
}

.availability-info {
    background: white;
    padding: 16px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}

.availability-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 600;
    margin-bottom: 8px;
}

.availability-status.available {
    color: #16a34a;
}

.availability-status.unavailable {
    color: #dc2626;
}

.quantity-info {
    color: #71717a;
    font-size: 14px;
}

.book-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.btn-block {
    width: 100%;
    justify-content: center;
}

.book-info-section {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.book-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e4e4e7;
}

.book-title {
    font-size: 28px;
    font-weight: 700;
    color: #18181b;
    margin-bottom: 8px;
    line-height: 1.2;
}

.book-author {
    font-size: 18px;
    color: #52525b;
    font-style: italic;
    margin-bottom: 12px;
}

.book-meta {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.category-badge, .year-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-badge {
    background: #3f3f46;
    color: white;
}

.year-badge {
    background: #f4f4f5;
    color: #52525b;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 30px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item label {
    font-weight: 600;
    color: #71717a;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-item span {
    color: #18181b;
    font-weight: 500;
}

.book-description {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e4e4e7;
}

.book-description h3 {
    margin-bottom: 12px;
    color: #18181b;
}

.book-description p {
    color: #52525b;
    line-height: 1.6;
}

.borrowing-history-section {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.borrowing-history-section h3 {
    margin-bottom: 20px;
    color: #18181b;
}

@media (max-width: 768px) {
    .book-details-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .book-cover-large {
        height: 300px;
    }
    
    .book-info-section,
    .borrowing-history-section {
        padding: 20px;
    }
    
    .book-title {
        font-size: 24px;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>
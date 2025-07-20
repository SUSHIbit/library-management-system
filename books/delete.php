<?php
/**
 * Delete Book Page
 * Library Management System
 */

define('LIBRARY_SYSTEM', true);
require_once '../config/config.php';

requireLogin();
requireRole(['admin', 'librarian']);

$book_id = (int)($_GET['id'] ?? 0);
if ($book_id <= 0) {
    showError('Invalid book ID.');
    redirect('index.php');
}

// Get book details
$book = getSingleRow("SELECT * FROM books WHERE id = ?", "i", $book_id);
if (!$book) {
    showError('Book not found.');
    redirect('index.php');
}

// Check if book has active borrowings
$active_borrowings = getSingleRow("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status = 'borrowed'", "i", $book_id)['count'];

if ($active_borrowings > 0) {
    showError("Cannot delete book. It has $active_borrowings active borrowing(s).");
    redirect('view.php?id=' . $book_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        beginTransaction();
        
        // Delete related fines first
        executeNonQuery("DELETE f FROM fines f JOIN borrowings b ON f.borrowing_id = b.id WHERE b.book_id = ?", "i", $book_id);
        
        // Delete borrowing history
        executeNonQuery("DELETE FROM borrowings WHERE book_id = ?", "i", $book_id);
        
        // Delete the book
        $result = executeNonQuery("DELETE FROM books WHERE id = ?", "i", $book_id);
        
        if ($result) {
            commitTransaction();
            logActivity('book_deleted', "Book deleted: {$book['title']}", $current_user['id']);
            showSuccess('Book deleted successfully.');
            redirect('index.php');
        } else {
            rollbackTransaction();
            showError('Failed to delete book.');
        }
    } catch (Exception $e) {
        rollbackTransaction();
        logError('Book deletion failed: ' . $e->getMessage());
        showError('Failed to delete book. Please try again.');
    }
}

$page_title = 'Delete Book';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Books', 'url' => 'index.php'],
    ['title' => 'Delete Book']
];

include_once '../includes/header.php';
?>

<div class="delete-container">
    <div class="delete-card">
        <div class="delete-header">
            <i class="icon-alert-triangle"></i>
            <h2>Confirm Deletion</h2>
        </div>
        
        <div class="delete-body">
            <p class="warning-text">
                You are about to permanently delete the following book. This action cannot be undone.
            </p>
            
            <div class="book-preview">
                <div class="book-cover-small">
                    <?php if ($book['book_cover']): ?>
                        <img src="<?php echo UPLOADS_URL . '/covers/' . $book['book_cover']; ?>" alt="Book Cover">
                    <?php else: ?>
                        <div class="cover-placeholder">
                            <i class="icon-book"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="book-details">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p>by <?php echo htmlspecialchars($book['author']); ?></p>
                    <?php if ($book['isbn']): ?>
                        <p class="isbn">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                    <?php endif; ?>
                    <p class="quantity">Total Copies: <?php echo $book['quantity']; ?></p>
                </div>
            </div>
            
            <div class="warning-box">
                <i class="icon-info"></i>
                <div>
                    <strong>What will be deleted:</strong>
                    <ul>
                        <li>Book record and all details</li>
                        <li>All borrowing history for this book</li>
                        <li>Related fine records</li>
                        <li>Book cover image (if any)</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="delete-actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="confirm_delete" value="1">
                <button type="submit" class="btn btn-danger">
                    <i class="icon-trash"></i> Yes, Delete Book
                </button>
            </form>
        <div class="delete-actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="confirm_delete" value="1">
                <button type="submit" class="btn btn-danger">
                    <i class="icon-trash"></i> Yes, Delete Book
                </button>
            </form>
            <a href="view.php?id=<?php echo $book_id; ?>" class="btn btn-secondary">
                <i class="icon-x"></i> Cancel
            </a>
        </div>
    </div>
</div>

<style>
.delete-container {
    max-width: 600px;
    margin: 0 auto;
}

.delete-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.delete-header {
    background: #fef2f2;
    color: #dc2626;
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #fecaca;
}

.delete-header i {
    font-size: 48px;
    margin-bottom: 12px;
    display: block;
}

.delete-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.delete-body {
    padding: 30px;
}

.warning-text {
    text-align: center;
    color: #71717a;
    margin-bottom: 30px;
    font-size: 16px;
}

.book-preview {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 20px;
}

.book-cover-small {
    width: 80px;
    height: 120px;
    background: #e4e4e7;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.book-cover-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.cover-placeholder {
    color: #71717a;
    font-size: 24px;
}

.book-details h3 {
    margin: 0 0 8px 0;
    color: #18181b;
    font-size: 18px;
}

.book-details p {
    margin: 4px 0;
    color: #52525b;
    font-size: 14px;
}

.isbn {
    color: #71717a !important;
    font-size: 12px !important;
}

.quantity {
    font-weight: 600;
    color: #3f3f46 !important;
}

.warning-box {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: #fffbeb;
    border: 1px solid #fed7aa;
    border-radius: 8px;
    color: #92400e;
}

.warning-box i {
    font-size: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.warning-box ul {
    margin: 8px 0 0 16px;
    padding: 0;
}

.warning-box li {
    margin-bottom: 4px;
}

.delete-actions {
    padding: 20px 30px;
    background: #f9fafb;
    border-top: 1px solid #e4e4e7;
    display: flex;
    gap: 12px;
    justify-content: center;
}

@media (max-width: 768px) {
    .delete-container {
        margin: 0;
    }
    
    .delete-card {
        border-radius: 0;
    }
    
    .book-preview {
        flex-direction: column;
        text-align: center;
    }
    
    .delete-actions {
        flex-direction: column;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>
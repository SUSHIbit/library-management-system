<?php
/**
 * Delete Category Page
 * Library Management System
 */

define('LIBRARY_SYSTEM', true);
require_once '../config/config.php';

requireLogin();
requireRole(['admin', 'librarian']);

$category_id = (int)($_GET['id'] ?? 0);
if ($category_id <= 0) {
    showError('Invalid category ID.');
    redirect('index.php');
}

// Get category details
$category = getSingleRow("SELECT * FROM categories WHERE id = ?", "i", $category_id);
if (!$category) {
    showError('Category not found.');
    redirect('index.php');
}

// Check if category has books
$book_count = getSingleRow("SELECT COUNT(*) as count FROM books WHERE category_id = ?", "i", $category_id)['count'];

if ($book_count > 0) {
    showError("Cannot delete category. It contains $book_count book(s).");
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $result = executeNonQuery("DELETE FROM categories WHERE id = ?", "i", $category_id);

    if ($result) {
        logActivity('category_deleted', "Category deleted: {$category['name']}", $current_user['id']);
        showSuccess('Category deleted successfully.');
        redirect('index.php');
    } else {
        showError('Failed to delete category.');
    }
}

$page_title = 'Delete Category';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Categories', 'url' => 'index.php'],
    ['title' => 'Delete Category']
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
                You are about to permanently delete the following category. This action cannot be undone.
            </p>
            
            <div class="category-preview">
                <div class="category-icon">
                    <i class="icon-folder"></i>
                </div>
                <div class="category-details">
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <?php if ($category['description']): ?>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    <?php endif; ?>
                    <p class="created-date">Created: <?php echo formatDate($category['created_at']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="delete-actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="confirm_delete" value="1">
                <button type="submit" class="btn btn-danger">
                    <i class="icon-trash"></i> Yes, Delete Category
                </button>
            </form>
            <a href="index.php" class="btn btn-secondary">
                <i class="icon-x"></i> Cancel
            </a>
        </div>
    </div>
</div>

<style>
.delete-container {
    max-width: 500px;
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

.category-preview {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 20px;
}

.category-icon {
    width: 60px;
    height: 60px;
    background: #3f3f46;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.category-details h3 {
    margin: 0 0 8px 0;
    color: #18181b;
    font-size: 18px;
}

.category-details p {
    margin: 4px 0;
    color: #52525b;
    font-size: 14px;
}

.created-date {
    color: #71717a !important;
    font-size: 12px !important;
    font-style: italic;
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
    
    .category-preview {
        flex-direction: column;
        text-align: center;
    }
    
    .delete-actions {
        flex-direction: column;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>
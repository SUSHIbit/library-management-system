<?php
/**
 * Add New Book Page
 * Library Management System
 */

define('LIBRARY_SYSTEM', true);
require_once '../config/config.php';

requireLogin();
requireRole(['admin', 'librarian']);

$page_title = 'Add New Book';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Books', 'url' => 'index.php'],
    ['title' => 'Add New Book']
];

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'author' => sanitizeInput($_POST['author'] ?? ''),
        'isbn' => sanitizeInput($_POST['isbn'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'quantity' => (int)($_POST['quantity'] ?? 1),
        'publication_year' => sanitizeInput($_POST['publication_year'] ?? ''),
        'publisher' => sanitizeInput($_POST['publisher'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? '')
    ];

    // Validation
    if (empty($form_data['title'])) $errors[] = 'Title is required.';
    if (empty($form_data['author'])) $errors[] = 'Author is required.';
    if ($form_data['category_id'] <= 0) $errors[] = 'Please select a valid category.';
    if ($form_data['quantity'] < 1) $errors[] = 'Quantity must be at least 1.';
    
    // Check if ISBN already exists
    if (!empty($form_data['isbn'])) {
        $existing = getSingleRow("SELECT id FROM books WHERE isbn = ?", "s", $form_data['isbn']);
        if ($existing) $errors[] = 'A book with this ISBN already exists.';
    }

    if (empty($errors)) {
        $available_quantity = $form_data['quantity'];
        $publication_year = !empty($form_data['publication_year']) ? $form_data['publication_year'] : null;
        
        $query = "INSERT INTO books (title, author, isbn, category_id, quantity, available_quantity, publication_year, publisher, description) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $book_id = executeNonQuery($query, "sssiiiiss",
            $form_data['title'],
            $form_data['author'],
            $form_data['isbn'],
            $form_data['category_id'],
            $form_data['quantity'],
            $available_quantity,
            $publication_year,
            $form_data['publisher'],
            $form_data['description']
        );

        if ($book_id) {
            logActivity('book_added', "New book added: {$form_data['title']}", $current_user['id']);
            showSuccess('Book added successfully!');
            redirect('view.php?id=' . $book_id);
        } else {
            $errors[] = 'Failed to add book. Please try again.';
        }
    }
}

// Get categories
$categories = getAllRows("SELECT * FROM categories ORDER BY name ASC");

include_once '../includes/header.php';
?>

<div class="form-container">
    <form method="POST" class="book-form" data-validate="true">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <!-- Basic Information -->
            <div class="form-section">
                <h3 class="section-title">Basic Information</h3>
                
                <div class="form-group">
                    <label for="title" class="form-label">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-input" 
                           value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="author" class="form-label">Author <span class="required">*</span></label>
                    <input type="text" id="author" name="author" class="form-input" 
                           value="<?php echo htmlspecialchars($form_data['author'] ?? ''); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" id="isbn" name="isbn" class="form-input" 
                               value="<?php echo htmlspecialchars($form_data['isbn'] ?? ''); ?>"
                               placeholder="978-0-123456-78-9">
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Category <span class="required">*</span></label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($form_data['category_id'] ?? 0) == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Publication Details -->
            <div class="form-section">
                <h3 class="section-title">Publication Details</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="publication_year" class="form-label">Publication Year</label>
                        <input type="number" id="publication_year" name="publication_year" class="form-input" 
                               value="<?php echo htmlspecialchars($form_data['publication_year'] ?? ''); ?>"
                               min="1800" max="<?php echo date('Y'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="quantity" class="form-label">Quantity <span class="required">*</span></label>
                        <input type="number" id="quantity" name="quantity" class="form-input" 
                               value="<?php echo $form_data['quantity'] ?? 1; ?>" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="publisher" class="form-label">Publisher</label>
                    <input type="text" id="publisher" name="publisher" class="form-input" 
                           value="<?php echo htmlspecialchars($form_data['publisher'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-textarea" rows="4"
                              placeholder="Brief description of the book..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="icon-plus"></i> Add Book
            </button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.book-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-grid {
    display: grid;
    gap: 30px;
}

.form-section {
    border: 1px solid #e4e4e7;
    border-radius: 8px;
    padding: 20px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #18181b;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e4e4e7;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e4e4e7;
}

.required {
    color: #dc2626;
}

@media (max-width: 768px) {
    .form-container {
        margin: 0;
    }
    
    .book-form {
        padding: 20px;
        margin: 0;
        border-radius: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>
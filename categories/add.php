<?php
/**
 * Add Category Page
 * Library Management System
 */

define('LIBRARY_SYSTEM', true);
require_once '../config/config.php';

requireLogin();
requireRole(['admin', 'librarian']);

$page_title = 'Add New Category';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Categories', 'url' => 'index.php'],
    ['title' => 'Add Category']
];

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? '')
    ];

    // Validation
    if (empty($form_data['name'])) {
        $errors[] = 'Category name is required.';
    }

    // Check if category name already exists
    if (!empty($form_data['name'])) {
        $existing = getSingleRow("SELECT id FROM categories WHERE name = ?", "s", $form_data['name']);
        if ($existing) {
            $errors[] = 'A category with this name already exists.';
        }
    }

    if (empty($errors)) {
        $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $category_id = executeNonQuery($query, "ss", $form_data['name'], $form_data['description']);

        if ($category_id) {
            logActivity('category_added', "New category added: {$form_data['name']}", $current_user['id']);
            showSuccess('Category added successfully!');
            redirect('index.php');
        } else {
            $errors[] = 'Failed to add category. Please try again.';
        }
    }
}

include_once '../includes/header.php';
?>

<div class="form-container">
    <form method="POST" class="category-form" data-validate="true">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="name" class="form-label">Category Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="form-input" 
                   value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" 
                   required maxlength="100"
                   placeholder="Enter category name">
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-textarea" 
                      rows="4" placeholder="Enter category description..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="icon-plus"></i> Add Category
            </button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.form-container {
    max-width: 600px;
    margin: 0 auto;
}

.category-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
    
    .category-form {
        padding: 20px;
        margin: 0;
        border-radius: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>
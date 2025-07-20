<?php
/**
 * Categories Management Page
 * Library Management System
 */

define('LIBRARY_SYSTEM', true);
require_once '../config/config.php';

requireLogin();
requireRole(['admin', 'librarian']);

$page_title = 'Categories Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Categories']
];

// Get search parameter
$search = sanitizeInput($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Build query
$where_clause = "";
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause = " WHERE name LIKE ? OR description LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
    $types = "ss";
}

// Get categories with book count
$base_query = "SELECT c.*, COUNT(b.id) as book_count 
               FROM categories c 
               LEFT JOIN books b ON c.id = b.category_id" . 
               $where_clause . " 
               GROUP BY c.id 
               ORDER BY c.name ASC";

$count_query = "SELECT COUNT(*) as total FROM categories c" . $where_clause;

$result = getPaginatedResults($base_query, $count_query, $page, ITEMS_PER_PAGE, $types, ...$params);
$categories = $result['data'];
$total_pages = $result['total_pages'];

$page_actions = '<a href="add.php" class="btn btn-primary"><i class="icon-plus"></i> Add Category</a>';

include_once '../includes/header.php';
?>

<div class="categories-container">
    <!-- Search Bar -->
    <div class="filter-bar">
        <form method="GET" class="search-form">
            <div class="search-inputs">
                <div class="search-field">
                    <input type="text" name="search" class="form-input" 
                           placeholder="Search categories..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-search"></i> Search
                    </button>
                    <a href="index.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    <?php if (!empty($categories)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Books Count</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                        </td>
                        <td>
                            <?php if ($category['description']): ?>
                                <?php echo htmlspecialchars(truncateText($category['description'], 80)); ?>
                            <?php else: ?>
                                <span class="text-muted">No description</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo $category['book_count']; ?> books</span>
                        </td>
                        <td><?php echo formatDate($category['created_at']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit.php?id=<?php echo $category['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Edit">
                                    <i class="icon-edit"></i>
                                </a>
                                <?php if ($category['book_count'] == 0): ?>
                                    <a href="delete.php?id=<?php echo $category['id']; ?>" 
                                       class="btn btn-sm btn-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this category?')">
                                        <i class="icon-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-danger" disabled title="Cannot delete - has books">
                                        <i class="icon-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php echo generatePagination($page, $total_pages, 'index.php', ['search' => $search]); ?>
        
    <?php else: ?>
        <div class="empty-state">
            <i class="icon-folder"></i>
            <h3>No Categories Found</h3>
            <p>No categories match your search criteria.</p>
            <a href="add.php" class="btn btn-primary">Add First Category</a>
        </div>
    <?php endif; ?>
</div>

<style>
.filter-bar {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.search-inputs {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 15px;
    align-items: end;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
    color: #71717a;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .search-inputs {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 4px;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>
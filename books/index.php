<?php
/**
 * Books Listing Page
 * Library Management System
 * 
 * Display all books with search and filter functionality
 */

define('LIBRARY_SYSTEM', true);
require_once '../config/config.php';

requireLogin();

$page_title = 'Books Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '../index.php'],
    ['title' => 'Books']
];

// Get search and filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if ($category_filter > 0) {
    $where_conditions[] = "b.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Get books with pagination
$base_query = "SELECT b.*, c.name as category_name 
               FROM books b 
               JOIN categories c ON b.category_id = c.id" . $where_clause . " 
               ORDER BY b.title ASC";

$count_query = "SELECT COUNT(*) as total 
                FROM books b 
                JOIN categories c ON b.category_id = c.id" . $where_clause;

$result = getPaginatedResults($base_query, $count_query, $page, ITEMS_PER_PAGE, $types, ...$params);
$books = $result['data'];
$total_pages = $result['total_pages'];

// Get categories for filter
$categories = getAllRows("SELECT * FROM categories ORDER BY name ASC");

// Page actions for header
if (hasPermission($current_user['role'], 'books', 'create')) {
    $page_actions = '<a href="add.php" class="btn btn-primary"><i class="icon-plus"></i> Add New Book</a>';
}

include_once '../includes/header.php';
?>

<div class="books-container">
    <!-- Search and Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="search-form">
            <div class="search-inputs">
                <div class="search-field">
                    <input type="text" name="search" class="form-input" 
                           placeholder="Search by title, author, or ISBN..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-field">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

    <!-- Books Grid/List -->
    <?php if (!empty($books)): ?>
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-cover">
                        <?php if ($book['book_cover']): ?>
                            <img src="<?php echo UPLOADS_URL . '/covers/' . $book['book_cover']; ?>" 
                                 alt="Book Cover" class="cover-image">
                        <?php else: ?>
                            <div class="cover-placeholder">
                                <i class="icon-book"></i>
                            </div>
                        <?php endif; ?>
                        <div class="availability-badge <?php echo $book['available_quantity'] > 0 ? 'available' : 'unavailable'; ?>">
                            <?php echo $book['available_quantity']; ?> / <?php echo $book['quantity']; ?> available
                        </div>
                    </div>
                    
                    <div class="book-info">
                        <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></p>
                        
                        <?php if ($book['isbn']): ?>
                            <p class="book-isbn">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($book['publication_year']): ?>
                            <p class="book-year">Published: <?php echo $book['publication_year']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="book-actions">
                        <a href="view.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-secondary">
                            <i class="icon-eye"></i> View Details
                        </a>
                        
                        <?php if (hasPermission($current_user['role'], 'books', 'update')): ?>
                            <a href="edit.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="icon-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission($current_user['role'], 'books', 'delete')): ?>
                            <a href="delete.php?id=<?php echo $book['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this book?')">
                                <i class="icon-trash"></i> Delete
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php echo generatePagination($page, $total_pages, 'index.php', ['search' => $search, 'category' => $category_filter]); ?>
        
    <?php else: ?>
        <div class="empty-state">
            <i class="icon-book"></i>
            <h3>No Books Found</h3>
            <p>No books match your search criteria.</p>
            <?php if (hasPermission($current_user['role'], 'books', 'create')): ?>
                <a href="add.php" class="btn btn-primary">Add First Book</a>
            <?php endif; ?>
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
    grid-template-columns: 1fr auto auto auto;
    gap: 15px;
    align-items: end;
}

.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.book-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.book-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.book-cover {
    position: relative;
    height: 200px;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cover-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cover-placeholder {
    font-size: 48px;
    color: #71717a;
}

.availability-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: white;
}

.availability-badge.available {
    background: #16a34a;
}

.availability-badge.unavailable {
    background: #dc2626;
}

.book-info {
    padding: 16px;
}

.book-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #18181b;
    line-height: 1.3;
}

.book-author {
    color: #52525b;
    margin-bottom: 4px;
    font-style: italic;
}

.book-category {
    color: #3f3f46;
    font-size: 13px;
    margin-bottom: 8px;
}

.book-isbn, .book-year {
    color: #71717a;
    font-size: 12px;
    margin-bottom: 2px;
}

.book-actions {
    padding: 16px;
    border-top: 1px solid #e4e4e7;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
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
    
    .books-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .book-actions {
        flex-direction: column;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>
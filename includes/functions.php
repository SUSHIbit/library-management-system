<?php
/**
 * Common Functions
 * Library Management System
 * 
 * This file contains commonly used functions throughout the application.
 * Functions for pagination, validation, formatting, and utility operations.
 * 
 * @author Final Year Student
 * @version 1.0
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

/**
 * Generate pagination HTML
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $base_url Base URL for pagination links
 * @param array $params Additional URL parameters
 * @return string Pagination HTML
 */
function generatePagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav class="pagination-nav" aria-label="Page navigation">';
    $pagination .= '<ul class="pagination">';
    
    // Build query string
    $query_params = [];
    foreach ($params as $key => $value) {
        if ($key !== 'page' && !empty($value)) {
            $query_params[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
    
    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=' . $prev_page . $query_string . '" aria-label="Previous">';
        $pagination .= '<span aria-hidden="true">&laquo; Previous</span>';
        $pagination .= '</a>';
        $pagination .= '</li>';
    }
    
    // Calculate page range
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    // First page
    if ($start_page > 1) {
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=1' . $query_string . '">1</a>';
        $pagination .= '</li>';
        
        if ($start_page > 2) {
            $pagination .= '<li class="page-item disabled">';
            $pagination .= '<span class="page-link">...</span>';
            $pagination .= '</li>';
        }
    }
    
    // Page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = ($i == $current_page) ? ' active' : '';
        $pagination .= '<li class="page-item' . $active_class . '">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=' . $i . $query_string . '">' . $i . '</a>';
        $pagination .= '</li>';
    }
    
    // Last page
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $pagination .= '<li class="page-item disabled">';
            $pagination .= '<span class="page-link">...</span>';
            $pagination .= '</li>';
        }
        
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=' . $total_pages . $query_string . '">' . $total_pages . '</a>';
        $pagination .= '</li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=' . $next_page . $query_string . '" aria-label="Next">';
        $pagination .= '<span aria-hidden="true">Next &raquo;</span>';
        $pagination .= '</a>';
        $pagination .= '</li>';
    }
    
    $pagination .= '</ul>';
    $pagination .= '</nav>';
    
    return $pagination;
}

/**
 * Get paginated results
 * 
 * @param string $base_query Base SQL query without LIMIT
 * @param string $count_query SQL query to count total records
 * @param int $page Current page number
 * @param int $items_per_page Items per page
 * @param string $types Parameter types for prepared statement
 * @param mixed ...$params Parameters for prepared statement
 * @return array Array containing data, total_records, total_pages, current_page
 */
function getPaginatedResults($base_query, $count_query, $page = 1, $items_per_page = ITEMS_PER_PAGE, $types = "", ...$params) {
    // Ensure page is valid
    $page = max(1, intval($page));
    
    // Get total count
    $total_records = 0;
    $count_result = executeQuery($count_query, $types, ...$params);
    if ($count_result) {
        $count_row = $count_result->fetch_row();
        $total_records = $count_row[0];
    }
    
    // Calculate pagination
    $total_pages = ceil($total_records / $items_per_page);
    $offset = ($page - 1) * $items_per_page;
    
    // Add LIMIT to query
    $paginated_query = $base_query . " LIMIT $offset, $items_per_page";
    
    // Get data
    $data = getAllRows($paginated_query, $types, ...$params);
    
    return [
        'data' => $data,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'items_per_page' => $items_per_page
    ];
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES array element
 * @param array $allowed_types Allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array Array with 'success' boolean and 'message' string
 */
function validateFileUpload($file, $allowed_types = [], $max_size = MAX_FILE_SIZE) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file was uploaded.'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit.',
            UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
        ];
        
        $message = $error_messages[$file['error']] ?? 'Unknown upload error.';
        return ['success' => false, 'message' => $message];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size of ' . formatFileSize($max_size) . '.'];
    }
    
    // Check file type
    if (!empty($allowed_types)) {
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'message' => 'File type not allowed.'];
        }
    }
    
    return ['success' => true, 'message' => 'File validation passed.'];
}

/**
 * Upload file to specified directory
 * 
 * @param array $file $_FILES array element
 * @param string $upload_dir Upload directory
 * @param string $filename Custom filename (optional)
 * @return array Array with 'success' boolean, 'message' string, and 'filename' string
 */
function uploadFile($file, $upload_dir, $filename = null) {
    // Validate file first
    $validation = validateFileUpload($file);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate filename if not provided
    if (!$filename) {
        $extension = getFileExtension($file['name']);
        $filename = uniqid() . '_' . time() . '.' . $extension;
    }
    
    // Sanitize filename
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    $destination = $upload_dir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'message' => 'File uploaded successfully.',
            'filename' => $filename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

/**
 * Delete file from filesystem
 * 
 * @param string $filepath Full path to file
 * @return bool True on success, false on failure
 */
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * Generate unique filename
 * 
 * @param string $original_filename Original filename
 * @param string $prefix Filename prefix
 * @return string Unique filename
 */
function generateUniqueFilename($original_filename, $prefix = '') {
    $extension = getFileExtension($original_filename);
    $name = pathinfo($original_filename, PATHINFO_FILENAME);
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    
    return $prefix . $name . '_' . uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Create breadcrumb navigation
 * 
 * @param array $breadcrumbs Array of breadcrumb items
 * @return string Breadcrumb HTML
 */
function createBreadcrumb($breadcrumbs) {
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb-nav" aria-label="Breadcrumb">';
    $html .= '<ol class="breadcrumb">';
    
    $total = count($breadcrumbs);
    $count = 0;
    
    foreach ($breadcrumbs as $breadcrumb) {
        $count++;
        $is_last = ($count === $total);
        
        $html .= '<li class="breadcrumb-item' . ($is_last ? ' active' : '') . '">';
        
        if (!$is_last && isset($breadcrumb['url'])) {
            $html .= '<a href="' . htmlspecialchars($breadcrumb['url']) . '">';
            $html .= htmlspecialchars($breadcrumb['title']);
            $html .= '</a>';
        } else {
            $html .= htmlspecialchars($breadcrumb['title']);
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Create data table HTML
 * 
 * @param array $headers Table headers
 * @param array $data Table data
 * @param array $options Table options
 * @return string Table HTML
 */
function createDataTable($headers, $data, $options = []) {
    $table_class = $options['class'] ?? 'data-table';
    $table_id = $options['id'] ?? '';
    $sortable = $options['sortable'] ?? false;
    $striped = $options['striped'] ?? true;
    $hover = $options['hover'] ?? true;
    
    $classes = [$table_class];
    if ($striped) $classes[] = 'table-striped';
    if ($hover) $classes[] = 'table-hover';
    if ($sortable) $classes[] = 'table-sortable';
    
    $html = '<div class="table-responsive">';
    $html .= '<table class="' . implode(' ', $classes) . '"' . ($table_id ? ' id="' . $table_id . '"' : '') . '>';
    
    // Table header
    $html .= '<thead>';
    $html .= '<tr>';
    foreach ($headers as $header) {
        $header_class = '';
        if ($sortable && isset($header['sortable']) && $header['sortable']) {
            $header_class = ' class="sortable"';
        }
        $html .= '<th' . $header_class . '>' . htmlspecialchars($header['title'] ?? $header) . '</th>';
    }
    $html .= '</tr>';
    $html .= '</thead>';
    
    // Table body
    $html .= '<tbody>';
    if (empty($data)) {
        $colspan = count($headers);
        $html .= '<tr><td colspan="' . $colspan . '" class="text-center text-muted">No data available</td></tr>';
    } else {
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</tbody>';
    
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Create status badge HTML
 * 
 * @param string $status Status value
 * @param array $status_config Status configuration
 * @return string Badge HTML
 */
function createStatusBadge($status, $status_config = []) {
    $default_config = [
        'active' => ['class' => 'badge-success', 'text' => 'Active'],
        'inactive' => ['class' => 'badge-secondary', 'text' => 'Inactive'],
        'suspended' => ['class' => 'badge-danger', 'text' => 'Suspended'],
        'borrowed' => ['class' => 'badge-warning', 'text' => 'Borrowed'],
        'returned' => ['class' => 'badge-success', 'text' => 'Returned'],
        'overdue' => ['class' => 'badge-danger', 'text' => 'Overdue'],
        'paid' => ['class' => 'badge-success', 'text' => 'Paid'],
        'unpaid' => ['class' => 'badge-danger', 'text' => 'Unpaid'],
        'partial' => ['class' => 'badge-warning', 'text' => 'Partial']
    ];
    
    $config = array_merge($default_config, $status_config);
    
    if (isset($config[$status])) {
        $badge_class = $config[$status]['class'];
        $badge_text = $config[$status]['text'];
    } else {
        $badge_class = 'badge-secondary';
        $badge_text = ucfirst($status);
    }
    
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars($badge_text) . '</span>';
}

/**
 * Create action buttons HTML
 * 
 * @param array $actions Array of action configurations
 * @param mixed $record_id Record identifier
 * @return string Action buttons HTML
 */
function createActionButtons($actions, $record_id) {
    $html = '<div class="action-buttons">';
    
    foreach ($actions as $action) {
        $url = str_replace('{id}', $record_id, $action['url']);
        $class = $action['class'] ?? 'btn btn-sm';
        $title = $action['title'] ?? '';
        $icon = $action['icon'] ?? '';
        $text = $action['text'] ?? '';
        $confirm = $action['confirm'] ?? false;
        
        $onclick = '';
        if ($confirm) {
            $message = $action['confirm_message'] ?? 'Are you sure?';
            $onclick = ' onclick="return confirm(\'' . htmlspecialchars($message) . '\')"';
        }
        
        $html .= '<a href="' . htmlspecialchars($url) . '" class="' . $class . '" title="' . htmlspecialchars($title) . '"' . $onclick . '>';
        
        if ($icon) {
            $html .= '<i class="icon-' . $icon . '"></i> ';
        }
        
        if ($text) {
            $html .= htmlspecialchars($text);
        }
        
        $html .= '</a> ';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Validate required fields
 * 
 * @param array $data Data to validate
 * @param array $required_fields Required field names
 * @return array Validation errors
 */
function validateRequiredFields($data, $required_fields) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $field_name = ucfirst(str_replace('_', ' ', $field));
            $errors[] = $field_name . ' is required.';
        }
    }
    
    return $errors;
}

/**
 * Validate field lengths
 * 
 * @param array $data Data to validate
 * @param array $field_lengths Field length constraints
 * @return array Validation errors
 */
function validateFieldLengths($data, $field_lengths) {
    $errors = [];
    
    foreach ($field_lengths as $field => $constraints) {
        if (!isset($data[$field])) {
            continue;
        }
        
        $value = $data[$field];
        $length = strlen($value);
        $field_name = ucfirst(str_replace('_', ' ', $field));
        
        if (isset($constraints['min']) && $length < $constraints['min']) {
            $errors[] = $field_name . ' must be at least ' . $constraints['min'] . ' characters long.';
        }
        
        if (isset($constraints['max']) && $length > $constraints['max']) {
            $errors[] = $field_name . ' cannot exceed ' . $constraints['max'] . ' characters.';
        }
    }
    
    return $errors;
}

/**
 * Generate random string
 * 
 * @param int $length String length
 * @param string $characters Character set
 * @return string Random string
 */
function generateRandomString($length = 10, $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
    $string = '';
    $char_length = strlen($characters);
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[random_int(0, $char_length - 1)];
    }
    
    return $string;
}

/**
 * Format phone number
 * 
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format Malaysian phone numbers
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        // Format: 01X-XXX XXXX
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    } elseif (strlen($phone) === 11 && substr($phone, 0, 2) === '60') {
        // Format: +60 1X-XXX XXXX
        return '+60 ' . substr($phone, 2, 2) . '-' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
    }
    
    return $phone;
}

/**
 * Calculate reading time for text
 * 
 * @param string $text Text content
 * @param int $wpm Words per minute (default: 200)
 * @return string Reading time
 */
function calculateReadingTime($text, $wpm = 200) {
    $word_count = str_word_count(strip_tags($text));
    $minutes = ceil($word_count / $wpm);
    
    if ($minutes < 1) {
        return 'Less than 1 minute';
    } elseif ($minutes === 1) {
        return '1 minute';
    } else {
        return $minutes . ' minutes';
    }
}

/**
 * Get time ago string
 * 
 * @param string $datetime Datetime string
 * @return string Time ago string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($time / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

/**
 * Truncate text with ellipsis
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Convert array to CSV string
 * 
 * @param array $data Array data
 * @param array $headers CSV headers
 * @return string CSV string
 */
function arrayToCSV($data, $headers = []) {
    $csv = '';
    
    // Add headers if provided
    if (!empty($headers)) {
        $csv .= implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";
    }
    
    // Add data rows
    foreach ($data as $row) {
        $csv_row = [];
        foreach ($row as $value) {
            $csv_row[] = '"' . str_replace('"', '""', $value) . '"';
        }
        $csv .= implode(',', $csv_row) . "\n";
    }
    
    return $csv;
}

/**
 * Download file as attachment
 * 
 * @param string $data File content
 * @param string $filename Filename
 * @param string $content_type Content type
 */
function downloadFile($data, $filename, $content_type = 'application/octet-stream') {
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    echo $data;
    exit();
}

/**
 * Get user avatar URL
 * 
 * @param string $email User email
 * @param int $size Avatar size
 * @return string Avatar URL
 */
function getUserAvatar($email, $size = 80) {
    // Use Gravatar as default avatar service
    $hash = md5(strtolower(trim($email)));
    $default = urlencode(IMAGES_URL . '/default-avatar.png');
    
    return "https://www.gravatar.com/avatar/$hash?s=$size&d=$default";
}

/**
 * Create notification HTML
 * 
 * @param string $type Notification type (success, error, warning, info)
 * @param string $message Notification message
 * @param bool $dismissible Whether notification is dismissible
 * @return string Notification HTML
 */
function createNotification($type, $message, $dismissible = true) {
    $type_classes = [
        'success' => 'notification-success',
        'error' => 'notification-error',
        'warning' => 'notification-warning',
        'info' => 'notification-info'
    ];
    
    $class = $type_classes[$type] ?? 'notification-info';
    
    $html = '<div class="notification ' . $class . '">';
    $html .= '<div class="notification-content">';
    $html .= htmlspecialchars($message);
    $html .= '</div>';
    
    if ($dismissible) {
        $html .= '<button type="button" class="notification-close" onclick="this.parentElement.remove()">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Log error to file
 * 
 * @param string $message Error message
 * @param array $context Additional context
 */
function logError($message, $context = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'file' => $_SERVER['PHP_SELF'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $log_file = ROOT_PATH . '/logs/error.log';
    $log_message = json_encode($log_entry) . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Debug helper function
 * 
 * @param mixed $data Data to debug
 * @param bool $die Whether to stop execution
 */
function debug($data, $die = false) {
    if (getSystemSetting('debug_mode', false)) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
}

/**
 * Check if date is valid
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return bool True if valid, false otherwise
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Get days between two dates
 * 
 * @param string $date1 First date
 * @param string $date2 Second date
 * @return int Number of days
 */
function getDaysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    
    return $interval->days;
}

/**
 * Format number with suffix (K, M, B)
 * 
 * @param int $number Number to format
 * @return string Formatted number
 */
function formatNumber($number) {
    if ($number >= 1000000000) {
        return round($number / 1000000000, 1) . 'B';
    } elseif ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    
    return $number;
}

/**
 * Clean output buffer
 */
function cleanBuffer() {
    while (ob_get_level()) {
        ob_end_clean();
    }
}

/**
 * Send JSON response
 * 
 * @param mixed $data Response data
 * @param int $status_code HTTP status code
 */
function sendJSONResponse($data, $status_code = 200) {
    cleanBuffer();
    
    http_response_code($status_code);
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo json_encode($data);
    exit();
}

/**
 * Get client IP address
 * 
 * @return string Client IP address
 */
function getClientIP() {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
?>
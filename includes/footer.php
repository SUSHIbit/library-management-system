<?php
/**
 * Footer Template
 * Library Management System
 * 
 * Common footer template included in all pages.
 * Contains closing tags, JavaScript includes, and footer content.
 * 
 * @author Final Year Student
 * @version 1.0
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}
?>
            </div> <!-- End page-content -->
        </main> <!-- End main-content -->
    </div> <!-- End main-container -->
    
    <!-- Footer -->
    <footer class="app-footer">
        <div class="footer-content">
            <div class="footer-left">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                <p>Developed by <?php echo APP_AUTHOR; ?> - Final Year Project</p>
            </div>
            
            <div class="footer-right">
                <div class="footer-links">
                    <a href="<?php echo BASE_URL; ?>/help.php">Help</a>
                    <a href="<?php echo BASE_URL; ?>/privacy.php">Privacy Policy</a>
                    <a href="<?php echo BASE_URL; ?>/terms.php">Terms of Service</a>
                    <?php if (hasRole(['admin'])): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/system-info.php">System Info</a>
                    <?php endif; ?>
                </div>
                
                <div class="footer-info">
                    <span class="version">Version <?php echo APP_VERSION; ?></span>
                    <span class="separator">|</span>
                    <span class="server-time">Server Time: <?php echo date('H:i:s'); ?></span>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Overlay for mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="modal-close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Are you sure you want to perform this action?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmButton">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                    <p class="mt-3">Processing...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo ASSETS_URL . '/js/' . $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inline_js)): ?>
        <script>
            <?php echo $inline_js; ?>
        </script>
    <?php endif; ?>
    
    <!-- Page-specific JavaScript -->
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            initializeTooltips();
            
            // Initialize dropdowns
            initializeDropdowns();
            
            // Initialize modals
            initializeModals();
            
            // Initialize form validation
            initializeFormValidation();
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(function(alert) {
                    if (alert.style.display !== 'none') {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.remove();
                        }, 300);
                    }
                });
            }, 5000);
            
            // Update notification count
            updateNotificationCount();
            
            // Check for overdue books (for students/staff)
            <?php if (hasRole(['student', 'staff'])): ?>
            checkOverdueBooks();
            <?php endif; ?>
        });
        
        // Global CSRF token for AJAX requests
        window.csrfToken = '<?php echo generateCSRFToken(); ?>';
        
        // Global application settings
        window.appSettings = {
            baseUrl: '<?php echo BASE_URL; ?>',
            assetsUrl: '<?php echo ASSETS_URL; ?>',
            userRole: '<?php echo $current_user['role']; ?>',
            userId: <?php echo $current_user['id']; ?>,
            currency: '<?php echo CURRENCY_SYMBOL; ?>',
            dateFormat: 'dd/mm/yyyy',
            timeZone: 'Asia/Kuala_Lumpur'
        };
        
        // Update server time every second
        setInterval(function() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-GB');
            const timeElement = document.querySelector('.footer-info .server-time');
            if (timeElement) {
                timeElement.textContent = 'Server Time: ' + timeString;
            }
        }, 1000);
    </script>
    
    <!-- Development Tools (only in debug mode) -->
    <?php if (getSystemSetting('debug_mode', false)): ?>
    <script>
        console.log('Debug Mode Active');
        console.log('User Info:', <?php echo json_encode($current_user); ?>);
        console.log('Page Title:', '<?php echo htmlspecialchars($page_title ?? 'Unknown'); ?>');
        console.log('Current URL:', window.location.href);
        
        // Add debug panel
        if (window.location.search.includes('debug=1')) {
            const debugPanel = document.createElement('div');
            debugPanel.id = 'debug-panel';
            debugPanel.style.cssText = `
                position: fixed;
                top: 10px;
                right: 10px;
                background: #2d3748;
                color: #fff;
                padding: 10px;
                border-radius: 5px;
                font-size: 12px;
                z-index: 9999;
                max-width: 300px;
            `;
            debugPanel.innerHTML = `
                <strong>Debug Info</strong><br>
                User: <?php echo htmlspecialchars($current_user['username']); ?><br>
                Role: <?php echo htmlspecialchars($current_user['role']); ?><br>
                Page: <?php echo htmlspecialchars($page_title ?? 'Unknown'); ?><br>
                Memory: ${(performance.memory ? (performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(2) + ' MB' : 'N/A')}<br>
                <button onclick="this.parentElement.remove()" style="margin-top: 5px; padding: 2px 6px; font-size: 10px;">Close</button>
            `;
            document.body.appendChild(debugPanel);
        }
    </script>
    <?php endif; ?>
    
</body>
</html>
<?php
/**
 * Common Footer Component
 */
$root_path = isset($root_path) ? $root_path : '';
?>
        </main>
        
        <footer style="padding: 16px 32px; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
            <div>&copy; <?php echo date('Y'); ?> <strong>EduCore School Management System</strong>. All rights reserved.</div>
            <div style="display: flex; gap: 16px;">
                <span>XAMPP / WAMP Ready</span>
                <span>•</span>
                <span>Centralized MySQL ($conn)</span>
            </div>
        </footer>
    </div>
</div>

<!-- Custom Confirmation Modal (Pre-rendered for immediate availability) -->
<div id="customConfirmModal" class="custom-modal-backdrop" aria-hidden="true" role="dialog">
    <div class="custom-modal-dialog">
        <div id="customModalIcon" class="custom-modal-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="28" height="28">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <h3 id="customModalTitle" class="custom-modal-title">Confirm Deletion</h3>
        <div id="customModalBody" class="custom-modal-body">
            Are you sure you want to permanently delete this record?
        </div>
        <div class="custom-modal-actions">
            <button type="button" id="customModalCancelBtn" class="btn btn-cancel">
                Cancel
            </button>
            <button type="button" id="customModalConfirmBtn" class="btn btn-confirm-delete">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span id="customModalConfirmBtnText">Yes, Delete</span>
            </button>
        </div>
    </div>
</div>

<!-- Main Interactive JavaScript -->
<script src="<?php echo $root_path; ?>assets/js/main.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/main.js') ?: time(); ?>"></script>
</body>
</html>

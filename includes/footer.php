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

<!-- Main Interactive JavaScript -->
<script src="<?php echo $root_path; ?>assets/js/main.js"></script>
</body>
</html>

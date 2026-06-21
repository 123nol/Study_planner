        </main> <!-- /main-content -->
    </div> <!-- /app-shell -->

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Core Scripts -->
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/app.js"></script>

    <?php if (!empty($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="../assets/js/<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

<?php
$pageCss = 'reminders.css';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/header.php';
?>

<header class="page-header">
    <div>
        <h1 class="page-header__title">Reminders</h1>
        <p class="text-tertiary">Create study reminders and get notified before deadlines.</p>
    </div>
</header>

<div class="page-body reminders-page">
    <div class="reminder-panel">
        <section class="section">
            <div class="section__header">
                <h2 class="section__title">Active Reminders</h2>
            </div>
            <div id="reminder-list"></div>
        </section>
    </div>

    <aside class="reminder-sidebar">
        <div>
            <h2>Add Reminder</h2>
            <p class="text-tertiary">Schedule a reminder for a task or a study goal.</p>
        </div>

        <form id="reminder-form" class="reminder-form">
            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea id="reminder-message" class="form-input" required placeholder="What should I remind you about?"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Link to Task (optional)</label>
                <select id="reminder-task" class="form-select">
                    <option value="">No task</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Date & Time</label>
                <input type="datetime-local" id="reminder-date" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Reminder Type</label>
                <select id="reminder-type" class="form-select">
                    <option value="alert">Alert</option>
                    <option value="notification">Notification</option>
                    <option value="countdown">Countdown</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Save Reminder</button>
        </form>

        <div class="notification-settings">
            <label>
                Browser notifications
                <input type="checkbox" id="notification-toggle">
            </label>
            <button type="button" class="btn btn-secondary" id="btn-test-notification">Send Test Notification</button>
        </div>
    </aside>
</div>

<script src="../assets/js/reminders.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

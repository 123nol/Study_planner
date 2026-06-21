<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$isPagesContext = str_contains($scriptName, '/pages/');

$dashboardHref = $isPagesContext ? '../index.php' : 'index.php';
$scheduleHref = $isPagesContext ? 'schedule.php' : 'pages/schedule.php';
$tasksHref = $isPagesContext ? 'tasks.php' : 'pages/tasks.php';
$remindersHref = $isPagesContext ? 'reminders.php' : 'pages/reminders.php';
$profileHref = $isPagesContext ? 'profile.php' : 'pages/profile.php';
?>
<aside class="sidebar">
    <div class="sidebar__brand">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="url(#paint0_linear)"/>
            <path d="M2 17L12 22L22 17" stroke="url(#paint0_linear)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="url(#paint0_linear)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <defs>
                <linearGradient id="paint0_linear" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#6366f1"/>
                    <stop offset="1" stop-color="#a855f7"/>
                </linearGradient>
            </defs>
        </svg>
        <span class="sidebar__logo-text">SmartPlanner</span>
    </div>

    <nav class="sidebar__nav">
        <a href="<?= htmlspecialchars($dashboardHref, ENT_QUOTES, 'UTF-8') ?>" class="sidebar__link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>
        <a href="<?= htmlspecialchars($scheduleHref, ENT_QUOTES, 'UTF-8') ?>" class="sidebar__link <?= $currentPage === 'schedule.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Schedule
        </a>
        <a href="<?= htmlspecialchars($tasksHref, ENT_QUOTES, 'UTF-8') ?>" class="sidebar__link <?= $currentPage === 'tasks.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            Tasks
        </a>
        <a href="<?= htmlspecialchars($remindersHref, ENT_QUOTES, 'UTF-8') ?>" class="sidebar__link <?= $currentPage === 'reminders.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            Reminders
        </a>
        <a href="<?= htmlspecialchars($profileHref, ENT_QUOTES, 'UTF-8') ?>" class="sidebar__link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Profile
        </a>
    </nav>
</aside>

<style>
/* Temporary inline styles for sidebar to ensure it looks good immediately */
.sidebar {
    width: var(--sidebar-width);
    background: var(--bg-surface);
    border-right: 1px solid var(--glass-border);
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    padding: var(--space-6) 0;
    z-index: var(--z-sticky);
}
.sidebar__brand {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: 0 var(--space-6);
    margin-bottom: var(--space-8);
}
.sidebar__logo-text {
    font-family: var(--font-heading);
    font-size: var(--fs-lg);
    font-weight: var(--fw-bold);
    background: var(--accent-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.sidebar__nav {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    padding: 0 var(--space-4);
}
.sidebar__link {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-md);
    color: var(--text-secondary);
    font-weight: var(--fw-medium);
    transition: all var(--transition-fast);
}
.sidebar__link:hover {
    background: var(--bg-surface-hover);
    color: var(--text-primary);
}
.sidebar__link.active {
    background: rgba(99, 102, 241, 0.1);
    color: var(--accent-indigo);
    border-left: 3px solid var(--accent-indigo);
}
</style>

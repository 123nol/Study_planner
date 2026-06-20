<?php require_once __DIR__ . '/../includes/auth_check.php'; ?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<header class="page-header">
    <h1 class="page-header__title">Schedule Generator</h1>
    <div class="page-header__actions">
        <button class="btn btn-secondary" id="btn-reset">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            Reset
        </button>
        <button class="btn btn-primary" id="btn-save" style="display: none;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Schedule
        </button>
    </div>
</header>

<div class="page-body">
    <div class="split-layout">
        
        <!-- Left Panel: Configuration -->
        <aside class="split-layout__panel">
            
            <!-- Generate Button -->
            <button class="btn btn-generate" id="btn-generate">
                Generate Schedule
            </button>

            <!-- Courses Section -->
            <section class="section">
                <header class="section__header">
                    <h2 class="section__title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        Courses to Study
                    </h2>
                    <button class="btn btn-ghost btn-icon btn-sm" id="btn-manage-courses" title="Manage Courses">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                </header>
                <div class="course-list" id="course-list-container">
                    <!-- Loaded via JS -->
                    <div class="course-list__empty">Loading courses...</div>
                </div>
            </section>

            <!-- Availability Section -->
            <section class="section">
                <header class="section__header">
                    <h2 class="section__title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Availability (9am - 9pm)
                    </h2>
                </header>
                <p class="text-tertiary" style="font-size: var(--fs-xs); margin-bottom: var(--space-3);">Click and drag to mark times you are available to study.</p>
                <div class="availability-grid" id="availability-grid">
                    <!-- Generated via JS -->
                </div>
            </section>

            <!-- Preferences Section -->
            <section class="section">
                <header class="section__header">
                    <h2 class="section__title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Preferences
                    </h2>
                </header>
                <div class="preferences-form">
                    <div class="form-group">
                        <label class="form-label">Max Study Block</label>
                        <div class="preference-value">
                            <input type="number" id="pref-max-block" class="form-input" value="90" min="30" step="15">
                            <span>minutes</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min Study Block</label>
                        <div class="preference-value">
                            <input type="number" id="pref-min-block" class="form-input" value="30" min="15" step="15">
                            <span>minutes</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Break Duration</label>
                        <div class="preference-value">
                            <input type="number" id="pref-break" class="form-input" value="15" min="5" step="5">
                            <span>minutes</span>
                        </div>
                    </div>
                </div>
            </section>
        </aside>

        <!-- Right Panel: Calendar Grid -->
        <div class="split-layout__main">
            <!-- Stats Bar -->
            <div class="schedule-stats" id="schedule-stats" style="display: none; margin-bottom: var(--space-4);">
                <div class="schedule-stat">
                    <span class="schedule-stat__label">Total Study Time</span>
                    <span class="schedule-stat__value" id="stat-total-time">0h 0m</span>
                </div>
                <div class="schedule-stat">
                    <span class="schedule-stat__label">Blocks Scheduled</span>
                    <span class="schedule-stat__value" id="stat-blocks">0</span>
                </div>
            </div>

            <div class="calendar" id="calendar">
                <div class="calendar__empty" id="calendar-empty">
                    <svg class="calendar__empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <h3 class="calendar__empty-title">Ready to Plan?</h3>
                    <p class="calendar__empty-text">Select your available times and adjust your preferences, then click Generate Schedule.</p>
                </div>

                <div id="calendar-content" style="display: none; height: 100%; display: flex; flex-direction: column;">
                    <div class="calendar__header" id="calendar-header">
                        <div class="calendar__corner">GMT+3</div>
                        <!-- Days generated via JS -->
                    </div>
                    <div class="calendar__body" id="calendar-body">
                        <div class="calendar__grid" id="calendar-grid">
                            <!-- Times & Columns generated via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Course Management Modal -->
<div class="modal-overlay" id="course-modal">
    <div class="modal">
        <div class="modal__header">
            <h3 class="modal__title">Manage Courses</h3>
            <button class="btn btn-ghost btn-icon btn-sm" id="btn-close-modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal__body">
            <form id="course-form">
                <div class="form-group">
                    <label class="form-label">Course Name</label>
                    <input type="text" id="course-name" class="form-input" required placeholder="e.g. Linear Algebra">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <input type="color" id="course-color" class="form-input-color" value="#6366f1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select id="course-priority" class="form-select">
                            <option value="3">High</option>
                            <option value="2" selected>Medium</option>
                            <option value="1">Low</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Weekly Hours Goal</label>
                        <input type="number" id="course-hours" class="form-input" required min="1" value="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date (optional)</label>
                        <input type="date" id="course-date" class="form-input">
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-secondary" id="btn-cancel-course">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
            <div class="divider"></div>
            <h4 class="form-label" style="margin-bottom: var(--space-2)">Current Courses</h4>
            <div id="modal-course-list" style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <!-- Filled via JS -->
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/schedule.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

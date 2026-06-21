<?php require_once __DIR__ . '/../includes/auth_check.php'; ?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<header class="page-header">
    <div>
        <h1 class="page-header__title">Task Manager</h1>
        <p class="text-tertiary">Create tasks, update progress, and keep your study work organized.</p>
    </div>
    <div class="page-header__actions">
        <button class="btn btn-primary" id="btn-open-task-modal">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14m-7-7h14"/></svg>
            Add Task
        </button>
    </div>
</header>

<div class="page-body">
    <section class="section">
        <div class="form-row" style="flex-wrap: wrap; gap: 12px; align-items: center;">
            <div class="form-group" style="flex: 1 1 160px;">
                <label class="form-label">Status</label>
                <select id="filter-status" class="form-select">
                    <option value="">All</option>
                    <option value="todo">Todo</option>
                    <option value="in_progress">In Progress</option>
                    <option value="done">Done</option>
                </select>
            </div>
            <div class="form-group" style="flex: 1 1 180px;">
                <label class="form-label">Course</label>
                <select id="filter-course" class="form-select">
                    <option value="">All Courses</option>
                </select>
            </div>
            <div class="form-group" style="flex: 1 1 160px;">
                <label class="form-label">Priority</label>
                <select id="filter-priority" class="form-select">
                    <option value="">All</option>
                    <option value="3">High</option>
                    <option value="2">Medium</option>
                    <option value="1">Low</option>
                </select>
            </div>
            <div class="form-group" style="flex: 0 0 auto; align-self: flex-end;">
                <button class="btn btn-secondary" id="btn-clear-filters" type="button">Clear</button>
            </div>
        </div>
    </section>

    <section class="section" style="padding: 0;">
        <div class="task-board" id="task-board">
            <div class="task-column">
                <h3 class="task-column__title">Todo</h3>
                <div class="task-column__list" id="todo-column"></div>
            </div>
            <div class="task-column">
                <h3 class="task-column__title">In Progress</h3>
                <div class="task-column__list" id="in-progress-column"></div>
            </div>
            <div class="task-column">
                <h3 class="task-column__title">Done</h3>
                <div class="task-column__list" id="done-column"></div>
            </div>
        </div>
    </section>
</div>

<div class="modal-overlay" id="task-modal">
    <div class="modal">
        <div class="modal__header">
            <h3 class="modal__title" id="task-modal-title">Add Task</h3>
            <button class="btn btn-ghost btn-icon btn-sm" id="btn-close-task-modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal__body">
            <form id="task-form">
                <input type="hidden" id="task-id">
                <div class="form-group">
                    <label class="form-label">Task Title</label>
                    <input type="text" id="task-title" class="form-input" required placeholder="Describe the task">
                </div>
                <div class="form-group">
                    <label class="form-label">Course</label>
                    <select id="task-course" class="form-select">
                        <option value="">No course</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select id="task-priority" class="form-select">
                        <option value="3">High</option>
                        <option value="2" selected>Medium</option>
                        <option value="1">Low</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="task-status" class="form-select">
                        <option value="todo">Todo</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
                <div class="form-row" style="gap: 12px;">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Deadline</label>
                        <input type="datetime-local" id="task-deadline" class="form-input">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Estimate</label>
                        <select id="task-estimate" class="form-select">
                            <option value="15">15 mins</option>
                            <option value="30">30 mins</option>
                            <option value="45">45 mins</option>
                            <option value="60" selected>1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                            <option value="180">3 hours</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="task-description" class="form-input" rows="3" placeholder="Add optional details"></textarea>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-secondary" id="btn-cancel-task">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-task">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/tasks.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

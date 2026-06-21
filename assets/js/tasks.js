document.addEventListener('DOMContentLoaded', () => {
    const taskBoard = document.getElementById('task-board');
    const todoColumn = document.getElementById('todo-column');
    const inProgressColumn = document.getElementById('in-progress-column');
    const doneColumn = document.getElementById('done-column');

    const filterStatus = document.getElementById('filter-status');
    const filterCourse = document.getElementById('filter-course');
    const filterPriority = document.getElementById('filter-priority');
    const btnClearFilters = document.getElementById('btn-clear-filters');

    const btnOpenTaskModal = document.getElementById('btn-open-task-modal');
    const taskModal = document.getElementById('task-modal');
    const btnCloseTaskModal = document.getElementById('btn-close-task-modal');
    const btnCancelTask = document.getElementById('btn-cancel-task');
    const taskForm = document.getElementById('task-form');
    const taskModalTitle = document.getElementById('task-modal-title');

    const taskIdInput = document.getElementById('task-id');
    const taskTitleInput = document.getElementById('task-title');
    const taskCourseInput = document.getElementById('task-course');
    const taskPriorityInput = document.getElementById('task-priority');
    const taskStatusInput = document.getElementById('task-status');
    const taskDeadlineInput = document.getElementById('task-deadline');
    const taskEstimateInput = document.getElementById('task-estimate');
    const taskDescriptionInput = document.getElementById('task-description');

    let tasks = [];
    let courses = [];
    const estimateOptions = [15, 30, 45, 60, 90, 120, 180];

    init();

    async function init() {
        await loadCourses();
        await loadTasks();
        attachListeners();
    }

    function attachListeners() {
        filterStatus.addEventListener('change', loadTasks);
        filterCourse.addEventListener('change', loadTasks);
        filterPriority.addEventListener('change', loadTasks);
        btnClearFilters.addEventListener('click', () => {
            filterStatus.value = '';
            filterCourse.value = '';
            filterPriority.value = '';
            loadTasks();
        });

        btnOpenTaskModal.addEventListener('click', () => openTaskModal());
        btnCloseTaskModal.addEventListener('click', closeTaskModal);
        btnCancelTask.addEventListener('click', closeTaskModal);
        taskModal.addEventListener('click', (event) => {
            if (event.target === taskModal) {
                closeTaskModal();
            }
        });

        taskForm.addEventListener('submit', handleTaskFormSubmit);
        taskBoard.addEventListener('click', handleTaskBoardClick);
    }

    async function loadCourses() {
        try {
            const data = await API.courses.list();
            courses = data.courses || [];
        } catch (error) {
            courses = [];
            showToast('Unable to load courses. Please refresh the page.', 'error');
        }
        renderCourseFilters();
        renderCourseSelect();
    }

    async function loadTasks() {
        try {
            const filters = {
                status: filterStatus.value,
                course_id: filterCourse.value,
                priority: filterPriority.value,
            };
            const data = await API.tasks.list(filters);
            tasks = data.tasks || [];
            renderTaskBoard();
        } catch (error) {
            tasks = [];
            showToast('Unable to load tasks from the server.', 'error');
        }
    }

    function renderCourseFilters() {
        filterCourse.innerHTML = '<option value="">All Courses</option>';
        courses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name;
            filterCourse.appendChild(option);
        });
    }

    function renderCourseSelect() {
        taskCourseInput.innerHTML = '<option value="">No course</option>';
        courses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name;
            taskCourseInput.appendChild(option);
        });
    }

    function renderTaskBoard() {
        todoColumn.innerHTML = '';
        inProgressColumn.innerHTML = '';
        doneColumn.innerHTML = '';

        const statusBuckets = {
            todo: todoColumn,
            in_progress: inProgressColumn,
            done: doneColumn,
        };

        tasks.forEach(task => {
            const card = buildTaskCard(task);
            statusBuckets[task.status]?.appendChild(card);
        });

        Object.values(statusBuckets).forEach(column => {
            if (column.children.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'task-column__empty';
                empty.textContent = 'No tasks here yet.';
                column.appendChild(empty);
            }
        });
    }

    function buildTaskCard(task) {
        const card = document.createElement('article');
        card.className = 'card card--task';
        card.dataset.taskId = task.id;

        const courseBadge = task.course_name ? `<span class="badge badge--course" style="background-color: ${task.course_color};">${task.course_name}</span>` : '';
        const deadlineText = task.deadline ? `<div class="task-card__meta">Due ${formatDeadline(task.deadline)}</div>` : '';

        card.innerHTML = `
            <div class="task-card__header">
                <h4 class="task-card__title">${escapeHtml(task.title)}</h4>
                <span class="badge badge--priority-${priorityLabel(task.priority)}">${priorityLabel(task.priority)}</span>
            </div>
            <div class="task-card__body">
                ${courseBadge}
                ${deadlineText}
                <p class="task-card__description">${escapeHtml(task.description || '')}</p>
            </div>
            <div class="task-card__footer">
                <button type="button" class="btn btn-text btn-sm" data-action="edit" data-task-id="${task.id}">Edit</button>
                <button type="button" class="btn btn-text btn-sm" data-action="delete" data-task-id="${task.id}">Delete</button>
            </div>
        `;

        return card;
    }

    function formatDeadline(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function priorityLabel(value) {
        if (value === 3 || value === '3') return 'High';
        if (value === 2 || value === '2') return 'Medium';
        return 'Low';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function handleTaskBoardClick(event) {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }

        const taskId = parseInt(button.dataset.taskId, 10);
        const action = button.dataset.action;

        if (action === 'edit') {
            const task = tasks.find(item => item.id === taskId);
            if (task) {
                openTaskModal(task);
            }
            return;
        }

        if (action === 'delete') {
            deleteTask(taskId);
        }
    }

    function openTaskModal(task = null) {
        resetTaskForm();
        if (task) {
            taskModalTitle.textContent = 'Edit Task';
            taskIdInput.value = task.id;
            taskTitleInput.value = task.title;
            taskDescriptionInput.value = task.description || '';
            taskPriorityInput.value = task.priority;
            taskStatusInput.value = task.status;
            taskEstimateInput.value = normalizeEstimateMinutes(task.estimated_minutes || 60);
            if (task.deadline) {
                const deadline = new Date(task.deadline);
                taskDeadlineInput.value = deadline.toISOString().slice(0, 16);
            }
            taskCourseInput.value = task.course_id || '';
        } else {
            taskModalTitle.textContent = 'Add Task';
        }
        taskModal.classList.add('active');
    }

    function closeTaskModal() {
        taskModal.classList.remove('active');
        resetTaskForm();
    }

    function resetTaskForm() {
        taskIdInput.value = '';
        taskTitleInput.value = '';
        taskDescriptionInput.value = '';
        taskPriorityInput.value = '2';
        taskStatusInput.value = 'todo';
        taskDeadlineInput.value = '';
        taskEstimateInput.value = '60';
        taskCourseInput.value = '';
    }

    function normalizeEstimateMinutes(value) {
        const numericValue = parseInt(value, 10);
        if (Number.isNaN(numericValue)) {
            return '60';
        }

        let closest = estimateOptions[0];
        let smallestDelta = Math.abs(closest - numericValue);

        estimateOptions.forEach(option => {
            const delta = Math.abs(option - numericValue);
            if (delta < smallestDelta) {
                closest = option;
                smallestDelta = delta;
            }
        });

        return String(closest);
    }

    async function handleTaskFormSubmit(event) {
        event.preventDefault();
        const values = {
            title: taskTitleInput.value.trim(),
            course_id: taskCourseInput.value || null,
            priority: parseInt(taskPriorityInput.value, 10),
            status: taskStatusInput.value,
            deadline: taskDeadlineInput.value ? taskDeadlineInput.value.replace('T', ' ') : '',
            estimated_minutes: sanitizeInt(taskEstimateInput.value) || 60,
            description: taskDescriptionInput.value.trim(),
        };

        if (!values.title) {
            showToast('Task title is required', 'warning');
            return;
        }

        try {
            if (taskIdInput.value) {
                await API.tasks.update(parseInt(taskIdInput.value, 10), values);
                showToast('Task updated successfully', 'success');
            } else {
                await API.tasks.create(values);
                showToast('Task created successfully', 'success');
            }
            closeTaskModal();
            await loadTasks();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    async function deleteTask(taskId) {
        if (!confirm('Delete this task?')) {
            return;
        }

        try {
            await API.tasks.delete(taskId);
            showToast('Task deleted', 'success');
            await loadTasks();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function sanitizeInt(value) {
        const num = parseInt(value, 10);
        return Number.isNaN(num) ? 0 : num;
    }
});

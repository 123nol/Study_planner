document.addEventListener('DOMContentLoaded', () => {
    const reminderList = document.getElementById('reminder-list');
    const reminderForm = document.getElementById('reminder-form');
    const reminderMessage = document.getElementById('reminder-message');
    const reminderTask = document.getElementById('reminder-task');
    const reminderDate = document.getElementById('reminder-date');
    const reminderType = document.getElementById('reminder-type');
    const notificationToggle = document.getElementById('notification-toggle');
    const btnTestNotification = document.getElementById('btn-test-notification');

    let reminders = [];
    let tasks = [];
    let notificationEnabled = false;

    init();

    function init() {
        loadTasks();
        loadReminders();
        attachListeners();
        checkNotificationPermission();
    }

    function attachListeners() {
        reminderForm.addEventListener('submit', handleFormSubmit);
        reminderList.addEventListener('click', handleReminderClick);
        notificationToggle.addEventListener('change', handleNotificationToggle);
        btnTestNotification.addEventListener('click', handleNotificationTest);
        setInterval(updateCountdowns, 1000);
        setInterval(loadReminders, 60000);
    }

    async function loadReminders() {
        try {
            const data = await API.reminders.list();
            reminders = data.reminders || [];
            renderReminders();
        } catch (error) {
            showToast('Unable to load reminders.', 'error');
        }
    }

    async function loadTasks() {
        try {
            const data = await API.tasks.list();
            tasks = data.tasks || [];
            renderTaskOptions();
        } catch (error) {
            tasks = [];
            showToast('Unable to load tasks for reminders.', 'error');
        }
    }

    function renderTaskOptions() {
        reminderTask.innerHTML = '<option value="">No task</option>';
        tasks.forEach(task => {
            const option = document.createElement('option');
            option.value = task.id;
            option.textContent = task.title;
            reminderTask.appendChild(option);
        });
    }

    function renderReminders() {
        reminderList.innerHTML = '';

        if (reminders.length === 0) {
            reminderList.innerHTML = '<div class="reminder-empty">No active reminders yet.</div>';
            return;
        }

        reminders.forEach(reminder => {
            const card = document.createElement('article');
            card.className = 'card card--task reminder-card';
            card.dataset.reminderId = reminder.id;

            const taskBadge = reminder.task_title ? `<span class="badge badge--course" style="background-color: var(--accent-indigo);">${escapeHtml(reminder.task_title)}</span>` : '';
            const courseBadge = reminder.task_course_name ? `<span class="badge" style="background-color: ${escapeHtml(reminder.task_course_color || '#64748b')};">${escapeHtml(reminder.task_course_name)}</span>` : '';
            const typeBadge = `<span class="badge badge--reminder-type badge--${escapeHtml(reminder.type)}">${escapeHtml(getTypeLabel(reminder.type))}</span>`;
            const countdown = calculateCountdown(reminder.remind_at);
            const dueClass = new Date(reminder.remind_at) <= new Date() ? ' reminder-card--due' : '';

            card.className = `card card--task reminder-card${dueClass}`;
            card.innerHTML = `
                <div class="reminder-card__header">
                    <div>
                        <div class="reminder-card__title-row">
                            <h4>${escapeHtml(reminder.message)}</h4>
                            ${typeBadge}
                        </div>
                        <div class="reminder-card__task-line">${taskBadge}${courseBadge}</div>
                    </div>
                    <button class="btn btn-text btn-sm" data-action="dismiss" data-id="${reminder.id}">Dismiss</button>
                </div>
                <div class="reminder-card__meta">
                    <span>${escapeHtml(formatDateTime(reminder.remind_at))}</span>
                </div>
                <div class="reminder-card__countdown">${countdown}</div>
            `;

            reminderList.appendChild(card);
        });
    }

    async function handleFormSubmit(event) {
        event.preventDefault();

        const reminder = {
            message: reminderMessage.value.trim(),
            task_id: reminderTask.value ? parseInt(reminderTask.value, 10) : null,
            remind_at: reminderDate.value.replace('T', ' '),
            type: reminderType.value,
        };

        if (!reminder.message) {
            showToast('Enter a reminder message.', 'warning');
            return;
        }

        if (!reminder.remind_at) {
            showToast('Choose a date and time.', 'warning');
            return;
        }

        try {
            await API.reminders.create(reminder);
            showToast('Reminder created.', 'success');
            reminderForm.reset();
            loadReminders();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function handleReminderClick(event) {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const action = button.dataset.action;
        const id = parseInt(button.dataset.id, 10);

        if (action === 'dismiss') {
            dismissReminder(id);
        }
    }

    async function dismissReminder(id) {
        try {
            await API.reminders.dismiss(id);
            showToast('Reminder dismissed.', 'success');
            loadReminders();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function handleNotificationToggle() {
        if (notificationToggle.checked) {
            requestNotificationPermission();
        } else {
            notificationEnabled = false;
        }
    }

    function handleNotificationTest() {
        if (!notificationEnabled) {
            showToast('Enable notifications first.', 'warning');
            return;
        }

        new Notification('Smart Study Planner', {
            body: 'This is a test reminder notification.',
            silent: true,
        });
        showToast('Test notification sent.', 'success');
    }

    function calculateCountdown(target) {
        const now = new Date();
        const then = new Date(target);
        const diff = then - now;

        if (diff <= 0) {
            return 'Now';
        }

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        const remainingHours = hours % 24;
        const remainingMinutes = minutes % 60;

        return `${days}d ${remainingHours}h ${remainingMinutes}m`;
    }

    function updateCountdowns() {
        const now = new Date();

        document.querySelectorAll('.reminder-card').forEach(card => {
            const id = parseInt(card.dataset.reminderId, 10);
            const reminder = reminders.find(item => item.id === id);
            if (!reminder) return;
            const countdownEl = card.querySelector('.reminder-card__countdown');
            if (countdownEl) {
                countdownEl.textContent = calculateCountdown(reminder.remind_at);
            }

            if (new Date(reminder.remind_at) <= now && !reminder.notified) {
                handleDueReminder(reminder);
                card.classList.add('reminder-card--due');
            }
        });
    }

    function handleDueReminder(reminder) {
        if (reminder.type === 'notification') {
            if (notificationEnabled && Notification.permission === 'granted') {
                sendBrowserNotification('Reminder', reminder.message);
            } else {
                showToast(reminder.message, 'info');
            }
        } else if (reminder.type === 'alert') {
            showToast(reminder.message, 'info');
        }

        reminder.notified = true;
    }

    function getTypeLabel(type) {
        return type === 'notification' ? 'Notification'
            : type === 'countdown' ? 'Countdown'
            : 'Alert';
    }

    function formatDateTime(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function checkNotificationPermission() {
        if (!('Notification' in window)) {
            notificationToggle.disabled = true;
            return;
        }
        notificationEnabled = Notification.permission === 'granted';
        notificationToggle.checked = notificationEnabled;
    }

    function requestNotificationPermission() {
        if (!('Notification' in window)) return;
        Notification.requestPermission().then(permission => {
            notificationEnabled = permission === 'granted';
            notificationToggle.checked = notificationEnabled;
            if (notificationEnabled) {
                showToast('Browser notifications enabled.', 'success');
            } else {
                showToast('Browser notifications denied.', 'warning');
            }
        });
    }

    function sendBrowserNotification(title, body) {
        if (!notificationEnabled || Notification.permission !== 'granted') return;
        new Notification(title, { body, silent: true });
    }
});

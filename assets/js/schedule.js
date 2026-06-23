/**
 * ============================================
 * SCHEDULE GENERATOR LOGIC
 * ============================================
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // --- State ---
    let courses = [];
    let scheduleBlocks = [];
    let tasks = [];
    let reminders = [];
    
    let dragState = {
        blockEl: null,
        blockId: null,
        isResizing: false,
        startY: 0,
        startTop: 0,
        startHeight: 0,
        originalCol: null,
        currentCol: null
    };

    // 2D Array: availability[day][hour] = boolean
    // Initialize 7 days, 24 hours, all false by default
    let availability = Array(7).fill().map(() => Array(24).fill(false));
    
    // Default availability: Mon-Fri, 9am - 5pm
    for (let day = 1; day <= 5; day++) {
        for (let hour = 9; hour < 17; hour++) {
            availability[day][hour] = true;
        }
    }

    // --- DOM Elements ---
    const btnGenerate = document.getElementById('btn-generate');
    const btnEmptyGenerate = document.getElementById('btn-empty-generate');
    const btnReset = document.getElementById('btn-reset');
    const btnSave = document.getElementById('btn-save');
    
    const courseListContainer = document.getElementById('course-list-container');
    const availabilityGrid = document.getElementById('availability-grid');
    
    const prefMaxBlock = document.getElementById('pref-max-block');
    const prefMinBlock = document.getElementById('pref-min-block');
    const prefBreak = document.getElementById('pref-break');
    
    const calendarHeader = document.getElementById('calendar-header');
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarEmpty = document.getElementById('calendar-empty');
    const calendarContent = document.getElementById('calendar-content');
    
    const statsContainer = document.getElementById('schedule-stats');
    const statTotalTime = document.getElementById('stat-total-time');
    const statBlocks = document.getElementById('stat-blocks');

    const btnManageCourses = document.getElementById('btn-manage-courses');
    const courseModal = document.getElementById('course-modal');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const btnCancelCourse = document.getElementById('btn-cancel-course');
    const courseForm = document.getElementById('course-form');
    const modalCourseList = document.getElementById('modal-course-list');

    // --- Initialization ---
    init();

    async function init() {
        await loadCourses();
        await loadTasksAndReminders();
        renderAvailabilityGrid();
        renderCalendarBase();
        attachEventListeners();
        await loadSavedSchedule();
    }

    async function loadCourses() {
        try {
            const data = await API.courses.list();
            courses = data.courses || [];
        } catch (error) {
            courses = [];
            showToast('Unable to load courses. Please refresh the page.', 'error');
        }
        renderCourseList();
    }

    async function loadTasksAndReminders() {
        try {
            if (typeof API !== 'undefined' && API.tasks && typeof API.tasks.list === 'function') {
                const data = await API.tasks.list();
                tasks = data.tasks || [];
            }
        } catch (error) {
            console.warn('Unable to load tasks for calendar:', error);
        }

        try {
            if (typeof API !== 'undefined' && API.reminders && typeof API.reminders.list === 'function') {
                const data = await API.reminders.list();
                reminders = data.reminders || [];
            }
        } catch (error) {
            console.warn('Unable to load reminders for calendar:', error);
        }
    }

    async function loadSavedSchedule() {
        let loadedBlocks = [];
        let loadedFromAPI = false;

        // 1. Try to load from server database API first
        try {
            if (typeof API !== 'undefined' && API.schedule) {
                let response = null;
                if (typeof API.schedule.get === 'function') {
                    response = await API.schedule.get();
                } else if (typeof API.schedule.list === 'function') {
                    response = await API.schedule.list();
                }
                
                if (response && response.blocks) {
                    loadedBlocks = response.blocks;
                    loadedFromAPI = true;
                }
            }
        } catch (error) {
            console.warn('Could not load schedule from server database, trying local storage...', error);
        }

        // 2. Fallback to localStorage only if the API failed/doesn't exist
        if (!loadedFromAPI) {
            try {
                const localData = localStorage.getItem('study_schedule_blocks');
                if (localData) {
                    loadedBlocks = JSON.parse(localData);
                }
            } catch (e) {
                console.error('Error loading schedule from local storage:', e);
            }
        }

        // 3. Validate block matching safely
        if (loadedBlocks && loadedBlocks.length > 0) {
            // Normalize IDs to strings to prevent string-vs-number type mismatches
            const cachedCourseIds = new Set(
                loadedBlocks.map(b => String(b.course_id)).filter(id => id !== 'null' && id !== 'undefined' && id !== '')
            );
            const currentUserCourseIds = new Set(
                courses.map(c => String(c.id))
            );

            let isMismatch = false;
            if (courses.length === 0 && loadedBlocks.length > 0) {
                isMismatch = true;
            } else {
                for (let cid of cachedCourseIds) {
                    if (!currentUserCourseIds.has(cid)) {
                        isMismatch = true;
                        break;
                    }
                }
            }

            if (isMismatch) {
                // DO NOT delete it destructively. Simply clear the local variable so we don't render it.
                // This keeps the backup safe in localStorage if they log back into the original account!
                loadedBlocks = [];
            }
        }

        if (loadedBlocks && loadedBlocks.length > 0) {
            scheduleBlocks = loadedBlocks;
            renderScheduleBlocks();
            
            // Show calendar interface and hide empty screen state
            if (calendarEmpty) calendarEmpty.style.display = 'none';
            if (calendarContent) calendarContent.style.display = 'flex';
            if (statsContainer) statsContainer.style.display = 'flex';
            if (btnSave) btnSave.style.display = 'inline-flex';
        } else {
            showEmptyCalendarState();
        }
    }

    function attachEventListeners() {
        btnGenerate.addEventListener('click', handleGenerate);
        if (btnEmptyGenerate) {
            btnEmptyGenerate.addEventListener('click', handleGenerate);
        }
        btnReset.addEventListener('click', handleReset);
        btnSave.addEventListener('click', async () => {
            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = `<div class="spinner" style="margin: 0 auto; border-color: rgba(255,255,255,0.3); border-top-color: white;"></div>`;
            btnSave.disabled = true;

            try {
                const response = await API.schedule.save(scheduleBlocks);
                
                // Keep local storage synchronized
                try {
                    localStorage.setItem('study_schedule_blocks', JSON.stringify(scheduleBlocks));
                } catch (e) {
                    console.error(e);
                }

                showToast(response.message || 'Schedule saved successfully!', 'success');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                btnSave.innerHTML = originalText;
                btnSave.disabled = false;
            }
        });
        
        btnManageCourses.addEventListener('click', openCourseModal);
        btnCloseModal.addEventListener('click', closeCourseModal);
        btnCancelCourse.addEventListener('click', closeCourseModal);
        courseForm.addEventListener('submit', handleAddCourse);
        
        // Close modal on outside click
        courseModal.addEventListener('click', (e) => {
            if (e.target === courseModal) closeCourseModal();
        });
        
        // Handle dragging on availability grid to paint multiple cells
        let isDragging = false;
        let isPaintingAvailability = true; // true = setting to available, false = removing

        availabilityGrid.addEventListener('mousedown', (e) => {
            const cell = e.target.closest('.availability-grid__cell');
            if (!cell) return;
            
            isDragging = true;
            const day = parseInt(cell.dataset.day);
            const hour = parseInt(cell.dataset.hour);
            
            // Toggle the current cell and store the action (paint or erase) for the drag
            isPaintingAvailability = !availability[day][hour];
            availability[day][hour] = isPaintingAvailability;
            
            if (isPaintingAvailability) cell.classList.add('available');
            else cell.classList.remove('available');
        });

        availabilityGrid.addEventListener('mouseover', (e) => {
            if (!isDragging) return;
            const cell = e.target.closest('.availability-grid__cell');
            if (!cell) return;
            
            const day = parseInt(cell.dataset.day);
            const hour = parseInt(cell.dataset.hour);
            
            availability[day][hour] = isPaintingAvailability;
            if (isPaintingAvailability) cell.classList.add('available');
            else cell.classList.remove('available');
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
        });

        // Drag and Drop Schedule Blocks
        calendarGrid.addEventListener('mousedown', handleBlockMouseDown);
        document.addEventListener('mousemove', handleBlockMouseMove);
        document.addEventListener('mouseup', handleBlockMouseUp);
    }

    // --- UI Rendering ---

    function openCourseModal() {
        renderModalCourseList();
        courseModal.classList.add('active');
    }

    function closeCourseModal() {
        courseModal.classList.remove('active');
        courseForm.reset();
        document.getElementById('course-color').value = '#6366f1';
    }

    async function handleAddCourse(e) {
        e.preventDefault();

        const courseData = {
            name: document.getElementById('course-name').value.trim(),
            color: document.getElementById('course-color').value,
            priority: parseInt(document.getElementById('course-priority').value, 10),
            weekly_hours_goal: parseInt(document.getElementById('course-hours').value, 10),
            end_date: document.getElementById('course-date').value || ''
        };

        if (!courseData.name) {
            showToast('Course name is required.', 'warning');
            return;
        }

        try {
            const response = await API.courses.create(courseData);
            const createdCourse = response.course;
            courses.push(createdCourse);
            renderCourseList();
            renderModalCourseList();
            showToast('Course added successfully!', 'success');
            closeCourseModal();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function renderModalCourseList() {
        modalCourseList.innerHTML = '';
        if (courses.length === 0) {
            modalCourseList.innerHTML = '<div style="font-size: var(--fs-xs); color: var(--text-tertiary);">No courses yet. Add one above.</div>';
            return;
        }

        courses.forEach(course => {
            const html = `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background: var(--bg-secondary); border-radius: var(--radius-sm); border: 1px solid var(--glass-border);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 12px; height: 12px; border-radius: 50%; background-color: ${course.color}"></div>
                        <span style="font-size: var(--fs-sm); font-weight: var(--fw-medium);">${course.name}</span>
                    </div>
                    <button type="button" class="btn btn-ghost btn-icon btn-sm" onclick="window.deleteCourse(${course.id})" style="color: var(--color-danger);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            `;
            modalCourseList.insertAdjacentHTML('beforeend', html);
        });
    }

    // Expose delete function to window so it can be called from inline onclick
    window.deleteCourse = async function(id) {
        try {
            await API.courses.delete(id);
            courses = courses.filter(c => c.id !== id);
            renderCourseList();
            renderModalCourseList();
            showToast('Course deleted', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    };

    function renderCourseList() {
        courseListContainer.innerHTML = '';
        if (courses.length === 0) {
            courseListContainer.innerHTML = '<div class="course-list__empty">No courses added yet.</div>';
            return;
        }

        courses.forEach(course => {
            const priorityText = course.priority === 3 ? 'High' : course.priority === 2 ? 'Med' : 'Low';
            const html = `
                <div class="card card--course">
                    <div class="course-color" style="background-color: ${course.color}"></div>
                    <div class="course-info">
                        <div class="course-name">${course.name}</div>
                        <div class="course-meta">
                            <span>${course.weekly_hours_goal}h / week</span>
                            <span class="badge badge--priority-${priorityText.toLowerCase()}">${priorityText}</span>
                        </div>
                    </div>
                </div>
            `;
            courseListContainer.insertAdjacentHTML('beforeend', html);
        });
    }

    function renderAvailabilityGrid() {
        availabilityGrid.innerHTML = '';
        
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        // Header row
        availabilityGrid.insertAdjacentHTML('beforeend', `<div></div>`); // Top-left corner
        days.forEach(day => {
            availabilityGrid.insertAdjacentHTML('beforeend', `<div class="availability-grid__header">${day}</div>`);
        });

        // Rows (9am to 9pm only for the mini-editor to save space)
        for (let hour = 9; hour <= 21; hour++) {
            // Time label
            const displayHour = hour > 12 ? hour - 12 : hour;
            const ampm = hour >= 12 ? 'p' : 'a';
            availabilityGrid.insertAdjacentHTML('beforeend', `<div class="availability-grid__time">${displayHour}${ampm}</div>`);
            
            // Day cells
            for (let day = 0; day < 7; day++) {
                const isAvail = availability[day][hour];
                availabilityGrid.insertAdjacentHTML('beforeend', `
                    <div class="availability-grid__cell ${isAvail ? 'available' : ''}" 
                         data-day="${day}" 
                         data-hour="${hour}">
                    </div>
                `);
            }
        }
    }

    function renderCalendarBase() {
        // Keep header simple
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const today = new Date().getDay();

        // Ensure we only render header days once
        const existingDays = calendarHeader.querySelectorAll('.calendar__header-cell');
        if (existingDays.length === 0) {
            days.forEach((day, index) => {
                const isToday = index === today;
                calendarHeader.insertAdjacentHTML('beforeend', `
                    <div class="calendar__header-cell ${isToday ? 'today' : ''}">
                        <span class="day-name">${day.substring(0, 3)}</span>
                    </div>
                `);
            });
        }

        calendarGrid.innerHTML = '';

        // Time labels column
        let timesHtml = '<div class="calendar__times" style="grid-column: 1;">';
        for (let hour = 6; hour <= 22; hour++) {
            const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
            const ampm = hour >= 12 ? 'PM' : 'AM';
            timesHtml += `<div class="calendar__time-label">${displayHour} ${ampm}</div>`;
        }
        timesHtml += '</div>';
        calendarGrid.insertAdjacentHTML('beforeend', timesHtml);

        // 7 Day columns
        for (let day = 0; day < 7; day++) {
            let colHtml = `<div class="calendar__day-column" data-day="${day}" style="grid-column: ${day + 2};">`;
            for (let hour = 6; hour <= 22; hour++) {
                colHtml += `<div class="calendar__hour-cell" data-hour="${hour}"></div>`;
            }
            colHtml += '</div>';
            calendarGrid.insertAdjacentHTML('beforeend', colHtml);
        }
    }

    // --- Logic ---

    function showEmptyCalendarState() {
        document.querySelectorAll('.schedule-block').forEach(el => el.remove());
        calendarContent.style.display = 'none';
        statsContainer.style.display = 'none';
        btnSave.style.display = 'none';
        calendarEmpty.style.display = 'flex';
    }

    async function handleGenerate() {
        const originalText = btnGenerate.innerHTML;

        // Warning to prevent empty generation attempts
        if (courses.length === 0) {
            showToast("Please add at least one course first before generating a schedule.", "warning");
            return;
        }

        btnGenerate.innerHTML = `<div class="spinner" style="margin: 0 auto; border-color: rgba(255,255,255,0.3); border-top-color: white;"></div>`;
        btnGenerate.disabled = true;

        try {
            const prefs = {
                max_block_minutes: parseInt(prefMaxBlock.value),
                min_block_minutes: parseInt(prefMinBlock.value),
                break_minutes: parseInt(prefBreak.value)
            };

            const data = await API.schedule.generate(courses, availability, prefs);
            
            scheduleBlocks = data.blocks || [];
            
            if (scheduleBlocks.length === 0) {
                showEmptyCalendarState();
                showToast("Couldn't generate a schedule. Please add more availability.", "warning");
                return;
            }

            // Save generated blocks instantly to localStorage
            try {
                localStorage.setItem('study_schedule_blocks', JSON.stringify(scheduleBlocks));
            } catch (e) {
                console.error(e);
            }

            renderScheduleBlocks();
            
            // Switch views
            calendarEmpty.style.display = 'none';
            calendarContent.style.display = 'flex';
            statsContainer.style.display = 'flex';
            btnSave.style.display = 'inline-flex';
            
            showToast('Schedule generated successfully!', 'success');

        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            btnGenerate.innerHTML = originalText;
            btnGenerate.disabled = false;
        }
    }

    function renderScheduleBlocks() {
        // Clear existing blocks
        document.querySelectorAll('.schedule-block').forEach(el => el.remove());

        let totalMinutes = 0;

        scheduleBlocks.forEach(block => {
            const col = document.querySelector(`.calendar__day-column[data-day="${block.day}"]`);
            if (!col) return;

            // Calculate position and height
            // We start rendering at 6:00 AM (hour 6)
            // Each hour is 60px height.
            const [startHour, startMin] = block.start_time.split(':').map(Number);
            
            // If block starts before 6 AM or after 10 PM, ignore it for UI
            if (startHour < 6 || startHour > 22) return;

            const topOffset = ((startHour - 6) * 60) + startMin;
            const height = block.duration; // 1 minute = 1 px

            const blockEl = document.createElement('div');
            blockEl.className = 'schedule-block';
            blockEl.dataset.id = block.id;
            blockEl.style.top = `${topOffset}px`;
            blockEl.style.height = `${height}px`;
            blockEl.style.backgroundColor = `${block.color}20`; // 20 hex = 12% opacity
            blockEl.style.borderColor = block.color;
            blockEl.style.color = block.color;

            // Calculate font sizes based on height
            if (height >= 45) {
                blockEl.innerHTML = `
                    <div class="schedule-block__drag-handle"></div>
                    <div class="schedule-block__title">${block.label}</div>
                    <div class="schedule-block__time">${block.start_time} - ${block.end_time}</div>
                    <div class="schedule-block__resize-handle"></div>
                `;
            } else {
                // Compact view
                blockEl.innerHTML = `
                    <div class="schedule-block__drag-handle"></div>
                    <div class="schedule-block__title">${block.label}</div>
                    <div class="schedule-block__resize-handle"></div>
                `;
            }

            col.appendChild(blockEl);
            totalMinutes += block.duration;
        });

        // Update stats
        const hours = Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        statTotalTime.textContent = `${hours}h ${mins > 0 ? mins + 'm' : ''}`;
        statBlocks.textContent = scheduleBlocks.length;

        // Render Tasks and Reminders over study blocks
        renderTasksAndRemindersOnCalendar();
    }

    function renderTasksAndRemindersOnCalendar() {
        // Clear any existing calendar items for tasks/reminders to avoid duplicates
        document.querySelectorAll('.schedule-item--task, .schedule-item--reminder').forEach(el => el.remove());

        // Render Tasks
        tasks.forEach(task => {
            if (!task.deadline || task.status === 'done') return;

            // Parse deadline safely
            const deadlineStr = task.deadline.replace(' ', 'T');
            const deadlineDate = new Date(deadlineStr);
            if (isNaN(deadlineDate.getTime())) return;

            const day = deadlineDate.getDay();
            const hour = deadlineDate.getHours();
            const min = deadlineDate.getMinutes();

            // We only show items between 6 AM and 10 PM on calendar
            if (hour < 6 || hour > 22) return;

            const col = document.querySelector(`.calendar__day-column[data-day="${day}"]`);
            if (!col) return;

            const topOffset = ((hour - 6) * 60) + min;

            const taskEl = document.createElement('div');
            taskEl.className = 'schedule-block schedule-item--task';
            taskEl.style.top = `${topOffset}px`;
            taskEl.style.height = '35px'; // Compact static size
            taskEl.style.backgroundColor = 'rgba(99, 102, 241, 0.15)'; // indigo
            taskEl.style.borderColor = task.course_color || '#6366f1';
            taskEl.style.color = '#ffffff';
            taskEl.style.zIndex = '5';
            taskEl.style.padding = '4px 8px';
            taskEl.style.borderRadius = 'var(--radius-sm)';
            taskEl.style.fontSize = 'var(--fs-xs)';
            taskEl.style.borderLeft = `4px solid ${task.course_color || '#6366f1'}`;
            taskEl.style.cursor = 'default';
            taskEl.style.boxShadow = 'var(--shadow-sm)';
            taskEl.style.pointerEvents = 'none'; // prevent block drag interference

            taskEl.innerHTML = `
                <div class="schedule-block__title" style="font-weight: var(--fw-semibold); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    📌 Task: ${escapeHtml(task.title)}
                </div>
            `;

            col.appendChild(taskEl);
        });

        // Render Reminders
        reminders.forEach(reminder => {
            if (!reminder.remind_at) return;

            // Parse remind_at safely
            const remindStr = reminder.remind_at.replace(' ', 'T');
            const remindDate = new Date(remindStr);
            if (isNaN(remindDate.getTime())) return;

            const day = remindDate.getDay();
            const hour = remindDate.getHours();
            const min = remindDate.getMinutes();

            if (hour < 6 || hour > 22) return;

            const col = document.querySelector(`.calendar__day-column[data-day="${day}"]`);
            if (!col) return;

            const topOffset = ((hour - 6) * 60) + min;

            const reminderEl = document.createElement('div');
            reminderEl.className = 'schedule-block schedule-item--reminder';
            reminderEl.style.top = `${topOffset}px`;
            reminderEl.style.height = '35px'; // Compact static size
            reminderEl.style.backgroundColor = 'rgba(245, 158, 11, 0.15)'; // amber
            reminderEl.style.borderColor = '#f59e0b';
            reminderEl.style.color = '#ffffff';
            reminderEl.style.zIndex = '6';
            reminderEl.style.padding = '4px 8px';
            reminderEl.style.borderRadius = 'var(--radius-sm)';
            reminderEl.style.fontSize = 'var(--fs-xs)';
            reminderEl.style.borderLeft = '4px solid #f59e0b';
            reminderEl.style.cursor = 'default';
            reminderEl.style.boxShadow = 'var(--shadow-sm)';
            reminderEl.style.pointerEvents = 'none';

            reminderEl.innerHTML = `
                <div class="schedule-block__title" style="font-weight: var(--fw-semibold); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    🔔 Alert: ${escapeHtml(reminder.message)}
                </div>
            `;

            col.appendChild(reminderEl);
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

    // --- Drag and Drop Logic ---

    function handleBlockMouseDown(e) {
        const dragHandle = e.target.closest('.schedule-block__drag-handle');
        const resizeHandle = e.target.closest('.schedule-block__resize-handle');
        
        if (!dragHandle && !resizeHandle) return;

        const blockEl = e.target.closest('.schedule-block');
        if (!blockEl) return;

        e.preventDefault(); // Prevent text selection
        
        dragState.blockEl = blockEl;
        dragState.blockId = parseInt(blockEl.dataset.id);
        dragState.isResizing = !!resizeHandle;
        dragState.startY = e.clientY;
        dragState.startTop = parseInt(blockEl.style.top) || 0;
        dragState.startHeight = parseInt(blockEl.style.height) || 60;
        dragState.originalCol = blockEl.closest('.calendar__day-column');
        dragState.currentCol = dragState.originalCol;

        blockEl.classList.add('is-dragging');
    }

    function handleBlockMouseMove(e) {
        if (!dragState.blockEl) return;

        const deltaY = e.clientY - dragState.startY;

        if (dragState.isResizing) {
            // Resize (min height 15px = 15 mins)
            let newHeight = Math.max(15, dragState.startHeight + deltaY);
            // Snap to 5 mins (5px)
            newHeight = Math.round(newHeight / 5) * 5;
            dragState.blockEl.style.height = `${newHeight}px`;
        } else {
            // Move vertically
            let newTop = dragState.startTop + deltaY;
            // Snap to 5 mins (5px)
            newTop = Math.round(newTop / 5) * 5;
            // Prevent going out of bounds (top limit 0 = 6 AM, bottom limit 1020 = 11 PM)
            const maxTop = (17 * 60) - parseInt(dragState.blockEl.style.height);
            newTop = Math.max(0, Math.min(newTop, maxTop));
            dragState.blockEl.style.top = `${newTop}px`;

            // Handle horizontal move across days
            const oldPointerEvents = dragState.blockEl.style.pointerEvents;
            dragState.blockEl.style.pointerEvents = 'none';
            const hoveredCol = document.elementFromPoint(e.clientX, e.clientY)?.closest('.calendar__day-column');
            dragState.blockEl.style.pointerEvents = oldPointerEvents;

            if (hoveredCol && hoveredCol !== dragState.currentCol) {
                hoveredCol.appendChild(dragState.blockEl);
                dragState.currentCol = hoveredCol;
            }
        }
    }

    function handleBlockMouseUp(e) {
        if (!dragState.blockEl) return;

        dragState.blockEl.classList.remove('is-dragging');

        // Update the underlying scheduleBlocks data array
        const blockObj = scheduleBlocks.find(b => b.id === dragState.blockId);
        if (blockObj) {
            const finalTop = parseInt(dragState.blockEl.style.top) || 0;
            const finalHeight = parseInt(dragState.blockEl.style.height) || 60;
            const finalDay = parseInt(dragState.currentCol.dataset.day);

            // Recompute times based on top (pixels = minutes from 6 AM)
            const totalStartMins = (6 * 60) + finalTop;
            const totalEndMins = totalStartMins + finalHeight;

            blockObj.day = finalDay;
            blockObj.duration = finalHeight;
            blockObj.start_time = `${String(Math.floor(totalStartMins / 60)).padStart(2, '0')}:${String(totalStartMins % 60).padStart(2, '0')}`;
            blockObj.end_time = `${String(Math.floor(totalEndMins / 60)).padStart(2, '0')}:${String(totalEndMins % 60).padStart(2, '0')}`;

            // Save modified blocks to localStorage instantly
            try {
                localStorage.setItem('study_schedule_blocks', JSON.stringify(scheduleBlocks));
            } catch (e) {
                console.error(e);
            }

            // Re-render to ensure text and display are correct
            renderScheduleBlocks();
        }

        // Reset state
        dragState.blockEl = null;
        dragState.blockId = null;
    }

    function handleReset() {
        scheduleBlocks = [];
        try {
            localStorage.removeItem('study_schedule_blocks');
        } catch (e) {
            console.error(e);
        }
        showEmptyCalendarState();
        showToast('Schedule cleared.');
    }
});
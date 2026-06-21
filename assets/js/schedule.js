/**
 * ============================================
 * SCHEDULE GENERATOR LOGIC
 * ============================================
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // --- State ---
    let courses = [];
    let scheduleBlocks = [];
    
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
        renderAvailabilityGrid();
        renderCalendarBase();
        attachEventListeners();
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

    function attachEventListeners() {
        btnGenerate.addEventListener('click', handleGenerate);
        btnReset.addEventListener('click', handleReset);
        btnSave.addEventListener('click', async () => {
            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = `<div class="spinner" style="margin: 0 auto; border-color: rgba(255,255,255,0.3); border-top-color: white;"></div>`;
            btnSave.disabled = true;

            try {
                const response = await API.schedule.save(scheduleBlocks);
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

    async function handleGenerate() {
        const originalText = btnGenerate.innerHTML;
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
                showToast("Couldn't generate a schedule. Please add more availability.", "warning");
                return;
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
            // We temporarily hide the dragged element from pointer events so elementFromPoint sees what's under it
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

            // If it resized into/out of compact view, or changed times, re-render to ensure text is correct
            renderScheduleBlocks();
        }

        // Reset state
        dragState.blockEl = null;
        dragState.blockId = null;
    }

    function handleReset() {
        scheduleBlocks = [];
        document.querySelectorAll('.schedule-block').forEach(el => el.remove());
        
        calendarContent.style.display = 'none';
        statsContainer.style.display = 'none';
        btnSave.style.display = 'none';
        calendarEmpty.style.display = 'flex';
        
        showToast('Schedule cleared.');
    }
});

/**
 * ============================================
 * SCHEDULE GENERATOR LOGIC
 * ============================================
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // --- State ---
    let courses = [];
    let scheduleBlocks = [];
    
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

    // --- Initialization ---
    init();

    function init() {
        loadMockCourses();
        renderAvailabilityGrid();
        renderCalendarBase();
        attachEventListeners();
    }

    function loadMockCourses() {
        // Mock data to simulate DB load
        courses = [
            { id: 1, name: 'Web Development', color: '#6366f1', priority: 3, weekly_hours_goal: 10, end_date: '2026-06-15' },
            { id: 2, name: 'Data Structures', color: '#ec4899', priority: 3, weekly_hours_goal: 8, end_date: '2026-06-20' },
            { id: 3, name: 'Database Systems', color: '#14b8a6', priority: 2, weekly_hours_goal: 6, end_date: '2026-06-10' },
            { id: 4, name: 'Machine Learning', color: '#f59e0b', priority: 1, weekly_hours_goal: 4, end_date: '2026-07-01' }
        ];
        renderCourseList();
    }

    function attachEventListeners() {
        btnGenerate.addEventListener('click', handleGenerate);
        btnReset.addEventListener('click', handleReset);
        btnSave.addEventListener('click', () => showToast('Schedule saved successfully!', 'success'));
        
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
    }

    // --- UI Rendering ---

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
            blockEl.style.top = `${topOffset}px`;
            blockEl.style.height = `${height}px`;
            blockEl.style.backgroundColor = `${block.color}20`; // 20 hex = 12% opacity
            blockEl.style.borderColor = block.color;
            blockEl.style.color = block.color;

            // Calculate font sizes based on height
            if (height >= 45) {
                blockEl.innerHTML = `
                    <div class="schedule-block__title">${block.label}</div>
                    <div class="schedule-block__time">${block.start_time} - ${block.end_time}</div>
                `;
            } else {
                // Compact view
                blockEl.innerHTML = `
                    <div class="schedule-block__title">${block.label}</div>
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

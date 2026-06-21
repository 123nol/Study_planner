/**
 * ============================================
 * API WRAPPER
 * ============================================
 * Handles all fetch requests to the PHP backend
 */

const API = {
    /**
     * Base request function
     */
    async request(endpoint, method = 'GET', data = null) {
        const url = `/api/${endpoint}`;
        const options = {
            method,
            headers: {
                'Accept': 'application/json',
            }
        };

        if (data && method !== 'GET') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const text = await response.text();
            
            // Try to parse JSON, but catch HTML errors (like 404s or PHP errors)
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('API Error Response:', text);
                throw new Error('Server returned invalid JSON response');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.error || `HTTP Error: ${response.status}`);
            }

            return result.data;
        } catch (error) {
            console.error(`API Request failed for ${url}:`, error);
            throw error;
        }
    },

    // ----------------------------------------
    // Modules
    // ----------------------------------------

    schedule: {
        async generate(courses, availability, preferences) {
            // For now, since we haven't built the full database/auth layer,
            // we will send the mock data we want to schedule to the backend.
            return await API.request('schedule.php', 'POST', {
                action: 'generate',
                courses,
                availability,
                preferences
            });
        },

        async save(blocks) {
            return await API.request('schedule.php', 'POST', {
                action: 'save',
                blocks
            });
        }
    },

    courses: {
        async list() {
            return await API.request('courses.php', 'GET');
        },
        async create(course) {
            return await API.request('courses.php', 'POST', {
                action: 'create',
                ...course
            });
        },
        async update(id, course) {
            return await API.request('courses.php', 'POST', {
                action: 'update',
                id,
                ...course
            });
        },
        async delete(id) {
            return await API.request('courses.php', 'POST', {
                action: 'delete',
                id
            });
        }
    },

    tasks: {
        async list(filters = {}) {
            const params = new URLSearchParams();
            if (filters.status) params.append('status', filters.status);
            if (filters.course_id) params.append('course_id', filters.course_id);
            if (filters.priority) params.append('priority', filters.priority);
            const query = params.toString() ? `?${params.toString()}` : '';
            return await API.request(`tasks.php${query}`, 'GET');
        },
        async create(task) {
            return await API.request('tasks.php', 'POST', {
                action: 'create',
                ...task
            });
        },
        async update(id, task) {
            return await API.request('tasks.php', 'POST', {
                action: 'update',
                id,
                ...task
            });
        },
        async delete(id) {
            return await API.request('tasks.php', 'POST', {
                action: 'delete',
                id
            });
        }
    },

    reminders: {
        async list() {
            return await API.request('reminders.php', 'GET');
        },
        async create(reminder) {
            return await API.request('reminders.php', 'POST', {
                action: 'create',
                ...reminder
            });
        },
        async dismiss(id) {
            return await API.request('reminders.php', 'POST', {
                action: 'dismiss',
                id
            });
        }
    },

    auth: {
        async login(email, password) {
            return await API.request('auth.php', 'POST', { action: 'login', email, password });
        },
        async register(username, email, password) {
            return await API.request('auth.php', 'POST', { action: 'register', username, email, password });
        },
        async logout() {
            return await API.request('auth.php', 'POST', { action: 'logout' });
        },
        async checkSession() {
            return await API.request('auth.php', 'GET');
        }
    }
};

/**
 * ============================================
 * TOAST NOTIFICATION SYSTEM
 * ============================================
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    
    // Icon based on type
    let icon = '';
    if (type === 'success') icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
    else if (type === 'error') icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    else if (type === 'warning') icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';

    toast.innerHTML = `
        ${icon}
        <div class="toast__message">${message}</div>
    `;

    container.appendChild(toast);

    // Remove after 4 seconds
    setTimeout(() => {
        toast.classList.add('removing');
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    }, 4000);
}

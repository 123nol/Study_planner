<?php
require_once __DIR__ . '/../config/app.php';

// If already logged in, redirect to schedule generator
if (isset($_SESSION['user_id'])) {
    header('Location: schedule.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Smart Planner</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--bg-primary);
            background-image: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.08) 0%, transparent 40%);
            overflow-x: hidden;
            margin: 0;
            font-family: var(--font-body);
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: var(--space-4);
            animation: fadeIn var(--transition-slow);
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: var(--space-8);
            box-shadow: var(--shadow-xl);
        }

        .logo-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: var(--space-6);
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            margin-bottom: var(--space-3);
            background: var(--accent-gradient);
            border-radius: var(--radius-md);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-icon svg {
            color: #fff;
        }

        .brand-name {
            font-family: var(--font-heading);
            font-size: var(--fs-xl);
            font-weight: var(--fw-bold);
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .brand-tagline {
            font-size: var(--fs-sm);
            color: var(--text-secondary);
            margin: var(--space-1) 0 0 0;
        }

        .form-group {
            margin-bottom: var(--space-4);
        }

        .form-label {
            display: block;
            margin-bottom: var(--space-2);
            font-size: var(--fs-sm);
            font-weight: var(--fw-medium);
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            background-color: rgba(10, 10, 18, 0.4);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: var(--fs-base);
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: var(--space-3);
            background: var(--accent-gradient);
            border: none;
            border-radius: var(--radius-md);
            color: #fff;
            font-family: var(--font-heading);
            font-size: var(--fs-base);
            font-weight: var(--fw-semibold);
            cursor: pointer;
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--space-2);
            margin-top: var(--space-2);
        }

        .btn-submit:hover {
            background: var(--accent-gradient-hover);
            box-shadow: var(--shadow-glow);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .auth-footer {
            text-align: center;
            margin-top: var(--space-6);
            font-size: var(--fs-sm);
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: var(--accent-indigo);
            text-decoration: none;
            font-weight: var(--fw-medium);
            transition: color var(--transition-fast);
        }

        .auth-footer a:hover {
            color: var(--accent-purple);
            text-decoration: underline;
        }

        .error-message {
            background-color: var(--color-danger-soft);
            border: 1px solid rgba(244, 63, 94, 0.2);
            border-radius: var(--radius-sm);
            color: var(--color-danger);
            padding: var(--space-3);
            margin-bottom: var(--space-5);
            font-size: var(--fs-sm);
            display: none;
            align-items: center;
            gap: var(--space-2);
        }

        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 2px solid #fff;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <!-- Logo and Brand -->
        <div class="logo-header">
            <div class="logo-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <h1 class="brand-name">SmartPlanner</h1>
            <p class="brand-tagline">Create your planner account</p>
        </div>

        <!-- Error Panel -->
        <div class="error-message" id="error-panel">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span id="error-text">Registration failed.</span>
        </div>

        <!-- Registration Form -->
        <form id="register-form">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" class="form-control" placeholder="Choose a username" required minlength="3" maxlength="50" autocomplete="username">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" class="form-control" placeholder="name@domain.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" class="form-control" placeholder="Min 8 chars, letters & numbers" required minlength="8" autocomplete="new-password" pattern="(?=.*[0-9])(?=.*[A-Za-z]).{8,}">
            </div>

            <div class="form-group">
                <label for="confirm-password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm-password" class="form-control" placeholder="Repeat your password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-submit" id="btn-submit">
                <div class="spinner" id="spinner"></div>
                <span id="btn-text">Sign Up</span>
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</div>

<script>
    document.getElementById('register-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        
        const errorPanel = document.getElementById('error-panel');
        const errorText = document.getElementById('error-text');
        const btnSubmit = document.getElementById('btn-submit');
        const btnText = document.getElementById('btn-text');
        const spinner = document.getElementById('spinner');

        // Reset error state
        errorPanel.style.display = 'none';

        // Client-side validations
        if (username.length < 3) {
            showError('Username must be at least 3 characters.');
            return;
        }

        if (password.length < 6) {
            showError('Password must be at least 6 characters.');
            return;
        }

        if (password !== confirmPassword) {
            showError('Passwords do not match.');
            return;
        }

        // Show loading state
        btnSubmit.disabled = true;
        btnText.textContent = 'Creating account...';
        spinner.style.display = 'block';

        try {
            const response = await fetch('../api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: 'register',
                    username: username,
                    email: email,
                    password: password
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Success redirect to schedule generator
                window.location.href = 'schedule.php';
            } else {
                showError(result.error || 'Registration failed.');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showError('A network error occurred. Please try again.');
        } finally {
            // Restore button state
            btnSubmit.disabled = false;
            btnText.textContent = 'Sign Up';
            spinner.style.display = 'none';
        }
    });

    function showError(message) {
        const errorPanel = document.getElementById('error-panel');
        const errorText = document.getElementById('error-text');
        errorText.textContent = message;
        errorPanel.style.display = 'flex';
    }
</script>

</body>
</html>

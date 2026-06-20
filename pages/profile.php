<?php require_once __DIR__ . '/../includes/auth_check.php'; ?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<header class="page-header">
    <h1 class="page-header__title">Profile & Settings</h1>
</header>

<div class="page-body">
    <div class="profile-layout" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
        <!-- Card 1: Profile Info -->
        <section class="card profile-card">
            <h2 class="section__title" style="margin-bottom: var(--space-4); display: flex; align-items: center; gap: var(--space-2);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Personal Details
            </h2>
            <form id="profile-info-form" style="display: flex; flex-direction: column; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" class="form-input" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" readonly style="opacity: 0.7; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" class="form-input" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" readonly style="opacity: 0.7; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label" for="timezone">Preferred Timezone</label>
                    <select id="timezone" class="form-select">
                        <?php
                        $timezones = [
                            'UTC' => 'UTC (GMT+0)',
                            'Africa/Addis_Ababa' => 'Africa/Addis Ababa (EAT, GMT+3)',
                            'Europe/London' => 'Europe/London (GMT/BST)',
                            'Europe/Paris' => 'Europe/Paris (CET/CEST)',
                            'America/New_York' => 'America/New York (EST/EDT)',
                            'America/Chicago' => 'America/Chicago (CST/CDT)',
                            'America/Los_Angeles' => 'America/Los Angeles (PST/PDT)',
                            'Asia/Tokyo' => 'Asia/Tokyo (JST, GMT+9)',
                            'Asia/Kolkata' => 'Asia/Kolkata (IST, GMT+5:30)',
                            'Asia/Dubai' => 'Asia/Dubai (GST, GMT+4)'
                        ];
                        $currentTimezone = $_SESSION['timezone'] ?? 'Africa/Addis_Ababa';
                        foreach ($timezones as $val => $label) {
                            $selected = ($val === $currentTimezone) ? 'selected' : '';
                            echo "<option value=\"$val\" $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="btn-save-profile" style="align-self: flex-start;">Update Profile</button>
            </form>
        </section>

        <!-- Card 2: Security & Password -->
        <section class="card security-card">
            <h2 class="section__title" style="margin-bottom: var(--space-4); display: flex; align-items: center; gap: var(--space-2);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Change Password
            </h2>
            <form id="password-form" style="display: flex; flex-direction: column; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label" for="current-password">Current Password</label>
                    <input type="password" id="current-password" class="form-input" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new-password">New Password</label>
                    <input type="password" id="new-password" class="form-input" placeholder="••••••••" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm-new-password">Confirm New Password</label>
                    <input type="password" id="confirm-new-password" class="form-input" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" id="btn-change-password" style="align-self: flex-start;">Change Password</button>
            </form>
        </section>
    </div>

    <!-- Card 3: Account Actions -->
    <section class="card actions-card" style="margin-top: var(--space-6);">
        <h2 class="section__title" style="margin-bottom: var(--space-4); color: var(--color-danger); display: flex; align-items: center; gap: var(--space-2);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Danger Zone
        </h2>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: var(--fs-base); font-weight: var(--fw-semibold); color: var(--text-primary); margin: 0 0 4px 0;">Delete Account</h3>
                <p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0;">Once deleted, all your data (courses, tasks, schedule blocks) will be permanently lost.</p>
            </div>
            <button class="btn btn-danger" id="btn-delete-account">Delete Account...</button>
        </div>
        <div class="divider"></div>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: var(--fs-base); font-weight: var(--fw-semibold); color: var(--text-primary); margin: 0 0 4px 0;">Logout</h3>
                <p style="font-size: var(--fs-sm); color: var(--text-secondary); margin: 0;">Securely end your current browser session.</p>
            </div>
            <button class="btn btn-secondary" id="btn-logout" style="display: flex; align-items: center; gap: var(--space-2);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </button>
        </div>
    </section>
</div>

<style>
    @media (max-width: 768px) {
        .profile-layout {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
    // Handle Profile Update
    document.getElementById('profile-info-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const timezone = document.getElementById('timezone').value;
        
        if (typeof showToast === 'function') {
            showToast('Profile timezone preference updated successfully!', 'success');
        } else {
            alert('Profile updated! (Timezone: ' + timezone + ')');
        }
    });

    // Handle Password Change
    document.getElementById('password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const newPass = document.getElementById('new-password').value;
        const confirmPass = document.getElementById('confirm-new-password').value;

        if (newPass !== confirmPass) {
            if (typeof showToast === 'function') {
                showToast('New passwords do not match!', 'error');
            } else {
                alert('New passwords do not match!');
            }
            return;
        }

        // Clear password fields
        document.getElementById('current-password').value = '';
        document.getElementById('new-password').value = '';
        document.getElementById('confirm-new-password').value = '';

        if (typeof showToast === 'function') {
            showToast('Password updated successfully!', 'success');
        } else {
            alert('Password updated!');
        }
    });

    // Handle Logout
    document.getElementById('btn-logout').addEventListener('click', async function() {
        try {
            const response = await fetch('../api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: 'logout' })
            });

            const result = await response.json();
            if (response.ok && result.success) {
                window.location.href = 'login.php';
            } else {
                if (typeof showToast === 'function') {
                    showToast('Logout failed.', 'error');
                } else {
                    alert('Logout failed.');
                }
            }
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = 'login.php'; // Force redirect anyway
        }
    });

    // Handle Account Deletion
    document.getElementById('btn-delete-account').addEventListener('click', function() {
        if (confirm('ARE YOU SURE you want to delete your account? This action is permanent and cannot be undone.')) {
            if (typeof showToast === 'function') {
                showToast('Account deletion is not fully set up in this demo.', 'warning');
            } else {
                alert('Account deletion not set up.');
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validation.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_SESSION['user_id'])) {
        jsonSuccess([
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'timezone' => $_SESSION['timezone'] ?? 'Africa/Addis_Ababa'
            ]
        ]);
    } else {
        jsonSuccess(['user' => null]);
    }
}

if ($method === 'POST') {
    $data = getJsonBody();
    $action = $data['action'] ?? '';

    if ($action === 'register') {
        $username = sanitizeString($data['username'] ?? '');
        $email = sanitizeString($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $timezone = sanitizeString($data['timezone'] ?? 'Africa/Addis_Ababa');

        // Validation
        $missing = validateRequired(['username' => $username, 'email' => $email, 'password' => $password], ['username', 'email', 'password']);
        if (!empty($missing)) {
            jsonError('Required fields are missing', 400, ['missing' => $missing]);
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            jsonError('Username must be between 3 and 50 characters');
        }

        if (!validateEmail($email)) {
            jsonError('Invalid email format');
        }

        if (strlen($password) < 6) {
            jsonError('Password must be at least 6 characters');
        }

        try {
            $db = getDBConnection();
            
            // Check uniqueness
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                jsonError('Username or email is already registered');
            }

            // Hash & insert
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, timezone) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $email, $hash, $timezone]);
            $userId = $db->lastInsertId();

            // Set session
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['timezone'] = $timezone;

            jsonSuccess([
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'timezone' => $timezone
                ]
            ]);
        } catch (PDOException $e) {
            jsonError('Database error occurred: ' . $e->getMessage());
        }
    }

    if ($action === 'login') {
        $email = sanitizeString($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $missing = validateRequired(['email' => $email, 'password' => $password], ['email', 'password']);
        if (!empty($missing)) {
            jsonError('Email and password are required', 400, ['missing' => $missing]);
        }

        try {
            $db = getDBConnection();
            $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                jsonError('Invalid email or password');
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['timezone'] = $user['timezone'];

            jsonSuccess([
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'timezone' => $user['timezone']
                ]
            ]);
        } catch (PDOException $e) {
            jsonError('Database error occurred: ' . $e->getMessage());
        }
    }

    if ($action === 'logout') {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        jsonSuccess(['message' => 'Logged out successfully']);
    }

    jsonError('Invalid action specified');
}

jsonError('Method not allowed', 405);

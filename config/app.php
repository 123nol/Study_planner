<?php
/* ============================================
   APP CONFIGURATION
   ============================================ */

// Timezone
date_default_timezone_set('Africa/Addis_Ababa');

// App info
define('APP_NAME', 'Smart Study Planner');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost:8000');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('ASSETS_PATH', BASE_PATH . '/assets');
define('PAGES_PATH', BASE_PATH . '/pages');
define('INCLUDES_PATH', BASE_PATH . '/includes');

// Session config
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

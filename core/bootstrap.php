<?php

/**
 * Bootstrap
 * 
 * Loads environment, starts session, connects DB, and auto-includes
 * all module controllers and models.
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Composer autoloader (loads helpers.php via "files" autoload)
require BASE_PATH . '/vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting based on debug mode
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load core files
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/router.php';
require BASE_PATH . '/core/middleware.php';

// Auto-include all module controllers and models
$modulesDir = BASE_PATH . '/modules';
if (is_dir($modulesDir)) {
    foreach (scandir($modulesDir) as $module) {
        if ($module === '.' || $module === '..') continue;

        $modulePath = $modulesDir . '/' . $module;
        if (!is_dir($modulePath)) continue;

        // Load models first (controllers may depend on them)
        $modelsFile = $modulePath . '/models.php';
        if (file_exists($modelsFile)) {
            require $modelsFile;
        }

        // Load controllers
        $controllersFile = $modulePath . '/controllers.php';
        if (file_exists($controllersFile)) {
            require $controllersFile;
        }
    }
}

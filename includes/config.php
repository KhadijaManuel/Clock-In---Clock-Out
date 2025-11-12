<?php
// ========================
// CONFIGURATION FILE
// ========================

// ------------------------
// Load .env File Function
// ------------------------
function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Remove surrounding quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        putenv("$name=$value");
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
    }
}

// ------------------------
// Load .env file
// ------------------------
$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
loadEnvFile($envPath);

// ------------------------
// Database Configuration
// ------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'tracker_db');

// ------------------------
// Application Settings
// ------------------------
define('APP_NAME', getenv('APP_NAME') ?: 'Clock It - Attendance Tracker');
define('BASE_URL', getenv('BASE_URL') ?: '/attendance_dashboard/public');

// ------------------------
// Timezone
// ------------------------
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Johannesburg');

// ------------------------
// Error Reporting
// ------------------------
$appDebug = getenv('APP_DEBUG') ?: '1';
$appDebugBool = filter_var($appDebug, FILTER_VALIDATE_BOOLEAN);

if ($appDebugBool) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ------------------------
// Session Start (Safe)
// ------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // safe because no output before this point
}

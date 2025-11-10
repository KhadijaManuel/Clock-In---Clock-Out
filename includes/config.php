<?php
/**
 * Configuration File
 * Loads environment variables and sets up database/app settings.
 */

/**
 * Simple .env loader
 */
function loadEnvFile(string $path): void
{
    if (!is_readable($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove quotes
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Load .env file from project root
$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
loadEnvFile($envPath);

// Database Settings
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'Qwert90!.');
define('DB_NAME', getenv('DB_NAME') ?: 'tracker_db');

// App Settings
define('APP_NAME', getenv('APP_NAME') ?: 'Clock It - Attendance Tracker');
define('BASE_URL', getenv('BASE_URL') ?: '/Weekly-Report-Backend/app/views');

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Johannesburg');

// Debug Mode
$debug = filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN);
error_reporting($debug ? E_ALL : 0);
ini_set('display_errors', $debug ? '1' : '0');

// Session Handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

<?php
// =============================================
// ENVIRONMENT VALIDATION & SECURITY CHECKS
// =============================================

// 1. Verify all required environment variables exist
$requiredEnvVars = [
    'TELEGRAM_SECRET',
    'TELEGRAM_BOT_TOKEN',
    'BINANCE_REF_LINK',
    'ADMIN_TELEGRAM_ID'
];

foreach ($requiredEnvVars as $var) {
    if (!getenv($var)) {
        error_log("Missing required environment variable: $var");
        header('HTTP/1.1 500 Internal Server Error');
        die("Server configuration error");
    }
}

// 2. Validate Telegram Secret Token
$secretToken = getenv('TELEGRAM_SECRET');
if (empty($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) || 
    !hash_equals($secretToken, $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'])) {
    error_log("Invalid secret token attempt from IP: ".($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    header('HTTP/1.1 403 Forbidden');
    die("Unauthorized");
}

// 3. Validate other environment variables
$botToken = getenv('TELEGRAM_BOT_TOKEN');
if (!preg_match('/^\d+:[a-zA-Z0-9_-]+$/', $botToken)) {
    error_log("Invalid Telegram bot token format");
    die("Invalid server configuration");
}

$adminId = getenv('ADMIN_TELEGRAM_ID');
if (!is_numeric($adminId)) {
    error_log("Invalid ADMIN_TELEGRAM_ID format");
    die("Invalid server configuration");
}

// =============================================
// HEALTH CHECK ENDPOINT
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/health') {
    header('Content-Type: text/plain');
    echo 'OK';
    exit;
}

// =============================================
// APPLICATION SETUP
// =============================================
require __DIR__ . '/vendor/autoload.php';

// Initialize data files with validation
$usersFile = __DIR__ . '/users.json';
$txFile = __DIR__ . '/transactions.json';

try {
    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid users.json data");
    }
    
    $transactions = file_exists($txFile) ? json_decode(file_get_contents($txFile), true) : [];
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid transactions.json data");
    }
} catch (Exception $e) {
    error_log("Data file error: " . $e->getMessage());
    die("System maintenance in progress");
}

// =============================================
// BOT FUNCTIONALITY (remainder of your code)
// =============================================
// [Keep all your existing bot logic unchanged]
// [Include all your functions: trackReferral, handleWithdrawal, reply, saveData]
// =============================================

// Secure file writing
function saveData($file, $data) {
    if (!file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        error_log("Failed to write data to $file");
    }
    chmod($file, 0644); // Ensure proper permissions
}

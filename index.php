<?php
// =============================================
// ENVIRONMENT VALIDATION & SECURITY CHECKS
// =============================================

// Handle Render.com proxy IP
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_SERVER['REMOTE_ADDR'] = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

// Skip validation for Render health checks and local development
$isRenderHealthCheck = ($_SERVER['HTTP_USER_AGENT'] ?? '') === 'Go-http-client/2.0';
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['::1', '127.0.0.1']);

if (!$isRenderHealthCheck && !$isLocalhost) {
    // Verify all required environment variables exist
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

    // Validate Telegram Secret Token
    $secretToken = getenv('TELEGRAM_SECRET');
    if (empty($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) || 
        !hash_equals($secretToken, $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'])) {
        error_log("Invalid secret token attempt from IP: ".($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        header('HTTP/1.1 403 Forbidden');
        die("Unauthorized");
    }
}

// Validate other environment variables (always check)
$botToken = getenv('TELEGRAM_BOT_TOKEN');
if ($botToken && !preg_match('/^\d+:[a-zA-Z0-9_-]+$/', $botToken)) {
    error_log("Invalid Telegram bot token format");
    die("Invalid server configuration");
}

$adminId = getenv('ADMIN_TELEGRAM_ID');
if ($adminId && !is_numeric($adminId)) {
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

// Initialize data directory
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Initialize data files
$usersFile = __DIR__ . '/data/users.json';
$txFile = __DIR__ . '/data/transactions.json';

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
// SECURE FILE WRITING FUNCTION
// =============================================
function saveData($file, $data) {
    $tmpFile = $file . '.tmp';
    $result = file_put_contents(
        $tmpFile, 
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
    
    if ($result === false) {
        error_log("Failed to write data to $file");
        return false;
    }
    
    // Atomic rename for data integrity
    if (!rename($tmpFile, $file)) {
        error_log("Failed to rename temp file for $file");
        return false;
    }
    
    chmod($file, 0640);
    return true;
}

// =============================================
// TELEGRAM BOT FUNCTIONALITY
// =============================================
function trackReferral($userId, $referralId, &$users) {
    if ($userId === $referralId) return false;
    
    if (!isset($users[$referralId]['referrals'])) {
        $users[$referralId]['referrals'] = [];
    }
    
    if (!in_array($userId, $users[$referralId]['referrals'])) {
        $users[$referralId]['referrals'][] = $userId;
        return true;
    }
    
    return false;
}

function handleWithdrawal($userId, $amount, &$users, &$transactions) {
    if (!isset($users[$userId]['balance']) || $users[$userId]['balance'] < $amount) {
        return false;
    }
    
    $txId = uniqid('tx_', true);
    $transactions[$txId] = [
        'user_id' => $userId,
        'amount' => $amount,
        'status' => 'pending',
        'timestamp' => time()
    ];
    
    $users[$userId]['balance'] -= $amount;
    return $txId;
}

function reply($message, $keyboard = null) {
    $response = [
        'method' => 'sendMessage',
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    if ($keyboard) {
        $response['reply_markup'] = [
            'inline_keyboard' => $keyboard
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// =============================================
// MAIN REQUEST HANDLER
// =============================================
try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $userId = $input['message']['from']['id'] ?? $input['callback_query']['from']['id'] ?? null;
    if (!$userId) {
        throw new Exception('No user ID found');
    }

    // Initialize user if not exists
    if (!isset($users[$userId])) {
        $users[$userId] = [
            'balance' => 0,
            'referrals' => [],
            'joined_at' => time()
        ];
        
        // Check for referral
        $referralId = $input['message']['text'] ?? null;
        if ($referralId && isset($users[$referralId])) {
            if (trackReferral($userId, $referralId, $users)) {
                $users[$referralId]['balance'] += 10; // Referral bonus
                $users[$userId]['balance'] += 5; // Joining bonus
            }
        }
    }

    // Handle commands
    $command = $input['message']['text'] ?? $input['callback_query']['data'] ?? null;
    switch ($command) {
        case '/start':
            $refLink = getenv('BINANCE_REF_LINK');
            reply("Welcome! Use our Binance referral: $refLink");
            break;
            
        case '/balance':
            $balance = $users[$userId]['balance'] ?? 0;
            reply("Your balance: $balance USDT");
            break;
            
        case '/withdraw':
            $amount = 50; // Minimum withdrawal amount
            if (handleWithdrawal($userId, $amount, $users, $transactions)) {
                reply("Withdrawal request submitted!");
            } else {
                reply("Insufficient balance for withdrawal");
            }
            break;
            
        default:
            reply("Unknown command");
    }

    // Save all changes
    saveData($usersFile, $users);
    saveData($txFile, $transactions);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die("An error occurred");
}

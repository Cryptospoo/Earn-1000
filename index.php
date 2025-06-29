<?php
// =============================================
// INITIAL CONFIGURATION
// =============================================

// Error reporting (disable in production)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('UTC');

// =============================================
// ENVIRONMENT VALIDATION
// =============================================

// Handle Render.com proxy IP
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_SERVER['REMOTE_ADDR'] = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

// Identify special requests
$isRenderHealthCheck = ($_SERVER['HTTP_USER_AGENT'] ?? '') === 'Go-http-client/2.0';
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['::1', '127.0.0.1']);

// =============================================
// HEALTH CHECK HANDLER (FOR RENDER.COM)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/plain');
    
    if ($_SERVER['REQUEST_URI'] === '/health' || $isRenderHealthCheck) {
        echo 'OK';
        exit;
    }
    
    // Simple homepage for root GET requests
    echo "Telegram Bot is running";
    exit;
}

// =============================================
// SECURITY VALIDATION (FOR TELEGRAM WEBHOOKS)
// =============================================
if (!$isRenderHealthCheck && !$isLocalhost) {
    // Required environment variables
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

    // Validate Telegram secret token
    $secretToken = getenv('TELEGRAM_SECRET');
    if (empty($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) || 
        !hash_equals($secretToken, $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'])) {
        error_log("Invalid secret token attempt from IP: ".($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        header('HTTP/1.1 403 Forbidden');
        die("Unauthorized");
    }
}

// =============================================
// DATA STORAGE SETUP
// =============================================

// Create data directory if needed
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// File paths
$usersFile = __DIR__ . '/data/users.json';
$txFile = __DIR__ . '/data/transactions.json';

// Initialize data with validation
try {
    $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("Invalid users.json");
    
    $transactions = file_exists($txFile) ? json_decode(file_get_contents($txFile), true) : [];
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("Invalid transactions.json");
} catch (Exception $e) {
    error_log("Data error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die("System maintenance in progress");
}

// =============================================
// CORE FUNCTIONS
// =============================================

/**
 * Securely save data to JSON file
 */
function saveData($file, $data) {
    $tmpFile = $file . '.tmp';
    
    if (file_put_contents($tmpFile, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) === false) {
        error_log("Failed to write to $tmpFile");
        return false;
    }
    
    if (!rename($tmpFile, $file)) {
        error_log("Failed to rename $tmpFile to $file");
        return false;
    }
    
    chmod($file, 0640);
    return true;
}

/**
 * Send response to Telegram
 */
function reply($text, $keyboard = null) {
    $response = [
        'method' => 'sendMessage',
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($keyboard) {
        $response['reply_markup'] = ['inline_keyboard' => $keyboard];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * Track referral relationships
 */
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

/**
 * Process withdrawal request
 */
function handleWithdrawal($userId, $amount, &$users, &$transactions) {
    if (!isset($users[$userId]['balance']) || $users[$userId]['balance'] < $amount) {
        return false;
    }
    
    $txId = 'tx_' . bin2hex(random_bytes(8));
    $transactions[$txId] = [
        'user_id' => $userId,
        'amount' => $amount,
        'status' => 'pending',
        'timestamp' => time()
    ];
    
    $users[$userId]['balance'] -= $amount;
    return $txId;
}

// =============================================
// REQUEST PROCESSING
// =============================================

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid request data');
    }

    // Get user ID
    $userId = $input['message']['from']['id'] ?? $input['callback_query']['from']['id'] ?? null;
    if (!$userId) {
        throw new Exception('No user ID found');
    }

    // Initialize new user
    if (!isset($users[$userId])) {
        $users[$userId] = [
            'balance' => 0,
            'referrals' => [],
            'joined_at' => time()
        ];
        
        // Check for referral
        $text = $input['message']['text'] ?? '';
        if (preg_match('/^\/start (\d+)$/', $text, $matches)) {
            $referralId = $matches[1];
            if (trackReferral($userId, $referralId, $users)) {
                $users[$referralId]['balance'] += 10; // Referral bonus
                $users[$userId]['balance'] += 5; // Signup bonus
            }
        }
    }

    // Process commands
    $command = $input['message']['text'] ?? $input['callback_query']['data'] ?? '';
    
    switch (explode(' ', $command)[0]) {
        case '/start':
            $refLink = getenv('BINANCE_REF_LINK');
            reply("🚀 Welcome to EarnBot!\n\n"
                . "Your referral link: https://t.me/".getenv('TELEGRAM_BOT_NAME')."?start=$userId\n\n"
                . "Binance signup: $refLink");
            break;
            
        case '/balance':
            $balance = $users[$userId]['balance'];
            reply("💰 Your balance: $balance USDT\n"
                . "Referrals: " . count($users[$userId]['referrals'] ?? []));
            break;
            
        case '/withdraw':
            $minAmount = 50;
            if (handleWithdrawal($userId, $minAmount, $users, $transactions)) {
                reply("✅ Withdrawal request submitted!\n"
                    . "Amount: $minAmount USDT\n"
                    . "Admin will process it shortly.");
            } else {
                reply("❌ You need at least $minAmount USDT to withdraw");
            }
            break;
            
        case '/help':
            reply("📚 Available commands:\n"
                . "/start - Get started\n"
                . "/balance - Check your balance\n"
                . "/withdraw - Withdraw funds\n"
                . "/help - This message");
            break;
            
        default:
            reply("🤖 I don't understand that command. Try /help");
    }

    // Save all data
    if (!saveData($usersFile, $users) || !saveData($txFile, $transactions)) {
        throw new Exception('Failed to save data');
    }

} catch (Exception $e) {
    error_log("Processing error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die("An error occurred");
}

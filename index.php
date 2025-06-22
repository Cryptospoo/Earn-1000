<?php
require __DIR__ . '/vendor/autoload.php';

// Config
$botToken = getenv('TELEGRAM_BOT_TOKEN');
$binanceRef = getenv('BINANCE_REF_LINK');
$adminId = getenv('ADMIN_TELEGRAM_ID'); // For payout approvals

// Init files
$usersFile = __DIR__ . '/users.json';
$txFile = __DIR__ . '/transactions.json';

// Load data
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
$transactions = file_exists($txFile) ? json_decode(file_get_contents($txFile), true) : [];

// Process update
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) die('Invalid request');

$message = $input['message'] ?? [];
$chatId = $message['chat']['id'] ?? null;
$userId = $message['from']['id'] ?? null;
$text = trim($message['text'] ?? '');

if ($chatId && $userId) {
    // Register new user
    if (!isset($users[$userId])) {
        $users[$userId] = [
            'chat_id' => $chatId,
            'username' => $message['from']['username'] ?? 'anon',
            'referrals' => 0,
            'earnings' => 0,
            'ref_code' => 'CRYPTO' . $userId,
            'paypal' => null,
            'wallet' => null
        ];
        saveData($usersFile, $users);
    }

    // Handle commands
    switch (strtolower($text)) {
        case '/start':
            reply($chatId, "🤑 *Crypto Affiliate Bot*\nEarn \$20-50 per sign-up!\n\n🔗 Your link:\n`https://your-url.com/ref={$users[$userId]['ref_code']}`");
            break;

        case '/stats':
            reply($chatId, "📊 *Your Stats*\nReferrals: {$users[$userId]['referrals']}\nEarnings: \${$users[$userId]['earnings']}");
            break;

        case '/withdraw':
            handleWithdrawal($userId, $chatId);
            break;

        default:
            // Handle referral links
            if (strpos($text, '/ref=') !== false) {
                $refCode = str_replace('/ref=', '', $text);
                trackReferral($refCode, $userId, $chatId);
            }
            // Handle PayPal/crypto setup
            elseif ($users[$userId]['awaiting_paypal'] ?? false) {
                $users[$userId]['paypal'] = filter_var($text, FILTER_VALIDATE_EMAIL) ? $text : null;
                unset($users[$userId]['awaiting_paypal']);
                saveData($usersFile, $users);
                reply($chatId, $users[$userId]['paypal'] ? "✅ PayPal set!" : "❌ Invalid email");
            }
    }
}

// Core functions
function trackReferral($refCode, $newUserId, $chatId) {
    global $users, $usersFile;
    foreach ($users as $id => $user) {
        if ($user['ref_code'] === $refCode && $id != $newUserId) {
            $users[$id]['referrals']++;
            $users[$id]['earnings'] += 20; // $20 per referral
            saveData($usersFile, $users);
            reply($chatId, "🎉 Referral tracked! + \$20");
            return;
        }
    }
    reply($chatId, "❌ Invalid referral code");
}

function handleWithdrawal($userId, $chatId) {
    global $users, $usersFile, $transactions, $txFile, $adminId;
    
    if ($users[$userId]['earnings'] < 50) {
        reply($chatId, "❌ Minimum withdrawal: \$50");
        return;
    }

    if (empty($users[$userId]['paypal'])) {
        reply($chatId, "⚠️ Set your PayPal first with /setpaypal");
        return;
    }

    // Admin approval flow
    $txId = 'TX' . time();
    $transactions[$txId] = [
        'user_id' => $userId,
        'amount' => 50,
        'status' => 'pending',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    saveData($txFile, $transactions);

    // Notify admin
    reply($adminId, "⚠️ Payout Request\nUser: @{$users[$userId]['username']}\nAmount: \$50\nTXID: $txId\n\nApprove with /approve_$txId");

    reply($chatId, "⏳ Payout request sent!\nWe'll process within 24h.");
}

function reply($chatId, $text) {
    global $botToken;
    $url = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ]);
    file_get_contents($url);
}

function saveData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Health check
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/health') {
    echo 'OK';
    exit;
}
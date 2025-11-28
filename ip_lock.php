<?php
// ip_lock.php
// Simple IP-lock login endpoint. Returns JSON responses.
// Make sure this file sits on a PHP-enabled server.
// The script stores account => ip in ip_store.json (in same dir).
// WARNING: This is a minimal server example. For production, use HTTPS, proper user DB, password hashing, rate limit, etc.

header('Content-Type: application/json; charset=utf-8');

// --- CONFIGURE ACCOUNTS ---
// For improved security, store hashed passwords instead of plain text.
// Here we store bcrypt hashes (precomputed) — replace with your own.
$ACCOUNTS = [
    // username => password_hash('pass1', PASSWORD_DEFAULT)
    "user1" => password_hash("pass1", PASSWORD_DEFAULT),
    "user2" => password_hash("pass2", PASSWORD_DEFAULT),
    "user3" => password_hash("pass3", PASSWORD_DEFAULT)
];

// Path to store IP bindings
$IP_STORE = __DIR__ . '/ip_store.json';

// Read POST data (x-www-form-urlencoded)
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// basic validation
if ($username === '' || $password === '') {
    echo json_encode(['status'=>'error','msg'=>'Username and password required']);
    exit;
}

// check account exists
if (!array_key_exists($username, $ACCOUNTS)) {
    echo json_encode(['status'=>'error','msg'=>'Invalid username or password']);
    exit;
}

// verify password
$hash = $ACCOUNTS[$username];
if (!password_verify($password, $hash)) {
    echo json_encode(['status'=>'error','msg'=>'Invalid username or password']);
    exit;
}

// determine client IP (this is basic; in proxied setups you may use HTTP_X_FORWARDED_FOR)
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// load existing ip_store
$ip_data = [];
if (file_exists($IP_STORE)) {
    $raw = file_get_contents($IP_STORE);
    $ip_data = json_decode($raw, true);
    if (!is_array($ip_data)) $ip_data = [];
}

// if user has no binding yet -> bind to current IP
if (!isset($ip_data[$username]) || !$ip_data[$username]) {
    $ip_data[$username] = $client_ip;
    file_put_contents($IP_STORE, json_encode($ip_data, JSON_PRETTY_PRINT));
    echo json_encode(['status'=>'success','msg'=>'Login OK (IP bound)']);
    exit;
}

// if binding exists, check match
if ($ip_data[$username] === $client_ip) {
    echo json_encode(['status'=>'success','msg'=>'Login OK']);
    exit;
} else {
    echo json_encode([
        'status'=>'blocked',
        'msg'=>'ACCESS DENIED. This account is bound to IP: ' . $ip_data[$username] . '. Login from that IP only, or ask admin to reset.'
    ]);
    exit;
}

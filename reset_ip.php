<?php
// reset_ip.php
// POST 'admin_pass' and 'username' to reset the IP binding for that username.
// For demo only: admin password is simple constant. Change in production.

header('Content-Type: application/json; charset=utf-8');

$ADMIN_PASS = 'adminResetPass123'; // change this now!
$IP_STORE = __DIR__ . '/ip_store.json';

$admin = $_POST['admin_pass'] ?? '';
$user = $_POST['username'] ?? '';

if ($admin !== $ADMIN_PASS) {
    echo json_encode(['status'=>'error','msg'=>'Bad admin password']);
    exit;
}
if (!$user) {
    echo json_encode(['status'=>'error','msg'=>'Username required']);
    exit;
}

$ip_data = [];
if (file_exists($IP_STORE)) {
    $raw = file_get_contents($IP_STORE);
    $ip_data = json_decode($raw, true);
    if (!is_array($ip_data)) $ip_data = [];
}

// remove binding
if (isset($ip_data[$user])) {
    unset($ip_data[$user]);
    file_put_contents($IP_STORE, json_encode($ip_data, JSON_PRETTY_PRINT));
    echo json_encode(['status'=>'ok','msg'=>'Binding removed for ' . $user]);
} else {
    echo json_encode(['status'=>'ok','msg'=>'No binding found for ' . $user]);
}

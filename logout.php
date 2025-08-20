<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    logAction("user_logout", "users", $_SESSION['user_id']);
}

$ip_address = getClientIP();
$location = getIPLocation($ip_address);
logAction("user_logout", "users", $_SESSION['user_id'], null, json_encode([
    'ip_address' => $ip_address,
    'location' => $location,
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
], JSON_PRETTY_PRINT));

setcookie('remember_token', '', time() - 3600, '/');


session_unset();
session_destroy();

header("Location: login.php");
exit();
?>

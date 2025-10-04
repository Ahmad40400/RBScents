<?php
session_start();

$timeout = 900;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['session_expired'] = true;
}

$_SESSION['LAST_ACTIVITY'] = time();

function csrf_token() {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function verify_csrf($token) {
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function e($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
  return !empty($_SESSION['user_id']) && !empty($_SESSION['LAST_ACTIVITY']);
}

function is_admin() {
  return !empty($_SESSION['is_admin']);
}
?>
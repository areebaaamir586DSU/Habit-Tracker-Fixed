<?php
// Session persistence + hardening (must run before session_start)
$sessionLifetime = 30 * 24 * 60 * 60; // 30 days: keep user logged in across browser restarts
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$cookiePath = ($basePath === '') ? '/' : $basePath . '/';
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
ini_set('session.cookie_lifetime', (string)$sessionLifetime);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => $cookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

// Security session hardening
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
if (empty($_SESSION['fingerprint'])) {
    $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
}

define('DB_PATH', __DIR__ . '/data/habit_tracker.db');
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));
define('RATE_LIMIT_FILE', __DIR__ . '/data/rate_limits.json');
define('RATE_LIMIT_MAX', 10); // max attempts
define('RATE_LIMIT_WINDOW', 900); // 15 minutes

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Security headers
function sendSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data:; connect-src \'self\'');
}
sendSecurityHeaders();

// CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

function verifyCsrf() {
    $token = $_POST['csrf_token'] ?? $_GET['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonError('Invalid security token. Please refresh and try again.', 403);
    }
}

// Rate limiting
function checkRateLimit($key) {
    $ip = getClientIp();
    $rateFile = RATE_LIMIT_FILE;
    $limits = file_exists($rateFile) ? json_decode(file_get_contents($rateFile), true) : [];
    if (!is_array($limits)) $limits = [];
    $now = time();
    $rk = $key . ':' . $ip;

    if (isset($limits[$rk])) {
        $attempts = array_filter($limits[$rk], fn($t) => $t > $now - RATE_LIMIT_WINDOW);
        $limits[$rk] = array_values($attempts);
        if (count($attempts) >= RATE_LIMIT_MAX) {
            $retryAfter = min($attempts) + RATE_LIMIT_WINDOW - $now;
            header('Retry-After: ' . $retryAfter);
            jsonError('Too many attempts. Please try again in ' . ceil($retryAfter / 60) . ' minutes.', 429);
        }
    } else {
        $limits[$rk] = [];
    }

    $limits[$rk][] = $now;

    // Keep the rate-limit file bounded: prune stale buckets when it grows large
    if (count($limits) > 500) {
        foreach ($limits as $r => &$arr) {
            $arr = is_array($arr) ? array_values(array_filter($arr, fn($t) => $t > $now - RATE_LIMIT_WINDOW)) : [];
            if (empty($arr)) unset($limits[$r]);
        }
        unset($arr);
    }

    file_put_contents($rateFile, json_encode($limits), LOCK_EX);
}

function getClientIp() {
    // Handle reverse proxy / Cloudflare
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if (!empty($forwarded)) {
        $ips = explode(',', $forwarded);
        return trim($ips[0]);
    }
    $real = $_SERVER['HTTP_X_REAL_IP'] ?? '';
    if (!empty($real)) return $real;
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    // Fingerprint check - use only User-Agent (IP changes behind proxies)
    $fp = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!empty($_SESSION['fingerprint']) && $fp !== $_SESSION['fingerprint']) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function getDb() {
    $db = new SQLite3(DB_PATH);
    $db->enableExceptions(true);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');
    return $db;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonError($message, $code = 400) {
    jsonResponse(['error' => $message], $code);
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

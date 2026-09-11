<?php
/**
 * Router for the PHP built-in dev server (php -S localhost:8080 router.php).
 * The .htaccess rules only apply under Apache; this blocks /data/ and any
 * hidden files (e.g. .htaccess, .git) from being served by php -S.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($uri === false) {
    http_response_code(400);
    echo '400 Bad Request';
    exit;
}

if (preg_match('#(?:^|/)\.|^/data(/|$)#i', $uri)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo '403 Forbidden';
    exit;
}

// Let the built-in server handle everything else normally.
return false;
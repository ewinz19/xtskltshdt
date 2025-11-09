<?php
// inews.php - License proxy skeleton
// PURPOSE: menerima challenge dari Kodi (inputstream.adaptive), forward ke upstream license server,
//          dan kembalikan response license ke Kodi.
//
// SECURITY: Protect endpoint (IP whitelist / basic auth / token). Use HTTPS.

ini_set('display_errors', 0);

// ---------- CONFIG ----------
define('SECRET_TOKEN', 'replace_with_strong_secret'); // token untuk mengakses proxy (optional)
define('UPSTREAM_LICENSE_URL', 'https://lic.example.com/getlicense'); // license server resmi
// headers to add when contacting upstream (array of 'Header: value')
$UPSTREAM_HEADERS = [
    'x-auth-token: REPLACETOKEN',     // contoh header otorisasi upstream
    'User-Agent: Kodi/19.0',          // jika perlu
    // tambahkan header lain yg dibutuhkan provider
];
// optional: restrict access by IP
$ALLOWED_IPS = []; // e.g. ['1.2.3.4'] or empty = allow all (not recommended)
// --------------------------------

// simple access control (prefer more robust in production)
if (!empty($ALLOWED_IPS)) {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, $ALLOWED_IPS)) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

// Optional: token auth via query ?key=SECRET or header X-Proxy-Token
$token_ok = false;
if (isset($_GET['key']) && hash_equals(SECRET_TOKEN, $_GET['key'])) $token_ok = true;
$headers = getallheaders();
if (!$token_ok && isset($headers['X-Proxy-Token']) && hash_equals(SECRET_TOKEN, $headers['X-Proxy-Token'])) $token_ok = true;
if (!empty(SECRET_TOKEN) && !$token_ok) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// Read raw POST body (binary challenge)
$body = file_get_contents('php://input');

// Build cURL request to upstream license server
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, UPSTREAM_LICENSE_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $UPSTREAM_HEADERS);
// pass-through client headers that might be relevant (optional)
if (isset($headers['Content-Type'])) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($UPSTREAM_HEADERS, ['Content-Type: '.$headers['Content-Type']]));
}
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

// execute
$response = curl_exec($ch);
$curl_errno = curl_errno($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($curl_errno || $response === false) {
    http_response_code(502);
    echo "Bad Gateway";
    exit;
}

// Return upstream response (binary). Set appropriate content-type if known, otherwise default.
if ($content_type) header('Content-Type: ' . $content_type);
else header('Content-Type: application/octet-stream');

http_response_code($http_code ?: 200);
echo $response;
exit;

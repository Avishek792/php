<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function slowAES_decrypt($cipher_hex, $key_hex, $iv_hex) {
    $key = hex2bin($key_hex);
    $iv  = hex2bin($iv_hex);
    $cipher = hex2bin($cipher_hex);

    $plain = openssl_decrypt(
        $cipher,
        "AES-128-CBC",
        $key,
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $iv
    );

    $pad = ord($plain[strlen($plain)-1]);
    if ($pad > 0 && $pad <= 16) $plain = substr($plain, 0, -$pad);

    return bin2hex($plain);
}

// ----------------------
// 1) Get challenge page
// ----------------------
$ch = curl_init("https://bpanel.42web.io/api/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$html = curl_exec($ch);
curl_close($ch);

// Extract dynamic ciphertext C
preg_match('/toNumbers\("([0-9a-fA-F]+)"\);document/', $html, $match);
$cipher_hex = $match[1];

// Constant A (key)
$key_hex = "f655ba9d09a112d4968c63579db590b4";

// Constant B (IV)
$iv_hex  = "98344c2eee86c3994890592585b49f80";

// Generate cookie
$cookie_val = slowAES_decrypt($cipher_hex, $key_hex, $iv_hex);

// ----------------------
// 2) Forward login POST
// ----------------------
$payload = file_get_contents("php://input");

$ch = curl_init("https://bpanel.42web.io/api/login.php");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Cookie: __test=$cookie_val",
    "User-Agent: Mozilla/5.0"
]);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $response;
?>

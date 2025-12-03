<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// AES 128 CBC decrypt
function decrypt_cookie($c, $a, $b) {
    $key = hex2bin($a);
    $iv  = hex2bin($b);
    $cipher = hex2bin($c);

    $plain = openssl_decrypt(
        $cipher,
        "AES-128-CBC",
        $key,
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $iv
    );

    // remove padding
    $pad = ord(substr($plain, -1));
    if ($pad > 0 && $pad <= 16) {
        $plain = substr($plain, 0, -$pad);
    }

    return bin2hex($plain);
}

// 1. GET HTML challenge
$ch = curl_init("https://bpanel.42web.io/api/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

// 2. extract the "c" value (it changes every request)
preg_match('/toNumbers\("([0-9a-fA-F]+)"\)\)/', $html, $match);
$c_value = $match[1];

// constants from the JS
$a = "f655ba9d09a112d4968c63579db590b4";
$b = "98344c2eee86c3994890592585b49f80";

// 3. decrypt cookie
$cookie_val = decrypt_cookie($c_value, $a, $b);

// 4. send REAL login request
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
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($status);
echo $response;
?>

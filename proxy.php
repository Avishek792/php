<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// AES decrypt function
function decrypt_cookie($cipherHex, $keyHex, $ivHex) 
{
    $key = hex2bin($keyHex);
    $iv = hex2bin($ivHex);
    $cipher = hex2bin($cipherHex);

    $plain = opensssl_decrypt(
        $cipher,
        "AES-128-CBC",
        $key,
        OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,
        $iv
    );

    if ($plain === false) {
        return null;
    }

    // Remove PKCS7 padding
    $pad = ord(substr($plain, -1));
    if ($pad > 0 && $pad <= 16) {
        $plain = substr($plain, 0, -$pad);
    }

    return bin2hex($plain);
}

// 1. GET anti-bot HTML challenge page
$ch = curl_init("https://bpanel.42web.io/api/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
$html = curl_exec($ch);
curl_close($ch);

if (!$html) {
    echo json_encode(["error" => "Failed to load challenge page"]);
    exit();
}

// 2. Extract dynamic AES ciphertext "c"
if (!preg_match('/toNumbers\("([0-9a-fA-F]+)"\)\)/', $html, $match)) {
    echo json_encode(["error" => "Failed to extract challenge token"]);
    exit();
}

$c = $match[1];

// 3. Static AES key + iv from original JS
$key = "f655ba9d09a112d4968c63579db590b4";
$iv  = "98344c2eee86c3994890592585b49f80";

// 4. Decrypt cookie
$cookieValue = decrypt_cookie($c, $key, $iv);

if (!$cookieValue) {
    echo json_encode(["error" => "Failed to decrypt challenge cookie"]);
    exit();
}

// 5. Read incoming JSON POST
$body = file_get_contents("php://input");

// 6. Send actual login request with decrypted cookie
$ch = curl_init("https://bpanel.42web.io/api/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Cookie: __test=$cookieValue",
    "User-Agent: Mozilla/5.0"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response) {
    echo json_encode(["error" => "Request failed"]);
    exit();
}

// 7. Return REAL API response
http_response_code($httpCode);
echo $response;
?>

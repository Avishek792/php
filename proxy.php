<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ---------- Generate "__test" cookie ----------
function generate_test_cookie() {
    $key = hex2bin("f655ba9d09a112d4968c63579db590b4");
    $iv  = hex2bin("98344c2eee86c3994890592585b49f80");
    $ciphertext = hex2bin("bdeb8b5d16b0836c7b239804572d8cca");

    $decrypted = openssl_decrypt(
        $ciphertext,
        "AES-128-CBC",
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return bin2hex($decrypted);
}

$cookie_val = generate_test_cookie();

// ---------- Forward request ----------
$ch = curl_init("https://bpanel.42web.io/api/login.php");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Cookie: __test=$cookie_val"
]);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $response;
?>

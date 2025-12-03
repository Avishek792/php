<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ---------- slowAES.decrypt equivalent ----------
function slowAES_decrypt($c_hex, $key_hex, $iv_hex) {
    $key = hex2bin($key_hex);
    $iv  = hex2bin($iv_hex);
    $ciphertext = hex2bin($c_hex);

    $decrypted = openssl_decrypt(
        $ciphertext,
        "AES-128-CBC",
        $key,
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $iv
    );

    // Remove PKCS#7 padding
    $pad = ord($decrypted[strlen($decrypted)-1]);
    if ($pad > 0 && $pad <= 16) {
        $decrypted = substr($decrypted, 0, -$pad);
    }

    return bin2hex($decrypted); // same as JS toHex
}

// ---------- generate __test cookie ----------
$cookie_val = slowAES_decrypt(
    "bdeb8b5d16b0836c7b239804572d8cca",
    "f655ba9d09a112d4968c63579db590b4",
    "98344c2eee86c3994890592585b49f80"
);

// ---------- forward request ----------
$ch = curl_init("https://bpanel.42web.io/api/login.php");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Cookie: __test=$cookie_val",
    "User-Agent: Mozilla/5.0" // match normal browser
]);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $response;
?>

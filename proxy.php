<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

$ch = curl_init("https://bpanel.42web.io/api/login.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
curl_close($ch);

echo $response;

?>

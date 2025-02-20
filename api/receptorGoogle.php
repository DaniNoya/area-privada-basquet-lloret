<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/dbConnection.php';
include_once __DIR__ . '/constants.php';
$con = returnConection();

$path = isset($_GET['state']) ? $_GET['state'] : "/";
$error = isset($_GET['error']) ? $_GET['error'] : "";
$code = isset($_GET['code']) ? $_GET['code'] : "";
$scope = isset($_GET['scope']) ? $_GET['scope'] : "";

if ($error == ""){
  $client = new Google_Client(array("client_id"=>GOOGLE_OAUTH2_CLIENT_ID,"client_secret"=>GOOGLE_OAUTH2_CLIENT_SECRET, "developer_key"=>GOOGLE_API_KEY));
  $client->setScopes('https://www.googleapis.com/auth/youtube');
  $redirect = filter_var('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],FILTER_SANITIZE_URL);
  $client->setRedirectUri($redirect);
  $client->setAccessType("offline");
  $client->setPrompt("consent");
  $token = $client->fetchAccessTokenWithAuthCode($code);
  $client->setAccessToken($token);
  $sql = "UPDATE club SET google_token = '" . json_encode($token) . "'";
  if(!mysqli_query($con,$sql)){
    error_log(mysqli_error($con));
  }
  returnToWeb($path);
} else{
  returnToWeb($path);
}

function returnToWeb($path = "/"){
  header("Location: " . str_replace('/api/',$path,BASE_API_URL));
}
?>

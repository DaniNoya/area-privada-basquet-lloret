<?php
require './vendor/twitteroauth/autoload.php';
include_once './constants.php';
require_once 'dbConnection.php';
use Abraham\TwitterOAuth\TwitterOAuth;

$con = returnConection();
$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY,TWITTER_CONSUMER_SECRET,TWITTER_OAUTH_TOKEN,TWITTER_OAUTH_TOKEN_SECRET);

$afterPath = $_GET['p'];
$oauth_token = $_GET['oauth_token'];
$oauth_verifier = $_GET['oauth_verifier'];

$sql = "SELECT * FROM club WHERE twitter_oauth_token = '" . $oauth_token . "'";
if($result = mysqli_query($con,$sql)){
  if((mysqli_num_rows($result)) > 0) {
    $access_token = $connection->oauth("oauth/access_token",array('oauth_token'=>$oauth_token,'oauth_verifier'=>$oauth_verifier));
    if ($connection->getLastHttpCode() == 200) {
      $sql = "UPDATE club SET twitter_oauth_token = '" . $access_token['oauth_token'] . "', twitter_oauth_token_secret = '" . $access_token['oauth_token_secret'] . "', twitter_oauth_verifier = NULL";
      if($result = mysqli_query($con,$sql)){
        header("Location: " . str_replace('/api/',openssl_decrypt($afterPath,'aes-256-ecb','W1f1Nu7s2017'),BASE_API_URL));
      } else{
        error_log(mysqli_error($con));
      }
    } else {
      error_log("Error obtenint el token d'usuari");
    }
  } else{
    error_log("Oauth token no trobat en BBDD");
  }
} else{
  error_log(mysqli_error($con));
}

?>

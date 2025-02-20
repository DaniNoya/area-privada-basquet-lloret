<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");
  require './vendor/abraham/twitteroauth/autoload.php';
  require_once __DIR__ . '/vendor/autoload.php';
  use Abraham\TwitterOAuth\TwitterOAuth;
  include_once './constants.php';
  require_once 'dbConnection.php';

  $con = returnConection();

  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'PUT':
      $json = file_get_contents('php://input');
      $params = json_decode($json);
      switch($params->xarxa){
        case "twitter":
          $response->url = getUrlGrantAccessTwitter($params->path);
          if (strpos(strtoupper($response->url), "ERROR") !== false){
            $response->status = "ERROR";
            $response->errMessage = "No se ha podido integrar Twitter";
            error_log($response->url);
          } else{
            $response->status = "OK";
          }
          break;
        case "google":
          $response->url = getUrlGrantAccessGoogle($params->path);
          $response->status = "OK";
          break;
        default:
          $response->status = "ERROR";
          $response->errMessage = "Error al integrar";
          error_log("No ha arribat xarxa per integrar");
          break;
      }
      break;
    case 'GET':
        $json = json_encode($_GET);
        $params = json_decode($json);
        switch ($params->tipo){
          case 'apis':
            $response->apis = array();
            $sql = "SELECT * FROM club";
            if ($result = mysqli_query($con,$sql)){
              $clubData = $result->fetch_assoc();
              $response->apis['twitter'] = ($clubData['twitter_oauth_token'] != NULL && $clubData['twitter_oauth_token_secret'] != NULL);
              $response->apis['facebook'] = ($clubData['facebook_token'] != NULL);
              $response->apis['instagram'] = ($clubData['instagram_token'] != NULL);
              $response->apis['google'] = ($clubData['google_token'] != NULL);
            }
            break;
          default:
            break;
        }
      break;
    default:
      break;
  };

  //error_log(print_r($response->categorias, TRUE));
  header('Content-Type: application/json');
  echo json_encode($response);

  function getUrlGrantAccessTwitter($afterPath): string{
    $con = returnConection();
    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY,TWITTER_CONSUMER_SECRET,TWITTER_OAUTH_TOKEN,TWITTER_OAUTH_TOKEN_SECRET);
    $access_token = $connection->oauth("oauth/request_token",array('oauth_callback'=> BASE_API_URL . "receptorTwitter.php?p=" . openssl_encrypt($afterPath,'aes-256-ecb','W1f1nU7s2017')));
    $sql = "UPDATE club SET twitter_oauth_token= '" . $access_token['oauth_token'] . "', twitter_oauth_verifier = '" . $access_token['oauth_token_secret'] . "'";
    if (mysqli_query($con,$sql)){
      return $connection->url("oauth/authorize",array("oauth_token"=>$access_token['oauth_token']));
    } else{
      return "ERROR." . mysqli_error($con);
    }
  }

  function getUrlGrantAccessGoogle($afterPath): string{
    $client = new Google_Client(array("client_id"=>GOOGLE_OAUTH2_CLIENT_ID,"client_secret"=>GOOGLE_OAUTH2_CLIENT_SECRET, "developer_key"=>GOOGLE_API_KEY));
    $client->setScopes('https://www.googleapis.com/auth/youtube');
    $redirect = filter_var('https://' . $_SERVER['HTTP_HOST'] .
      str_replace("apis.php","receptorGoogle.php",$_SERVER['PHP_SELF']),FILTER_SANITIZE_URL);
    $client->setRedirectUri($redirect);
    $client->setAccessType('offline');
    $client->setPrompt("consent");
    $client->setState($afterPath);
    return $client->createAuthUrl();
  }
?>

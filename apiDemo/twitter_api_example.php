<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';
  include('./vendor/twitteroauth/autoload.php');

  /* define the classes */
  use Abraham\TwitterOAuth\TwitterOAuth;

  $con = returnConection();
  $response = new Result();

  // Solo se puede seleccionar hasta 4 fotos para twittear a la vez.
  $imagesArray = [];
  $files = glob('../imagesTemp/*.{jpg,png}', GLOB_BRACE);
  foreach($files as $file){
    if(is_file($file)){
        array_push($imagesArray, $file);
    }
  }
  publishToTwitter("MARVEL V4","Spider-Man, Doctor Strange.",$imagesArray);
  //deleteFromTwitter('1476473816750645249');


  function twitterConnection() {
    // Twitter OAuth keys
    $api_key="suW7gAUfRPTK6bIXHES0azHPM";
    $api_secret="R2MhzfQrseDQTFsQbLay6y3RYtiaQvJSScNbKLC261tFowpqCT";
    $access_token="1204429277564981255-zY61KwQk6JCee81FKdDBMhbW4QUBKG";
    $access_token_secret="Mg50W8ngNnd8DD9vOwyUPBixsvkSYGPlEGqJLLM896uSq";
    $connection = new TwitterOAuth($api_key,$api_secret,$access_token,$access_token_secret);

    return $connection;
  }

  function publishToTwitter($titol, $text, $mediaArray) {
    $status = $titol.$text;
    if (strlen($status) >= 130) {
        $status = substr($status, 0, 130);
    }

    // init API
    $connection = twitterConnection();
    $content = $connection->get("account/verify_credentials");

    if (is_array($mediaArray)) {
        $mediaIDS = array();
        foreach ($mediaArray as $key => $media_path) {
            // Upload media to twitter API and get media ID back
            $mediaOBJ = $connection->upload('media/upload', ['media' => $media_path]);

            // push uploaded media ID to array
            array_push($mediaIDS, $mediaOBJ->media_id_string);
        }

        // create comma delimited list of media ID:s
        $mediaIDstr = implode(',', $mediaIDS);
    }

    // API params
    $arrayCfg['status'] = str_replace("&nbsp;", "", strip_tags(str_replace("<div>","\r\n",$titol . "\r\n" . $text)));
    $arrayCfg['media_ids'] = $mediaIDstr;

    // Make POST request to Twitter API
    $statuses = $connection->post("statuses/update", $arrayCfg);
  }

  function deleteFromTwitter($idPost) {
    // init API
    $connection = twitterConnection();
    $content = $connection->get("account/verify_credentials");

    // API params
    $arrayCfg['id'] = (int)$idPost;

    // Make POST request to Twitter API
    $statuses = $connection->post("statuses/destroy", $arrayCfg);
  }

  function updateToTwitter($noticia, $mediaArray) {

    deleteFromTwitter($noticia->twitter);

    publishToTwitter($noticia,$mediaArray);
  }

  header('Content-Type: application/json');
  echo json_encode($response);
?>
<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");

require_once 'dbConnection.php';
include 'defines_networks.php';

// load twitter graph-sdk files
require_once __DIR__ . '/vendor/abraham/twitteroauth/autoload.php'; ///vendor/twitteroauth/autoload.php
// load facebook graph-sdk files
require_once __DIR__ . '/vendor/Facebook/autoload.php';

// define the classes twitter
use Abraham\TwitterOAuth\TwitterOAuth;

$conn   = returnConection();
$result = new Result();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'POST':
    $params  = json_decode(file_get_contents("php://input"));
    $noticia = $params->noticia;

    $tipo       = $noticia->tipo;
    $imgPortada = $noticia->imagenPortada;
    $media      = $noticia->media;
    $titol      = $noticia->titol;
    $texto      = mysqli_real_escape_string($conn,$noticia->text);

    if ($tipo == 'cronica') {
      $equipo = $noticia->equipo;
      $sql = sprintf("INSERT INTO noticias VALUES (NULL,'%s',%d,'%s','%s','%s',current_timestamp(),0,NULL,NULL,NULL)", $tipo, $equipo, $imgPortada, $titol, $texto);
    } else {
      $sql = sprintf("INSERT INTO noticias VALUES (NULL,'%s',NULL,'%s','%s','%s',current_timestamp(),0,NULL,NULL,NULL)", $tipo, $imgPortada, $titol, $texto);
    }

    if (!mysqli_query($conn, $sql)) {
      return http_response_code(400);
    } else {
      $id_bbdd = mysqli_insert_id($conn);

      foreach ($media as $m) {
        $sqlMedia = sprintf("INSERT INTO media VALUES (NULL,'%s','%s','%s','%s')", $m->tipoMedia, $m->mediaURL, $m->filetype, $m->filename);
        if (mysqli_query($conn, $sqlMedia)) {
          $sqlNoticiasMedia = sprintf("INSERT INTO noticias_media VALUES (%d,%d)", $id_bbdd, mysqli_insert_id($conn));
          if (!mysqli_query($conn, $sqlNoticiasMedia)) {
            $result->resultat = "ERROR";
            $result->causa .= "Error al asignar la imagen $m->filename a la noticia\r\n";
          }
        } else {
          $result->resultat = "ERROR";
          $result->causa .= "Error al guardar la imagen $m->filename\r\n";
        }
      }

      if ((isset($params->twitter)) && ($params->twitter == true)) {
        $error = publishToTwitter($conn, $noticia, $id_bbdd);
        if ($error != "OK") {
          $result->resultat = "ERROR";
          $result->causa = $error;
        }
      }

      if ((isset($params->facebook)) && ($params->facebook == true)) {
        $error = publishToFacebook($conn, $noticia, $id_bbdd);
        if ($error != "OK") {
          $result->resultat = "ERROR";
          $result->causa = $error;
        }
      }

      if ((isset($params->instagram)) && ($params->instagram == true)) {}

    }
    break;

  case 'PUT':
    $params  = json_decode(file_get_contents("php://input"));
    $noticia = $params->noticia;

    $tipo       = $noticia->tipo;
    $imgPortada = $noticia->imagenPortada;
    $titol      = $noticia->titol;
    $texto      = mysqli_real_escape_string($conn,$noticia->text);

    if (isset($params->delete)) {
      $errorTwitter   = "OK";
      $errorFacebook  = "OK";
      $errorInstagram = "OK";

      if ($noticia->twitter != "") {
        $errorTwitter = deleteFromTwitter($conn, $noticia);
        if ($errorTwitter != "OK") {
          $result->resultat = "ERROR";
          $result->causa = $errorTwitter;
        }
      }

      if ($noticia->facebook != "") {
        $errorFacebook = deleteFromFacebook($conn, $noticia);
        if ($errorFacebook != "OK") {
          $result->resultat = "ERROR";
          $result->causa = $errorFacebook;
        }
      }

      if ($errorTwitter == "OK" && $errorFacebook == "OK" && $errorInstagram == "OK") {
        $sqlDelete = "UPDATE noticias SET borrado = 1 WHERE id = $noticia->id";
        if (!mysqli_query($conn, $sqlDelete)) {
          return http_response_code(400);
        }
      }
    } elseif (isset($params->enableDisable)) {

      switch ($params->xarxa) {
        case 'twitter':
          if ($noticia->twitter != "") {
              deleteFromTwitter($conn, $noticia);
          }else if ($noticia->twitter == "") {
              publishToTwitter($conn, $noticia);
          }
          break;

        case 'facebook':
          if ($noticia->facebook != "") {
              deleteFromFacebook($conn, $noticia);
          }else if ($noticia->facebook == "") {
              publishToFacebook($conn, $noticia);
          }
          break;

        default:
          break;
      }
    } else {
      // En caso de una modificación, sólo actualizaremos redes si anteriormente ya estaba publicado

      $errorTwitter   = "OK";
      $errorFacebook  = "OK";
      $errorInstagram = "OK";

      if ($noticia->twitter != "") {
        $errorTwitter = updateToTwitter($conn, $noticia);
        if ($errorTwitter != "OK") {
          $result->resultat = "ERROR";
          $result->causa = $errorTwitter;
        }
      }

      if ($noticia->facebook != "") {
        $errorFacebook = updateToFacebook($conn, $noticia);
        if ($errorFacebook != "OK") {
          $result->resultat = "ERROR";
          $result->causa = $errorFacebook;
        }
      }

      if ($noticia->instagram != "") {}

      if ($errorTwitter == "OK" && $errorFacebook == "OK" && $errorInstagram == "OK") {
        $sqlUpdateNoticias = "UPDATE noticias SET imagenPortada = '$imgPortada', titol = '$titol', text = '$texto' WHERE id = $noticia->id";
        if (!mysqli_query($conn, $sqlUpdateNoticias)) {
          $result->resultat = "ERROR";
          $result->causa = "Error al modificar la not�cia\r\n";
        }

        $sqlDeleteMedia = sprintf("DELETE FROM media WHERE id IN (SELECT idMedia from noticias_media WHERE idNoticia = %d)", $noticia->id);
        if (!mysqli_query($conn, $sqlDeleteMedia)) {
          $result->resultat = "ERROR";
          $result->causa = "Error al eliminar las im�genes anteriormente seleccionadas\r\n";
        }

        foreach ($noticia->media as $m) {
          $sqlMedia = sprintf("INSERT INTO media VALUES (NULL,'%s','%s','%s','%s')", $m->tipoMedia, $m->mediaURL, $m->filetype, $m->filename);
          if (mysqli_query($conn, $sqlMedia)) {
            $sqlNoticiasMedia = sprintf("INSERT INTO noticias_media VALUES (%d,%d)", $noticia->id, mysqli_insert_id($conn));
            if (!mysqli_query($conn, $sqlNoticiasMedia)) {
              $result->resultat = "ERROR";
              $result->causa .= "Error al asignar la imagen $m->filename a la noticia\r\n";
            }
          } else {
            $result->resultat = "ERROR";
            $result->causa .= "Error al guardar la imagen $m->filename\r\n";
          }
        }
      }
    }
    break;

  case 'GET':
    $json = json_encode($_GET);
    $params = json_decode($json);

    $result->noticias = array();

    if (isset($params->tipo)) {
      if ($params->tipo == "noticia") {
        $queryNoticias = "SELECT * FROM noticias WHERE borrado = 0 AND tipo = '$params->tipo' ORDER BY fecha DESC";
      } elseif ($params->tipo == "cronica") {
        $queryNoticias = "SELECT * FROM noticias WHERE borrado = 0 AND equipo IN (SELECT id FROM equipo WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1)) ORDER BY fecha DESC";
      }
    } elseif (isset($params->id)) {
      $queryNoticias = "SELECT n.* FROM noticias n LEFT JOIN noticias n2 ON (n.equipo = n2.equipo AND n.fecha < n2.fecha) WHERE n.borrado = 0 AND n2.fecha IS NULL AND n.equipo IN (SELECT id FROM equipo WHERE id_temporada = $params->id) ORDER BY n.fecha DESC";
    } else {
      $queryNoticias = "SELECT * FROM noticias WHERE borrado = 0 ORDER BY fecha DESC";
    }

    if ($resultNoticias = mysqli_query($conn, $queryNoticias)) {
      while ($noticiasData = mysqli_fetch_array($resultNoticias,MYSQLI_ASSOC)) {

        $sqlMedia = sprintf("SELECT m.* FROM media m INNER JOIN noticias_media nm on m.id = nm.idMedia WHERE nm.idNoticia = %d",$noticiasData['id']);
        $noticiasData['media'] = array();

        if ($resultMedia = mysqli_query($conn, $sqlMedia)) {
          if (mysqli_num_rows($resultMedia) > 0) {
            while ($mediaData = mysqli_fetch_array($resultMedia, MYSQLI_ASSOC)) {

              $med = array();
              $med['tipoMedia'] = $mediaData['tipoMedia'];
              $med['mediaURL'] = $mediaData['mediaURL'];
              $med['filetype'] = $mediaData['filetype'];
              $med['filename'] = $mediaData['filename'];
              $noticiasData['media'][] = $med;
            }
          }
        }
        $result->noticias[] = $noticiasData;
      }
    }
    break;

  default:
    break;
}


/**
 * Get twitter connection.
 *
 * @param object   $conn
 *
 * @return object
 */
function twitterConnection($conn) {
  $queryClub = "SELECT twitter_oauth_token, twitter_oauth_token_secret FROM club;";
  if ($resultClub = mysqli_query($conn, $queryClub)) {
    $clubData = $resultClub->fetch_assoc();

    $oauthToken = $clubData['twitter_oauth_token'];
    $oauthTokenSecret = $clubData['twitter_oauth_token_secret'];
    return new TwitterOAuth(TWITTER_API_KEY,TWITTER_API_SECRET,$oauthToken,$oauthTokenSecret);
  }
}

/**
 * Post on twitter.
 *
 * @param object      $conn
 * @param object      $noticia
 * @param string|null $id_bbdd
 * 
 * @return string
 */
function publishToTwitter($conn, $noticia, $id_bbdd = NULL) {
  // Get twitter object
  $twitter = twitterConnection($conn);
  $content = $twitter->get("account/verify_credentials");

  /* Twitter post content */
  $titulo = $noticia->titol;
  $texto  = $noticia->text;
  $status = $titulo.$texto;
  if (strlen($status) >= 130) {
    $texto = substr($texto, 0, 130-strlen($titulo));
  }

  if (is_array($noticia->media)) {
    $mediaIDS = array();
    $nameportada=rand(100000,999999).".jpg";
      $media = array();
    $portadaMedia = new stdClass;
    $portadaMedia->filename = $nameportada;
    $portadaMedia->filetype = 'photo';
    $portadaMedia->tipoMedia = 'data';
    $portadaMedia->mediaURL = $noticia->imagenPortada;
      $media = $noticia->media;
      $media[] = $portadaMedia;
    //$noticia->media[] = $portadaMedia;

    //foreach ($noticia->media as $mediaInfo) {
    foreach ($media as $mediaInfo) {
      $media_path = $mediaInfo->mediaURL;
      $imgName = $mediaInfo->filename;

      list(, $media_path) = explode(';', $media_path);
      list(, $media_path) = explode(',', $media_path);
      $media_path = base64_decode($media_path);
      // Save
      file_put_contents('../tempImageSN/'.$imgName, $media_path);

      // Upload media to twitter API and get media ID back
      $mediaOBJ = $twitter->upload('media/upload', ['media' => '../tempImageSN/'.$imgName]);

      array_push($mediaIDS, $mediaOBJ->media_id_string);
      unlink('../tempImageSN/'.$imgName);
    }

    // create comma delimited list of media ID:s
    $mediaIDstr = implode(',', $mediaIDS);
  }

  /* API params */
  $arrayCfg['status'] = str_replace("&nbsp;", "", strip_tags(str_replace("<div>","\r\n",$titulo . "\r\n" . $texto)));
  $arrayCfg['media_ids'] = $mediaIDstr;

  /* Make POST request to Twitter API */
  $statuses = $twitter->post("statuses/update", $arrayCfg);

  /* DATA BASE */
  if ($statuses->id_str != "") {
    $idTwitter = $statuses->id_str;

    if ($id_bbdd != NULL) {
      $sqlUpdateTwitterID = "UPDATE noticias SET twitter = '$idTwitter' WHERE id = $id_bbdd";
    } else {
      $sqlUpdateTwitterID = "UPDATE noticias SET twitter = '$idTwitter' WHERE id = $noticia->id";
    }

    if (!mysqli_query($conn,$sqlUpdateTwitterID)) {
      return "No se ha podido guardar el ID de Twitter en Base de datos";
    }

    return "OK";
  } else {
    return "No se ha podido cargar en Twitter";
  }
}

/**
 * Delete twitter post.
 *
 * @param object      $conn
 * @param object      $noticia
 * 
 * @return string
 */
function deleteFromTwitter($conn, $noticia) {
  // Get twitter object
  $twitter = twitterConnection($conn);
  $content = $twitter->get("account/verify_credentials");

  // API params
  $arrayCfg['id'] = (int)$noticia->twitter;

  // Make POST request to Twitter API
  $statuses = $twitter->post("statuses/destroy", $arrayCfg);

  // DATA BASE
  if ($statuses) {
    $sqlDeleteTwitter = "UPDATE noticias SET twitter = NULL WHERE id = $noticia->id";
    if (!mysqli_query($conn,$sqlDeleteTwitter)) {
      return "No se ha podido eliminar el ID de Twitter en Base de datos";
    }
    return "OK";
  } else {
    return "No se ha podido eliminar de Twitter";
  }
}

/**
 * Update twitter post.
 *
 * @param object      $conn
 * @param object      $noticia
 * 
 * @return string
 */
function updateToTwitter($conn, $noticia) {
  $delete = deleteFromTwitter($conn, $noticia);

  if ($delete != "OK") {
    return $delete;
  } else {
    $publish = publishToTwitter($conn, $noticia);

    if ($publish != "OK") {
      return $publish;
    } else {
      return "OK";
    }
  }
}


/**
 * Get facebook connection.
 *
 * @return object
 */
function facebookConnection() {
  // facebook credentials array
  $creds = array(
    'app_id'                  => FACEBOOK_APP_ID,
    'app_secret'              => FACEBOOK_APP_SECRET,
    'default_graph_version'   => 'v3.2',
    'persistent_data_handler' => 'session'
  );

  return new Facebook\Facebook( $creds );
}

/**
 * Get facebook access token from database.
 *
 * @param object   $conn
 * 
 * @return string
 */
function facebookObtainingAccessToken($conn) {
  $queryClub = "SELECT facebook_token FROM club;";
  if ($resultClub = mysqli_query($conn, $queryClub)) {
    $clubData = $resultClub->fetch_assoc();

    $accessToken = $clubData['facebook_token'];
  }

  return $accessToken;
}

/**
 * Upload media to Facebook API and get media ID
 *
 * @param object   $facebook
 * @param string   $accessToken
 * @param object   $noticia
 *
 * @return array
 */
function facebookUploadPhoto($facebook, $accessToken, $noticia, $conn) {
  $photoIdArray = array();

  $portadaMedia = new stdClass;
  $portadaMedia->filename = rand(100000,999999).".jpg";
    $media = array();
  $portadaMedia->filetype = 'photo';
  $portadaMedia->tipoMedia = 'data';
  $portadaMedia->mediaURL = $noticia->imagenPortada;
    $media = $noticia->media;
    $media[] = $portadaMedia;
  //$noticia->media[] = $portadaMedia;

  //foreach ($noticia->media as $mediaInfo) {
  foreach ($media as $mediaInfo) {
    $media_path = $mediaInfo->mediaURL;
    $imgName = $mediaInfo->filename;
      $imgName = str_replace(" ","_",$imgName);
      //$imgName = substr($imgName,-10);

    list(, $media_path) = explode(';', $media_path);
    list(, $media_path) = explode(',', $media_path);
    $media_path = base64_decode($media_path);
    // Save
    file_put_contents('../tempImageSN/'.$imgName, $media_path);

    $params = array(
      "url" => 'https://areaprivada.basquetlloret.com/tempImageSN/'.$imgName,
      "published" => false
    );
    try {
      $response = $facebook->post('/'.FACEBOOK_PAGE_ID.'/photos', $params, $accessToken);
      $photoId = $response->getDecodedBody();
      if(!empty($photoId["id"])) {
        $photoIdArray[] = $photoId["id"];
      }
      unlink('../tempImageSN/'.$imgName);
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
      // return 'Graph returned an error: ' . $e->getMessage();
      exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
      // return 'Facebook SDK returned an error: ' . $e->getMessage();
      exit;
    }
  }

  return $photoIdArray;
}

/**
 * Post on facebook.
 *
 * @param object      $conn
 * @param object      $noticia
 * @param string|null $id_bbdd
 * 
 * @return string
 */
function publishToFacebook($conn, $noticia, $id_bbdd = NULL) {
  // Get facebook object
  $facebook = facebookConnection();

  // Get accessToken
  $accessToken = facebookObtainingAccessToken($conn);

  // Facebook post content
  $titulo  = $noticia->titol;
  $texto   = $noticia->text;
  $message = str_replace("&nbsp;", "", strip_tags(str_replace("<div>","\r\n",$titulo . "\r\n" . $texto)));
  $photoIdArray = facebookUploadPhoto($facebook, $accessToken, $noticia, $conn);

  $attachment = [
    'message' => $message,
  ];

  foreach($photoIdArray as $k => $photoId) {
    $attachment["attached_media"][$k] = '{"media_fbid":"' . $photoId . '"}';
  }

  try {
    $response = $facebook->post('/'.FACEBOOK_PAGE_ID.'/feed', $attachment, $accessToken);
  } catch(Facebook\Exceptions\FacebookResponseException $e) {
    return 'Graph returned an error: ' . $e->getMessage();
    exit;
  } catch(Facebook\Exceptions\FacebookSDKException $e) {
    return 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
  }
  $graphNode = $response->getGraphNode();

  // DATA BASE
  if ($graphNode['id'] != "") {
    $idFacebook = $graphNode['id'];

    if ($id_bbdd != NULL) {
      $sqlUpdateFacebookID = "UPDATE noticias SET facebook = '$idFacebook' WHERE id = $id_bbdd";
    } else {
      $sqlUpdateFacebookID = "UPDATE noticias SET facebook = '$idFacebook' WHERE id = $noticia->id";
    }

    if (!mysqli_query($conn, $sqlUpdateFacebookID)) {
      return "No se ha podido guardar el ID de Facebook en Base de datos";
    }

    return "OK";
  } else {
    return "No se ha podido cargar en Facebook";
  }
}

/**
 * Delete facebook post.
 *
 * @param object      $conn
 * @param object      $noticia
 * 
 * @return string
 */
function deleteFromFacebook($conn, $noticia) {
  // Get facebook object
  $facebook = facebookConnection();

  // Get accessToken
  $accessToken = facebookObtainingAccessToken($conn);

  $statuses = $facebook->delete('/'.$noticia->facebook, array(), $accessToken);

  // DATA BASE
  if ($statuses) {
    $sqlDeleteFacebook = "UPDATE noticias SET facebook = NULL WHERE id = $noticia->id";
    if (!mysqli_query($conn,$sqlDeleteFacebook)) {
      return "No se ha podido eliminar el ID de Facebook en Base de datos";
    }
    return "OK";
  } else {
    return "No se ha podido eliminar de Facebook";
  }
}

/**
 * Update facebook post.
 *
 * @param object      $conn
 * @param object      $noticia
 * 
 * @return string
 */
function updateToFacebook($conn, $noticia) {
  $delete = deleteFromFacebook($conn, $noticia);

  if ($delete != "OK") {
    return $delete;
  } else {
    $publish = publishToFacebook($conn, $noticia);

    if ($publish != "OK") {
      return $publish;
    } else {
      return "OK";
    }
  }
}

header('Content-Type: application/json');
echo json_encode($result);
?>
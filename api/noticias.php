<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");
  include_once './constants.php';
  require_once 'dbConnection.php';
  require './vendor/twitteroauth/autoload.php';
  use Abraham\TwitterOAuth\TwitterOAuth;


$con = returnConection();

  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));
      $noticia = $params->noticia;
      $tipo = $noticia->tipo;
      $imagenPortada = $noticia->imagenPortada;
      $media = $noticia->media;
      $titol = $noticia->titol;
      $texto = mysqli_real_escape_string($con,$noticia->text);
      if ($tipo == 'cronica') {
        $equipo = $noticia->equipo;
        $sql = sprintf("INSERT INTO noticias VALUES (NULL,'%s',%d,'%s','%s','%s',current_timestamp(),0,NULL,NULL,NULL)", $tipo, $equipo, $imagenPortada, $titol, $texto);
      } else {
        $sql = sprintf("INSERT INTO noticias VALUES (NULL,'%s',NULL,'%s','%s','%s',current_timestamp(),0,NULL,NULL,NULL)", $tipo, $imagenPortada, $titol, $texto);
      }
      if (!mysqli_query($con, $sql)){
        return http_response_code(400);
      } else{
        $id_bbdd = mysqli_insert_id($con);
        foreach ($media as $m){
          $sqlMedia = sprintf("INSERT INTO media VALUES (NULL,'%s','%s','%s','%s')", $m->tipoMedia, $m->mediaURL, $m->filetype, $m->filename);
          if (mysqli_query($con, $sqlMedia)){
            $sql2 = sprintf("INSERT INTO noticias_media VALUES (%d,%d)", $id_bbdd, mysqli_insert_id($con));
            if (!mysqli_query($con, $sql2)){
              $response->resultat = "ERROR";
              $response->causa .= "Error al asignar la imagen $m->filename a la noticia\r\n";
            }
          } else{
            $response->resultat = "ERROR";
            $response->causa .= "Error al guardar la imagen $m->filename\r\n";
          }
        }
        if ((isset($params->facebook)) && ($params->facebook == true)) {

        }
        if ((isset($params->instagram)) && ($params->instagram == true)) {

        }
        if ((isset($params->twitter)) && ($params->twitter == true)) {
          $error = publishToTwitter($noticia, $id_bbdd);
          if ($error != "OK"){
            $response->resultat = "ERROR";
            $response->causa = $error;
          }
        }
      }
      break;
    case 'PUT':
      $params = json_decode(file_get_contents("php://input"));
      $noticia = $params->noticia;
      $tipo = $noticia->tipo;
      $imagenPortada = $noticia->imagenPortada;
      $titol = $noticia->titol;
      $texto = mysqli_real_escape_string($con,$noticia->text);

      if (isset($params->delete)){
        $errorFacebook = "OK";
        $errorInstagram = "OK";
        $errorTwitter = "OK";
        if ($noticia->facebook != ""){
          //deleteFromFacebook($noticia);
        }
        if ($noticia->instagram != ""){
          //deleteFromInstagram($noticia);
        }
        if ($noticia->twitter != ""){
          $errorTwitter = deleteFromTwitter($noticia);
          if ($errorTwitter != "OK"){
            $response->resultat = "ERROR";
            $response->causa = $errorTwitter;
          }
        }
        if ($errorFacebook == "OK" && $errorInstagram == "OK" && $errorTwitter == "OK"){
          $sql = "UPDATE noticias SET borrado = 1 WHERE id = $noticia->id";
          if (!mysqli_query($con, $sql)){
            return http_response_code(400);
          }
        }
      } else if (isset($params->enableDisable)){
        switch ($params->xarxa) {
          case 'facebook':
            if ($noticia->facebook != ""){
              //deleteFromFacebook($noticia);
            } else{
              //enableToFacebook($noticia);
            }
            break;
          case 'instagram':
            if ($noticia->instagram != ""){
              //deleteFromInstagram($noticia);
            } else{
              //enableToInstagram($noticia);
            }
            break;
          case 'twitter':
            if ($noticia->twitter != ""){
              if(deleteFromTwitter($noticia) != "OK"){
                $response->resultat = "ERROR";
                $response->causa = "Error al eliminar de Twitter";
              }
            } else{
              $error = publishToTwitter($noticia);
              if ($error != "OK"){
                $response->resultat = "ERROR";
                $response->causa = $error;
              }
            }
            break;
          default:
            break;
        }
      } else {
        // En cas d'una modificació només actualitzarem xarxes si anteriorment ja estava publicat
        $errorFacebook = "OK";
        $errorInstagram = "OK";
        $errorTwitter = "OK";
        if ($noticia->twitter != ""){
          $errorTwitter = deleteFromTwitter($noticia);
          if ($errorTwitter != "OK"){
            $response->resultat = "ERROR";
            $response->causa = $errorTwitter;
          } else {
            $errorTwitter = publishToTwitter($noticia);
            if ($errorTwitter != "OK"){
              $response->resultat = "ERROR";
              $response->causa = $errorTwitter;
            }
          }
        }
        if ($errorFacebook == "OK" && $errorInstagram == "OK" && $errorTwitter == "OK"){
          $sql = "UPDATE noticias SET imagenPortada = '$imagenPortada', titol = '$titol', text = '$texto' WHERE id = $noticia->id";
          if (!mysqli_query($con, $sql)){
            $response->resultat = "ERROR";
            $response->causa = "Error al modificar la notícia\r\n";
          }
          $sqlDeleteMedia = sprintf("DELETE FROM media WHERE id IN (SELECT idMedia from noticias_media WHERE idNoticia = %d)", $noticia->id);
          if (!mysqli_query($con, $sqlDeleteMedia)){
            $response->resultat = "ERROR";
            $response->causa = "Error al eliminar las imágenes anteriormente seleccionadas\r\n";
          }
          foreach ($noticia->media as $m){
            $sqlMedia = sprintf("INSERT INTO media VALUES (NULL,'%s','%s','%s','%s')", $m->tipoMedia, $m->mediaURL, $m->filetype, $m->filename);
            if (mysqli_query($con, $sqlMedia)){
              $sql2 = sprintf("INSERT INTO noticias_media VALUES (%d,%d)", $noticia->id, mysqli_insert_id($con));
              if (!mysqli_query($con, $sql2)){
                $response->resultat = "ERROR";
                $response->causa .= "Error al asignar la imagen $m->filename a la noticia\r\n";
              }
            } else{
              $response->resultat = "ERROR";
              $response->causa .= "Error al guardar la imagen $m->filename\r\n";
            }
          }
        }
      }
      break;
    case 'GET':
      $json = json_encode($_GET);

      $params = json_decode($json);
      $response->noticias = array();

      if (isset($params->tipo)) {
        if ($params->tipo == "noticia"){
          $sql = "SELECT * FROM noticias WHERE borrado = 0 AND tipo = '$params->tipo' ORDER BY fecha DESC";
        } else if ($params->tipo == "cronica") {
          $sql = "SELECT * FROM noticias WHERE borrado = 0 AND equipo IN (SELECT id FROM equipo WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1)) ORDER BY fecha DESC";
        }
      }
      else if (isset($params->id)){
        $sql = "SELECT n.* FROM noticias n LEFT JOIN noticias n2 ON (n.equipo = n2.equipo AND n.fecha < n2.fecha) WHERE n.borrado = 0 AND n2.fecha IS NULL AND n.equipo IN (SELECT id FROM equipo WHERE id_temporada = $params->id) ORDER BY n.fecha DESC";
      } else{
        $sql = "SELECT * FROM noticias WHERE borrado = 0 ORDER BY fecha DESC";
      }
      if ($result = mysqli_query($con, $sql)){
        while($noticiasData = mysqli_fetch_array($result,MYSQLI_ASSOC)){
          $sqlMedia = sprintf("SELECT m.* FROM media m INNER JOIN noticias_media nm on m.id = nm.idMedia WHERE nm.idNoticia = %d",$noticiasData['id']);
          $noticiasData['media'] = array();
          if ($resultMedia = mysqli_query($con, $sqlMedia)) {
            if (mysqli_num_rows($resultMedia) > 0){
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
          $response->noticias[] = $noticiasData;
        }
      }
      break;
    default:
      break;
  };

  //error_log(print_r($response->tipos_parentesco, TRUE));
  header('Content-Type: application/json');
  echo json_encode($response);

  function publishToTwitter($new, $id_bbdd = NULL) {
    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY,
      TWITTER_CONSUMER_SECRET,
      TWITTER_OAUTH_TOKEN,
      TWITTER_OAUTH_TOKEN_SECRET);
    $content = $connection->get("account/verify_credentials");

    list($type, $new->imagen) = explode(';', $new->imagen);
    list(, $new->imagen)      = explode(',', $new->imagen);
    $new->imagen = base64_decode($new->imagen);
    file_put_contents('./tmp.jpg', $new->imagen);

    $media = $connection->upload('media/upload', [
      'media' => "./tmp.jpg",
    ]);

    $statuses = $connection->post("statuses/update", [
        "status" => str_replace("&nbsp;", "", strip_tags(str_replace("<div>","\r\n",$new->titol . "\r\n" . $new->text))),
        "media_ids" => $media->media_id_string
      ]
    );

    if ($statuses->id_str != ""){
      $con = returnConection();
      if ($id_bbdd != NULL){
        $sql = "UPDATE noticias SET twitter = '$statuses->id_str' WHERE id = $id_bbdd";
      } else{
        $sql = "UPDATE noticias SET twitter = '$statuses->id_str' WHERE id = $new->id";
      }
      if (!mysqli_query($con,$sql)){
        return "No se ha podido guardar el ID de Twitter en Base de datos";
      }
      return "OK";
    } else{
      return "No se ha podido cargar en Twitter";
    }
  }

  function deleteFromTwitter($new) {
    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY,
      TWITTER_CONSUMER_SECRET,
      TWITTER_OAUTH_TOKEN,
      TWITTER_OAUTH_TOKEN_SECRET);
    $content = $connection->get("account/verify_credentials");

    $id = (int)$new->twitter;
    $statuses = $connection->post("statuses/destroy", [
        "id" => (int)$new->twitter
      ]
    );

    if ($statuses){
      $con = returnConection();
      $sql = "UPDATE noticias SET twitter = NULL WHERE id = $new->id";
      if (!mysqli_query($con,$sql)){
        return "No se ha podido eliminar el ID de Twitter en Base de datos";
      }
      return "OK";
    } else {
      return "No se ha podido eliminar de Twitter";
    }
  }
?>

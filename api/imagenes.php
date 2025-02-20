<?php

  header('Access-Control-Allow-Origin: *');

  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

  header("Access-Control-Allow-Methods: *");



  require_once 'dbConnection.php';



  $con = returnConection();



  $response = new Result();



  switch ($_SERVER['REQUEST_METHOD']) {

    case 'POST':

      $params = json_decode(file_get_contents("php://input"));

      if (isset($params->equipo)){

        $equipo = $params->equipo;

        foreach ($params->fotos as $foto){

          $sql = "INSERT INTO galeria_equipos VALUES (NULL,$equipo->id,'$foto')";

          if(!mysqli_query($con, $sql)) {

            return http_response_code(400);

          }

        }

      } else{

        foreach ($params->fotos as $foto){

          $sql = "INSERT INTO galeria_temporada VALUES (NULL,(SELECT id FROM temporada ORDER BY id DESC LIMIT 1),'$foto')";

          if(!mysqli_query($con, $sql)) {

            return http_response_code(400);

          }

        }

      }

      break;

    case 'PUT':

      $params = json_decode(file_get_contents("php://input"));

      if (isset($params->equipo)){

        $equipo = $params->equipo;

        $sql  = "DELETE FROM galeria_equipos WHERE id_equipo = $equipo->id";

        if(mysqli_query($con, $sql)) {

          foreach ($params->fotos as $foto){

            $sql = "INSERT INTO galeria_equipos VALUES (NULL,$equipo->id,'$foto')";

            if(!mysqli_query($con, $sql)) {

              return http_response_code(400);

            }

          }

        }

      } else{

        $sql  = "DELETE FROM galeria_temporada WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT  1)";

        if(mysqli_query($con, $sql)) {

          foreach ($params->fotos as $foto){

            $sql = "INSERT INTO galeria_temporada VALUES (NULL,(SELECT id FROM temporada ORDER BY id DESC LIMIT 1),'$foto')";

            if(!mysqli_query($con, $sql)) {

              return http_response_code(400);

            }

          }

        }

      }

      break;

    case 'GET':

      $json = json_encode($_GET);



      $params = json_decode($json);

      $response->imagenes = array();



      if (isset($params->equipo)){

        $sql = "SELECT * FROM galeria_equipos WHERE id_equipo = $params->equipo";

        if ($result = mysqli_query($con, $sql)) {

          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

            $response->imagenes[] = $userData['foto'];

          }

        }

      } else if (isset($params->club)){

        $sql = "SELECT foto FROM galeria_temporada WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1)";

        if ($result = mysqli_query($con, $sql)) {

          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

            $response->imagenes[] = $userData['foto'];

          }

        }

      } else if (isset($params->id)) {
        if(isset($params->primaryCount)){//contar todas las fotos de temporada
          $sql = "SELECT((SELECT count(foto) FROM galeria_temporada as row WHERE id_temporada = $params->id) + (SELECT count(foto) FROM galeria_equipos as row WHERE id_equipo IN (SELECT id FROM equipo WHERE id_temporada = $params->id)) + (SELECT count(imagenPortada) FROM noticias as row WHERE fecha BETWEEN (SELECT fecha_inicio FROM temporada WHERE temporada.id = $params->id) AND (SELECT fecha_final FROM temporada WHERE temporada.id = $params->id)) + (SELECT count(mediaURL) FROM media as row WHERE id IN(SELECT nm.idMedia FROM noticias_media nm WHERE nm.idNoticia IN(SELECT noticias.id FROM noticias WHERE fecha BETWEEN (SELECT fecha_inicio FROM temporada WHERE temporada.id = $params->id) AND (SELECT fecha_final FROM temporada WHERE temporada.id = $params->id))))) as totalCountImg;";

          if ($result = mysqli_query($con, $sql)) {
            $galleryFotoData = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $response->resultat = $galleryFotoData['totalCountImg'];
          }
        }

        if (isset($params->size)) {//selecionar las fotos de temporada de 9 en 9
          $size = $params->size;
          $response->resultat = $size;
          $sizeoff = $size - 9;

          //selecionar fotos segun la temporada y el size
          $sql = "SELECT foto FROM galeria_temporada WHERE id_temporada = $params->id UNION SELECT foto FROM galeria_equipos WHERE id_equipo IN (SELECT id FROM equipo WHERE id_temporada = $params->id) UNION SELECT imagenPortada FROM noticias WHERE fecha BETWEEN (SELECT fecha_inicio FROM temporada WHERE temporada.id = $params->id) AND (SELECT fecha_final FROM temporada WHERE temporada.id = $params->id) UNION SELECT m.mediaURL FROM media m WHERE m.id IN(SELECT nm.idMedia FROM noticias_media nm WHERE nm.idNoticia IN(SELECT noticias.id FROM noticias WHERE fecha BETWEEN (SELECT fecha_inicio FROM temporada WHERE temporada.id = $params->id) AND (SELECT fecha_final FROM temporada WHERE temporada.id = $params->id))) limit 9 offset $sizeoff;";
          $response->causa = $sizeoff;
          if ($result = mysqli_query($con, $sql)) {
            while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
              $response->imagenes[] = $userData['foto'];
            }
          }
        }
      } else {
        $sql = "SELECT foto FROM galeria_temporada WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1) UNION SELECT foto FROM galeria_equipos WHERE id_equipo IN (SELECT id FROM equipo WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1))";

        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->imagenes[] = $userData['foto'];
          }
        }
      }
      break;
    default:
      break;
  };


  //error_log(print_r($response->tipos_parentesco, TRUE));
  header('Content-Type: application/json');
  echo json_encode($response);
?>


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
        $sql = "SELECT foto FROM galeria_temporada WHERE id_temporada = $params->id UNION SELECT foto FROM galeria_equipos WHERE id_equipo IN (SELECT id FROM equipo WHERE id_temporada = $params->id)";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->imagenes[] = $userData['foto'];
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

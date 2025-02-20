<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();

  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $response->tipos_parentesco = array();
      $sql = "SELECT * FROM tipo_parentesco";

      if ($result = mysqli_query($con, $sql)) {
        while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
          $response->tipos_parentesco[] = $userData;
        }
      }

      break;
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));

      $id_familiar = $params->idFamiliar;
      $id_jugador = $params->idJugador;
      $tipo_parenteso = $params->tipoParentesco;
      $baja = '0';
      $fecha_baja = '0000-00-00';

      $sql = "INSERT INTO familiar VALUES ($id_familiar, $baja, '$fecha_baja') ON DUPLICATE KEY UPDATE baja = $baja, fecha_baja = '$fecha_baja'";
      if(mysqli_query($con, $sql)) {
        $sql2 = "INSERT INTO familiar_jugador VALUES ($id_familiar,$id_jugador,$tipo_parenteso)";
        if(mysqli_query($con, $sql2)) {
          return http_response_code(200);
        }else {
          return http_response_code(422);
        }
      }else {
        return http_response_code(422);
      }
      break;
    case 'DELETE':
      $json = json_encode($_GET);

      $params = json_decode($json);

      $idJugador = $params->jugador;
      $idFamiliar = $params->familiar;

      $sql = "DELETE FROM familiar_jugador WHERE id_jugador = $idJugador AND id_familiar = $idFamiliar";

      if(mysqli_query($con, $sql))
      {
        return http_response_code(200);
      }else
      {
        return http_response_code(422);
      }
      break;
    default:
      break;
  };

  //error_log(print_r($response->tipos_parentesco, TRUE));
  header('Content-Type: application/json');
  echo json_encode($response);

?>

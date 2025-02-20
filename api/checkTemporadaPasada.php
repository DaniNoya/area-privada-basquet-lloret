<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();

  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':

        $json = json_encode($_GET);
        $persona = json_decode($json);

        // error_log(print_r($persona,true));

        $dni = $persona->dni;
        $fecha = date('Y');

        $sql = "SELECT dni FROM persona WHERE dni = '$dni' AND  id IN((
                SELECT id_jugador FROM equipos_jugadores WHERE id_equipo IN((
                SELECT id FROM equipo WHERE id_temporada = (
                SELECT id FROM temporada WHERE (year(fecha_final) = $fecha))))));";

        if ($result = mysqli_query($con, $sql)) {
            if ($result->num_rows > 0) {
              $userData = mysqli_fetch_array($result, MYSQLI_ASSOC);

              if ($dni == $userData["dni"]) {
                $response->resultat = "OK";
              } else if (!$dni == $userData["dni"]) {
                $response->resultat = "NO OK";
              }
            } else {
              $response->resultat = "No existe";
            }
        }
      break;
    default:
      break;
  };

  header('Content-Type: application/json');
  echo json_encode($response);
?>

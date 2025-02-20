<?php
 header('Access-Control-Allow-Origin: *');
 header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
 header("Access-Control-Allow-Methods: *");

 require_once 'dbConnection.php';

 $json = json_encode($_GET);
 $params = json_decode($json);

 $con = returnConection();
 $response = new Result();

 switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $json = json_encode($_GET);
        $params = json_decode($json);

        $metodoVisualizacion = $params->metodoVisualizacion;
        $exclusiones = isset($params->exclusiones) ? $params->exclusiones : null;

        $response->descuentos = array();
        //$sql = "SELECT d.*, (SELECT temporada FROM temporada WHERE id = d.idTemporada) AS temporada FROM descuentosTemporada d WHERE d.id IS NOT NULL AND borrado = '0'";
        $sql = "SELECT d.*, CONCAT((SELECT concepto FROM tipo_pago WHERE id = d.idTipo),' ',(SELECT temporada FROM temporada WHERE id = (SELECT idTemporada FROM tipo_pago where id = d.idTipo))) AS concepto, IF(porcentaje > 0,(SELECT conceptoVisible FROM importes WHERE importe IN (d.porcentaje) AND idTemporada = (SELECT MAX(id) FROM temporada)),'Ninguno') AS conceptoVisible FROM descuentosTemporada d WHERE d.id IS NOT NULL AND borrado = '0' ";
        switch ($metodoVisualizacion) {
            case "temporadaRegular":
              $sql .= "AND d.idTipo IN (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular') ";
              break;
            case "campus":
              $sql .= "AND d.idTipo IN (SELECT id FROM tipo_pago WHERE concepto = 'Campus') ";
              break;
            default:
              $sql .= "AND d.idTipo IS NOT NULL ";
              break;
        }
        $sql .= "ORDER BY d.idTipo DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->descuentos[] = $userData;
          }
        }

        $response->tiposDescuentos = array();

        $sql = "SELECT * FROM importes WHERE concepto IN ('descuentoSonHermanos','descuentoPrimerEquipo') AND idTemporada = (SELECT MAX(id) FROM temporada)";
        if ($result = mysqli_query($con, $sql)) {
            $response->tiposDescuentos[] = array(
              "id" => 0,
              "concepto" => "",
              "conceptoVisible" => "Ninguno",
              "importe" => 0
            );
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->tiposDescuentos[] = $userData;
          }
        }
      break;
    case 'PUT':
        $params = json_decode(file_get_contents("php://input"));

        $id = mysqli_real_escape_string($con, $params->id);
        $idTipo = mysqli_real_escape_string($con, $params->idTipo);
        $dni = mysqli_real_escape_string($con, $params->dni);
        $porcentaje = mysqli_real_escape_string($con, $params->porcentaje);
        $desAnioPasado = mysqli_real_escape_string($con, $params->desAnioPasado);
        $borrado = mysqli_real_escape_string($con, $params->borrado);

        $sql = "UPDATE descuentosTemporada d SET idTipo = '$idTipo', porcentaje='$porcentaje', desAnioPasado='$desAnioPasado', borrado='$borrado' WHERE d.id='$id'";
        if(mysqli_query($con, $sql)){
          return http_response_code(200);
        } else {
          return http_response_code(422);
        }
      break;
    case 'POST':
        $params = json_decode(file_get_contents("php://input"));

        $idTipo = mysqli_real_escape_string($con, $params->idTipo);
        $dni = mysqli_real_escape_string($con, $params->dni);
        $porcentaje = mysqli_real_escape_string($con, $params->porcentaje);
        $desAnioPasado = mysqli_real_escape_string($con, $params->desAnioPasado);

        $sql = "INSERT INTO descuentosTemporada VALUES (NULL,'$idTipo', '$dni','$porcentaje','$desAnioPasado',0)";
        if(mysqli_query($con, $sql)){
        return http_response_code(200);
        } else {
        return http_response_code(422);
        }
      break;
    default:
      break;
 };

 //error_log(print_r($response, TRUE));
 header('Content-Type: application/json');
 echo json_encode($response);
?>

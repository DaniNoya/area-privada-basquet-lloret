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
        $sql = "SELECT d.*, (SELECT temporada FROM temporada WHERE id = d.idTemporada) AS temporada FROM descuentosTemporada d WHERE d.id IS NOT NULL AND borrado = '0'";
        switch ($metodoVisualizacion) {
            case "temporada":
              $sql .= "AND d.idTemporada = (SELECT MAX(d.idTemporada) FROM descuentosTemporada d) ";
              break;
            default:
              $sql .= "AND d.idTemporada IS NOT NULL ";
              break;
        }
        $sql .= "ORDER BY d.idTemporada DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->descuentos[] = $userData;
          }
        }
      break;
    case 'PUT':
        $params = json_decode(file_get_contents("php://input"));

        $id = mysqli_real_escape_string($con, $params->id);
        $idTemporada = mysqli_real_escape_string($con, $params->idTemporada);
        $dni = mysqli_real_escape_string($con, $params->dni);
        $porcentaje = mysqli_real_escape_string($con, $params->porcentaje);
        $desAnioPasado = mysqli_real_escape_string($con, $params->desAnioPasado);
        $borrado = mysqli_real_escape_string($con, $params->borrado);

        $sql = "UPDATE descuentosTemporada d SET idTemporada = '$idTemporada', porcentaje='$porcentaje', desAnioPasado='$desAnioPasado', borrado='$borrado' WHERE d.id='$id'";
        if(mysqli_query($con, $sql)){
          return http_response_code(200);
        } else {
          return http_response_code(422);
        }
      break;
    case 'POST':
        $params = json_decode(file_get_contents("php://input"));

        $idTemporada = mysqli_real_escape_string($con, $params->idTemporada);
        $dni = mysqli_real_escape_string($con, $params->dni);
        $porcentaje = mysqli_real_escape_string($con, $params->porcentaje);
        $desAnioPasado = mysqli_real_escape_string($con, $params->desAnioPasado);

        $sql = "INSERT INTO descuentosTemporada VALUES (NULL,'$idTemporada', '$dni','$porcentaje','$desAnioPasado',0)";
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

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

        $response->pagos = array();
        //$sql = "SELECT p.*, CONCAT((SELECT concepto FROM tipo_pago WHERE idTemporada = (SELECT MAX(id) FROM temporada)),' ',(SELECT temporada FROM temporada WHERE id = (SELECT MAX(id) FROM temporada))) AS tipo FROM pagos p WHERE p.id IS NOT NULL ";
        $sql = "SELECT p.*, CONCAT((SELECT concepto FROM tipo_pago WHERE id = p.tipo),' ',(SELECT temporada FROM temporada WHERE id = (SELECT idTemporada FROM tipo_pago where id = p.tipo))) AS concepto FROM pagos p WHERE p.id IS NOT NULL ";
        switch ($metodoVisualizacion) {
            case "pagoManual":
              $sql .= "AND p.pagoManual = 1 ";
              break;
            case "pagoOnline":
              $sql .= "AND p.pagoManual = 0 ";
              break;
            default:
              $sql .= "AND p.idPersona IS NOT NULL ";
              break;
        }
        $sql .= "ORDER BY p.id DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->pagos[] = $userData;
          }
        }
      break;
    case 'PUT':
        $params = json_decode(file_get_contents("php://input"));

        $id = mysqli_real_escape_string($con, $params->id);
        $idPersona = mysqli_real_escape_string($con, $params->idPersona);
        //$idTransaccion = mysqli_real_escape_string($con, $params->idTransaccion);
        $importe = mysqli_real_escape_string($con, $params->importe);
        $tipo = mysqli_real_escape_string($con, $params->tipo);
        //$pagoCompletado = mysqli_real_escape_string($con, $params->pagoCompletado);
        //$fechaTransaccion = mysqli_real_escape_string($con, $params->fechaTransaccion);
        //$pagoManual = mysqli_real_escape_string($con, $params->pagoManual);

        $sql = "UPDATE pagos p SET idPersona = '$idPersona', importe='$importe', tipo='$tipo' WHERE p.id='$id'";
        if(mysqli_query($con, $sql)){
          return http_response_code(200);
        } else {
          return http_response_code(422);
        }
      break;
    case 'POST':
        $params = json_decode(file_get_contents("php://input"));

        $idPersona = mysqli_real_escape_string($con, $params->idPersona);
        //$idTransaccion = mysqli_real_escape_string($con, $params->idTransaccion);
        $importe = mysqli_real_escape_string($con, $params->importe);
        $tipo = mysqli_real_escape_string($con, $params->tipo);
        //$pagoCompletado = mysqli_real_escape_string($con, $params->pagoCompletado);
        $fechaTransaccion = mysqli_real_escape_string($con, $params->fechaTransaccion);
        //$pagoManual = mysqli_real_escape_string($con, $params->pagoManual);

        $sql = "INSERT INTO pagos VALUES (NULL,'$idPersona', ' ','$importe','$tipo',1,'$fechaTransaccion',1)";
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
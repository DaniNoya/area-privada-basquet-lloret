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

        $response->conceptoDescuentos = array();

        $sql = "SELECT t.*,CONCAT((SELECT concepto FROM tipo_pago WHERE id = t.id),' ',(SELECT temporada FROM temporada WHERE id = (SELECT idTemporada FROM tipo_pago WHERE id = t.id))) AS concepto FROM tipo_pago t WHERE idTemporada = (SELECT MAX(id) FROM temporada) AND borrado = 0 ORDER BY id DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $userData['id'] = (int)$userData['id'];
            $response->conceptoDescuentos[] = $userData;
          }
        }
      break;
    default:
      break;
 };

 //error_log(print_r($response, TRUE));
 header('Content-Type: application/json');
 echo json_encode($response);
?>

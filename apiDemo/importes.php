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

        $response->importes = array();
        $sql = "SELECT i.* FROM importes i WHERE i.id IS NOT NULL AND idTemporada = (SELECT MAX(id) FROM temporada) ";
        switch ($metodoVisualizacion) {
            case "all":
              $sql .= "AND i.id IS NOT NULL ";
              break;
            case "descuento":
              $sql .= "AND i.esDescuento = 1 AND i.esPorcentaje = 0 ";
              break;
            case "porcentaje":
              $sql .= "AND i.esPorcentaje = 1 ";
              break;
            default:
              $sql .= "AND i.id IS NOT NULL ";
              break;
        }
        //$sql .= "ORDER BY d.idTemporada DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->importes[] = $userData;
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

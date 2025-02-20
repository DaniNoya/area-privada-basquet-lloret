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

      $params = json_decode($json);
      switch ($params->tipo){
        case 'sexo':
          $response->sexos = array();
          $sql = "SELECT * FROM sexo";
          if ($result = mysqli_query($con, $sql)) {
            while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
              $response->sexos[] = $userData;
            }
          }
          break;
        case 'categorias':
          $response->categorias = array();
          $sql = "SELECT * FROM categoria";
          if ($result = mysqli_query($con, $sql)) {
            while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
              $response->categorias[] = $userData;
            }
          }
          break;
        case 'competiciones':
          $response->competiciones = array();
          $sql = "SELECT * FROM competicion";
          if ($result = mysqli_query($con, $sql)) {
            while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
              $response->competiciones[] = $userData;
            }
          }
          break;
        case 'tipoCategorias':
          $response->tiposCategoria = array();
          $sql = "SELECT * FROM tipo_categoria";
          if ($result = mysqli_query($con, $sql)) {
            while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
              $response->tiposCategoria[] = $userData;
            }
          }
          break;
        case 'tipoPago':
          $response->tiposPago = array();
          //$sql = "SELECT * FROM tipo_pago";
          $sql = "SELECT t.*, CONCAT((SELECT concepto FROM tipo_pago WHERE id = t.id),' ',(SELECT temporada FROM temporada WHERE id = idTemporada)) AS concepto FROM tipo_pago t;";
          if ($result = mysqli_query($con, $sql)) {
            while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
              $response->tiposPago[] = $userData;
            }
          }
          break;
        default:
          break;
      }
      break;
    default:
      break;
  };

  //error_log(print_r($response->categorias, TRUE));
  header('Content-Type: application/json');
  echo json_encode($response);

?>

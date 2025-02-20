<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();

  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $response->niveles_formacion = array();
      $sql = "SELECT * FROM nivel_formacion";

      if ($result = mysqli_query($con, $sql)) {
        while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
          $response->niveles_formacion[] = $userData;
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

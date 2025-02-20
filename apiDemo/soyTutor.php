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

        $idUsuario = $params->idUsuario;

        $sql = "SELECT f.* FROM familiar f WHERE f.id='$idUsuario'";
        if ($result = mysqli_query($con, $sql)) {
            if ($result->num_rows > 0) {
                //$userData = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $response->soyTutor = true;
            } else {
                $response->soyTutor = false;
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
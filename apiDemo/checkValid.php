<?php
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header("Access-Control-Allow-Methods: *");

    require_once 'dbConnection.php';

    $response = new Result();
    $params = json_decode(file_get_contents("php://input"));

    if (isset($params->password) && !empty($params->password)){
      $password = $params->password;
      if($password == '1234'){
        $response->valido = true;
      } else {
        $response->valido = false;
      }
    } else{
      $response->valido = false;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
?>

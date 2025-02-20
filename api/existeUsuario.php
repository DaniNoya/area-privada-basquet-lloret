<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");
  
  require_once 'dbConnection.php';
  
  $con = returnConection();
  $response = new Result();

  $dni = $_POST['dni'];
  $sqlConsult = "SELECT * FROM usuario WHERE username = '$dni';";
  if ($result = mysqli_query($con, $sqlConsult)) {
    if ($result->num_rows > 0) {
        $response->resultat = "OK";
    } else {$response->resultat = "KO";}
  }
  header('Content-Type: application/json');
  echo json_encode($response);
?>
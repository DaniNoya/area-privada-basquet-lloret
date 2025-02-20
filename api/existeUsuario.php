<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");
  require_once 'dbConnection.php';
  $con = returnConection();
  $response = new Result();


  // Leer el cuerpo de la solicitud (POST) como JSON
$inputData = json_decode(file_get_contents('php://input'), true);

// Ahora puedes acceder al valor del "dni" desde el array decodificado
$dni = $inputData['dni'];
//$dni = $_POST['dni'];

if ($dni===""){
  $response->resultat="KO. DNI NULO";
}else{
  $sqlConsult = "SELECT * FROM usuario WHERE username = '$dni';";
  if ($result = mysqli_query($con, $sqlConsult)) {
    if ($result->num_rows > 0) {
        $response->resultat = "OK";
    } else {$response->resultat = "KO";}
  }

}

  

  header('Content-Type: application/json');
  echo json_encode($response);
?>
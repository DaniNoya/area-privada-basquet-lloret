<?php
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: POST");
header("Allow: POST");

require_once 'dbConnection.php';

$con = returnConection();
$response = new Result();

$playersDNI = $_POST['listOfPlayerDNIs'];
foreach($playersDNI as $key => $value) {
  $searchPersonID = "SELECT id FROM persona WHERE dni = '$value';";
  if ($resultSearchPersonID = mysqli_query($con, $searchPersonID)) {
    if ($resultSearchPersonID->num_rows > 0) {
      
      $personData = mysqli_fetch_array($resultSearchPersonID, MYSQLI_ASSOC);
      $personID = $personData['id'];
      $searchPlayerID = "SELECT idJugador FROM jugador_temporada WHERE idJugador = '$personID' AND idTipo = 4 AND idTemporada = 4;";
      if ($resultSearchPlayerID = mysqli_query($con, $searchPlayerID)) {
        if ($resultSearchPlayerID->num_rows > 0) {
        } else {
          unset($playersDNI[$key]);
        }
      }

    } else {
      unset($playersDNI[$key]);
    }
  }
}

if (count($playersDNI) > 0) {
  $response->resultat = "OK";
} else {
  $response->resultat = "KO";
}
$response->listPlayersDNI = $playersDNI;

echo json_encode($response);
?>

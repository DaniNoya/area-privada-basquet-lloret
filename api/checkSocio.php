<?php
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: POST");
header("Allow: POST");

require_once 'dbConnection.php';

$con = returnConection();
$response = new Result();

$personDNI = $_POST['dni'];
$searchSocioID = "SELECT s.id 
                  FROM persona p 
                      INNER JOIN socio s ON s.id_persona = p.id 
                      INNER JOIN socio_temporada st ON st.id_socio = s.id
                  WHERE p.dni = '$personDNI' AND st.id_temporada = (SELECT MAX(t.id) FROM temporada t);";
if ($resultSearchSocioID = mysqli_query($con, $searchSocioID)) {
  if ($resultSearchSocioID->num_rows > 0) {
    $socioData = mysqli_fetch_array($resultSearchSocioID, MYSQLI_ASSOC);
    $socioID = $socioData['id'];
    $response->resultat = "OK";
  } else {
    $response->resultat = "KO";
  }
}

echo json_encode($response);
?>

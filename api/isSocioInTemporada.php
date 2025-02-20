<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET");
header("Allow: GET");

require_once 'dbConnection.php';

$con = returnConection();
$response = new Result();

$json = json_encode($_GET);
$params = json_decode($json);

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $idUsuario = $params->idUsuario;

    $sql = "SELECT s.* FROM socio s 
            INNER JOIN socio_temporada st ON s.id = st.id_socio 
            WHERE s.id_persona = '$idUsuario' AND st.id_temporada = (SELECT MAX(t.id) FROM temporada t);";
    if ($result = mysqli_query($con, $sql)) {
      if ($result->num_rows > 0) {
        $response->isSocioInTemporada = true;
      } else {
        $response->isSocioInTemporada = false;
      }
    }
    break;
  default:
    break;
};

echo json_encode($response);
?>
<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, PUT");
header("Allow: GET, PUT");

require_once 'dbConnection.php';

$con = returnConection();
$response = new Result();

$json = json_encode($_GET);
$params = json_decode($json);

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $idUsuario = $params->idUsuario;

    // $sql = "SELECT p.*, s.* FROM persona p INNER JOIN socio s ON p.id = s.id_persona WHERE p.id = '$idUsuario'"; str_replace("-", "/", $socioData['temporada']);
    $sql = "SELECT p.*, s.*, (SELECT temporada FROM cblloretdb.temporada t WHERE t.id = st.id_temporada) as temporada 
            FROM cblloretdb.persona p 
            INNER JOIN cblloretdb.socio s ON p.id = s.id_persona 
            INNER JOIN cblloretdb.socio_temporada st ON s.id = st.id_socio
            WHERE p.id = '$idUsuario' AND st.id_temporada =(SELECT MAX(t.id) FROM temporada t);";
    if ($result = mysqli_query($con, $sql)) {
      $socioData = mysqli_fetch_array($result, MYSQLI_ASSOC);
      $socioData['partner_code'] = str_pad($socioData['id'], 4, "0", STR_PAD_LEFT);
      $firstYear = substr($socioData['temporada'], 2, 2);
      $secondYear = substr($socioData['temporada'], -2);
      $socioData['temporada'] = $firstYear." / ".$secondYear;
      $response->socio = $socioData;
    }
    break;
  default:
    break;
};

echo json_encode($response);
?>
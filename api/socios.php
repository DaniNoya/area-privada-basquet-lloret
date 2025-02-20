<?php
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, PUT, POST");
header("Allow: GET, PUT, POST");

require_once 'dbConnection.php';

$con = returnConection();
$response = new Result();

$json = json_encode($_GET);
$params = json_decode($json);

switch ($_SERVER['REQUEST_METHOD']) {

  case 'GET':
    $json = json_encode($_GET);
    $params = json_decode($json);
    
    $metodoVisualizacion = $params->metodoVisualizacion;
    $exclusiones = isset($params->exclusiones) ? $params->exclusiones : null;
    
    $response->socios = array();
    $sql = "SELECT p.*, s.*, (SELECT sexo FROM sexo WHERE id = p.id_sexo) as sexo FROM persona p INNER JOIN socio s ON p.id = s.id_persona ";

    switch ($metodoVisualizacion) {
      case "baja":
        $sql .= "WHERE s.baja = '1' ";
      break;

      case "alta":
        $sql .= "WHERE s.baja = '0' ";
      break;
      
      default:
      break;
    }

    if ($exclusiones != null){
      $sql .= "AND p.id NOT IN $exclusiones ";
    }

    // $sql .= "ORDER BY s.id_tipo_cargo ASC";

    if ($result = mysqli_query($con, $sql)) {
      while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $userData['partner_code'] = str_pad($userData['id'], 4, "0", STR_PAD_LEFT);
        $response->socios[] = $userData;
      }
    }
  break;

  case 'PUT':
    $params = json_decode(file_get_contents("php://input"));

    $nombre = mysqli_real_escape_string($con, $params->nombre);
    $primer_apellido = mysqli_real_escape_string($con, $params->primer_apellido);
    $segundo_apellido = isset($params->segundo_apellido) ? mysqli_real_escape_string($con, $params->segundo_apellido) : '';
    $dni = mysqli_real_escape_string($con, $params->dni);
    $fecha_nacimiento = $params->fecha_nacimiento;
    $direccion = mysqli_real_escape_string($con, $params->direccion);
    $codigo_postal = mysqli_real_escape_string($con, $params->codigo_postal);
    $localidad = mysqli_real_escape_string($con, $params->localidad);
    $telefono1 = mysqli_real_escape_string($con, $params->telefono1);
    $email = mysqli_real_escape_string($con, $params->email);
    $id_sexo = $params->id_sexo;
    
    if (isset($params->baja)) {
      $baja = $params->baja;

      if ($params->baja == '1') {
        $fecha_baja = $params->fecha_baja;
      } else {
        $fecha_baja = NULL;
      }
    } else {
      $baja = '0';
      $fecha_baja = NULL;
    }

    $sql = "UPDATE persona p INNER JOIN socio s ON p.id = s.id_persona SET nombre = '$nombre', primer_apellido='$primer_apellido', segundo_apellido='$segundo_apellido', dni='$dni', fecha_nacimiento='$fecha_nacimiento', " .
          "direccion='$direccion', codigo_postal='$codigo_postal', localidad='$localidad', telefono1='$telefono1', email='$email', " .
          "id_sexo=$id_sexo, s.baja = $baja, s.fecha_baja = '$fecha_baja' WHERE p.id=$params->id_persona";
    
    if (mysqli_query($con, $sql)) {
      return http_response_code(200);
    } else {
      return http_response_code(422);
    }
  break;

  case 'POST':
    $params = json_decode(file_get_contents("php://input"));

    $nombre = mysqli_real_escape_string($con, $params->nombre);
    $primer_apellido = mysqli_real_escape_string($con, $params->primer_apellido);
    $segundo_apellido = isset($params->segundo_apellido) ? mysqli_real_escape_string($con, $params->segundo_apellido) : '';
    $dni = mysqli_real_escape_string($con, $params->dni);
    $fecha_nacimiento = $params->fecha_nacimiento;
    $direccion = mysqli_real_escape_string($con, $params->direccion);
    $codigo_postal = mysqli_real_escape_string($con, $params->codigo_postal);
    $localidad = mysqli_real_escape_string($con, $params->localidad);
    $telefono1 = mysqli_real_escape_string($con, $params->telefono1);
    $email = mysqli_real_escape_string($con, $params->email);
    $id_sexo = $params->id_sexo;
    $baja = '0';
    $fecha_baja = 'NULL';

    $searchPersonID = "SELECT id FROM persona WHERE dni = '$dni';";
    if ($resultSearchPersonID = mysqli_query($con, $searchPersonID)) {
      if ($resultSearchPersonID->num_rows > 0) {

        $personData = mysqli_fetch_array($resultSearchPersonID, MYSQLI_ASSOC);
        $personID = $personData['id'];
        $insertSocio = "INSERT INTO socio VALUES(NULL, $personID, 0, NULL)";
        if (mysqli_query($con, $insertSocio)) {
          return http_response_code(200);
        } else {
          return http_response_code(422);
        }

      } else {

        $insertPerson = "INSERT INTO persona VALUES (NULL,NULL,'$nombre', '$primer_apellido','$segundo_apellido','$dni','$fecha_nacimiento','$direccion','$codigo_postal','$localidad','$telefono1','$email',$id_sexo)";
        if (mysqli_query($con, $insertPerson)) {
          $insertSocio = "INSERT INTO socio VALUES(NULL, " . mysqli_insert_id($con) . ", 0, NULL)";
          if (mysqli_query($con, $insertSocio)) {
            return http_response_code(200);
          } else {
            return http_response_code(422);
          }
        } else {
          return http_response_code(422);
        }

      }
    }
  break;

  default:
  break;
};

echo json_encode($response);
?>

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

        $sql = "SELECT p.*,(SELECT tarjetaSanitaria FROM jugador WHERE id = '$idUsuario') as tarjetaSanitaria, (SELECT sexo FROM sexo WHERE id = p.id_sexo) as sexo FROM persona p WHERE p.id='$idUsuario'";
        if ($result = mysqli_query($con, $sql)) {
            $userData = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $response->persona = $userData;
        }
      break;
    case 'PUT':
        $params = json_decode(file_get_contents("php://input"));

        $id = mysqli_real_escape_string($con, $params->id);
        $nombre = mysqli_real_escape_string($con, $params->nombre);
        $primer_apellido = mysqli_real_escape_string($con, $params->primer_apellido);
        $segundo_apellido = isset($params->segundo_apellido) ? mysqli_real_escape_string($con, $params->segundo_apellido) : '';
        $dni = mysqli_real_escape_string($con, $params->dni);
        $fecha_nacimiento = $params->fecha_nacimiento;
        $direccion = mysqli_real_escape_string($con, $params->direccion);
        $codigo_postal = mysqli_real_escape_string($con, $params->codigo_postal);
        $localidad = mysqli_real_escape_string($con, $params->localidad);
        $telefono1 = mysqli_real_escape_string($con, $params->telefono1);
        $telefono2 = isset($params->telefono2) ? mysqli_real_escape_string($con, $params->telefono2) : '';
        $email = mysqli_real_escape_string($con, $params->email);
        $observaciones = isset($params->observaciones) ? mysqli_real_escape_string($con, $params->observaciones) : '';
        $id_sexo = $params->id_sexo;
        $tarjetaSanitaria = isset($params->tarjetaSanitaria) ? mysqli_real_escape_string($con, $params->tarjetaSanitaria) : '';

        $sql = "UPDATE persona p INNER JOIN jugador j ON p.id = j.id SET nombre = '$nombre', primer_apellido='$primer_apellido', segundo_apellido='$segundo_apellido', dni='$dni', fecha_nacimiento='$fecha_nacimiento', " .
          "direccion='$direccion', codigo_postal='$codigo_postal', localidad='$localidad', telefono1='$telefono1', telefono2='$telefono2', email='$email',observaciones='$observaciones', " .
          "id_sexo = $id_sexo, j.tarjetaSanitaria = '$tarjetaSanitaria' WHERE p.id='$id'";
        if(mysqli_query($con, $sql)){
          return http_response_code(200);
        } else {
          return http_response_code(422);
        }
      break;
    default:
      break;
 };

 //error_log(print_r($response, TRUE));
 header('Content-Type: application/json');
 echo json_encode($response);
?>
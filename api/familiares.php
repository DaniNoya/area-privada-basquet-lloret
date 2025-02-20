<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();

  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $json = json_encode($_GET);

      $params = json_decode($json);

      $metodoVisualizacion = $params->metodoVisualizacion;
      $sinJugadoresAsignados = $params->sinJugadoresAsignados;
      $exclusiones = isset($params->exclusiones) ? $params->exclusiones : null;

      $response->familiares = array();
      $sql = "SELECT p.*, f.* FROM persona p INNER JOIN familiar f ON p.id = f.id WHERE p.id IS NOT NULL ";
      if ($sinJugadoresAsignados == "true") {
        $sql .= "AND (SELECT count(*) FROM familiar_jugador WHERE id_familiar = p.id) = 0 ";
      }
      switch ($metodoVisualizacion) {
        case "baja":
          $sql .= "AND f.baja = '1' ";
          break;
        case "alta":
          $sql .= "AND f.baja = '0' ";
          break;
        default:
          $sql .= "AND f.baja IS NOT NULL ";
          break;
      }
      if ($exclusiones != null){
        $sql .= "AND p.id NOT IN $exclusiones ";
      }
      $sql .= "ORDER BY p.primer_apellido ASC";
      if ($result = mysqli_query($con, $sql)) {
        while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
          $response->familiares[] = $userData;
        }
      }

      break;
    case 'PUT':
      $params = json_decode(file_get_contents("php://input"));

      if (isset($params->idAssign)) {
        $sql = "INSERT INTO familiar VALUES ($params->idAssign,'0',NULL)";
      } else {
        $nombre = mysqli_real_escape_string($con, $params->nombre);
        $primer_apellido = mysqli_real_escape_string($con, $params->primer_apellido);
        $segundo_apellido = isset($params->segundo_apellido) ? mysqli_real_escape_string($con, $params->segundo_apellido) : '';
        $dni = mysqli_real_escape_string($con, $params->dni);
        $fecha_nacimiento = isset($params->fecha_nacimiento) ? $params->fecha_nacimiento : '1900-01-01';
        $direccion = mysqli_real_escape_string($con, $params->direccion);
        $codigo_postal = mysqli_real_escape_string($con, $params->codigo_postal);
        $localidad = mysqli_real_escape_string($con, $params->localidad);
        $telefono1 = mysqli_real_escape_string($con, $params->telefono1);
        $telefono2 = isset($params->telefono2) ? mysqli_real_escape_string($con, $params->telefono2) : '';
        $email = mysqli_real_escape_string($con, $params->email);
        $observaciones = isset($params->observaciones) ? mysqli_real_escape_string($con, $params->observaciones) : '';
        $id_sexo = $params->id_sexo;
        if (isset($params->baja)) {
          $baja = $params->baja;
          if ($params->baja == '1') {
            $fecha_baja = $params->fecha_baja;
          } else{
            $fecha_baja = NULL;
          }
        } else {
          $baja = '0';
          $fecha_baja = NULL;
        }

        $sql = "UPDATE persona p INNER JOIN familiar f ON p.id = f.id SET nombre = '$nombre', primer_apellido='$primer_apellido', segundo_apellido='$segundo_apellido', dni='$dni', fecha_nacimiento='$fecha_nacimiento', " .
            "direccion='$direccion', codigo_postal='$codigo_postal', localidad='$localidad', telefono1='$telefono1', telefono2='$telefono2', email='$email',observaciones='$observaciones', " .
            "id_sexo = $id_sexo, f.baja = $baja, f.fecha_baja = '$fecha_baja' WHERE p.id=$params->id";
      }
      if(mysqli_query($con, $sql))
      {
        return http_response_code(200);
      }
      else
      {
        return http_response_code(422);
      }

      break;
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));

      $nombre = mysqli_real_escape_string($con, $params->nombre);
      $primer_apellido = mysqli_real_escape_string($con, $params->primer_apellido);
      $segundo_apellido = isset($params->segundo_apellido) ? mysqli_real_escape_string($con, $params->segundo_apellido) : '';
      $dni = mysqli_real_escape_string($con, $params->dni);
      $fecha_nacimiento = isset($params->fecha_nacimiento) ? $params->fecha_nacimiento : '1900-01-01';
      $direccion = mysqli_real_escape_string($con, $params->direccion);
      $codigo_postal = mysqli_real_escape_string($con, $params->codigo_postal);
      $localidad = mysqli_real_escape_string($con, $params->localidad);
      $telefono1 = mysqli_real_escape_string($con, $params->telefono1);
      $telefono2 = isset($params->telefono2) ? mysqli_real_escape_string($con, $params->telefono2) : '';
      $email = mysqli_real_escape_string($con, $params->email);
      $observaciones = isset($params->observaciones) ? mysqli_real_escape_string($con, $params->observaciones) : '';
      $id_sexo = $params->id_sexo;
      $baja = '0';
      $fecha_baja = 'NULL';

      $sql = "INSERT INTO persona VALUES (NULL,NULL,'$nombre', '$primer_apellido','$segundo_apellido','$dni','$fecha_nacimiento','$direccion','$codigo_postal','$localidad',".
          "'$telefono1','$telefono2','$email','$observaciones',$id_sexo)";
      if(mysqli_query($con, $sql))
      {
        $ultimId = mysqli_insert_id($con);
        $sql2 = "INSERT INTO familiar VALUES (" . mysqli_insert_id($con) . ",0,NULL)";
        if(mysqli_query($con, $sql2))
        {
          $response->ultimId = $ultimId;
        }else
        {
          return http_response_code(422);
        }
      }else
      {
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

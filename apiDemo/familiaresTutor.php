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

      $idUsuario = $params->idUsuario;
      $metodoVisualizacion = $params->metodoVisualizacion;
      $exclusiones = isset($params->exclusiones) ? $params->exclusiones : null;

      $response->familiares = array();
      $sql = "SELECT p.*, j.*, (SELECT sexo FROM sexo WHERE id = p.id_sexo) as sexo FROM persona p INNER JOIN jugador j ON p.id = j.id WHERE p.id IN (SELECT id_jugador FROM familiar_jugador where id_familiar='$idUsuario') ";
      switch ($metodoVisualizacion) {
        case "baja":
          $sql .= "AND j.baja = '1' ";
          break;
        case "alta":
          $sql .= "AND j.baja = '0' ";
          break;
        default:
          $sql .= "AND j.baja IS NOT NULL ";
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
      $tarjetaSanitaria = isset($params->tarjetaSanitaria) ? mysqli_real_escape_string($con, $params->tarjetaSanitaria) : '';

      $sql = "UPDATE persona p INNER JOIN jugador j ON p.id = j.id SET nombre = '$nombre', primer_apellido='$primer_apellido', segundo_apellido='$segundo_apellido', dni='$dni', fecha_nacimiento='$fecha_nacimiento', " .
      "direccion='$direccion', codigo_postal='$codigo_postal', localidad='$localidad', telefono1='$telefono1', telefono2='$telefono2', email='$email',observaciones='$observaciones', " .
      "id_sexo = $id_sexo, j.baja = $baja, j.fecha_baja = '$fecha_baja', j.tarjetaSanitaria = '$tarjetaSanitaria' WHERE p.id=$params->id";
      if(mysqli_query($con, $sql)){
        return http_response_code(200);
      }
      else{
        return http_response_code(422);
      }

      break;
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));

      $idTutor = mysqli_real_escape_string($con, $params->idTutor);
      $tipoParentesco = mysqli_real_escape_string($con, $params->TipoParentesco);
      $jugador = $params->Jugador;

      $nombre = mysqli_real_escape_string($con, $jugador->nombre);
      $primer_apellido = mysqli_real_escape_string($con, $jugador->primer_apellido);
      $segundo_apellido = isset($jugador->segundo_apellido) ? mysqli_real_escape_string($con, $jugador->segundo_apellido) : '';
      $dni = mysqli_real_escape_string($con, $jugador->dni);
      $fecha_nacimiento = isset($jugador->fecha_nacimiento) ? $jugador->fecha_nacimiento : '1900-01-01';
      $direccion = mysqli_real_escape_string($con, $jugador->direccion);
      $codigo_postal = mysqli_real_escape_string($con, $jugador->codigo_postal);
      $localidad = mysqli_real_escape_string($con, $jugador->localidad);
      $telefono1 = mysqli_real_escape_string($con, $jugador->telefono1);
      $telefono2 = isset($jugador->telefono2) ? mysqli_real_escape_string($con, $jugador->telefono2) : '';
      $email = mysqli_real_escape_string($con, $jugador->email);
      $observaciones = isset($jugador->observaciones) ? mysqli_real_escape_string($con, $jugador->observaciones) : '';
      $id_sexo = $jugador->id_sexo;
      $baja = '0';
      $fecha_baja = 'NULL';
      $tarjetaSanitaria = isset($jugador->tarjetaSanitaria) ? mysqli_real_escape_string($con, $jugador->tarjetaSanitaria) : '';

      $sql = "INSERT INTO persona VALUES (NULL,NULL,'$nombre', '$primer_apellido','$segundo_apellido','$dni','$fecha_nacimiento','$direccion','$codigo_postal','$localidad',".
      "'$telefono1','$telefono2','$email','$observaciones',$id_sexo)";
      error_log($sql);
      if(mysqli_query($con, $sql)){
        $idPersona = mysqli_insert_id($con);
        $sql2 = "INSERT INTO jugador VALUES (" . $idPersona . ",0,NULL,'$tarjetaSanitaria')";
        error_log($sql2);
        if(mysqli_query($con, $sql2)) {
          $sql3 = "INSERT INTO familiar_jugador VALUES (" . $idTutor . "," . $idPersona . "," . $tipoParentesco . ")";
          error_log($sql3);
          if(mysqli_query($con, $sql3)) {
            return http_response_code(200);
          } else {
            return http_response_code(422);
          }
        }else{
          return http_response_code(422);
        }
      }else{
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

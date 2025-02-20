<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      //$params = json_decode(json_encode($_GET));
      $response->partidos = array();
      $response->partidos['partidosTemporada'] = array();
      $response->partidos['partidosAmistosos'] = array();

      $sqlPartidos = "SELECT p.id_partido, p.idEquipo, p.id_equipo_local, p.nombre_equipo_local, p.nombre_equipo_visitante, p.fecha_partido, p.hora_partido, p.iptv_url, CONCAT(c.categoria,' ',tc.tipo,' ',e.descripcion) AS nombreEquipoLocal ".
      "FROM partidos p ".
      "INNER JOIN equipo e ON p.idEquipo= e.id ".
      "INNER JOIN categoria c ON e.id_categoria = c.id ".
      "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
      "WHERE p.id_equipo_local = e.id_fcbq AND borrado = '0' ".
      "AND p.fecha_partido >= CURRENT_DATE() ".
      "ORDER BY fecha_partido ASC;";
      if ($resultPartidos = mysqli_query($con, $sqlPartidos)) {
        while ($partidoData = mysqli_fetch_array($resultPartidos, MYSQLI_ASSOC)) {
          //$partidoData['fecha_partido'] = date('d-m-Y', strtotime($partidoData['fecha_partido']));

          if (strpos($partidoData['id_partido'], "a") === 0){
            $response->partidos['partidosAmistosos'][] = $partidoData;
          } else {
            $response->partidos['partidosTemporada'][] = $partidoData;
          }
        }
      }

      $response->equipos = array();

      $sqlEquipos = "SELECT e.*, CONCAT(c.categoria,' ',tc.tipo,' ',e.descripcion) AS nombreEquipo FROM equipo e ".
      "INNER JOIN categoria c ON e.id_categoria = c.id ".
      "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
      "WHERE e.id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1) ".
      "ORDER BY c.id ASC;";
      if ($resultEquipos = mysqli_query($con, $sqlEquipos)) {
        while ($equipoData = mysqli_fetch_array($resultEquipos, MYSQLI_ASSOC)) {
          $response->equipos[] = $equipoData;
        }
      }
      break;
    case 'PUT':
      $params = json_decode(file_get_contents("php://input"));

      $id_partido = mysqli_real_escape_string($con, $params->id_partido);
      $idEquipo = mysqli_real_escape_string($con, $params->idEquipo);
      $nombre_equipo_local = mysqli_real_escape_string($con, $params->nombre_equipo_local);
      $nombre_equipo_visitante = mysqli_real_escape_string($con, $params->nombre_equipo_visitante);
      $fecha_partido = mysqli_real_escape_string($con, $params->fecha_partido);
      $hora_partido = mysqli_real_escape_string($con, $params->hora_partido);
      $iptv_url = isset($params->iptv_url) ? mysqli_real_escape_string($con, $params->iptv_url) : '';
      $borrado = mysqli_real_escape_string($con, $params->borrado);

      $sql = "UPDATE partidos p SET idEquipo = '$idEquipo', nombre_equipo_local = '$nombre_equipo_local', nombre_equipo_visitante='$nombre_equipo_visitante', fecha_partido='$fecha_partido', hora_partido='$hora_partido', iptv_url='$iptv_url', borrado='$borrado' WHERE p.id_partido='$id_partido';";
      if(mysqli_query($con, $sql)){
        return http_response_code(200);
      } else {
        return http_response_code(422);
      }
      break;
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));

      if (empty($params->id_partido)) {
        $sqlNextId = "SELECT CONCAT('a',LPAD(IFNULL(MAX(CONVERT(SUBSTR(id_partido,2), UNSIGNED)),0) + 1,7,'0')) as nextId FROM partidos WHERE id_partido LIKE 'a%';";
        if($result = mysqli_query($con, $sqlNextId)){
          $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
          $id_partido = $data['nextId'];
        } else {
          return http_response_code(422);
        }
      } else {
        $id_partido = mysqli_real_escape_string($con, $params->id_partido);
      }

      $idEquipo = mysqli_real_escape_string($con, $params->idEquipo);
      $sqlEquipo = "SELECT e.*, CONCAT(c.categoria,' ',tc.tipo,' ',e.descripcion) AS nombreEquipo FROM equipo e ".
      "INNER JOIN categoria c ON e.id_categoria = c.id ".
      "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
      "WHERE e.id = $idEquipo;";
      if($resultEquipo = mysqli_query($con, $sqlEquipo)){
        $dataEquipo = mysqli_fetch_array($resultEquipo, MYSQLI_ASSOC);
        $id_equipo_local = $dataEquipo['id_fcbq'];
        $nombre_equipo_local = $dataEquipo['nombreEquipo'];
      } else {
        return http_response_code(422);
      }

      $nombre_equipo_visitante = mysqli_real_escape_string($con, $params->nombre_equipo_visitante);
      $fecha_partido = mysqli_real_escape_string($con, $params->fecha_partido);
      $hora_partido = mysqli_real_escape_string($con, $params->hora_partido);
      $iptv_url = isset($params->iptv_url) ? mysqli_real_escape_string($con, $params->iptv_url) : '';

      $sql = "INSERT INTO partidos (id_partido,idEquipo,id_equipo_local,nombre_equipo_local,nombre_equipo_visitante,fecha_partido,hora_partido,iptv_url) VALUES ('$id_partido','$idEquipo','$id_equipo_local','$nombre_equipo_local','$nombre_equipo_visitante','$fecha_partido','$hora_partido','$iptv_url');";
      if(mysqli_query($con, $sql)){
        return http_response_code(200);
      } else {
        return http_response_code(422);
      }
      break;
    default:
      break;
  }
  header('Content-Type: application/json');
  echo json_encode($response);
?>

<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $params = json_decode(json_encode($_GET));

      if (isset($params->equipos)) {
        $response->equipos = array();

        $sqlEquipos = "SELECT e.*, c.categoria, tc.tipo as tipoCategoria FROM equipo e ".
        "INNER JOIN categoria c ON e.id_categoria = c.id ".
        "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
        "WHERE e.id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1) ".
        "AND e.id IN(SELECT p.idEquipo FROM partidos p WHERE p.fecha_partido >= CURRENT_DATE()) ".
        "ORDER BY c.id ASC;";
        if ($resultEquipos = mysqli_query($con, $sqlEquipos)) {
          while ($equipoData = mysqli_fetch_array($resultEquipos, MYSQLI_ASSOC)) {
            $response->equipos[] = $equipoData;
          }
        }
      } else if (isset($params->partidos)) {
        $response->partidos = array();
        $id_equipo = $params->idSelectedTeam;

        $sqlFCBQ = "SELECT e.id_fcbq FROM equipo e WHERE e.id = $id_equipo;";
        if ($resultFCBQ = mysqli_query($con, $sqlFCBQ)) {
          $id_fcbqTeam = mysqli_fetch_array($resultFCBQ, MYSQLI_ASSOC);

          $sqlPartidos = "SELECT p.id_partido, p.nombre_equipo_local, p.nombre_equipo_visitante, p.fecha_partido, p.hora_partido, p.iptv_url FROM partidos p WHERE p.idEquipo = $id_equipo AND p.id_equipo_local = $id_fcbqTeam[id_fcbq] AND p.fecha_partido >= CURRENT_DATE() ORDER BY fecha_partido ASC;";
          if ($resultPartidos = mysqli_query($con, $sqlPartidos)) {
            while ($partidoData = mysqli_fetch_array($resultPartidos, MYSQLI_ASSOC)) {
              $partidoData['fecha_partido'] = date('d-m-Y', strtotime($partidoData['fecha_partido']));
              $response->partidos[] = $partidoData;
            }
          }
        }
      }
      break;
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));

      if(isset($params->assistenciaPublic)){
        $datosPersona = $params->datos;

        foreach($datosPersona as $k => $value){
          if ($k == 'form_fields[dniPersona]') {$dniPerson =  mysqli_real_escape_string($con,$value);}
          if ($k == 'form_fields[nomPersona]') {$namePerson = mysqli_real_escape_string($con,$value);}
          if ($k == 'form_fields[primerCognomPersona]') {$firstSurnamePerson = mysqli_real_escape_string($con,$value);}
          if ($k == 'form_fields[segonCognomPersona]') {$secondSurnamePerson = mysqli_real_escape_string($con,$value);}
          if ($k == 'form_fields[telefonPersona]') {$phonePerson = $value;}
          if ($k == 'form_fields[emailPersona]') {$emailPerson = mysqli_real_escape_string($con,$value);}
          if ($k == 'id_partido') {$idPartido = $value;}
        }
        $lowerCaseDNI = strtolower($dniPerson);

        $sqlInsertPublico = "INSERT INTO asistencia VALUES ($idPartido,'$lowerCaseDNI','$namePerson','$firstSurnamePerson','$secondSurnamePerson',$phonePerson,'$emailPerson') ON DUPLICATE KEY UPDATE nombre='$namePerson',primer_apellido='$firstSurnamePerson',segundo_apellido='$secondSurnamePerson',telefono=$phonePerson,email='$emailPerson';";
        if (mysqli_query($con, $sqlInsertPublico)) {
          $response->resultat = "INSERT_OK";
          $response->causa = $sqlInsertPublico;
        } else {
          $response->resultat = "INSERT_KO";
          $response->causa = mysqli_error($con);
        }
      }
      break;
    case 'PUT':
      break;
    default:
      break;
  }
  header('Content-Type: application/json');
  echo json_encode($response);
?>

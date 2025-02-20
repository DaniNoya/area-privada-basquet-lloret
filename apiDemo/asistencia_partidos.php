<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';
  require_once '../libs/simplexlsx/SimpleXLSXGen.php';

  $con = returnConection();
  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
      case 'GET':
          // Extraer fechas del fin de semana.
          $saturday = new Datetime();
          $saturday->add(new DateInterval('P' . (6 - date('w')) . 'D'));
          $dateSaturday = $saturday->format('Y-m-d');

          $sunday = clone $saturday;
          $sunday->add(new DateInterval('P1D'));
          $dateSunday = $sunday->format('Y-m-d');

          // Eliminar ficheros del directorio.
          $files = glob('../excelsEquipos/partidos/*');
          foreach($files as $file){
            if(is_file($file)){
              unlink($file);
            }
          }

          $sqlUpcomingMatches = "SELECT p.*, CONCAT(c.categoria,' ',tc.tipo,' ',e.descripcion,'(',p.fecha_partido,')') AS equipo, e.id_fcbq ".
          "FROM partidos p ".
          "INNER JOIN equipo e ON p.idEquipo= e.id ".
          "INNER JOIN categoria c ON e.id_categoria = c.id ".
          "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
          "WHERE e.id_fcbq = p.id_equipo_local AND p.fecha_partido = '$dateSaturday' OR p.fecha_partido = '$dateSunday' ".
          "GROUP BY idEquipo;";
          if ($resultUpcomingMatches = mysqli_query($con, $sqlUpcomingMatches)) {
            $attendancePublic = array();
            //$xlsx = new SimpleXLSXGen();
            while ($gameData = mysqli_fetch_array($resultUpcomingMatches, MYSQLI_ASSOC)) {

              $idGame = $gameData['id_partido'];
              $idTeam = $gameData['idEquipo'];
              $sheetName = $gameData['equipo'];
              $sheetTitle = "$gameData[nombre_equipo_local] VS $gameData[nombre_equipo_visitante] ($gameData[fecha_partido] / $gameData[hora_partido])";
              $fileName = "$sheetName.xlsx";
              //$upcomingMatches[] = [$sheetName,$sheetTitle];

              $sqlAttendancePublic = "SELECT * FROM asistencia WHERE idPartido = $idGame;";
              if ($resultAttendancePublic = mysqli_query($con, $sqlAttendancePublic)) {
                $attendancePublic = [
                  ['<b>Nombre</b>', '<b>Primer Apellido</b>', '<b>Segundo Apellido</b>', '<b>DNI</b>', '<b>Telefono</b>', '<b>Correo</b>']
                ];
                while ($publicData = mysqli_fetch_array($resultAttendancePublic, MYSQLI_ASSOC)) {

                  $dni = $publicData['dni'];
                  $nombre = $publicData['nombre'];
                  $primerApellido = $publicData['primer_apellido'];
                  $segundoApellido = $publicData['segundo_apellido'];
                  $telefono = $publicData['telefono'];
                  $correo = $publicData['email'];

                  $attendancePublic[] = [$nombre, $primerApellido, $segundoApellido, $dni, $telefono, $correo];
                }
                //$xlsx->addSheet($attendancePublic, $sheetName);
                $xlsx = SimpleXLSXGen::fromArray($attendancePublic);
                $xlsx->saveAs('../excelsEquipos/partidos/'.$fileName);
              }
            }
            $zip = new ZipArchive();

            $nombreArchivoZip = "partidos(".$dateSaturday.")_(".$dateSunday.").zip";
            $rutaArchivoZip = "../excelsEquipos/partidos/".$nombreArchivoZip;

            if (!$zip->open($rutaArchivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
              exit("Error abriendo ZIP en $rutaArchivoZip");
            }

            $files = glob('../excelsEquipos/partidos/*.xlsx');
            foreach($files as $file){
              if(is_file($file)){
                $zip->addFile($file,substr($file,26));
              }
            }

            $resultado = $zip->close();
            if ($resultado) {
              $response->resultat = 'https://areaprivada.basquetlloret.com/excelsEquipos/partidos/'.$nombreArchivoZip;
            } else {
              $response->causa = "Error creando archivo";
            }
          }
          break;
      default:
          break;
  }

  header('Content-Type: application/json');
  echo json_encode($response);
?>
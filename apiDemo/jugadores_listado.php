<?php
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET");
header("Allow: GET");

require_once 'dbConnection.php';
require_once '../libs/simplexlsx/SimpleXLSXGen.php';

$conn = returnConection();
$response = new Result();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $playersList = array();

    $sqlPlayers = "SELECT (SELECT t.temporada FROM temporada t WHERE id = 5) AS lastSeason, p.dni,
                           CONCAT(p.nombre, ' ', p.primer_apellido, ' ', p.segundo_apellido) AS nombre, j.numero_dorsal, j.nombre_dorsal, j.talla_ropa_juego, j.talla_camiseta_tecnica_escoleta
                    FROM persona p
                    INNER JOIN jugador j ON p.id = j.id INNER JOIN jugador_temporada jt ON j.id = jt.idJugador
                    WHERE jt.idTemporada = 5 AND jt.idTipo = 6;";

    /*if ($_GET['paymentType'] == "all") {
      $sqlPlayers .= "WHERE pagoManual = 1 OR pagoCompletado = 1 ";
    } else {
      $sqlPlayers .= "WHERE tipo_pago IN (SELECT id FROM tipo_pago WHERE id = '".$_GET['paymentType']."' AND idTemporada = (SELECT MAX(id) FROM temporada))
                       AND pagoManual = 1
                       OR tipo_pago IN (SELECT id FROM tipo_pago WHERE id = '".$_GET['paymentType']."' AND idTemporada = (SELECT MAX(id) FROM temporada))
                            AND pagoCompletado = 1 ";
    }
    $sqlPlayers .= "ORDER BY paymentType;";*/

    if ($resultPlayers = mysqli_query($conn, $sqlPlayers)) {
      $playersList = [['<b>DNI</b>', '<b>Nombre</b>', '<b>Numero dorsal</b>', '<b>Nombre dorsal</b>', '<b>Talla camiseta 2ª equipación</b>', '<b>Talla camiseta tecnica escoleta</b>']];
      $last_season = "";
      while ($playerData = mysqli_fetch_array($resultPlayers, MYSQLI_ASSOC)) {
        
        $lastSeason = $playerData['lastSeason'];
        $dni         = $playerData['dni'];
        $nombre      = $playerData['nombre'];
        $numeroDorsal = $playerData['numero_dorsal'];
        $nombreDorsal = $playerData['nombre_dorsal'];
        $tallaRopaJuego = $playerData['talla_ropa_juego'];
        $tallaCamisetaTecnicaEscoleta = $playerData['talla_camiseta_tecnica_escoleta'];
        
        $playersList[] = [$dni, $nombre, $numeroDorsal, $nombreDorsal, $tallaRopaJuego, $tallaCamisetaTecnicaEscoleta];
        $last_season = $lastSeason;
      }

      $xlsx = SimpleXLSXGen::fromArray($playersList);
      $last_season = str_replace(" ", "", $last_season);
      $xlsx->saveAs('../excelsJugadores/jugadores_temporada_'.$last_season.'.xlsx');
      $response->resultat = 'https://areaprivada.basquetlloret.com/excelsJugadores/jugadores_temporada_'.$last_season.'.xlsx';
    }
    break;
  default:
    break;
}

echo json_encode($response);
?>
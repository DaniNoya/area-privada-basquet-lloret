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
            /*$json = json_encode($_GET);
            $params = json_decode($json);*/
            //$response->equipos = array();
            $equiposList = array();

            $sqlPlayers = "SELECT CONCAT((SELECT categoria FROM categoria WHERE id = e.id_categoria),' ',(SELECT tipo FROM tipo_categoria WHERE id = e.id_tipo_categoria),' ',e.descripcion) AS equipo,
                           (SELECT competicio FROM competicion WHERE id = e.id_competicion) AS competicion,
                           p.dni, CONCAT(p.nombre, ' ', p.primer_apellido, ' ', p.segundo_apellido) AS nombre,
                           (SELECT jt.quota FROM jugador_temporada jt WHERE jt.idJugador = p.id AND jt.idTipo = (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular' AND idTemporada = (SELECT MAX(id) FROM temporada))) AS total,
                           (SELECT IFNULL(SUM(importe),0) FROM movimientos WHERE dniJugador = p.dni AND pagoCompletado = 1 AND tipo_pago = (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular' AND idTemporada = (SELECT MAX(id) FROM temporada))) as pagado
                           FROM equipos_jugadores ej 
                           INNER JOIN persona p ON ej.id_jugador = p.id
                           INNER JOIN equipo e ON ej.id_equipo = e.id
                           WHERE e.id_temporada = (SELECT MAX(id) FROM temporada)
                           ORDER BY e.nacidos_desde_anyo DESC, e.nacidos_hasta_anyo ASC;";

            if ($resultPlayers = mysqli_query($con, $sqlPlayers)) {
                $equiposList = [
                                    ['<b>Equipo</b>', '<b>Competición</b>', '<b>DNI</b>', '<b>Nombre</b>', '<b>Cuota</b>', '<b>Pagado</b>', '<b>Restante</b>']
                                  ];
                while ($playersData = mysqli_fetch_array($resultPlayers, MYSQLI_ASSOC)) {

                    $equipo = $playersData['equipo'];
                    $competicion = $playersData['competicion'];

                    $dni = $playersData['dni'];
                    $nombre = $playersData['nombre'];
                    $total = (float)$playersData['total'];
                    $pagado = (float)$playersData['pagado'];
                    $restante = ((float)$playersData['total'] - (float)$playersData['pagado']);

                    $equiposList[] = [$equipo, $competicion, $dni, $nombre, $total, $pagado, $restante];
                }
                //print_r($equiposList);

                $xlsx = SimpleXLSXGen::fromArray($equiposList);
                $xlsx->saveAs('../excelsEquipos/equipos_club.xlsx');

                $response->resultat = 'https://areaprivada.basquetlloret.com/excelsEquipos/equipos_club.xlsx';
            }
            break;
        default:
            break;
    }

    //error_log(print_r($response->tipos_parentesco, TRUE));
    header('Content-Type: application/json');
    echo json_encode($response);
?>
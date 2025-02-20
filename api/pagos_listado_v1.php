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
    $paymentList = array();

    $sqlPayments = "SELECT (SELECT concepto FROM tipo_pago WHERE id = m.tipo_pago) as paymentType, dni,
                          CONCAT(p.nombre, ' ', p.primer_apellido, ' ', p.segundo_apellido) AS nombre,
                          (SELECT jt.quota FROM jugador_temporada jt WHERE jt.idJugador = p.id AND jt.idTipo = (SELECT id FROM tipo_pago WHERE id = m.tipo_pago)) AS total,
                          (SELECT IFNULL(SUM(importe),0) FROM movimientos WHERE dniJugador = p.dni AND pagoCompletado = 1 AND tipo_pago = (SELECT id FROM tipo_pago WHERE id = m.tipo_pago)) as pagado
                  FROM movimientos m
                  INNER JOIN persona p ON m.dniJugador = p.dni ";

    if ($_GET['paymentType'] == "all") {
      $sqlPayments .= "WHERE pagoManual = 1 OR pagoCompletado = 1 ";
    } else {
      $sqlPayments .= "WHERE tipo_pago IN (SELECT id FROM tipo_pago WHERE id = '".$_GET['paymentType']."')
                       AND pagoManual = 1
                       OR tipo_pago IN (SELECT id FROM tipo_pago WHERE id = '".$_GET['paymentType']."')
                            AND pagoCompletado = 1 ";
    }
    $sqlPayments .= "ORDER BY paymentType;";

    if ($resultPayments = mysqli_query($conn, $sqlPayments)) {
      $paymentList = [['<b>Equipo</b>', '<b>DNI</b>', '<b>Nombre</b>', '<b>Cuota</b>', '<b>Pagado</b>', '<b>Restante</b>']];
      $paymentName = "";
      while ($paymentsData = mysqli_fetch_array($resultPayments, MYSQLI_ASSOC)) {
        
        $paymentType = $paymentsData['paymentType'];
        $dni         = $paymentsData['dni'];
        $nombre      = $paymentsData['nombre'];
        $total       = (float)$paymentsData['total'];
        $pagado      = (float)$paymentsData['pagado'];
        $restante    = ((float)$paymentsData['total'] - (float)$paymentsData['pagado']);
        
        $paymentList[] = [$paymentType, $dni, $nombre, $total, $pagado, $restante];
        $paymentName = $paymentType;
      }

      $xlsx = SimpleXLSXGen::fromArray($paymentList);
      if ($_GET['paymentType'] == "all") {
        $xlsx->saveAs('../excelsPagos/pagos_club_todo.xlsx');
        $response->resultat = 'https://areaprivada.basquetlloret.com/excelsPagos/pagos_club_todo.xlsx';
      } else {
        $paymentName = str_replace(" ", "_", $paymentName);
        $xlsx->saveAs('../excelsPagos/pagos_club_'.$paymentName.'.xlsx');
        $response->resultat = 'https://areaprivada.basquetlloret.com/excelsPagos/pagos_club_'.$paymentName.'.xlsx';
      }
    }
    break;
  default:
    break;
}

echo json_encode($response);
?>
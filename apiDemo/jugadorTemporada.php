<?php
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header("Access-Control-Allow-Methods: *");

    require_once 'dbConnection.php';

    $con = returnConection();
    $response = new Result();

    $params = json_decode(file_get_contents("php://input"));

    if (isset($params->nuevaCuota) && $params->nuevaCuota >= 0){
      $id = $params->idJugador;
      $nuevaCuota = $params->nuevaCuota;
      $idTipoPago = $params->idTipoPago;

      $sql = "UPDATE jugador_temporada jt SET quota = '$nuevaCuota' WHERE jt.idJugador='$id' AND jt.idTipo='$idTipoPago';";
      if(mysqli_query($con, $sql)){
        //$response->jugador = array();
        $response->jugador;

        $sqlConsulta = "SELECT p.id,p.dni, j.id, ".
        "(SELECT COUNT(*) FROM movimientos WHERE dniJugador = p.dni) as countMovimientos FROM persona p INNER JOIN jugador j ON p.id = j.id ".
        "WHERE p.id = $id";
        if ($result = mysqli_query($con, $sqlConsulta)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

            $sqlQuotes = "SELECT idTipo, quota, (SELECT IFNULL(SUM(importe),0) FROM movimientos WHERE dniJugador = p.dni AND pagoCompletado = 1 AND tipo_pago = jt.idTipo) as pagado ".
            "FROM jugador_temporada jt INNER JOIN persona p ON jt.idJugador = p.id WHERE idJugador = {$userData['id']}";

            if ($resultQuotes = mysqli_query($con, $sqlQuotes)) {

              $userData['quotes'] = array();

              while ($quotesData = mysqli_fetch_array($resultQuotes, MYSQLI_ASSOC)){

                $quota = array();
                $quota['idTipo'] = (int)$quotesData['idTipo'];
                $quota['quota'] = (float)$quotesData['quota'];
                $quota['pagado'] = (float)$quotesData['pagado'];

                $userData['quotes'][] = $quota;
              }
            }

            $sqlPagos = "SELECT * FROM movimientos WHERE pagoCompletado = 1 AND dniJugador = '{$userData['dni']}'";
            if ($resultPagos = mysqli_query($con, $sqlPagos)) {
  
              $userData['pagos'] = array();
  
              while ($pagosData = mysqli_fetch_array($resultPagos, MYSQLI_ASSOC)){

                $pago = array();
                $pago['tipoPago'] = (int)$pagosData['tipo_pago'];
                $pago['descripcion'] = $pagosData['descripcion'];
                $pago['fechaTransaccion'] =  date("d/m/Y", strtotime($pagosData['fechaTransaccion']));
                $pago['importe'] = (float)$pagosData['importe'];

                $userData['pagos'][] = $pago;
              }
            }

            $response->jugador = $userData;
          }
        }
      } else {
        return http_response_code(422);
      }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
?>
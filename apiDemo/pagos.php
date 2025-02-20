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

        $metodoVisualizacion = $params->metodoVisualizacion;
        $tipoMovimiento = isset($params->tipoMovimiento) ? (($params->tipoMovimiento == 'all') ? null : $params->tipoMovimiento) : null;
        $onlyDNIs = isset($_GET['dnis']) ? true : false;

        if ($onlyDNIs){
          $sql = "SELECT * FROM persona WHERE dni IN (SELECT DISTINCT(dniJugador) FROM movimientos)";
        } else{
          $idMovimientos = array();
          $sqlUltimosPagosManuales = "SELECT MAX(id) as id FROM movimientos WHERE pagoManual = 1 GROUP BY dniJugador";
          if ($resultUltimosPagosManuales = mysqli_query($con, $sqlUltimosPagosManuales)) {
            while ($ultimosPMData = mysqli_fetch_array($resultUltimosPagosManuales, MYSQLI_ASSOC)){
              $idMovimientos[] = $ultimosPMData['id'];
            }
          }
          //print_r($idMovimientos);

          $response->pagos = array();
          $response->pagos['pagos'] = array();
          $response->pagos['pagosNoCompletados'] = array();
          $response->pagos['pagosFallidos'] = array();
          $sql = "SELECT m.id, (SELECT CONCAT(nombre,' ',primer_apellido,' ', segundo_apellido) FROM persona WHERE dni = m.dniTutor) as tutor, dniTutor, (SELECT CONCAT(nombre,' ',primer_apellido,' ', segundo_apellido) FROM persona WHERE dni = m.dniJugador) as jugador, dniJugador, ".
          "idTransaccion, fechaTransaccion, tipo_pago as tipoPago, (SELECT CONCAT(concepto,' ',(SELECT temporada FROM temporada WHERE id = tipo_pago.idTemporada)) FROM tipo_pago WHERE id = m.tipo_pago) as tipoPagoDescripcion, descripcion, importe, pagoManual, pagoCompletado " .
          " FROM movimientos m INNER JOIN tipo_pago tp ON m.tipo_pago = tp.id WHERE pagoCompletado IS NOT NULL AND fechaTransaccion IS NOT NULL ";
          switch ($metodoVisualizacion) {
              case "pagoManual":
                $sql .= "AND pagoManual = 1 ";
                break;
              case "pagoOnline":
                $sql .= "AND pagoManual = 0 ";
                break;
              default:
                break;
          }
          if (!empty($tipoMovimiento)){
            $sql .= "AND tipo_pago = $tipoMovimiento ";
          }
          $sql .= "ORDER BY m.id DESC";
        }
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            if ($onlyDNIs){
              $sqlFamiliares = "SELECT * FROM persona WHERE dni = (SELECT dniTutor FROM movimientos WHERE dniJugador = '{$userData['dni']}' AND dniTutor IS NOT NULL LIMIT 1)";
              if ($resultFamiliares = mysqli_query($con, $sqlFamiliares)) {
                $familiarData = mysqli_fetch_array($resultFamiliares, MYSQLI_ASSOC);
                $userData['familiar'] = $familiarData;
              }
              $sqlQuotes = "SELECT idTipo, quota, (SELECT IFNULL(SUM(importe),0) FROM movimientos WHERE dniJugador = p.dni AND pagoCompletado = 1 AND tipo_pago = jt.idTipo) as pagado ".
                "FROM jugador_temporada jt INNER JOIN persona p ON jt.idJugador = p.id WHERE idJugador = {$userData['id']}";
              if ($resultQuotes = mysqli_query($con, $sqlQuotes)) {
                $userData['quotes'] = array();
                while ($quotesData = mysqli_fetch_array($resultQuotes, MYSQLI_ASSOC)){
                  $quota = array();
                  $quota['idTipo'] = $quotesData['idTipo'];
                  $quota['quota'] = (float)$quotesData['quota'];
                  $quota['pagado'] = (float)$quotesData['pagado'];
                  $userData['quotes'][] = $quota;
                }
              }
              $response->jugadores[] = $userData;
            } else{
              if(in_array($userData['id'], $idMovimientos)){
                $userData['pagoModificable'] = 1;
              } else {
                $userData['pagoModificable'] = 0;
              }

              $userData['importe'] = (float)$userData['importe'];
              $userData['importePago'] = (float)$userData['importe'];
              if ($userData['pagoCompletado'] == 1){
                $response->pagos['pagos'][] = $userData;
              } else if ($userData['pagoManual'] == 1 && $userData['pagoCompletado'] == 0){
                $response->pagos['pagosNoCompletados'][] = $userData;
              } else if ($userData['pagoManual'] == 0){
                $response->pagos['pagosFallidos'][] = $userData;
              }
            }
          }
        }
      break;
    case 'PUT':
        $params = json_decode(file_get_contents("php://input"));

        $id = mysqli_real_escape_string($con, $params->id);
        $dniTutor = (!empty($params->dniTutor)) ? "'" . mysqli_real_escape_string($con, $params->dniTutor) . "'" : 'NULL';
        $dniJugador = mysqli_real_escape_string($con, $params->dniJugador);
        $fechaTransaccion = str_replace('T',' ',mysqli_real_escape_string($con, $params->fechaTransaccion));
        $tipo = mysqli_real_escape_string($con, $params->tipoPago);
        $descripcion = mysqli_real_escape_string($con, $params->descripcion);
        $importe = mysqli_real_escape_string($con, $params->importe);
        $pagoCompletado = mysqli_real_escape_string($con, $params->pagoCompletado);

		$sqlInsertData = "SELECT idDatosIntermedios FROM movimientos WHERE id = $id AND pagoManual = 0 AND pagoCompletado = 0";
		if($result = mysqli_query($con, $sqlInsertData)){
			if ($result->num_rows > 0 && $pagoCompletado == "1"){
				$data = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$ch = curl_init('https://areaprivada.basquetlloret.com/apiDemo/insertData.php');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array('id' => $data['idDatosIntermedios']));
				$responseCurl = curl_exec($ch);
				curl_close($ch);
			}
		} else{
			return http_response_code(422);
		}

        $sql = "UPDATE movimientos SET dniTutor = $dniTutor, dniJugador = '$dniJugador', fechaTransaccion = '$fechaTransaccion', tipo_pago = $tipo, descripcion='$descripcion', ".
              "importe = '$importe', pagoCompletado = $pagoCompletado WHERE id='$id'";
        if(mysqli_query($con, $sql)){
          return http_response_code(200);
        } else {
          return http_response_code(422);
        }
      break;
    case 'POST':
        $params = json_decode(file_get_contents("php://input"));

        $idDatosIntermedios = isset($params->$idDatosIntermedios) ? "'" . mysqli_real_escape_string($con, $params->$idDatosIntermedios) . "'" : 'NULL';
        $dniTutor = (!empty($params->dniTutor)) ? "'" . mysqli_real_escape_string($con, $params->dniTutor) . "'" : 'NULL';
        $dniJugador = mysqli_real_escape_string($con, $params->dniJugador);
        if (empty($params->idTransaccion)){
          $sqlNextId = "SELECT CONCAT('m',LPAD(IFNULL(MAX(CONVERT(SUBSTR(idTransaccion,2), UNSIGNED)),0) + 1,7,'0')) as nextId FROM movimientos WHERE pagoManual = 1 AND idTransaccion LIKE 'm%'";
          if($result = mysqli_query($con, $sqlNextId)){
            $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $idTransaccion = $data['nextId'];
          } else{
            return http_response_code(422);
          }
        } else {
          $idTransaccion = mysqli_real_escape_string($con, $params->idTransaccion);
        }
        $fechaTransaccion = str_replace('T',' ',mysqli_real_escape_string($con, $params->fechaTransaccion));
        $tipo = mysqli_real_escape_string($con, $params->tipoPago);
        $descripcion = mysqli_real_escape_string($con, $params->descripcion);
        $importe = mysqli_real_escape_string($con, $params->importe);
        $pagoManual = mysqli_real_escape_string($con, $params->pagoManual);
        $pagoCompletado = mysqli_real_escape_string($con, $params->pagoCompletado);

        $sql = "INSERT INTO movimientos VALUES (NULL,$idDatosIntermedios,$dniTutor,'$dniJugador','$idTransaccion','$fechaTransaccion',$tipo,'$descripcion','$importe',$pagoManual,$pagoCompletado)";
        error_log($sql);
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

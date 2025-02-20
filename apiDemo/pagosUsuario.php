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

            $idUsuario = $params->idUsuario;
            $metodoVisualizacion = $params->metodoVisualizacion;
            $exclusiones = isset($params->exclusiones) ? $params->exclusiones : null;

            //$sql = "SELECT p.*, CONCAT((SELECT concepto FROM tipo_pago WHERE id = p.tipo),' ',(SELECT temporada FROM temporada WHERE id = (SELECT idTemporada FROM tipo_pago where id = p.tipo))) AS concepto FROM pagos p WHERE p.idPersona IN (SELECT dni FROM persona WHERE id = '$idUsuario') ";
            //$sql = "SELECT p.*, CONCAT((SELECT concepto FROM tipo_pago WHERE id = p.tipo),' ',(SELECT temporada FROM temporada WHERE id = (SELECT idTemporada FROM tipo_pago where id = p.tipo))) AS concepto, (SELECT presupuesto FROM datos_intermedios WHERE id = p.idDatosIntermedios) AS presupuesto, (SELECT `data` FROM datos_intermedios WHERE id = p.idDatosIntermedios) AS `data` FROM pagos p WHERE p.idPersona IN (SELECT dni FROM persona WHERE id = '$idUsuario') ";
            $sql = "SELECT m.*, CONCAT((SELECT concepto FROM tipo_pago WHERE id = m.tipo_pago),' ',(SELECT temporada FROM temporada WHERE id = (SELECT idTemporada FROM tipo_pago where id = m.tipo_pago))) AS concepto FROM movimientos m WHERE m.pagoCompletado = 1 AND m.dniJugador IN (SELECT dni FROM persona WHERE id = '$idUsuario' OR id IN (SELECT id_jugador FROM familiar_jugador WHERE id_familiar = '$idUsuario')) ";
            switch ($metodoVisualizacion) {
                case "pagoManual":
                $sql .= "AND m.pagoManual = 1 ";
                break;
                case "pagoOnline":
                $sql .= "AND m.pagoManual = 0 ";
                break;
                default:
                $sql .= "AND m.id IS NOT NULL ";
                break;
            }
            $sql .= "ORDER BY m.id DESC";
            $response->causa = $sql;
            if ($result = mysqli_query($con, $sql)) {
            $arrayFinal = array();
            $data = array();
            $pago = array();

            while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $conceptoGeneral = $userData['concepto'];
                $dniTutor = $userData['dniTutor'];
                $dniJugador = $userData['dniJugador'];
                $idTransaccion = $userData['idTransaccion'];
                $fechaTransaccion = $userData['fechaTransaccion'];
                $tipo_pago = $userData['tipo_pago'];
                $descripcionPago = $userData['descripcion'];
                $importePagado = $userData['importe'];
                $pagoManual = $userData['pagoManual'];
                $pagoCompletado = $userData['pagoCompletado'];


                $sqlConsultPersona = "SELECT id, CONCAT(nombre,' ',primer_apellido,' ',segundo_apellido) as nombreCompleto FROM persona WHERE dni = '$dniJugador';";
                if ($resultPersona = mysqli_query($con, $sqlConsultPersona)) {
                    $personaData = mysqli_fetch_array($resultPersona, MYSQLI_ASSOC);
                    $idJugador = $personaData['id'];
                    $nombreCompleto = $personaData['nombreCompleto'];
                }

                $sqlConsultJugadorTemporada = "SELECT * FROM jugador_temporada WHERE idJugador = (SELECT id FROM persona WHERE dni = '$dniJugador') AND idTipo = $tipo_pago;";
                if ($resultJugadorTemporada = mysqli_query($con, $sqlConsultJugadorTemporada)) {
                    $jugadorTemporadaData = mysqli_fetch_array($resultJugadorTemporada, MYSQLI_ASSOC);
                    $quota = $jugadorTemporadaData['quota'];
                }

                if ($quota == '' || $quota == null) {
                    $sqlConsultSocioTemporada = "SELECT * FROM cblloretdb.socio_temporada st WHERE st.id_socio = (SELECT s.id FROM cblloretdb.socio s WHERE s.id_persona = '$idUsuario');";
                    if ($resultSocioTemporada = mysqli_query($con, $sqlConsultSocioTemporada)) {
                        $socioTemporadaData = mysqli_fetch_array($resultSocioTemporada, MYSQLI_ASSOC);
                        $quota = $socioTemporadaData['quota'];
                    }
                }

                $totalPagos = 0;
                if($pagoCompletado == 1){
                    $totalPagos += $importePagado;
                }

                $restante = $quota - $totalPagos;

                $pago = [
                    "importe" => $importePagado,
                    "fecha" => $fechaTransaccion,
                    "descripcion" => $descripcionPago,
                    "pagoCompletado" => $pagoCompletado
                ];

                $data = [
                    "mas18" => empty($dniTutor),
                    "idJugador" => $idJugador,
                    "jugador" => $nombreCompleto,
                    "pagos" => array(),
                    "quota" => $quota,
                    "restante" => $restante
                ];

                $data['pagos'][] = $pago;
                $estaConcepto = false;
                foreach ($arrayFinal as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        if ($key2 == 'tipo') {
                            if ($value['tipo'] == $conceptoGeneral) {
                                $estaJugador = false;
                                $estaConcepto = true;
                                foreach ($arrayFinal[$key]['data'] as $key3 => $value3) {
                                    if($value3['jugador'] == $data['jugador']){
                                        $estaJugador = true;
                                        $arrayFinal[$key]['data'][$key3]['pagos'][] = $pago;
                                        if($pagoCompletado == 1){
                                            $arrayFinal[$key]['data'][$key3]['restante'] -= $pago['importe'];
                                        }
                                    }
                                }
                                if($estaJugador == false){
                                    $arrayFinal[$key]['data'][] = $data;
                                }
                            }
                        }
                    }
                }
                if ($estaConcepto == false) {
                    $arrayConcepto = array();
                    $arrayConcepto['idTipo'] = $tipo_pago;
                    $arrayConcepto['tipo'] = $conceptoGeneral;
                    $arrayConcepto['data'] = array();
                    $arrayConcepto['data'][] = $data;
                    $arrayFinal[] = $arrayConcepto;
                }
            }
            //print_r($arrayFinal);
            //$response->resultat = $arrayFinal;
            $response->arrayFinal = $arrayFinal;
            }
        break;
        case 'POST':
            $params = json_decode(file_get_contents("php://input"));

            $idTipo = $params->IdTipo;
            $conceptoTipo = $params->ConceptoTipo;
            $descripcion = 'Pago'.' '.$conceptoTipo;

            $idUsuario = $params->idUsuario;
            $idJugador = $params->IdJugador;

            $pago_Importe = $params->Importe;
            //$response->resultat = $idTipo.', '.$conceptoTipo.', '.$idJugador.', '.$pago_Importe;

            $horaActual = date('H:i:s');
            $date = date_create($horaActual);
            $horaNumero = date_format($date, 'His');
            $idTransaccion = str_pad($idJugador,6,"0",STR_PAD_LEFT).$horaNumero; //12 dígitos ([a-z][A-Z][0-9]) *Primeros 4 obligatorio [0-9]

            $sqlConsultTutor = "SELECT * FROM persona WHERE id = $idUsuario";
            if ($resultTutor = mysqli_query($con, $sqlConsultTutor)) {
                $tutorData = mysqli_fetch_array($resultTutor, MYSQLI_ASSOC);
                $dniTutor = $tutorData['dni'];
            }

            $sqlConsultJugador = "SELECT * FROM persona WHERE id = $idJugador";
            if ($resultJugador = mysqli_query($con, $sqlConsultJugador)) {
                $jugadorData = mysqli_fetch_array($resultJugador, MYSQLI_ASSOC);
                $dniJugador = $jugadorData['dni'];
            }

            if ($idUsuario == $idJugador) {
                $sqlInsertPago = "INSERT INTO movimientos(`id`,`dniTutor`,`dniJugador`,`idTransaccion`,`fechaTransaccion`,`tipo_pago`,`descripcion`,`importe`,`pagoManual`,`pagoCompletado`) VALUES (NULL,NULL,'$dniJugador','$idTransaccion',NULL,$idTipo,'$descripcion','$pago_Importe',0,NULL);";
            } else {
                $sqlInsertPago = "INSERT INTO movimientos(`id`,`dniTutor`,`dniJugador`,`idTransaccion`,`fechaTransaccion`,`tipo_pago`,`descripcion`,`importe`,`pagoManual`,`pagoCompletado`) VALUES (NULL,'$dniTutor','$dniJugador','$idTransaccion',NULL,$idTipo,'$descripcion','$pago_Importe',0,NULL);";
            }
            //$response->resultat = $sqlInsertPago;
            if (mysqli_query($con, $sqlInsertPago)) {
                $response->resultat = mysqli_insert_id($con);
            } else {
                $response->resultat = "INSERT_PAGO_KO";
                $response->causa = mysqli_error($con);
            }
        break;
        default:
        break;
    };
    //error_log(print_r($response, TRUE));
    header('Content-Type: application/json');
    echo json_encode($response);
?>

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
            $sql = "SELECT p.*, CONCAT((SELECT concepto FROM tipo_pago WHERE id = p.tipo),' ',(SELECT temporada FROM temporada WHERE id = (SELECT idTemporada FROM tipo_pago where id = p.tipo))) AS concepto, (SELECT presupuesto FROM datos_intermedios WHERE id = p.idDatosIntermedios) AS presupuesto, (SELECT `data` FROM datos_intermedios WHERE id = p.idDatosIntermedios) AS `data` FROM pagos p WHERE p.idPersona IN (SELECT dni FROM persona WHERE id = '$idUsuario') ";
            switch ($metodoVisualizacion) {
                case "pagoManual":
                  $sql .= "AND p.pagoManual = 1 ";
                  break;
                case "pagoOnline":
                  $sql .= "AND p.pagoManual = 0 ";
                  break;
                default:
                  $sql .= "AND p.id IS NOT NULL ";
                  break;
            }
            $sql .= "ORDER BY p.id DESC";
            if ($result = mysqli_query($con, $sql)) {

                $arrayFinal = array();
                $data = array();
                $pago = array();
                $descuento = array();
                $cantidad = 0;

                while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

                    $dataForm = json_decode($userData['data']);
                    $dataPresupuesto = json_decode($userData['presupuesto']);

                    //$precio = strval(number_format($userData['importe'], 2, ',', '.'));
                    
                    $conceptoPago = $dataPresupuesto->tipoPago->tipo;
                    if ($dataForm->form_mas18 == false) {
                        $precioUnitrioJ = 420;

                        $cantidad += count($dataPresupuesto->lineas);
                        $importePorJugador = $userData['importe'] / $cantidad;
                        if($dataPresupuesto->tipoPago->tipo == "Unico"){
                            $conceptoPago = "Quota anual";
                            $importeFinal = $userData['importe'];
                            $restantePagar = "0,00€";
                            $restante = "0";
                            $colorHeader = "0";
                        } else if ($dataPresupuesto->tipoPago->tipo == "Inscripcion"){
                            //$precioInscripcion = strval(150 * $cantidad);
                            $precioInscripcion = $userData['importe'];
                            $restante = $precioInscripcion;
                            $importeFinal = $precioInscripcion;
                            if($precioInscripcion == $userData['importe']){
                                $restante = $userData['importe'] - ($precioUnitrioJ * $cantidad);
                                $restantePagar = strval(number_format(($restante / $cantidad), 2, ',', '.'));
                                $colorHeader = "1";
                            } else {
                                $restantePagar = "0,00€";
                                $colorHeader = "0";
                            }
                        }
                        $precio = strval(number_format($importePorJugador, 2, ',', '.'));
                    } else {
                        if($dataPresupuesto->tipoPago->tipo == "Unico"){
                            $conceptoPago = "Quota anual";
                            $importeFinal = $userData['importe'];
                            $restantePagar = "0,00€";
                            $restante = "0";
                            $colorHeader = "0";
                        } else if ($dataPresupuesto->tipoPago->tipo == "Inscripcion"){
                            //$precioUnitrioJ = 300;
                            $restante = $dataPresupuesto->lineas[0]->importe;
                            $importeFinal = $restante;
                            if($userData['importe'] != $restante){
                                $restante = ($userData['importe'] - $restante);
                                $restantePagar = strval(number_format($restante, 2, ',', '.'));
                                $colorHeader = "1";
                            } else {
                                $restantePagar = "0,00€";
                                $colorHeader = "0";
                            }
                        }
                        $precio = strval(number_format($userData['importe'], 2, ',', '.'));
                    }

                    /*$conceptoPago = $dataPresupuesto->tipoPago->tipo;
                    if($dataPresupuesto->tipoPago->tipo == "Unico"){
                        $conceptoPago = "Quota anual";
                    }*/

                    $pago = [
                        "fecha" => $userData['fechaTransaccion'],
                        "importe" => $precio,
                        "concepto" => $conceptoPago
                    ];

                    $descuentoH = [
                        "descuento" => $dataPresupuesto->descuentoHermanos->importe,
                        "tipo" => $dataPresupuesto->descuentoHermanos->porcentaje
                    ];

                    $descuentoU = [
                        "descuento" => $dataPresupuesto->tipoPago->descuentoUnicoPago->importe,
                        "tipo" => $dataPresupuesto->tipoPago->descuentoUnicoPago->porcentaje
                    ];

                    $data = [
                        "mas18" => $dataForm->form_mas18,
                        "jugador" => '',
                        "pagos" => array(),
                        "descuentos" => array(),
                        "totalInscripcion" => $importeFinal,
                        "restante" => $restantePagar,
                        "restanteSelect" => $restante,
                        "colorHeader" => $colorHeader
                    ];
                    foreach($dataPresupuesto as $k => $value){
                        if ($k == 'lineas') {
                            foreach($value as $persona){
                                foreach($persona as $k2 => $value2){
                                    if ($k2 == 'nombre') {
                                        $data['jugador'] = $value2;
                                    }
                                    /*if ($k2 == 'importe') {
                                        $pago['importe'] = $value2;
                                    }*/
                                }
                                /*if ($dataForm->form_mas18 == true) {
                                    if($dataPresupuesto->tipoPago->tipo == "Inscripcion"){
                                        $descuento['descuento'] = "0";
                                        $descuento['tipo'] = "0%";
                                    } else {
                                        $descuento['descuento'] = $dataPresupuesto->tipoPago->descuentoUnicoPago->importe;
                                        $descuento['tipo'] = $dataPresupuesto->tipoPago->descuentoUnicoPago->porcentaje;
                                    }
                                } else {
                                    $descuento['descuento'] = $dataPresupuesto->tipoPago->descuentoUnicoPago->importe;
                                    $descuento['tipo'] = $dataPresupuesto->tipoPago->descuentoUnicoPago->porcentaje;

                                    if ($k == 'descuentoHermanos') {
                                        $descuento['descuento'] = $dataPresupuesto->descuentoHermanos->importe;
                                        $descuento['tipo'] = $dataPresupuesto->descuentoHermanos->porcentaje;
                                    }
                                }*/
                                if ($dataForm->form_mas18 == true) {
                                    $data['descuentos'][] = $descuentoU;
                                } else {
                                    $data['descuentos'][] = $descuentoH;
                                    $data['descuentos'][] = $descuentoU;
                                }
                                $data['pagos'][] = $pago;
                            }
                        }
                    }

                    $estaConcepto = false;
                    foreach ($arrayFinal as $key => $value) {
                        foreach ($value as $key2 => $value2) {
                            if ($key2 == 'tipo') {
                                if ($value['tipo'] == $userData['concepto']) {
                                    $estaJugador = false;
                                    $estaConcepto = true;
                                    foreach ($arrayFinal[$key]['data'] as $key3 => $value3) {
                                        if($value3['jugador'] == $data['jugador']){
                                            $estaJugador = true;
                                            foreach ($data['pagos'] as $key4 => $value4) {
                                                $arrayFinal[$key]['data'][$key3]['pagos'][] = $value4;
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
                        $arrayConcepto['tipo'] = $userData['concepto'];
                        $arrayConcepto['data'] = array();
                        $arrayConcepto['data'][] = $data;
                        $arrayFinal[] = $arrayConcepto;
                    }
                }
                //print_r($arrayFinal);
                //$response->resultat = $arrayFinal;
                $response->causa = $cantidad;
                $response->arrayFinal = $arrayFinal;
            }
        break;

        case 'POST':
            $params = json_decode(file_get_contents("php://input"));

            $pago_Importe = $params->Importe;
            $response->causa = $pago_Importe;

            //$dataArrayFinal = $params->ContentsArrayFinal;
            //$response->resultat = $dataArrayFinal;

            //$pagina = mysqli_real_escape_string($con, $dataArrayFinal->tipo);


        break;

        default:
        break;
    };

    //error_log(print_r($response, TRUE));
    header('Content-Type: application/json');
    echo json_encode($response);
?>
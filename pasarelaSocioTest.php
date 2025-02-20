<?php
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header("Access-Control-Allow-Methods: *");

    require_once 'apiDemo/dbConnection.php';
    require_once __DIR__.'/libs/apiRedsys.php';

    $con = returnConection();
    $response = new Result();

    $id = $_POST['id'];
    $sqlConsultDatosIntermedios = "SELECT * FROM datos_intermedios WHERE id = '$id';";
    if($result = mysqli_query($con, $sqlConsultDatosIntermedios)){
        if ($result->num_rows > 0) {
            $datosIntermediosData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $idTipo_pago = $datosIntermediosData['idTipo_pago'];

            $importe = intval($datosIntermediosData['pago_Importe']*100);

            $horaActual = date('H:i:s');
            $date = date_create($horaActual);
            $horaNumero = date_format($date, 'His');
            $idTransaccion = str_pad($datosIntermediosData['id'],6,"0",STR_PAD_LEFT).$horaNumero; //12 dígitos ([a-z][A-Z][0-9]) *Primeros 4 obligatorio [0-9]

            $descripcionPago = $datosIntermediosData['pagina'].': ';
            $presupuestoForm = json_decode($datosIntermediosData['presupuesto']);
            foreach($presupuestoForm as $k => $value){
                if ($k == 'lineas') {
                    foreach($value as $persona){
                        foreach($persona as $k2 => $value2){
                            if ($k2 == 'nombre') {$descripcionPago .= $value2.', ';}
                        }
                    }
                }
            }
            $descripcionPago = substr($descripcionPago, 0, -2);

            $codigoComercio = "327626198"; //Basquet lloret Real: 347148595
            $clave = "sq7HjrUOBfKmC576ILgskD5srU870gJ7";
            $transCurrency = "978";
            $transType = "0";
            $terminal = "001";
            $urlResponse = "https://areaprivada.basquetlloret.com/receptorSocioServerTest.php"; //URL dónde el servidor espera la respuesta del pago
            $urlCliente = "https://areaprivada.basquetlloret.com/receptorSocioClienteTest.php?id=".$idTransaccion; //URL dónde el cliente será redirigido para ver el resumen del pago (OK/KO)
            $pruebas = true;


            $dataForm = json_decode($datosIntermediosData['data']);
            $esMas18 = $dataForm->form_mas18;

            $arrayJugadores = [];
            foreach($dataForm as $k => $value){
                if ($k == 'form_fields[dniTutor]') {$dniTutor = $value;}
                if ($k == 'form_fields[dniJugador]') {$dniJugador = $value;}
                if ($k == 'form_fields[nomJugador]') {$nomJugador = $value;}
                if ($k == 'form_fields[primerCognomJugador]') {$primerCognomJugador = $value;}
                if ($k == 'Jugadores') {
                    foreach($value as $jugador){
                    //print_r($jugador);
                    $arrayJugador = [];
                    foreach($jugador as $k2 => $value2){
                        if ($k2 == 'form_fields[nomJugador]') {$arrayJugador['Name'] = $value2;}
                        if ($k2 == 'form_fields[primerCognomJugador]') {$arrayJugador['FirstSurname'] = $value2;}
                        if ($k2 == 'form_fields[dniJugador]') {$arrayJugador['DNI'] = $value2;}
                    }
                    array_push($arrayJugadores, $arrayJugador);
                    }
                }
            }

            foreach($presupuestoForm as $k => $value){
                if ($k == 'lineas') {
                    foreach($value as $persona){
                        foreach($persona as $k2 => $value2){
                            if ($k2 == 'nombre') {

                                if ($esMas18 == true) {
                                    $importeJugador = $persona->importe;
                                    $restanteJugador = $persona->restante;

                                    $importePagado = $importeJugador - $restanteJugador;

                                    $sqlInsertPago = "INSERT INTO movimientos(`id`,`idDatosIntermedios`,`dniTutor`,`dniJugador`,`idTransaccion`,`fechaTransaccion`,`tipo_pago`,`descripcion`,`importe`,`pagoManual`,`pagoCompletado`) VALUES (NULL,'$id',NULL,'$dniJugador','$idTransaccion',NULL,$idTipo_pago,'Inscripcion','$importePagado',0,NULL);";
                                    if (mysqli_query($con, $sqlInsertPago)) {
                                        $response->resultat = "INSERT_PAGO_OK";
                                    } else {
                                        $response->resultat = "INSERT_PAGO_KO";
                                        $response->causa = mysqli_error($con);
                                    }

                                    //$sql = $sqlInsertPago;
                                } else if ($esMas18 == false){
                                    for ($i=0; $i < count($arrayJugadores); $i++) {
                                        $name = $arrayJugadores[$i]['Name'].' '.$arrayJugadores[$i]['FirstSurname'];
                                        $dniJugador = $arrayJugadores[$i]['DNI'];

                                        if ($value2 == $name) {
                                            $importeJugador = $persona->importe;
                                            $restanteJugador = $persona->restante;

                                            $importePagado = $importeJugador - $restanteJugador;

                                            $sqlInsertPago = "INSERT INTO movimientos(`id`,`idDatosIntermedios`,`dniTutor`,`dniJugador`,`idTransaccion`,`fechaTransaccion`,`tipo_pago`,`descripcion`,`importe`,`pagoManual`,`pagoCompletado`) VALUES (NULL,'$id','$dniTutor','$dniJugador','$idTransaccion',NULL,$idTipo_pago,'Inscripcion','$importePagado',0,NULL);";
                                            if (mysqli_query($con, $sqlInsertPago)) {
                                                $response->resultat = "INSERT_PAGO_OK";
                                            } else {
                                                $response->resultat = "INSERT_PAGO_KO";
                                                $response->causa = mysqli_error($con);
                                            }

                                            //$sql .= $sqlInsertPago;
                                        }
                                    }
                                }
                                /*$response->resultat = $name;
                                $response->causa = $sql;*/

                            }
                        }
                    }
                }
            }

            try{
                $redsys = new RedsysAPI;

                $redsys->setParameter("DS_MERCHANT_AMOUNT", $importe);
                $redsys->setParameter("DS_MERCHANT_ORDER", $idTransaccion);
                $redsys->setParameter("Ds_Merchant_ProductDescription", $descripcionPago);
                $redsys->setParameter("DS_MERCHANT_MERCHANTCODE", $codigoComercio);
                $redsys->setParameter("DS_MERCHANT_CURRENCY", $transCurrency);
                $redsys->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $transType);
                $redsys->setParameter("DS_MERCHANT_TERMINAL", $terminal);
                $redsys->setParameter("DS_MERCHANT_MERCHANTURL", $urlResponse);
                $redsys->setParameter("DS_MERCHANT_URLOK", $urlCliente);
                $redsys->setParameter("DS_MERCHANT_URLKO", $urlCliente);

                //Datos de configuración
                $version="HMAC_SHA256_V1";
                $params = $redsys->createMerchantParameters();
                $signature = $redsys->createMerchantSignature($clave);

                $form_url = (!$pruebas) ? 'https://sis.redsys.es/sis/realizarPago' : 'https://sis-t.redsys.es:25443/sis/realizarPago';
            ?>
                <form action="<?php echo $form_url; ?>" method="post" target="_parent" id="tpv_form" name="tpv_form" style="display:none">
                    <input type="hidden"   name="Ds_SignatureVersion"   value="<?php echo $version; ?>"/>
                    <input type="hidden"   name="Ds_MerchantParameters" value="<?php echo $params; ?>"/>
                    <input type="hidden"   name="Ds_Signature"          value="<?php echo $signature; ?>"/>
                    <input type="submit"   name="enviar" />
                </form>
                <script type="text/javascript">
                    document.tpv_form.submit();
                </script>
            <?php
            } catch(Exception $e) {
                echo $e->getMessage();
            }
        } else {$response->resultat = "Tabla vacia";}
    }
    echo json_encode($response);
?>

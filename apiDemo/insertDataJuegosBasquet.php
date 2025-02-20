<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';
  require_once __DIR__ . "/../sendMailDemo.php";

  $con = returnConection();
  $response = new Result();

  $id = $_POST['id'];
  $sqlConsult = "SELECT * FROM datos_intermedios WHERE id = '$id';";

  if ($result = mysqli_query($con, $sqlConsult)) {
    if ($result->num_rows > 0) {
      $bdData = mysqli_fetch_array($result, MYSQLI_ASSOC);

      $idTipo_pago = $bdData['idTipo_pago'];

      $dataForm = json_decode($bdData['data']);
      //print_r($dataForm);

      $importeDatosIntermedios = $bdData['pago_Importe'];
      $esMas18 = $dataForm->form_mas18;
      if ($esMas18 == true) {
        //echo '+18';
        foreach($dataForm as $k => $value){
          if ($k == 'form_fields[nomJugador]') {$namePlayer = $value;}
          if ($k == 'form_fields[primerCognomJugador]') { $firstSurnamePlayer = $value;}
          if ($k == 'form_fields[segonCognomJugador]') {$secondSurnamePlayer = $value;}
          if ($k == 'form_fields[dataNaixementJugador]') {$dateOfBirthPlayer = $value;}
          if ($k == 'form_fields[dniJugador]') {$dniPlayer = $value;}
          if ($k == 'form_fields[tarjetaSanitariaJugador]') {$tsiPlayer = $value;}
          if ($k == 'form_fields[domiciliJugador]') {$addressPlayer = $value;}
          if ($k == 'form_fields[poblacioJugador]') {$populationPlayer = $value;}
          if ($k == 'form_fields[codiPostalJugador]') { $postalCodePlayer = $value;}
          if ($k == 'form_fields[emailJugador]') {$emailPlayer = $value;}
          if ($k == 'form_fields[telefonJugador]') {$phonePlayer = $value;}
          if ($k == 'form_fields[escolaJugador]') {$schoolPlayer = $value;}
          if ($k == 'form_fields[cursJugador]') {$coursePlayer = $value;}
          if ($k == 'form_fields[observacionsJugador]') {$observationsPlayer = $value;}
          if ($k == 'form_fields[categoriaSexoJugador]') {$sexoPlayer = $value;}
          if ($k == 'form_fields[numeroDorsalJugador]') {$numeroDorsalPlayer = $value;}
          if ($k == 'form_fields[talla1RopaJugador]') {$talla1RopaPlayer = $value;}
          if ($k == 'form_fields[nomDorsalJugador]') {$nomDorsalPlayer = $value;}
          if ($k == 'form_fields[campoUserPassword]') {$userPassword = $value;}
          if ($k == 'campoCodePlayer') {$codePlayer = $value;}
        }

        $quotas = array();
        if (!empty($codePlayer)) {
          $horaActual = date('H:i:s');
          $date = date_create($horaActual);
          $horaNumero = date_format($date, 'His');
          $idTransaccion = str_pad($bdData['id'],6,"0",STR_PAD_LEFT).$horaNumero;

          $concepto = substr($bdData['pagina'], 0, -10);
          //$importeDatosIntermedios = $bdData['pago_Importe'];

          $dataForm = json_decode($bdData['data']);
          $esMas18 = $dataForm->form_mas18;

          $sqlPrimerEquipo = "SELECT porcentaje FROM descuentosTemporada WHERE dni = '$dniPlayer' AND porcentaje = 100 AND idTipo = (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular' AND idTemporada = (Select MAX(id) FROM temporada))";
          $resultPrimerEquipo = mysqli_query($con, $sqlPrimerEquipo);
          if ($resultPrimerEquipo->num_rows > 0){
            $jugadorNoPaga = 1;
          } else {
            $jugadorNoPaga = 0;
          }

          foreach($dataForm as $k => $value){
              if ($k == 'form_fields[dniTutor]') {$dniTutor = $value;}
              if ($k == 'form_fields[dniJugador]') {$dniJugador = $value;}
              if ($k == 'form_fields[nomJugador]') {$nomJugador = $value;}
              if ($k == 'form_fields[primerCognomJugador]') {$primerCognomJugador = $value;}
          }

          $presupuestoForm = json_decode($bdData['presupuesto']);
          foreach($presupuestoForm as $k => $value){
            if ($k == 'lineas') {
              foreach($value as $persona){
                foreach($persona as $k2 => $value2){
                  if ($k2 == 'nombre') {

                    $importeJugador = $persona->importe;
                    $restanteJugador = $persona->restante;

                    $importePagado = $importeJugador - $restanteJugador;

                    $quotas[$dniPlayer] = $importeJugador;

                    if($importeDatosIntermedios == 0){
                      $importePagado = 0;
                      $quotas[$dniPlayer] = 0;
                    }

                    $sqlInsertPago = "INSERT INTO movimientos(`id`,`idDatosIntermedios`,`dniTutor`,`dniJugador`,`idTransaccion`,`fechaTransaccion`,`tipo_pago`,`descripcion`,`importe`,`pagoManual`,`pagoCompletado`) VALUES (NULL,'$id',NULL,'$dniJugador','$idTransaccion',CURRENT_TIMESTAMP(),$idTipo_pago,'Pago inscripcion','$importePagado',1,$jugadorNoPaga);";
                    if (mysqli_query($con, $sqlInsertPago)) {
                        $response->resultat = "INSERT_PAGO_OK";

                        $descripcionPago = $bdData['pagina'].': ';
                        $presupuestoForm = json_decode($bdData['presupuesto']);
                        foreach($presupuestoForm as $k3 => $value3){
                            if ($k3 == 'lineas') {
                                foreach($value3 as $persona2){
                                    foreach($persona2 as $k4 => $value4){
                                        if ($k4 == 'nombre') {$descripcionPago .= $value4.', ';}
                                    }
                                }
                            }
                        }
                        $descripcionPago = substr($descripcionPago, 0, -2);

                        $idDatosIntermedios = $bdData['id'];
                        $sqlUpdatePagoOKDatosIntermedios = "UPDATE datos_intermedios SET pagoOK = CURRENT_TIMESTAMP() WHERE id = '$idDatosIntermedios' ";
                        if (mysqli_query($con, $sqlUpdatePagoOKDatosIntermedios)) {
                            $response->resultat = "UPDATE_PAGO_DATOS_INTERMEDIOS_OK";
                            $msgTitulo = 'Inscripción finalizada correctamente';
                        } else {
                            $response->resultat = "UPDATE_PAGO_DATOS_INTERMEDIOS_KO";
                            $response->causa = mysqli_error($con);
                            $msgTitulo = 'La Inscripción no a finalizada correctamente';
                        }
?>
                        <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Google' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'><?php echo $msgTitulo;?> </div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td align='center'> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'><?php echo $descripcionPago;?></a> </td></tr></tbody> </table> </div><div style='padding-top:32px;text-align:center'> <a href='https://basquetlloret.com/' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Ir a inicio</a> </div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
<?php
                    } else {
                        $response->resultat = "INSERT_PAGO_KO";
                        $response->causa = mysqli_error($con);
?>
                        <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Google' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'> La Inscripción no a finalizada correctamente </div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td align='center'> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'><?php echo $descripcionPago;?></a> </td></tr></tbody> </table> </div><div style='padding-top:32px;text-align:center'> <a href='https://basquetlloret.com/' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Ir a inicio</a> </div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
<?php
                    }
                    /*$sql = $sqlInsertPago;
                    $response->resultat = $name;
                    $response->causa = $sql;*/

                  }
                }
              }
            }
          }
        } else {
          $presupuestoForm = json_decode($bdData['presupuesto']);
          foreach($presupuestoForm as $k => $value){
            if ($k == 'lineas') {
              foreach($value as $persona){
                foreach($persona as $k2 => $value2){
                  if ($k2 == 'nombre') {

                    $importeJugador = $persona->importe;
                    $restanteJugador = $persona->restante;

                    $quotas[$dniPlayer] = $importeJugador;
                  }
                }
              }
            }
          }
        }

        if(strpos($response->resultat, "KO") === false){
          $sqlExistDNI = "SELECT dni FROM persona WHERE dni = '$dniPlayer'";
          if ($result = mysqli_query($con, $sqlExistDNI)) {
              if ($result->num_rows > 0) {
                  $sqlUpdatePerona = "UPDATE persona SET nombre = '$namePlayer', primer_apellido = '$firstSurnamePlayer', segundo_apellido = '$secondSurnamePlayer', fecha_nacimiento = '$dateOfBirthPlayer', direccion = '$addressPlayer', codigo_postal = '$postalCodePlayer', localidad = '$populationPlayer', telefono1 = '$phonePlayer', email = '$emailPlayer', observaciones = '$observationsPlayer', id_sexo = '$sexoPlayer' WHERE dni = '$dniPlayer'";
                  if (mysqli_query($con, $sqlUpdatePerona)) {
                      $response->resultat = "UPDATE_PER_OK";
                  } else {
                      $response->resultat = "UPDATE_PER_KO";
                      $response->causa = mysqli_error($con);
                  }
              } else {
                  //$response->resultat = "No existe";
                  $sqlExistNombreCompleto = "SELECT * FROM persona WHERE nombre = '$namePlayer' AND primer_apellido = '$firstSurnamePlayer' AND segundo_apellido = '$secondSurnamePlayer' AND fecha_nacimiento = '$dateOfBirthPlayer'";
                  if ($result = mysqli_query($con, $sqlExistNombreCompleto)) {
                      if ($result->num_rows > 0) {
                          $personaData = mysqli_fetch_array($result, MYSQLI_ASSOC);

                          $idPersonaMas18 = $personaData['id'];
                          $sqlUpdatePerona = "UPDATE persona SET nombre = '$namePlayer', primer_apellido = '$firstSurnamePlayer', segundo_apellido = '$secondSurnamePlayer', dni = '$dniPlayer', fecha_nacimiento = '$dateOfBirthPlayer', direccion = '$addressPlayer', codigo_postal = '$postalCodePlayer', localidad = '$populationPlayer', telefono1 = '$phonePlayer', email = '$emailPlayer', observaciones = '$observationsPlayer', id_sexo = '$sexoPlayer' WHERE id = '$idPersonaMas18'";
                          if (mysqli_query($con, $sqlUpdatePerona)) {
                              $response->resultat = "UPDATE_PER_OK";
                          } else {
                              $response->resultat = "UPDATE_PER_KO";
                              $response->causa = mysqli_error($con);
                          }
                      } else {
                          $sqlInsertPersona = "INSERT INTO persona VALUES (NULL, NULL,'$namePlayer','$firstSurnamePlayer','$secondSurnamePlayer','$dniPlayer','$dateOfBirthPlayer','$addressPlayer','$postalCodePlayer','$populationPlayer','$phonePlayer',NULL,'$emailPlayer','$observationsPlayer','$sexoPlayer')";
                          if (mysqli_query($con, $sqlInsertPersona)) {
                              $response->resultat = "INSERT_PER_OK";
                              $idLastPerson = mysqli_insert_id($con);
                          } else {
                              $response->resultat = "INSERT_PER_KO";
                              $response->causa = mysqli_error($con);
                          }
                      }
                  }
              }
          }
          if(strpos($response->resultat, "KO") === false){
              $sqlConsultaIdPersona = "SELECT * FROM persona WHERE dni = '$dniPlayer'";
              if ($resultPersona = mysqli_query($con, $sqlConsultaIdPersona)) {
                  $personaData = mysqli_fetch_array($resultPersona, MYSQLI_ASSOC);
                  $idPersonaMas18 = $personaData['id'];

                  $sqlConsultaJugador = "SELECT id FROM jugador WHERE id = '$idPersonaMas18'";
                  if ($result = mysqli_query($con, $sqlConsultaJugador)) {
                      if ($result->num_rows > 0) {
                          $sqlUpdateJugador = "UPDATE jugador SET baja = 0, tarjetaSanitaria = '$tsiPlayer', numero_dorsal = '$numeroDorsalPlayer', talla_ropa_juego = '$talla1RopaPlayer', nombre_dorsal = '$nomDorsalPlayer' WHERE id = '$idPersonaMas18'";
                          if (mysqli_query($con, $sqlUpdateJugador)) {
                              $response->resultat = "UPDATE_JUGADOR_OK";
                          } else {
                              $response->resultat = "UPDATE_JUGADOR_KO";
                              $response->causa = mysqli_error($con);
                          }
                      } else {
                          $sqlInsertJugador = "INSERT INTO jugador (id, baja, tarjetaSanitaria, numero_dorsal, talla_ropa_juego, nombre_dorsal) VALUES ('$idPersonaMas18',0,'$tsiPlayer','$numeroDorsalPlayer','$talla1RopaPlayer','$nomDorsalPlayer')";
                          if (mysqli_query($con, $sqlInsertJugador)) {
                              $response->resultat = "INSERT_JUGADOR_OK";
                          } else {
                              $response->resultat = "INSERT_JUGADOR_KO";
                              $response->causa = mysqli_error($con);
                          }
                      }
                  }
              }
          }
        }
        if(strpos($response->resultat, "KO") === false){
          $sqlInsertJugadorTemporada = "INSERT INTO `jugador_temporada`(`idJugador`,`idTemporada`,`idTipo`,`quota`) VALUES ((SELECT id FROM persona WHERE dni = '$dniPlayer'),(SELECT MAX(id) FROM temporada),$idTipo_pago,$quotas[$dniPlayer]);";
          if (mysqli_query($con, $sqlInsertJugadorTemporada)) {
            $response->resultat = "INSERT_JUGADOR_TEMPORADA_OK";
          } else {
            $response->resultat = "INSERT_JUGADOR_TEMPORADA_KO";
            $response->causa = mysqli_error($con);
          }
        }

        if(strpos($response->resultat, "KO") === false){
          if (!empty($userPassword)) {
            $sqlInsertUsuarioJugador = "INSERT INTO usuario VALUES ((SELECT id FROM persona WHERE dni = '$dniPlayer'),2,'$dniPlayer',AES_ENCRYPT('$userPassword', UNHEX(SHA2('W1f1Nu7s2017',512))),NULL,0,NULL,NULL,NULL,NULL,NULL)";
            if (mysqli_query($con, $sqlInsertUsuarioJugador)) {
              $response->resultat = "INSERT_USUARIO_JUGADOR_OK";
              emailConf($namePlayer, $firstSurnamePlayer, $secondSurnamePlayer, $emailPlayer, $dniPlayer);
            } else {
              $response->resultat = "INSERT_USUARIO_JUGADOR_KO";
              $response->causa = mysqli_error($con);
            }
          }
        }
      } else if ($esMas18 == false) {
        //echo '-18';
        $arrayJugadores = [];
        foreach($dataForm as $k => $value){
          if ($k == 'form_fields[nomTutor]') {$nameTutor = $value;}
          if ($k == 'form_fields[primerCognomTutor]') { $firstSurnameTutor = $value;}
          if ($k == 'form_fields[segonCognomTutor]') {$secondSurnameTutor = $value;}
          if ($k == 'form_fields[dniTutor]') {$dniTutor = $value;}
          if ($k == 'form_fields[domiciliTutor]') {$addressTutor = $value;}
          if ($k == 'form_fields[poblacioTutor]') {$populationTutor = $value;}
          if ($k == 'form_fields[codiPostalTutor]') { $postalCodeTutor = $value;}
          if ($k == 'form_fields[emailTutor]') {$emailTutor = $value;}
          if ($k == 'form_fields[telefonTutor]') {$phoneTutor = $value;}
          if ($k == 'form_fields[parentescTutor]') {$parentescTutor = $value;}
          if ($k == 'campoCodePlayer') {$codePlayer = $value;}
          if ($k == 'form_fields[campoUserPassword]') {$userPassword = $value;}
          if ($k == 'Jugadores') {
            foreach($value as $jugador){
              //print_r($jugador);
              $arrayJugador = [];
              foreach($jugador as $k2 => $value2){
                if ($k2 == 'form_fields[nomJugador]') {$arrayJugador['Name'] = $value2;}
                if ($k2 == 'form_fields[primerCognomJugador]') {$arrayJugador['FirstSurname'] = $value2;}
                if ($k2 == 'form_fields[segonCognomJugador]') {$arrayJugador['SecondSurname'] = $value2;}
                if ($k2 == 'form_fields[dataNaixementJugador]') {$arrayJugador['DateOfBirth'] = $value2;}
                if ($k2 == 'form_fields[dniJugador]') {$arrayJugador['DNI'] = $value2;}
                if ($k2 == 'form_fields[tarjetaSanitariaJugador]') {$arrayJugador['TSI'] = $value2;}
                if ($k2 == 'form_fields[domiciliJugador]') {$arrayJugador['Address'] = $value2;}
                if ($k2 == 'form_fields[poblacioJugador]') {$arrayJugador['Population'] = $value2;}
                if ($k2 == 'form_fields[codiPostalJugador]') {$arrayJugador['PostalCode'] = $value2;}
                if ($k2 == 'form_fields[emailJugador]') {$arrayJugador['Email'] = $value2;}
                if ($k2 == 'form_fields[telefonJugador]') {$arrayJugador['Phone'] = $value2;}
                if ($k2 == 'form_fields[escolaJugador]') {$arrayJugador['School'] = $value2;}
                if ($k2 == 'form_fields[cursJugador]') {$arrayJugador['Course'] = $value2;}
                if ($k2 == 'form_fields[observacionsJugador]') {$arrayJugador['Observations'] = $value2;}
                if ($k2 == 'form_fields[categoriaSexoJugador]') {$arrayJugador['Sexo'] = $value2;}
                if ($k2 == 'form_fields[numeroDorsalJugador]') {$arrayJugador['NumeroDorsal'] = $value2;}
                if ($k2 == 'form_fields[talla1RopaJugador]') {$arrayJugador['Talla1Ropa'] = $value2;}
                if ($k2 == 'form_fields[nomDorsalJugador]') {$arrayJugador['NomDorsal'] = $value2;}
                if ($k2 == 'form_fields[camisetaTecnicaEscoletaJugador]') {$arrayJugador['TallaCamisetaTecnicaEscoleta'] = $value2;}
              }
              array_push($arrayJugadores, $arrayJugador);
            }
          }
        }
        //print_r($arrayJugadores);
        $sexoTutor = 3;

        $quotas = array();
        if (!empty($codePlayer)) {
          $horaActual = date('H:i:s');
          $date = date_create($horaActual);
          $horaNumero = date_format($date, 'His');
          $idTransaccion = str_pad($bdData['id'],6,"0",STR_PAD_LEFT).$horaNumero;

          $concepto = substr($bdData['pagina'], 0, -10);
          $importeDatosIntermedios = $bdData['pago_Importe'];

          $dataForm = json_decode($bdData['data']);
          $esMas18 = $dataForm->form_mas18;

          $arrayJugadoresPresupuesto = [];
          foreach($dataForm as $k => $value){
            if ($k == 'form_fields[dniTutor]') {$dniTutor = $value;}
            if ($k == 'form_fields[dniJugador]') {$dniJugador = $value;}
            if ($k == 'form_fields[primerCognomJugador]') {$primerCognomJugador = $value;}
            if ($k == 'Jugadores') {
              foreach($value as $jugador){
                //print_r($jugador);
                $arrayJugadorPresupuesto = [];
                foreach($jugador as $k2 => $value2){
                  if ($k2 == 'form_fields[nomJugador]') {$arrayJugadorPresupuesto['Name'] = $value2;}
                  if ($k2 == 'form_fields[primerCognomJugador]') {$arrayJugadorPresupuesto['FirstSurname'] = $value2;}
                  if ($k2 == 'form_fields[dniJugador]') {$arrayJugadorPresupuesto['DNI'] = $value2;}
                }
                array_push($arrayJugadoresPresupuesto, $arrayJugadorPresupuesto);
              }
            }
          }

          $presupuestoForm = json_decode($bdData['presupuesto']);
          foreach($presupuestoForm as $k => $value){
            if ($k == 'lineas') {
              foreach($value as $persona){
                foreach($persona as $k2 => $value2){
                  if ($k2 == 'nombre') {

                    for ($i=0; $i < count($arrayJugadoresPresupuesto); $i++) {
                      $name = $arrayJugadoresPresupuesto[$i]['Name'].' '.$arrayJugadoresPresupuesto[$i]['FirstSurname'];
                      $dniJugador = $arrayJugadoresPresupuesto[$i]['DNI'];

                      if ($value2 == $name) {
                        $importeJugador = $persona->importe;
                        $restanteJugador = $persona->restante;

                        $importePagado = $importeJugador - $restanteJugador;

                        $quotas[$dniJugador] = $importeJugador;

                        $insertMovimiento = true;
                        $sqlCheckDobleMovimiento = "SELECT * FROM movimientos WHERE `idDatosIntermedios` = '$id' AND `dniTutor` = '$dniTutor' AND `dniJugador` = '$dniJugador' AND `idTransaccion` = '$idTransaccion'";
                        //echo $sqlCheckDobleMovimiento;
                        if ($resultCheckDobleMovimiento = mysqli_query($con, $sqlCheckDobleMovimiento)){
                          if ($resultCheckDobleMovimiento->num_rows > 0){
                            $insertMovimiento = false;
                          }
                        }

                        if ($insertMovimiento){
                          $sqlInsertPago = "INSERT INTO movimientos(`id`,`idDatosIntermedios`,`dniTutor`,`dniJugador`,`idTransaccion`,`fechaTransaccion`,`tipo_pago`,`descripcion`,`importe`,`pagoManual`,`pagoCompletado`) VALUES (NULL,'$id','$dniTutor','$dniJugador','$idTransaccion',CURRENT_TIMESTAMP(),$idTipo_pago,'Pago inscripcion','$importePagado',1,0);";
                          if (mysqli_query($con, $sqlInsertPago)) {
                            $insertPagoCorrecto = true;
                            $response->resultat = "INSERT_PAGO_OK";

                            $descripcionPago = $bdData['pagina'].': ';
                            $presupuestoForm = json_decode($bdData['presupuesto']);
                            foreach($presupuestoForm as $k3 => $value3){
                              if ($k3 == 'lineas') {
                                foreach($value3 as $persona2){
                                  foreach($persona2 as $k4 => $value4){
                                    if ($k4 == 'nombre') {$descripcionPago .= $value4.', ';}
                                  }
                                }
                              }
                            }
                            $descripcionPago = substr($descripcionPago, 0, -2);

                            $idDatosIntermedios = $bdData['id'];
                            $sqlUpdatePagoOKDatosIntermedios = "UPDATE datos_intermedios SET pagoOK = CURRENT_TIMESTAMP() WHERE id = '$idDatosIntermedios' ";
                            if (mysqli_query($con, $sqlUpdatePagoOKDatosIntermedios)) {
                                $response->resultat = "UPDATE_PAGO_DATOS_INTERMEDIOS_OK";
                                $msgTitulo = 'Inscripción finalizada correctamente';
                            } else {
                                $response->resultat = "UPDATE_PAGO_DATOS_INTERMEDIOS_KO";
                                $response->causa = mysqli_error($con);
                                $msgTitulo = 'La Inscripción no a finalizada correctamente';
                            }
                          } else {
                            $insertPagoCorrecto = false;
                            $response->resultat = "INSERT_PAGO_KO";
                            $response->causa = mysqli_error($con);
                          }
                        }

                        //$sql .= $sqlInsertPago;
                      }
                    }
                    /*$response->resultat = $name;
                    $response->causa = $sql;*/

                  }
                }
              }
            }
          }

          if ($insertPagoCorrecto == true) {
?>
            <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Google' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'><?php echo $msgTitulo;?> </div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td align='center'> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'><?php echo $descripcionPago;?></a> </td></tr></tbody> </table> </div><div style='padding-top:32px;text-align:center'> <a href='https://basquetlloret.com/' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Ir a inicio</a> </div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
<?php
          } else if ($insertPagoCorrecto == false) {
?>
            <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Google' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'> La Inscripción no a finalizada correctamente </div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td align='center'> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'><?php echo $descripcionPago;?></a> </td></tr></tbody> </table> </div><div style='padding-top:32px;text-align:center'> <a href='https://basquetlloret.com/' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Ir a inicio</a> </div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
<?php
          }
        } else {
          $arrayJugadoresPresupuesto = [];
          foreach($dataForm as $k => $value){
            if ($k == 'form_fields[dniTutor]') {$dniTutor = $value;}
            if ($k == 'form_fields[dniJugador]') {$dniJugador = $value;}
            if ($k == 'form_fields[primerCognomJugador]') {$primerCognomJugador = $value;}
            if ($k == 'Jugadores') {
              foreach($value as $jugador){
                //print_r($jugador);
                $arrayJugadorPresupuesto = [];
                foreach($jugador as $k2 => $value2){
                  if ($k2 == 'form_fields[nomJugador]') {$arrayJugadorPresupuesto['Name'] = $value2;}
                  if ($k2 == 'form_fields[primerCognomJugador]') {$arrayJugadorPresupuesto['FirstSurname'] = $value2;}
                  if ($k2 == 'form_fields[dniJugador]') {$arrayJugadorPresupuesto['DNI'] = $value2;}
                }
                array_push($arrayJugadoresPresupuesto, $arrayJugadorPresupuesto);
              }
            }
          }

          $presupuestoForm = json_decode($bdData['presupuesto']);
          foreach($presupuestoForm as $k => $value){
            if ($k == 'lineas') {
              foreach($value as $persona){
                foreach($persona as $k2 => $value2){
                  if ($k2 == 'nombre') {

                    for ($i=0; $i < count($arrayJugadoresPresupuesto); $i++) {
                      $name = $arrayJugadoresPresupuesto[$i]['Name'].' '.$arrayJugadoresPresupuesto[$i]['FirstSurname'];
                      $dniJugador = $arrayJugadoresPresupuesto[$i]['DNI'];

                      if ($value2 == $name) {
                        $importeJugador = $persona->importe;
                        $restanteJugador = $persona->restante;

                        $importePagado = $importeJugador - $restanteJugador;

                        $quotas[$dniJugador] = $importeJugador;
                      }
                    }
                  }
                }
              }
            }
          }
        }

        if(strpos($response->resultat, "KO") === false){
          $sqlExistDniTutor = "SELECT dni FROM persona WHERE dni = '$dniTutor'";
          if ($result = mysqli_query($con, $sqlExistDniTutor)) {
            if ($result->num_rows > 0) {
              $sqlUpdatePersonaT = "UPDATE persona SET nombre = '$nameTutor', primer_apellido = '$firstSurnameTutor', segundo_apellido = '$secondSurnameTutor', direccion = '$addressTutor', codigo_postal = '$postalCodeTutor', localidad = '$populationTutor', telefono1 = '$phoneTutor', email = '$emailTutor' WHERE dni = '$dniTutor'";
              if (mysqli_query($con, $sqlUpdatePersonaT)) {
                $response->resultat = "UPDATE_PERSONA_TUTOR_OK";
              } else {
                $response->resultat = "UPDATE_PERSONA_TUTOR_KO";
                $response->causa = mysqli_error($con);
              }
            } else {
              $sqlInsertPersonaT = "INSERT INTO persona VALUES (NULL, NULL,'$nameTutor','$firstSurnameTutor','$secondSurnameTutor','$dniTutor','1900-01-01','$addressTutor','$postalCodeTutor','$populationTutor','$phoneTutor',NULL,'$emailTutor','',$sexoTutor)";
              if (mysqli_query($con, $sqlInsertPersonaT)) {
                $response->resultat = "INSERT_PERSONA_TUTOR_OK";
              } else {
                $response->resultat = "INSERT_PERSONA_TUTOR_KO";
                $response->causa = mysqli_error($con);
              }
            }
            //Check Tabla familiar
            $sqlExistTutorFamiliar = "SELECT id FROM familiar WHERE id = (SELECT id FROM persona WHERE dni = '$dniTutor')";
            if ($resultFamiliar = mysqli_query($con, $sqlExistTutorFamiliar)) {
              if ($resultFamiliar->num_rows > 0) {
                $sqlUpdateRelacionTutor = "UPDATE familiar SET baja = 0, fecha_baja = NULL WHERE id = (SELECT id FROM persona WHERE dni = '$dniTutor')";
                if (mysqli_query($con, $sqlUpdateRelacionTutor)) {
                  $response->resultat = "UPDATE_RELACION_TUTOR_OK";
                } else {
                  $response->resultat = "UPDATE_RELACION_TUTOR_KO";
                  $response->causa = mysqli_error($con);
                }
              } else {
                $sqlInsertRelacionTutor = "INSERT INTO familiar VALUES ((SELECT id FROM persona WHERE dni = '$dniTutor'),0,NULL)";
                if (mysqli_query($con, $sqlInsertRelacionTutor)) {
                  $response->resultat = "INSERT_RELACION_TUTOR_OK";
                } else {
                  $response->resultat = "INSERT_RELACION_TUTOR_KO";
                  $response->causa = mysqli_error($con);
                }
              }
            }
          }
          if(strpos($response->resultat, "KO") === false){
            foreach($arrayJugadores as $k => $value){
              foreach($value as $k2 => $value2){
                if($k2 == 'Name'){$nameJ = $value2;}
                if($k2 == 'FirstSurname'){$firstSurnameJ = $value2;}
                if($k2 == 'SecondSurname'){$secondSurnameJ = $value2;}
                if($k2 == 'DateOfBirth'){$dateOfBirthJ = $value2;}
                if($k2 == 'DNI'){$dniJ = $value2;}
                if($k2 == 'TSI'){$tsiJ = $value2;}
                if($k2 == 'Address'){$addressJ = $value2;}
                if($k2 == 'Population'){$populationJ = $value2;}
                if($k2 == 'PostalCode'){$postalCodeJ = $value2;}
                if($k2 == 'Email'){$emailJ = $value2;}
                if($k2 == 'Phone'){$phoneJ = $value2;}
                if($k2 == 'School'){$schoolJ = $value2;}
                if($k2 == 'Course'){$courseJ = $value2;}
                if($k2 == 'Observations'){$observationsJ = $value2;}
                if($k2 == 'Sexo'){$sexoJ = $value2;}
                if($k2 == 'NumeroDorsal') {$numeroDorsalJ = $value2;}
                if($k2 == 'Talla1Ropa') {$talla1RopaJ = $value2;}
                if($k2 == 'NomDorsal') {$nomDorsalJ = $value2;}
                if($k2 == 'TallaCamisetaTecnicaEscoleta') {$tallaCamisetaTecnicaEscoletaJ = $value2;}
              }

              $sqlExistDniJugador = "SELECT dni FROM persona WHERE dni = '$dniJ'";
              if ($result = mysqli_query($con, $sqlExistDniJugador)) {
                  if ($result->num_rows > 0) {
                      $sqlUpdatePersonaJ = "UPDATE persona SET nombre = '$nameJ', primer_apellido = '$firstSurnameJ', segundo_apellido = '$secondSurnameJ', fecha_nacimiento = '$dateOfBirthJ', direccion = '$addressJ', codigo_postal = '$postalCodeJ', localidad = '$populationJ', telefono1 = '$phoneJ', email = '$emailJ', observaciones = '$observationsJ', id_sexo = '$sexoJ' WHERE dni = '$dniJ'";
                      if (mysqli_query($con, $sqlUpdatePersonaJ)) {
                          $response->resultat = "UPDATE_PERSONA_JUGADOR_OK";
                      } else {
                          $response->resultat = "UPDATE_PERSONA_JUGADOR_KO";
                          $response->causa = mysqli_error($con);
                      }
                  } else {
                      $sqlExistNombreCompleto = "SELECT * FROM persona WHERE nombre = '$nameJ' AND primer_apellido = '$firstSurnameJ' AND segundo_apellido = '$secondSurnameJ' AND fecha_nacimiento = '$dateOfBirthJ'";
                      if ($result = mysqli_query($con, $sqlExistNombreCompleto)) {
                          if ($result->num_rows > 0) {
                              $personaData = mysqli_fetch_array($result, MYSQLI_ASSOC);

                              $idPersonaMenos18 = $personaData['id'];
                              $sqlUpdatePersonaJ = "UPDATE persona SET nombre = '$nameJ', primer_apellido = '$firstSurnameJ', segundo_apellido = '$secondSurnameJ', dni = '$dniJ', fecha_nacimiento = '$dateOfBirthJ', direccion = '$addressJ', codigo_postal = '$postalCodeJ', localidad = '$populationJ', telefono1 = '$phoneJ', email = '$emailJ', observaciones = '$observationsJ', id_sexo = '$sexoJ' WHERE id = '$idPersonaMenos18'";
                              if (mysqli_query($con, $sqlUpdatePersonaJ)) {
                                  $response->resultat = "UPDATE_PERSONA_JUGADOR_OK";
                              } else {
                                  $response->resultat = "UPDATE_PERSONA_JUGADOR_KO";
                                  $response->causa = mysqli_error($con);
                              }
                          } else {
                              $sqlInsertPersonaJ = "INSERT INTO persona VALUES (NULL, NULL,'$nameJ','$firstSurnameJ','$secondSurnameJ','$dniJ','$dateOfBirthJ','$addressJ','$postalCodeJ','$populationJ','$phoneJ',NULL,'$emailJ','$observationsJ','$sexoJ')";
                              if (mysqli_query($con, $sqlInsertPersonaJ)) {
                                  $response->resultat = "INSERT_PERSONA_JUGADOR_OK";
                                  $idLastPerson = mysqli_insert_id($con);
                              } else {
                                  $response->resultat = "INSERT_PERSONA_JUGADOR_KO";
                                  $response->causa = mysqli_error($con);
                              }
                          }
                      }
                  }
              }
              if(strpos($response->resultat, "KO") === false){
                  $sqlConsultaIdPersona = "SELECT * FROM persona WHERE dni = '$dniJ'";
                  if ($resultPersona = mysqli_query($con, $sqlConsultaIdPersona)) {
                      $personaData = mysqli_fetch_array($resultPersona, MYSQLI_ASSOC);
                      $idPersonaMenos18 = $personaData['id'];

                      $sqlConsultaJugador = "SELECT id FROM jugador WHERE id = '$idPersonaMenos18'";
                      if ($result = mysqli_query($con, $sqlConsultaJugador)) {
                          if ($result->num_rows > 0) {
                              $sqlUpdateRelacionJugador = "UPDATE jugador SET baja = 0, tarjetaSanitaria = '$tsiJ', escuela = '$schoolJ', curso = '$courseJ', talla_camiseta_tecnica_escoleta = '$tallaCamisetaTecnicaEscoletaJ' WHERE id = '$idPersonaMenos18'";
                              if (mysqli_query($con, $sqlUpdateRelacionJugador)) {
                                  $response->resultat = "UPDATE_RELACION_JUGADOR_OK";
                              } else {
                                  $response->resultat = "UPDATE_RELACION_JUGADOR_KO";
                                  $response->causa = mysqli_error($con);
                              }
                          } else {
                              $sqlInsertRelacionJugador = "INSERT INTO jugador (id, baja, tarjetaSanitaria, escuela, curso, talla_camiseta_tecnica_escoleta) VALUES ('$idPersonaMenos18',0,'$tsiJ','$schoolJ','$courseJ','$tallaCamisetaTecnicaEscoletaJ')";
                              if (mysqli_query($con, $sqlInsertRelacionJugador)) {
                                  $response->resultat = "INSERT_RELACION_JUGADOR_OK";
                              } else {
                                  $response->resultat = "INSERT_RELACION_JUGADOR_KO";
                                  $response->causa = mysqli_error($con);
                              }
                          }
                      }
                  }
              }
              if(strpos($response->resultat, "KO") === false){
                $sqlConsultaFamiliar = "SELECT id_familiar, id_jugador FROM familiar_jugador WHERE id_familiar = (SELECT id FROM persona WHERE dni = '$dniTutor') AND id_jugador = (SELECT id FROM persona WHERE dni = '$dniJ')";
                if ($result = mysqli_query($con, $sqlConsultaFamiliar)) {
                  if ($result->num_rows > 0) {
                    $sqlUpdateRelacionTutorJugador = "UPDATE familiar_jugador SET tipo_parentesco = '$parentescTutor' WHERE id_familiar = (SELECT id FROM persona WHERE dni = '$dniTutor') AND id_jugador = (SELECT id FROM persona WHERE dni = '$dniJ')";
                    if (mysqli_query($con, $sqlUpdateRelacionTutorJugador)) {
                      $response->resultat = "UPDATE_RELACION_TUTOR-JUGADOR_OK";
                    } else {
                      $response->resultat = "UPDATE_RELACION_TUTOR-JUGADOR_KO";
                      $response->causa = mysqli_error($con);
                    }
                  } else {
                    $sqlInsertRelacionTutorJugador = "INSERT INTO familiar_jugador VALUES ((SELECT id FROM persona WHERE dni = '$dniTutor'),(SELECT id FROM persona WHERE dni = '$dniJ'),'$parentescTutor')";
                    if (mysqli_query($con, $sqlInsertRelacionTutorJugador)) {
                      $response->resultat = "INSERT_RELACION_TUTOR-JUGADOR_OK";
                    } else {
                      $response->resultat = "INSERT_RELACION_TUTOR-JUGADOR_KO";
                      $response->causa = mysqli_error($con);
                    }
                  }
                }
              }
              if(strpos($response->resultat, "KO") === false){
                $sqlInsertJugadorTemporada = "INSERT INTO `jugador_temporada`(`idJugador`,`idTemporada`,`idTipo`,`quota`) VALUES ((SELECT id FROM persona WHERE dni = '$dniJ'),(SELECT MAX(id) FROM temporada),$idTipo_pago,$quotas[$dniJ]);";
                if (mysqli_query($con, $sqlInsertJugadorTemporada)) {
                  $response->resultat = "INSERT_JUGADOR_TEMPORADA_OK";
                } else {
                  $response->resultat = "INSERT_JUGADOR_TEMPORADA_KO";
                  $response->causa = mysqli_error($con);
                }
              }
            }
          }
        }

        if(strpos($response->resultat, "KO") === false){
          if (!empty($userPassword)) {
            $sqlInsertUsuarioTutor = "INSERT INTO usuario VALUES ((SELECT id FROM persona WHERE dni = '$dniTutor'),2,'$dniTutor',AES_ENCRYPT('$userPassword', UNHEX(SHA2('W1f1Nu7s2017',512))),NULL,0,NULL,NULL,NULL,NULL,NULL)";
            if (mysqli_query($con, $sqlInsertUsuarioTutor)) {
              $response->resultat = "INSERT_USUARIO_TUTOR_OK";
              emailConf($nameTutor, $firstSurnameTutor, $secondSurnameTutor, $emailTutor, $dniTutor);
            } else {
              $response->resultat = "INSERT_USUARIO_TUTOR_KO";
              $response->causa = mysqli_error($con);
            }
          }
        }
      }
    } else {$response->resultat = "Tabla vacia";}
  }
  //echo json_encode($response);
?>

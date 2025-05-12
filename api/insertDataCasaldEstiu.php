<?php

  $descripcionPago = "Pago casal d'Estiu";

  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';
  require_once 'logger.php';

  try {
    $con = returnConection();
    $response = new Result();

    writeLog("Iniciando proceso de inserción de datos del Casal d'Estiu", 'INFO');
    writeLog("Datos POST recibidos: " . json_encode($_POST, JSON_PRETTY_PRINT), 'DEBUG');

    if (!isset($_POST['id'])) {
      throw new Exception("ID no proporcionado");
    }
    $id = $_POST['id'];
    writeLog("ID de datos_intermedios: $id", 'INFO');
  } catch (Exception $e) {
    writeLog("Error al procesar datos: ". $e->getMessage(), 'ERROR');
    $response->resultat = "KO";
    echo json_encode($response);
    exit;
  }
  // Iniciar transacción
  mysqli_begin_transaction($con);

  $sqlConsult = "SELECT * FROM datos_intermedios AS di WHERE di.id = ?";
  $stmt = mysqli_prepare($con, $sqlConsult);
  mysqli_stmt_bind_param($stmt, 's', $id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  
  writeLog("Intentando ejecutar consulta a datos_intermedios", 'INFO');

  if ($result && $result->num_rows > 0) {
    writeLog("N filas obtenidas: " . $result->num_rows);
    $bdData = mysqli_fetch_array($result, MYSQLI_ASSOC);
    writeLog("Datos Obtenidos: " . json_encode($bdData, JSON_PRETTY_PRINT), 'DEBUG');

    $dataForm = json_decode($bdData['data']);
    $presupuestoForm = json_decode($bdData['presupuesto']);
    
    // Procesar cada jugador del formulario
    foreach ($dataForm->Jugadores as $jugador) {
        $dniJugador = $jugador->{'form_fields[dniJugador]'};
        $nombreJugador = $jugador->{'form_fields[nomJugador]'};
        $apellido1 = $jugador->{'form_fields[primerCognomJugador]'};
        $apellido2 = $jugador->{'form_fields[segonCognomJugador]'};
        
        // Insertar o actualizar jugador en tabla persona
        $sqlJugador = "INSERT INTO persona (dni, nombre, primer_apellido, segundo_apellido) 
                       VALUES (?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE 
                       nombre = VALUES(nombre),
                       primer_apellido = VALUES(primer_apellido),
                       segundo_apellido = VALUES(segundo_apellido)";
        
        $stmt = mysqli_prepare($con, $sqlJugador);
        mysqli_stmt_bind_param($stmt, 'ssss', $dniJugador, $nombreJugador, $apellido1, $apellido2);
        mysqli_stmt_execute($stmt);
        
        // Obtener ID del jugador
        $sqlGetJugadorId = "SELECT id FROM persona WHERE dni = ?";
        $stmt = mysqli_prepare($con, $sqlGetJugadorId);
        mysqli_stmt_bind_param($stmt, 's', $dniJugador);
        mysqli_stmt_execute($stmt);
        $resultJugador = mysqli_stmt_get_result($stmt);
        $jugadorData = mysqli_fetch_assoc($resultJugador);
        $idJugador = $jugadorData['id'];

        // Procesar semanas seleccionadas
        foreach ($presupuestoForm->lineas as $linea) {
            if ($linea->nombre === $nombreJugador . ' ' . $apellido1) {
                foreach ($linea->turnos as $semana) {
                    // Obtener ID de la semana
                    $numeroSemana = substr($semana, -1);
                    writeLog("Procesando semana número: $numeroSemana", 'DEBUG');
                    
                    $sqlSemana = "SELECT id_semana FROM semanas_campus WHERE id_semana = ? AND temporada_id = 7";
                    $stmt = mysqli_prepare($con, $sqlSemana);
                    mysqli_stmt_bind_param($stmt, 'i', $numeroSemana);
                    mysqli_stmt_execute($stmt);
                    $resultSemana = mysqli_stmt_get_result($stmt);
                    
                    if (!$resultSemana) {
                        writeLog("Error al consultar semana: " . mysqli_error($con), 'ERROR');
                        continue;
                    }
                    
                    $semanaData = mysqli_fetch_assoc($resultSemana);
                    if (!$semanaData) {
                        writeLog("No se encontró la semana $numeroSemana o no está activa", 'WARNING');
                        continue;
                    }
                    
                    $idSemana = $semanaData['id_semana'];
                    writeLog("ID de semana encontrado: $idSemana", 'DEBUG');

                    // Insertar relación jugador-semana
                    $sqlInsertSemana = "INSERT INTO jugador_semana (jugador_id, semana_id) VALUES (?, ?)";
                    $stmt = mysqli_prepare($con, $sqlInsertSemana);
                    mysqli_stmt_bind_param($stmt, 'ii', $idJugador, $idSemana);
                    mysqli_stmt_execute($stmt);
                    
                    writeLog("Semana $numeroSemana asignada al jugador $nombreJugador", 'INFO');
                }
            }
        }
    }

    // Actualizar estado de pago
    $sqlUpdatePago = "UPDATE datos_intermedios SET pagoOK = CURRENT_TIMESTAMP() WHERE id = ?";
    $stmt = mysqli_prepare($con, $sqlUpdatePago);
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);

    // Confirmar transacción
    mysqli_commit($con);
    $response->resultat = "OK";
    writeLog("Proceso de inserción completado exitosamente", 'INFO');
  }

  // Obtener el valor de esMas18 del formulario
  $esMas18 = isset($dataForm->form_mas18) ? $dataForm->form_mas18 : false;

  if ($esMas18 == true) {
    try {
      writeLog("Procesando datos para jugador mayor de 18", 'INFO');
      writeLog("Iniciando procesamiento de datos del jugador", 'INFO');
      
      // Mapeo de campos del formulario a variables
      $formFields = [
        'form_fields[nomJugador]' => ['var' => 'namePlayer', 'desc' => 'Nombre del jugador'],
        'form_fields[primerCognomJugador]' => ['var' => 'firstSurnamePlayer', 'desc' => 'Primer apellido'],
        'form_fields[segonCognomJugador]' => ['var' => 'secondSurnamePlayer', 'desc' => 'Segundo apellido'],
        'form_fields[dataNaixementJugador]' => ['var' => 'dateOfBirthPlayer', 'desc' => 'Fecha de nacimiento'],
        'form_fields[dniJugador]' => ['var' => 'dniPlayer', 'desc' => 'DNI'],
        'form_fields[tarjetaSanitariaJugador]' => ['var' => 'tsiPlayer', 'desc' => 'Tarjeta sanitaria'],
        'form_fields[domiciliJugador]' => ['var' => 'addressPlayer', 'desc' => 'Dirección'],
        'form_fields[poblacioJugador]' => ['var' => 'populationPlayer', 'desc' => 'Población'],
        'form_fields[codiPostalJugador]' => ['var' => 'postalCodePlayer', 'desc' => 'Código postal'],
        'form_fields[emailJugador]' => ['var' => 'emailPlayer', 'desc' => 'Email'],
        'form_fields[telefonJugador]' => ['var' => 'phonePlayer', 'desc' => 'Teléfono'],
        'form_fields[escolaJugador]' => ['var' => 'schoolPlayer', 'desc' => 'Escuela'],
        'form_fields[cursJugador]' => ['var' => 'coursePlayer', 'desc' => 'Curso'],
        'form_fields[observacionsJugador]' => ['var' => 'observationsPlayer', 'desc' => 'Observaciones'],
        'form_fields[categoriaSexoJugador]' => ['var' => 'sexoPlayer', 'desc' => 'Sexo'],
        'form_fields[numeroDorsalJugador]' => ['var' => 'numeroDorsalPlayer', 'desc' => 'Número dorsal'],
        'form_fields[talla1RopaJugador]' => ['var' => 'talla1RopaPlayer', 'desc' => 'Talla ropa'],
        'form_fields[camisetaSummerWorkoutJugador]' => ['var' => 'tallaCamisetaSummerWorkoutPlayer', 'desc' => 'Talla camiseta Summer'],
        'form_fields[nomDorsalJugador]' => ['var' => 'nomDorsalPlayer', 'desc' => 'Nombre dorsal'],
        'form_fields[campoUserPassword]' => ['var' => 'userPassword', 'desc' => 'Password'],
        'campoCodePlayer' => ['var' => 'codePlayer', 'desc' => 'Código jugador']
      ];

      // Procesar campos del formulario
      foreach ($formFields as $field => $config) {
        if (isset($dataForm->$field)) {
          ${$config['var']} = $dataForm->$field;
          writeLog("{$config['desc']}: {$dataForm->$field}", 'DEBUG');
        }
      }
      
      writeLog("Finalizado procesamiento de datos del jugador", 'INFO');
    } catch (Exception $e) {
      writeLog("Error procesando datos del jugador: " . $e->getMessage(), 'ERROR');
      throw $e;
    }

        $quotas = array();
        writeLog("Iniciando procesamiento de pago", 'INFO');
        if (!empty($codePlayer)) {
          writeLog("Código de jugador presente, procesando pago", 'INFO');
          $horaActual = date('H:i:s');
          $date = date_create($horaActual);
          $horaNumero = date_format($date, 'His');
          $idTransaccion = str_pad($bdData['id'],6,"0",STR_PAD_LEFT).$horaNumero;
          writeLog("ID de transacción generado: $idTransaccion", 'DEBUG');

          $concepto = substr($bdData['pagina'], 0, -10);
          writeLog("Concepto de pago: $concepto", 'DEBUG');

          // Ya tenemos los datos del formulario procesados anteriormente

          $sqlPrimerEquipo = "SELECT porcentaje FROM descuentosTemporada WHERE dni = ? AND porcentaje = 100 AND idTipo = (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular' AND idTemporada = (Select MAX(id) FROM temporada))";
          writeLog("Verificando descuentos del primer equipo para el jugador", 'DEBUG');
          
          $stmt = mysqli_prepare($con, $sqlPrimerEquipo);
          mysqli_stmt_bind_param($stmt, 's', $dniPlayer);
          mysqli_stmt_execute($stmt);
          $resultPrimerEquipo = mysqli_stmt_get_result($stmt);
          if ($resultPrimerEquipo->num_rows > 0){
            $jugadorNoPaga = 1;
            writeLog("Jugador tiene descuento del 100%", 'INFO');
          } else {
            $jugadorNoPaga = 0;
            writeLog("Jugador no tiene descuento del 100%", 'INFO');
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
                    writeLog("Importe total del jugador: $importeJugador", 'DEBUG');
                    writeLog("Importe restante: $restanteJugador", 'DEBUG');

                    $importePagado = $importeJugador - $restanteJugador;
                    writeLog("Importe a pagar calculado: $importePagado", 'INFO');

                    $quotas[$dniPlayer] = $importeJugador;
                    writeLog("Cuota asignada al jugador $dniPlayer: $importeJugador", 'DEBUG');

                    if($importeDatosIntermedios == 0){
                      $importePagado = 0;
                      $quotas[$dniPlayer] = 0;
                      writeLog("Importe datos intermedios es 0, reseteando importes", 'INFO');
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
                          $sqlUpdateJugador = "UPDATE jugador SET baja = 0, tarjetaSanitaria = '$tsiPlayer', talla_camiseta_summer_workout = '$tallaCamisetaSummerWorkoutPlayer' WHERE id = '$idPersonaMas18'";
                          if (mysqli_query($con, $sqlUpdateJugador)) {
                              $response->resultat = "UPDATE_JUGADOR_OK";
                          } else {
                              $response->resultat = "UPDATE_JUGADOR_KO";
                              $response->causa = mysqli_error($con);
                          }
                      } else {
                          $sqlInsertJugador = "INSERT INTO jugador (id, baja, tarjetaSanitaria, talla_camiseta_summer_workout) VALUES ('$idPersonaMas18',0,'$tsiPlayer','$tallaCamisetaSummerWorkoutPlayer')";
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
          // Insertar en jugador_temporada
          $sqlInsertJugadorTemporada = "INSERT INTO `jugador_temporada`(`idJugador`,`idTemporada`,`idTipo`,`quota`) VALUES ((SELECT id FROM persona WHERE dni = '$dniPlayer'),(SELECT MAX(id) FROM temporada),$idTipo_pago,$quotas[$dniPlayer]);";
          if (mysqli_query($con, $sqlInsertJugadorTemporada)) {
            $response->resultat = "INSERT_JUGADOR_TEMPORADA_OK";
            
            // Obtener el ID del jugador
            $idJugador = mysqli_insert_id($con);
            writeLog("ID del jugador obtenido: $idJugador");
            
            // Insertar las semanas seleccionadas en jugador_semana
            $presupuestoForm = json_decode($bdData['presupuesto']);
            writeLog("Estructura del presupuestoForm: " . json_encode($presupuestoForm, JSON_PRETTY_PRINT));
            writeLog("Procesando datos del presupuesto para el jugador: $namePlayer $firstSurnamePlayer $secondSurnamePlayer");
            
            foreach($presupuestoForm as $k => $value){
              if ($k == 'lineas') {
                foreach($value as $persona){
                  // Verificar que estamos procesando el jugador correcto
                  if ($persona->nombre == "$namePlayer $firstSurnamePlayer $secondSurnamePlayer") {
                    if (isset($persona->turnos) && is_array($persona->turnos)) {
                      writeLog("Procesando semanas seleccionadas para: " . $persona->nombre);
                      writeLog("Turnos encontrados: " . json_encode($persona->turnos, JSON_PRETTY_PRINT));
                      foreach($persona->turnos as $semana) {
                        writeLog("Procesando semana: $semana");
                        // Extraer el número de semana
                        $idSemana = (int)filter_var($semana, FILTER_SANITIZE_NUMBER_INT);
                        writeLog("Número de semana extraído: $idSemana");
                        if($idSemana >= 1 && $idSemana <= 9) {
                          // Verificar si la semana ya está asignada
                          $sqlCheckSemana = "SELECT id FROM jugador_semana WHERE id_jugador = $idJugador AND id_semana = $idSemana";
                          writeLog("Verificando semana existente: $sqlCheckSemana");
                          $resultCheckSemana = mysqli_query($con, $sqlCheckSemana);
                          
                          if ($resultCheckSemana && mysqli_num_rows($resultCheckSemana) == 0) {
                            $sqlInsertJugadorSemana = "INSERT INTO jugador_semana (id_jugador, id_semana) VALUES ($idJugador, $idSemana)";
                            writeLog("Intentando insertar semana: $sqlInsertJugadorSemana");
                            if(mysqli_query($con, $sqlInsertJugadorSemana)) {
                              writeLog("Semana $idSemana registrada exitosamente para el jugador $idJugador");
                            } else {
                              writeLog("ERROR al registrar semana $idSemana para jugador $idJugador: " . mysqli_error($con), 'ERROR');
                              writeLog("Error al registrar semana $idSemana: " . mysqli_error($con), 'ERROR');
                              $response->resultat = "ERROR_SEMANA";
                              $response->causa = "Error al registrar la semana $idSemana";
                            }
                          } else {
                            writeLog("La semana $idSemana ya está asignada al jugador $idJugador");
                          }
                        } else {
                          writeLog("Semana inválida detectada: $idSemana", 'ERROR');
                        }
                      }
                    } else {
                      writeLog("No se encontraron semanas seleccionadas para el jugador", 'WARNING');
                    }
                    break; // Salir del bucle una vez procesado el jugador
                  }
                }
              }
            }
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
                if ($k2 == 'form_fields[camisetaSummerWorkoutJugador]') {$arrayJugador['TallaCamisetaSummerWorkout'] = $value2;}
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

          // Ya tenemos los datos del formulario procesados anteriormente

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
                          $sqlInsertPago = "INSERT INTO movimientos(`id`,`idDatosIntermedios`,`dniTutor`,`dniJugador`,`idTransaccion`,`fechaTransaccion`,`tipo_pago`,`descripcion`,`importe`,`pagoManual`,`pagoCompletado`) VALUES (NULL,'$id','$dniTutor','$dniJugador','$idTransaccion',CURRENT_TIMESTAMP(),28,'Pago inscripcion','$importePagado',1,0);";
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
                if($k2 == 'TallaCamisetaSummerWorkout') {$tallaCamisetaSummerWorkoutJ = $value2;}
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
                              $sqlUpdateRelacionJugador = "UPDATE jugador SET baja = 0, tarjetaSanitaria = '$tsiJ', escuela = '$schoolJ', curso = '$courseJ', talla_camiseta_summer_workout = '$tallaCamisetaSummerWorkoutJ' WHERE id = '$idPersonaMenos18'";
                              if (mysqli_query($con, $sqlUpdateRelacionJugador)) {
                                  $response->resultat = "UPDATE_RELACION_JUGADOR_OK";
                              } else {
                                  $response->resultat = "UPDATE_RELACION_JUGADOR_KO";
                                  $response->causa = mysqli_error($con);
                              }
                          } else {
                              $sqlInsertRelacionJugador = "INSERT INTO jugador (id, baja, tarjetaSanitaria, escuela, curso, talla_camiseta_summer_workout) VALUES ('$idPersonaMenos18',0,'$tsiJ','$schoolJ','$courseJ','$tallaCamisetaSummerWorkoutJ')";
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
    
  //echo json_encode($response);
?>
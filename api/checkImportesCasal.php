<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  function writeLog($message, $type = 'INFO') {
    $logFile = __DIR__ . '/../logs/casal.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
  }

  $con = returnConection();
  $response = new Result();
  $importe = new ResultImportesDescuentos();

  writeLog("Procesando cálculo para casal");
  
  // Inicializar variables
  $isPlayerClub = false;
  $isBrothers = false;
  $isSocioClub = false;
  $totalPrice = 0;
  $dniPlayer = '';
  $nombre = '';
  $dniTutor = '';
  $jugadorMas18 = false;
  
  // Obtener datos del POST
  if (isset($_POST['dni'])) {
    $dniPlayer = $_POST['dni'];
  }
  if (isset($_POST['dniTutor'])) {
    $dniTutor = $_POST['dniTutor'];
  }
  if (isset($_POST['nombre'])) {
    $nombre = $_POST['nombre'];
  }
  if (isset($_POST['jugadorMas18'])) {
    $jugadorMas18 = $_POST['jugadorMas18'];
  }
  
  // Verificar si son hermanos
  if (isset($_POST['arrayApellidos'])) {
    $arrayApellidos = $_POST['arrayApellidos'];
    if(count($arrayApellidos) > 1) {
      for ($i=0; $i < count($arrayApellidos); $i++) {
        if($arrayApellidos[0] == $arrayApellidos[$i]) {
          $isBrothers = true;
        } else {
          $isBrothers = false;
          $i = count($arrayApellidos);
        }
      }
    }
  }
  
  // Inicializar arrays para almacenar datos
  $selectedWeeks = array();
  $players = array();
  
  // Procesar jugadores registrados
  if (isset($_POST['registrationPlayers']) && is_array($_POST['registrationPlayers'])) {
    writeLog("Datos recibidos: " . json_encode($_POST));
    
    // Extraer apellidos para verificar hermanos
    $playersSurnames = array();
    foreach ($_POST['registrationPlayers'] as $player) {
      if (isset($player['primerCognomJugador'])) {
        $playersSurnames[] = strtolower(trim($player['primerCognomJugador']));
      }
    }
    
    // Verificar si son hermanos (si hay más de un jugador y todos tienen el mismo apellido)
    if (count($playersSurnames) > 1) {
      $isBrothers = (count(array_unique($playersSurnames)) === 1);
      writeLog("Verificación de hermanos: " . ($isBrothers ? "Sí" : "No") . ", apellidos: " . json_encode($playersSurnames));
    }
    
    // Procesar cada jugador
    foreach ($_POST['registrationPlayers'] as $playerData) {
      $player = array();
      
      // Extraer datos del jugador
      if (isset($playerData['nomJugador'])) {
        $player['nomJugador'] = $playerData['nomJugador'];
      }
      if (isset($playerData['primerCognomJugador'])) {
        $player['primerCognomJugador'] = $playerData['primerCognomJugador'];
      }
      if (isset($playerData['segonCognomJugador'])) {
        $player['segonCognomJugador'] = $playerData['segonCognomJugador'];
      }
      if (isset($playerData['dniJugador'])) {
        $player['dniJugador'] = $playerData['dniJugador'];
      }
      
      // Extraer semanas seleccionadas
      if (isset($playerData['weeksList']) && is_array($playerData['weeksList'])) {
        $selectedWeeks = $playerData['weeksList'];
        $player['weeksList'] = $selectedWeeks;
        
        // Calcular precio según número de semanas seleccionadas
        $numWeeks = count($selectedWeeks);
        writeLog("Jugador: " . $player['nomJugador'] . " " . $player['primerCognomJugador'] . " con " . $numWeeks . " semanas seleccionadas: " . json_encode($selectedWeeks));
        writeLog("Datos completos del jugador: " . json_encode($playerData));
        
        switch($numWeeks) {
          case 1: $playerPrice = 60; break;
          case 2: $playerPrice = 115; break;
          case 3: $playerPrice = 170; break;
          case 4: $playerPrice = 220; break;
          case 5: $playerPrice = 260; break;
          case 6: $playerPrice = 300; break;
          case 7: $playerPrice = 340; break;
          case 8: $playerPrice = 380; break;
          case 9: $playerPrice = 420; break;
          default: $playerPrice = 0; break;
        }
        
        // Guardar precio base
        $player['basePrice'] = $playerPrice;
        $player['finalPrice'] = $playerPrice;
        
        // Aplicar descuento por hermanos (5%)
        if ($isBrothers) {
          $discountBrothers = round($playerPrice * 0.05, 2);
          $player['brothersDiscount'] = $discountBrothers;
          $player['finalPrice'] -= $discountBrothers;
        }
        
        // Verificar si es socio del club (20% descuento)
        $dniToCheck = $jugadorMas18 ? $player['dniJugador'] : $dniTutor;
        $querySocio = "SELECT * FROM persona 
                        WHERE dni = '$dniToCheck' 
                        AND id IN (SELECT id_persona FROM socio 
                                  WHERE id IN (SELECT id_socio FROM socio_temporada 
                                              WHERE id_temporada = (SELECT MAX(id) FROM temporada)))";
        if ($resultSocio = mysqli_query($con, $querySocio)) {
          if ($resultSocio->num_rows > 0) {
            $isSocioClub = true;
            $discountSocio = round($player['finalPrice'] * 0.20, 2);
            $player['socioDiscount'] = $discountSocio;
            $player['finalPrice'] -= $discountSocio;
            writeLog("Aplicando descuento de socio para DNI: $dniToCheck");
          }
        }
        
        // Crear estructura para el frontend
        $playerFullname = $player['nomJugador'] . ' ' . $player['primerCognomJugador'];
        $playerFrontend = [
          'fullname' => $playerFullname,
          'dni' => $player['dniJugador'],
          'pagoUnico' => [
            'importeUnitario' => $player['basePrice'],
            'concepto' => $numWeeks . ' ' . ($numWeeks == 1 ? 'semana' : 'semanas'),
            'importeUnitarioFinalOnline' => $player['finalPrice'],
            'importeUnitarioFinalPresencial' => $player['finalPrice'],
            'restante' => 0
          ],
          'weeksList' => $selectedWeeks
        ];
        
        $players[] = $playerFrontend;
        $totalPrice += $player['finalPrice'];
      }
    }
  }
  
  // Calcular totales de descuentos
  $totalBrothersDiscount = 0;
  $totalSocioDiscount = 0;
  
  foreach ($players as $player) {
    if (isset($player['pagoUnico'])) {
      $basePrice = $player['pagoUnico']['importeUnitario'];
      $finalPrice = $player['pagoUnico']['importeUnitarioFinalOnline'];
      $totalDiscount = $basePrice - $finalPrice;
      
      // Estimar cuánto corresponde a cada tipo de descuento
      if ($isSocioClub) {
        $socioDiscount = $isBrothers ? 
          round(($basePrice - $brothersDiscount) * 0.20, 2) : 
          round($basePrice * 0.20, 2);
        $totalSocioDiscount += $socioDiscount;
      }
    }
  }
  
  // Preparar respuesta
  $importe->jugador = isset($players[0]) ? $players[0] : null;
  $importe->players = $players;
  $importe->isBrothers = $isBrothers ? true : false;
  $importe->isPlayerClub = $isPlayerClub ? true : false;
  $importe->isSocioClub = $isSocioClub ? true : false;
  $importe->sonHermanos = $isBrothers ? true : false;
  
  // Totales para el frontend
  $importe->amountOnline = $totalPrice;
  $importe->amountPresencial = $totalPrice;
  $importe->amountDiscountAreBrothers = $totalBrothersDiscount;
  $importe->amountDiscountIsMember = $totalSocioDiscount;
  $importe->amountSinglePaymentDiscount = 0; // No hay descuento por pago único en este caso
  
  // Enviar respuesta
  header('Content-Type: application/json');
  try {
    writeLog("Respuesta final: " . json_encode($importe));
    $response->importe = $importe;
    echo json_encode($response->importe);
  } catch (Exception $e) {
    writeLog("Error al generar respuesta JSON: " . $e->getMessage(), 'ERROR');
    echo json_encode(['error' => 'Error al procesar la solicitud']);
  }
?>
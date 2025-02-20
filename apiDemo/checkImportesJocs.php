<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();
  $importe = new ResultImportesDescuentos();

  $player = array();

  $referencePage = $_POST['page'];
  switch ($referencePage) {
    case 'inscripcio':
    case 'inscripcion':
      if (isset($_POST['dni'])) {
        $dni = $_POST['dni'];
      }
      if (isset($_POST['dniTutor'])) {
        $dniT = $_POST['dniTutor'];
      }
    break;

    case 'jocs-del-basquet': 
    case 'juegos-del-basquet':
      $isBrothers = false;

      if (isset($_POST['nombre'])) {
        $nombre = $_POST['nombre'];
      }
      if (isset($_POST['arrayApellidos'])){
        $arrayApellidos = $_POST['arrayApellidos'];

        if(count($arrayApellidos) > 1){
          for ($i=0; $i < count($arrayApellidos); $i++) {
              if($arrayApellidos[0] == $arrayApellidos[$i]){
                  $isBrothers = true;
              } else {
                  $isBrothers = false;
                  $i = count($arrayApellidos);
              }
          }
        }
      }
      
      $queryPrices = "SELECT *, ".
                     "(SELECT importe FROM importes WHERE concepto = 'quotaInscripcionJuegosBasquet') AS priceInscription, ".
                     "(SELECT importe FROM importes WHERE concepto = 'descuentoPagoUnico') AS percentageDiscountSinglePayment ".
                     "FROM importes WHERE concepto = 'quotaJuegosBasquet';";
      
      if ($resultQueryPrices = mysqli_query($con, $queryPrices)) {
        $priceData = mysqli_fetch_array($resultQueryPrices, MYSQLI_ASSOC);

        $priceUnit = $priceData['importe'];
        $priceInscription = $priceData['priceInscription'];
        $priceDiscountSinglePayment = (($priceUnit * $priceData['percentageDiscountSinglePayment']) / 100);
        $totalPriceOnline = ($priceUnit - $priceDiscountSinglePayment);
        $totalFaceToFacePrice = $priceUnit;
        $onlyPaymentRemaining = 0;
        $remainingInscription = ($priceUnit - $priceInscription);

        $importe->concepto = $priceData['concepto'];
      }

      $pagoUnico = [
        "importeUnitario" => $priceUnit,
        "importeUnitarioFinalOnline" => $totalPriceOnline,
        "importeUnitarioFinalPresencial" => $totalFaceToFacePrice,
        "restante" => $onlyPaymentRemaining,
        "priceDesPagoUnico" => $priceDiscountSinglePayment
      ];
      
      $inscripcion = [
        "importeUnitario" => $priceUnit,
        "importeUnitarioFinalOnline" => $priceUnit,
        "importeUnitarioFinalPresencial" => $priceUnit,
        "restante" => $remainingInscription
      ];

      $player['nombre'] = $nombre;
      $player['pagoUnico'] = $pagoUnico;
      $player['inscripcion'] = $inscripcion;

      $importe->importe = $priceUnit;
      $importe->importeInscripcion = $priceInscription;
      $importe->precioDescunetPagoUnico = $priceDiscountSinglePayment;
      $importe->jugador = $player;
    break;

    case 'tecnificacio-navidad':
    case 'tecnificacion-navidad':
      if (isset($_POST['dni'])) {
        $dniPlayer = $_POST['dni'];
      }
      if (isset($_POST['nombre'])) {
        $nombre = $_POST['nombre'];
      }
      
      $queryPrices = "SELECT importe AS pricePlayerClub, ".
                     "(SELECT importe FROM importes WHERE concepto = 'quotaTecnificacionNavidadNoClub') AS priceNotPlayerClub ".
                     "FROM importes WHERE concepto = 'quotaTecnificacionNavidadClub';";
      $importe->sql = $queryPrices;
      if ($resultQueryPrices = mysqli_query($con, $queryPrices)) {
        $priceData = mysqli_fetch_array($resultQueryPrices, MYSQLI_ASSOC);

        $pricePlayerClub = $priceData['pricePlayerClub'];
        $priceNotPlayerClub = $priceData['priceNotPlayerClub'];
        
        $queryPerson = "SELECT * FROM persona WHERE dni = '$dniPlayer' AND id IN(SELECT idJugador FROM jugador_temporada WHERE idTemporada = (SELECT MAX(id) FROM temporada));";
        if ($resultQueryPerson = mysqli_query($con, $queryPerson)) {
          if ($resultQueryPerson->num_rows > 0) {
            $player['price'] = $pricePlayerClub;
          } else {
            $queryDescuentos ="SELECT * FROM descuentosTemporada WHERE borrado = 0 AND dni = '$dniPlayer' AND idTipo IN (SELECT id FROM tipo_pago WHERE concepto = 'Tecnificacion Navidad' AND idTemporada  = (SELECT MAX(id) FROM temporada));";
            if ($result = mysqli_query($con, $queryDescuentos)) {
                if ($result->num_rows > 0) {
                  $player['price'] = $pricePlayerClub;
                } else {
                  $player['price'] = $priceNotPlayerClub;
                }
            }
          }
        }
        $player['nombre'] = $nombre;

        $importe->importe = $player['price'];
        $importe->jugador = $player;
      }
    break;

    case 'mini-campus-bulls':
    case 'mini-campus-bulls-esp':
    case 'campus-hivern':
    case 'campus-invierno':
    case 'summer-workout':
    case 'summer-workout-esp':
      $isPlayerClub = false;
      $isBrothers = false;

      if (isset($_POST['dni'])) {
        $dniPlayer = $_POST['dni'];
      }
      if (isset($_POST['nombre'])) {
        $nombre = $_POST['nombre'];
      }
      if (isset($_POST['price'])) {
        $price = $_POST['price'];
      }
      if (isset($_POST['arrayApellidos'])){
        $arrayApellidos = $_POST['arrayApellidos'];

        if(count($arrayApellidos) > 1){
          for ($i=0; $i < count($arrayApellidos); $i++) {
              if($arrayApellidos[0] == $arrayApellidos[$i]){
                  $isBrothers = true;
              } else {
                  $isBrothers = false;
                  $i = count($arrayApellidos);
              }
          }
        }
      }

      $discountIsPlayer = $price * 0.05;
      $discountIsBrothers = $price * 0.05;

      if ($isBrothers == true) {
        $price = $price - $discountIsBrothers;
      }

      $queryPerson = "SELECT * FROM persona WHERE dni = '$dniPlayer' AND id IN(SELECT idJugador FROM jugador_temporada WHERE idTemporada = (SELECT MAX(id) FROM temporada) AND idTipo = 6);";
      if ($resultQueryPerson = mysqli_query($con, $queryPerson)) {
        if ($resultQueryPerson->num_rows > 0) {
          $isPlayerClub = true;
          $player['price'] = $price - $discountIsPlayer;
        } else {
          $queryDescuentos ="SELECT * FROM descuentosTemporada WHERE borrado = 0 AND dni = '$dniPlayer' AND idTipo IN (SELECT id FROM tipo_pago WHERE concepto = 'Campus de invierno' AND idTemporada  = (SELECT MAX(id) FROM temporada));";
          if ($result = mysqli_query($con, $queryDescuentos)) {
              if ($result->num_rows > 0) {
                $isPlayerClub = true;
                $player['price'] = $price - $discountIsPlayer;
              } else {
                $player['price'] = $price;
              }
          }
        }
      }

      $player['nombre'] = $nombre;

      $importe->jugador = $player;
      $importe->isBrothers = $isBrothers;
      $importe->isPlayerClub = $isPlayerClub;
    break;

    case 'fes-te-soci':
    case 'hazte-socio':
      if (isset($_POST['dni'])) {
        $dniPlayer = $_POST['dni'];
      }
      if (isset($_POST['nombre'])) {
        $nombre = $_POST['nombre'];
      }
      
      $queryPrices = "SELECT importe FROM importes WHERE concepto = 'quotaAnualSoci' AND idTemporada = (SELECT MAX(id) FROM temporada);";
      $importe->sql = $queryPrices;
      if ($resultQueryPrices = mysqli_query($con, $queryPrices)) {
        $priceData = mysqli_fetch_array($resultQueryPrices, MYSQLI_ASSOC);
        
        $player['nombre'] = $nombre;
        $player['price'] = $priceData['importe'];

        $importe->importe = $player['price'];
        $importe->jugador = $player;
      }
      break;
    default:
    break;
  }

  header('Content-Type: application/json');
  echo json_encode($response->importe = $importe);
?>
<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");
header('Content-Type: application/json');

require_once 'dbConnection.php';

$con = returnConection();
$response = new Result();
$importe = new ResultImportesDescuentos();

// Decodificar los datos JSON recibidos en la solicitud
$inputData = json_decode(file_get_contents('php://input'), true);

// Ahora puedes acceder a los valores del JSON como un array
$dniTutor = $inputData['dniTutor'] ?? null;
$isMas18 = filter_var($inputData['jugadorMas18'], FILTER_VALIDATE_BOOLEAN);
$registrationPlayers = $inputData['registrationPlayers'] ?? [];


// Función para obtener los importes
function obtenerImportes($con, $concepto) {
  $sql = "SELECT *,
                  (SELECT importe FROM importes WHERE concepto = 'descuentoAnioPasado' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS importeDescuentoAnioPasado,
                  (SELECT importe FROM importes WHERE concepto = 'descuentoPagoUnico' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoPagoUnico,
                  (SELECT importe FROM importes WHERE concepto = 'descuentoEsSocio' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoEsSocio,
                  (SELECT importe FROM importes WHERE concepto = 'descuentoSonHermanos' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoSonHermanos
          FROM importes
          WHERE concepto = '$concepto' AND idTemporada = (SELECT MAX(id) FROM temporada)";
  $result = mysqli_query($con, $sql);
  return $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
}

// Función para verificar si es socio del club
function esSocioClub($con, $dni, $temporadaId = 6) {
    $query = "SELECT * FROM persona WHERE dni = '$dni' AND id IN (
                SELECT id_persona FROM socio WHERE id IN (
                    SELECT id_socio FROM socio_temporada WHERE id_temporada = $temporadaId
                )
              )";
    $result = mysqli_query($con, $query);
    return $result && $result->num_rows > 0;
}

// // Función para verificar si jugo la temporada pasada
function comprobarTemporadaPasada($con, $dni) {
    $fecha = date('Y');
    $sqlTemporadaPasada = "SELECT dni FROM persona WHERE dni = '$dni' AND id IN (
                              SELECT id_jugador FROM equipos_jugadores WHERE id_equipo IN (
                                  SELECT id FROM equipo WHERE id_temporada = (
                                      SELECT id FROM temporada WHERE year(fecha_final) = $fecha
                                  )
                              )
                          )";
    $result = mysqli_query($con, $sqlTemporadaPasada);

    if ($result && $result->num_rows > 0) {
        $userData = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $dni == $userData["dni"] ? "OK" : "KO";
    } else {
        return "NoExiste";
    }
}

// Función para verificar si son hermanos
function comprobarHermanos($playersSurnamesData) {
  return count($playersSurnamesData) > 1 && count(array_unique($playersSurnamesData)) === 1;
}

function isPlayerClub($con, $dni) {}

$playersSurnames = [];
foreach ($registrationPlayers as $player) {
    $firstSurname = "";
    $secondSurname = "";

    foreach($player as $key => $value) {
        if ($key == "primerCognomJugador") $firstSurname = strtolower(trim($value));
        if ($key == "segonCognomJugador") $secondSurname = strtolower(trim($value));
    }

    $fullSurname = str_replace(" ", "", $firstSurname) . str_replace(" ", "", $secondSurname);
    $playersSurnames[] = $fullSurname;
}

$players = [];
foreach ($registrationPlayers as $player) {
  $namePlayer = "";
  $dniPlayer = "";
  $firstSurnamePlayer = "";
  $weeksList = [];

  foreach($player as $key => $value) {
      if ($key == "nomJugador") $namePlayer = $value;
      if ($key == "dniJugador") $dniPlayer = $value;
      if ($key == "primerCognomJugador") $firstSurnamePlayer = $value;
      if ($key == "weeksList") $weeksList = $value;
  }

  $importeData = obtenerImportes($con, "quotaCampusVerano2025");

  $concepto = "Turnos";
  $precioUnitario = 0;
  if ($importeData) {
    if (count($weeksList) == 1) {
      $concepto = "1 Turno";
      $precioUnitario = 90;
    } elseif (count($weeksList) == 2) {
      $concepto = "2 Turnos";
      $precioUnitario = 160;
    } elseif (count($weeksList) == 3) {
      $concepto = "3 Turnos";
      $precioUnitario = 240;
    }

    $temporadaImporte = $importeData['idTemporada'];
    $precioDescuentoAnioPasado = $importeData['importeDescuentoAnioPasado'];
    $porcentajeDescuentoPagoUnico = $importeData['porcentajeDescuentoPagoUnico'];
    $porcentajeDescuentoEsSocio = $importeData['porcentajeDescuentoEsSocio'];
    $porcentajeDescuentoSonHermanos = $importeData['porcentajeDescuentoSonHermanos'];

    $importe->porcentajeDescuentoUnico = $porcentajeDescuentoPagoUnico;
    $importe->porcentajeDescuentoEsSocio = $porcentajeDescuentoEsSocio;
    $importe->porcentajeDescuentoHermanos = $porcentajeDescuentoSonHermanos;
    $importe->temporadaImporte = $temporadaImporte;
  }

  $sonGermans = comprobarHermanos($playersSurnames);
  $importe->sonHermanos = $sonGermans;

  $isSocioClub = esSocioClub($con, $isMas18 ? $dniPlayer : $dniTutor);
  $importe->isSocioClub = $isSocioClub;

  $temporadaPasadaStatus = comprobarTemporadaPasada($con, $dniPlayer);
  $importe->temporadaPasada = $temporadaPasadaStatus;

  /*if ($temporadaPasadaStatus === "OK") {
      $precioUnitario -= $precioDescuentoAnioPasado;
  }*/

  // Calcular descuentos
  $precioTotalPagar = $precioUnitario;
  $precioDescunetHermano = $sonGermans ? ($precioUnitario * $porcentajeDescuentoSonHermanos) / 100 : 0;
  $precioDescunetPagoUnico = ($precioUnitario * $porcentajeDescuentoPagoUnico) / 100;
  $precioDescunetEsSocio = $isSocioClub ? ($precioUnitario * $porcentajeDescuentoEsSocio) / 100 : 0;

  $importe->precioDescunetHermano = $precioDescunetHermano;
  $importe->precioDescunetPagoUnico = $precioDescunetPagoUnico;
  $importe->precioDescunetEsSocio = $precioDescunetEsSocio;

  $precioTotalPagar -= ($precioDescunetHermano + $precioDescunetEsSocio);
  $importeUnitarioFinalPresencial = $precioTotalPagar;
  $importe->importe = $precioTotalPagar;

  $restante = 0;
  $importe->restante = $restante;
  $importe->total = $precioTotalPagar;

  $playerData = [
      'fullname' => $namePlayer.' '.$firstSurnamePlayer,
      'dni' => $dniPlayer,
      'pagoUnico' => [
          "importeUnitario" => $precioUnitario,
          "concepto" => $concepto,
          "importeUnitarioFinalOnline" => $precioTotalPagar,
          "importeUnitarioFinalPresencial" => $importeUnitarioFinalPresencial,
          "restante" => $restante,
          "priceDesHermanos" => $precioDescunetHermano,
          "priceDesPagoUnico" => $precioDescunetPagoUnico,
          "priceDesEsSocio" => $precioDescunetEsSocio
      ],
      'weeksList' => $weeksList
  ];
  
  $players[] = $playerData;
  $importe->amountOnline += $precioTotalPagar;
  $importe->amountPresencial += $importeUnitarioFinalPresencial;
  $importe->amountDiscountAreBrothers += $precioDescunetHermano;
  $importe->amountSinglePaymentDiscount += $precioDescunetPagoUnico;
  $importe->amountDiscountIsMember += $precioDescunetEsSocio;
}

$importe->players = $players;
echo json_encode(['importe' => $importe]);
?>
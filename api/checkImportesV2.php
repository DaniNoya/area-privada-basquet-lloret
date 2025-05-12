<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");
header('Content-Type: application/json');

require_once 'dbConnection.php';

$con = returnConection();
$response = new Result();
$importe = new ResultImportesDescuentos();

$dniTutor = $_POST['dniTutor'] ?? null;
$isMas18 = filter_var($_POST['jugadorMas18'], FILTER_VALIDATE_BOOLEAN);
$registrationPlayers = $_POST['registrationPlayers'] ?? [];

// Función para obtener los importes
function obtenerImportes($con, $concepto) {
    $sql = "SELECT *,
                   (SELECT importe FROM importes WHERE concepto = 'quotaInscripcion' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS importeQuotaInscripcion,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoAnioPasado' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS importeDescuentoAnioPasado,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoPagoUnico' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoPagoUnico,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoEsSocio' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoEsSocio,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoSonHermanos' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoSonHermanos,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoPrimerEquipo' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoPrimerEquipo
            FROM importes
            WHERE concepto = '$concepto' AND idTemporada = (SELECT MAX(id) FROM temporada)";
    $result = mysqli_query($con, $sql);
    return $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
}

// Función para verificar si es socio del club
function esSocioClub($con, $dni, $temporadaId = 7) {
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
    // Only consider it siblings if there are multiple players and they share the same surname
    if (count($playersSurnamesData) <= 1) {
        return false;
    }
    
    // Count occurrences of each surname
    $surnameCount = array_count_values($playersSurnamesData);
    
    // If any surname appears more than once, we have siblings
    foreach ($surnameCount as $count) {
        if ($count > 1) {
            return true;
        }
    }
    
    return false;
}

// Modify the calculation logic to ensure consistent discount application
$playersSurnames = [];
foreach ($registrationPlayers as $player) {
    $firstSurname = "";
    $secondSurname = "";
    foreach($player as $key => $value) {
        if ($key == "primerCognomJugador") $firstSurname = strtolower(trim($value));
        if ($key == "segonCognomJugador") $secondSurname = strtolower(trim($value));
    }

    // Only use first surname for sibling check to be more reliable
    $playersSurnames[] = str_replace(" ", "", $firstSurname);
}

// Initialize these properties before the foreach loop
$importe->amountOnline = 0;
$importe->amountPresencial = 0;
$importe->amountOnlineInscription = 0;
$importe->amountDiscountAreBrothers = 0;
$importe->amountSinglePaymentDiscount = 0;
$importe->amountDiscountIsMember = 0;

// Check for siblings once before processing individual players
$sonGermans = comprobarHermanos($playersSurnames);

// Calculate total amount for all players
$totalAmount = 0;
$players = [];

foreach ($registrationPlayers as $player) {
    $namePlayer = "";
    $dniPlayer = "";
    $firstSurnamePlayer = "";
    foreach($player as $key => $value) {
        if ($key == "nomJugador") $namePlayer = $value;
        if ($key == "dniJugador") $dniPlayer = $value;
        if ($key == "primerCognomJugador") $firstSurnamePlayer = $value;
    }

    $importesConcepto = $isMas18 ? 'quotaAnualSenior' : 'quotaAnualMenor';
    $importeData = obtenerImportes($con, $importesConcepto);

    if ($importeData) {
        $importe->concepto = $importeData['concepto'];
        $temporadaImporte = $importeData['idTemporada'];
        $precioQuotaAnual = $importeData['importe'];
        $precioInscripcion = $importeData['importeQuotaInscripcion'];
        $precioDescuentoAnioPasado = $importeData['importeDescuentoAnioPasado'];
        $porcentajeDescuentoPagoUnico = $importeData['porcentajeDescuentoPagoUnico'];
        $porcentajeDescuentoEsSocio = $importeData['porcentajeDescuentoEsSocio'];
        $porcentajeDescuentoPrimerEquipo = $importeData['porcentajeDescuentoPrimerEquipo'];
        $porcentajeDescuentoSonHermanos = $importeData['porcentajeDescuentoSonHermanos'];

        $importe->porcentajeDescuentoUnico = $porcentajeDescuentoPagoUnico;
        $importe->porcentajeDescuentoEsSocio = $porcentajeDescuentoEsSocio;
        $importe->porcentajeDescuentoHermanos = $porcentajeDescuentoSonHermanos;
        $importe->porcentajeDescuentoPrimerE = $porcentajeDescuentoPrimerEquipo;
        $importe->temporadaImporte = $temporadaImporte;
    }

    // Use the sibling status determined once for all players
    $importe->sonHermanos = $sonGermans;

    $isSocioClub = esSocioClub($con, $isMas18 ? $dniPlayer : $dniTutor);
    $importe->isSocioClub = $isSocioClub;

    $temporadaPasadaStatus = comprobarTemporadaPasada($con, $dniPlayer);
    $importe->temporadaPasada = $temporadaPasadaStatus;

    // Start with the base price
    $precioUnitario = $precioQuotaAnual;

    // Apply previous season discount first if applicable
    if ($temporadaPasadaStatus === "OK") {
        $precioUnitario -= $precioDescuentoAnioPasado;
    }

    // Calculate all discounts based on the adjusted base price
    $precioDescunetHermano = $sonGermans ? round(($precioUnitario * $porcentajeDescuentoSonHermanos) / 100, 2) : 0;
    $precioDescunetPagoUnico = round(($precioUnitario * $porcentajeDescuentoPagoUnico) / 100, 2);
    $precioDescunetEsSocio = $isSocioClub ? round(($precioUnitario * $porcentajeDescuentoEsSocio) / 100, 2) : 0;

    // Store discount values
    $importe->precioDescunetHermano = $precioDescunetHermano;
    $importe->precioDescunetPagoUnico = $precioDescunetPagoUnico;
    $importe->precioDescunetEsSocio = $precioDescunetEsSocio;

    // Calculate total discount
    $totalDiscount = $precioDescunetPagoUnico + $precioDescunetHermano + $precioDescunetEsSocio;
    
    // Apply all discounts to get final price
    $precioTotalPagar = round($precioUnitario - $totalDiscount, 2);
    
    // Add to total amount
    $totalAmount += $precioTotalPagar;
    
    // Set individual player amount
    $importe->importe = $precioTotalPagar;
    $importe->restante = 0;
    $importe->importeInscripcion = $precioInscripcion;
    $importe->total = $precioTotalPagar;

    // Calculate other values
    $importeUnitarioFinalOnlineIns = $precioTotalPagar + $precioDescunetPagoUnico;
    $importeUnitarioFinalPresencial = $precioTotalPagar + $precioDescunetPagoUnico;
    $restanteInscripcion = ($precioTotalPagar + $precioDescunetPagoUnico) - $precioInscripcion;

    // Create player data structure
    $playerData = [
        'fullname' => $namePlayer.' '.$firstSurnamePlayer,
        'dni' => $dniPlayer,
        'pagoUnico' => [
            "importeUnitario" => $precioUnitario,
            "importeUnitarioFinalOnline" => $precioTotalPagar,
            "importeUnitarioFinalPresencial" => $importeUnitarioFinalPresencial,
            "restante" => 0,
            "priceDesHermanos" => $precioDescunetHermano,
            "priceDesPagoUnico" => $precioDescunetPagoUnico,
            "priceDesEsSocio" => $precioDescunetEsSocio
        ],
        'inscripcion' => [
            "importeUnitario" => $precioInscripcion,
            "importeUnitarioFinalOnline" => $importeUnitarioFinalOnlineIns,
            "importeUnitarioFinalPresencial" => $importeUnitarioFinalPresencial,
            "restante" => $restanteInscripcion
        ],
        'temporadaPasada' => $temporadaPasadaStatus,
        'isSocioClub' => $isSocioClub,
        'sonHermanos' => $sonGermans
    ];
    
    $players[] = $playerData;
    
    // Accumulate totals
    $importe->amountOnline += $precioTotalPagar;
    $importe->amountPresencial += $importeUnitarioFinalPresencial;
    $importe->amountOnlineInscription += $precioInscripcion;
    $importe->amountDiscountAreBrothers += $precioDescunetHermano;
    $importe->amountSinglePaymentDiscount += $precioDescunetPagoUnico;
    $importe->amountDiscountIsMember += $precioDescunetEsSocio;
}

// Set the total amount for all players
$importe->total = $totalAmount;

$importe->players = $players;
echo json_encode(['importe' => $importe]);
?>
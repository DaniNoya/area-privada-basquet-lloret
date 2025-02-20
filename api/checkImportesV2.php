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

$playersSurnames = [];
foreach ($registrationPlayers as $player) {
    $firstSurname = "";
    $secondSurname = "";
    foreach($player as $key => $value) {
        if ($key == "primerCognomJugador") $firstSurname = strtolower(trim($value));
        if ($key == "segonCognomJugador") $secondSurname = strtolower(trim($value));
    }

    $fullSurname = str_replace(" ", "", $firstSurname) + str_replace(" ", "", $secondSurname);
    $playersSurnames[] = $fullSurname;
}

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

    $sonGermans = comprobarHermanos($playersSurnames);
    $importe->sonHermanos = $sonGermans;

    $isSocioClub = esSocioClub($con, $isMas18 ? $dniPlayer : $dniTutor);
    $importe->isSocioClub = $isSocioClub;

    $temporadaPasadaStatus = comprobarTemporadaPasada($con, $dniPlayer);
    $importe->temporadaPasada = $temporadaPasadaStatus;

    $precioUnitario = $precioQuotaAnual;

    if ($temporadaPasadaStatus === "OK") {
        $precioUnitario -= $precioDescuentoAnioPasado;
    }

    // Calcular descuentos
    $precioTotalPagar = $precioUnitario;
    $precioDescunetHermano = $sonGermans ? ($precioUnitario * $porcentajeDescuentoSonHermanos) / 100 : 0;
    $precioDescunetPagoUnico = ($precioUnitario * $porcentajeDescuentoPagoUnico) / 100;
    $precioDescunetEsSocio = $isSocioClub ? ($precioUnitario * $porcentajeDescuentoEsSocio) / 100 : 0;

    $importe->precioDescunetHermano = $precioDescunetHermano;
    $importe->precioDescunetPagoUnico = $precioDescunetPagoUnico;
    $importe->precioDescunetEsSocio = $precioDescunetEsSocio;

    $precioTotalPagar -= ($precioDescunetPagoUnico + $precioDescunetHermano + $precioDescunetEsSocio);
    $importe->importe = $precioTotalPagar;

    $restante = 0;
    $importe->restante = $restante;
    $importe->importeInscripcion = $precioInscripcion;
    $importe->total = $precioTotalPagar;

    $importeUnitarioFinalOnlineIns = $precioTotalPagar + $precioDescunetPagoUnico;
    $importeUnitarioFinalPresencial = $precioTotalPagar + $precioDescunetPagoUnico;
    $restanteInscripcion = ($precioTotalPagar + $precioDescunetPagoUnico) - $precioInscripcion;

    $playerData = [
        'fullname' => $namePlayer.' '.$firstSurnamePlayer,
        'dni' => $dniPlayer,
        'pagoUnico' => [
            "importeUnitario" => $precioUnitario,
            "importeUnitarioFinalOnline" => $precioTotalPagar,
            "importeUnitarioFinalPresencial" => $importeUnitarioFinalPresencial,
            "restante" => $restante,
            "priceDesHermanos" => $precioDescunetHermano,
            "priceDesPagoUnico" => $precioDescunetPagoUnico,
            "priceDesEsSocio" => $precioDescunetEsSocio
        ],
        'inscripcion' => [
            "importeUnitario" => $precioInscripcion,
            "importeUnitarioFinalOnline" => $importeUnitarioFinalOnlineIns,
            "importeUnitarioFinalPresencial" => $importeUnitarioFinalPresencial,
            "restante" => $restanteInscripcion
        ]
    ];
    
    $players[] = $playerData;
    $importe->amountOnline += $precioTotalPagar;
    $importe->amountPresencial += $importeUnitarioFinalPresencial;
    $importe->amountOnlineInscription += $precioInscripcion;
    $importe->amountDiscountAreBrothers += $precioDescunetHermano;
    $importe->amountSinglePaymentDiscount += $precioDescunetPagoUnico;
    $importe->amountDiscountIsMember += $precioDescunetEsSocio;
}

$importe->players = $players;
echo json_encode(['importe' => $importe]);
?>
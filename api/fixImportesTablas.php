<?php
// Change content type to HTML instead of JSON since we're outputting HTML tables
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");
header('Content-Type: text/html; charset=UTF-8');

require_once 'dbConnection.php';

$con = returnConection();
$response = new stdClass();
$response->success = false;
$response->message = "";
$response->updated = 0;

// Get current season ID
$sqlTemporada = "SELECT MAX(id) as id FROM temporada";
$resultTemporada = mysqli_query($con, $sqlTemporada);
$temporadaActual = mysqli_fetch_assoc($resultTemporada)['id'];

// Get all players with payments for the current season
$sqlJugadores = "SELECT p.id, p.dni, p.nombre, p.primer_apellido, p.segundo_apellido, 
                 TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad
                 FROM persona p 
                 INNER JOIN jugador j ON p.id = j.id
                 INNER JOIN jugador_temporada jt ON j.id = jt.idJugador
                 WHERE jt.idTemporada = ? AND j.baja = 0";
$stmtJugadores = mysqli_prepare($con, $sqlJugadores);
mysqli_stmt_bind_param($stmtJugadores, "i", $temporadaActual);
mysqli_stmt_execute($stmtJugadores);
$resultJugadores = mysqli_stmt_get_result($stmtJugadores);

if (!$resultJugadores) {
    echo "<p>Error al obtener jugadores: " . mysqli_error($con) . "</p>";
    exit;
}

// Function to get prices and discounts - Using the function from checkImportesV2.php
function obtenerImportes($con, $concepto, $temporadaId) {
    $sql = "SELECT *,
                   (SELECT importe FROM importes WHERE concepto = 'quotaInscripcion' AND idTemporada = ?) AS importeQuotaInscripcion,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoAnioPasado' AND idTemporada = ?) AS importeDescuentoAnioPasado,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoPagoUnico' AND idTemporada = ?) AS porcentajeDescuentoPagoUnico,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoEsSocio' AND idTemporada = ?) AS porcentajeDescuentoEsSocio,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoSonHermanos' AND idTemporada = ?) AS porcentajeDescuentoSonHermanos,
                   (SELECT importe FROM importes WHERE concepto = 'descuentoPrimerEquipo' AND idTemporada = ?) AS porcentajeDescuentoPrimerEquipo
            FROM importes
            WHERE concepto = ? AND idTemporada = ?";

    $stmt = mysqli_prepare($con, $sql);

    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Error preparing statement in obtenerImportes: " . mysqli_error($con));
        return null;
    }

    // Fix: Properly bind parameters
    mysqli_stmt_bind_param($stmt, "iiiiiisi", 
        $temporadaId, $temporadaId, $temporadaId, 
        $temporadaId, $temporadaId, $temporadaId, 
        $concepto, $temporadaId);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
}

// Function to check if player played last season - Using the function from checkImportesV2.php
function comprobarTemporadaPasada($con, $dni, $temporadaActual) {
    $temporadaAnterior = $temporadaActual - 1;
    $sqlTemporadaPasada = "SELECT dni FROM persona WHERE dni = ? AND id IN (
                              SELECT id_jugador FROM equipos_jugadores WHERE id_equipo IN (
                                  SELECT id FROM equipo WHERE id_temporada = ?
                              )
                          )";
    $stmt = mysqli_prepare($con, $sqlTemporadaPasada);

    // Fix: Properly bind parameters
    mysqli_stmt_bind_param($stmt, "si", $dni, $temporadaAnterior);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $userData = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $dni == $userData["dni"] ? "OK" : "KO";
    } else {
        return "NoExiste";
    }
}

// Function to check if player is a club member - Using the function from checkImportesV2.php
function esSocioClub($con, $dni, $temporadaId) {
    $query = "SELECT * FROM persona WHERE dni = ? AND id IN (
                SELECT id_persona FROM socio WHERE id IN (
                    SELECT id_socio FROM socio_temporada WHERE id_temporada = ?
                )
              )";
    $stmt = mysqli_prepare($con, $query);

    // Fix: Properly bind parameters
    mysqli_stmt_bind_param($stmt, "si", $dni, $temporadaId);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result && mysqli_num_rows($result) > 0;
}

// Function to check if player has siblings in the same season - Using the logic from checkImportesV2.php
function tieneHermanos($con, $jugadorId, $temporadaId) {
    // Get player's first surname
    $sqlJugador = "SELECT primer_apellido FROM persona WHERE id = ?";
    $stmtJugador = mysqli_prepare($con, $sqlJugador);
    mysqli_stmt_bind_param($stmtJugador, "i", $jugadorId);
    mysqli_stmt_execute($stmtJugador);
    $resultJugador = mysqli_stmt_get_result($stmtJugador);
    $jugadorData = mysqli_fetch_assoc($resultJugador);
    $apellido = $jugadorData['primer_apellido'];
    
    // Count players with same surname in this season
    $sqlHermanos = "SELECT COUNT(*) as total FROM persona p 
                    INNER JOIN jugador j ON p.id = j.id
                    INNER JOIN jugador_temporada jt ON j.id = jt.idJugador
                    WHERE p.primer_apellido = ? 
                    AND jt.idTemporada = ? 
                    AND j.baja = 0";
    $stmtHermanos = mysqli_prepare($con, $sqlHermanos);

    // Fix: Properly bind parameters
    mysqli_stmt_bind_param($stmtHermanos, "si", $apellido, $temporadaId);

    mysqli_stmt_execute($stmtHermanos);
    $resultHermanos = mysqli_stmt_get_result($stmtHermanos);
    $hermanosData = mysqli_fetch_assoc($resultHermanos);
    
    return $hermanosData['total'] > 1;
}

// Add HTML document structure
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Información de Jugadores</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .player-card { background-color: #f0f8ff; padding: 10px; margin-bottom: 10px; border: 1px solid #add8e6; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        th { background-color: #e6e6e6; text-align: left; }
        td.value { text-align: right; }
        .negative { color: red; font-weight: bold; }
        h3 { margin-top: 0; }
    </style>
</head>
<body>";

$updatedCount = 0;

// List of specific IDs to display
$specificIds = [614, 1080, 1028, 1123, 11, 886, 725, 486, 937, 1212, 959, 43, 4];

// Process each player
// After the function definitions and before processing players, add this function to get special discounts
function obtenerDescuentosEspeciales($con, $dni, $temporadaId) {
    $descuentos = [
        'porcentaje' => 0,
        'desAnioPasado' => 0,
        'descuentoEspecial' => false
    ];
    
    // Check for special discounts in descuentosTemporada table
    $sqlDescuentos = "SELECT * FROM descuentosTemporada 
                      WHERE dni = ? AND borrado = 0 
                      AND idTipo IN (SELECT id FROM tipo_pago 
                                    WHERE concepto = 'Temporada Regular' 
                                    AND idTemporada = ?)";
    $stmtDescuentos = mysqli_prepare($con, $sqlDescuentos);
    
    if ($stmtDescuentos) {
        mysqli_stmt_bind_param($stmtDescuentos, "si", $dni, $temporadaId);
        mysqli_stmt_execute($stmtDescuentos);
        $resultDescuentos = mysqli_stmt_get_result($stmtDescuentos);
        
        if ($resultDescuentos && mysqli_num_rows($resultDescuentos) > 0) {
            $descuentosData = mysqli_fetch_assoc($resultDescuentos);
            $descuentos['porcentaje'] = $descuentosData['porcentaje'];
            $descuentos['desAnioPasado'] = $descuentosData['desAnioPasado'];
            $descuentos['descuentoEspecial'] = true;
        }
    }
    
    return $descuentos;
}

// In the player processing loop, after getting player status and before calculating discounts:
while ($jugador = mysqli_fetch_assoc($resultJugadores)) {
    $jugadorId = $jugador['id'];
    $dni = $jugador['dni'];
    $edad = $jugador['edad'];
    
    // Only process specific players if needed
    if (!in_array($jugadorId, $specificIds)) {
        continue;
    }
    
    // Determine price concept based on age - Same as in checkImportesV2.php
    $importesConcepto = $edad >= 18 ? 'quotaAnualSenior' : 'quotaAnualMenor';
    $importeData = obtenerImportes($con, $importesConcepto, $temporadaActual);
    
    if (!$importeData) {
        continue; // Skip if no price data found
    }
    
    // Get player status - Same as in checkImportesV2.php
    $temporadaPasadaStatus = comprobarTemporadaPasada($con, $dni, $temporadaActual);
    $isSocioClub = esSocioClub($con, $dni, $temporadaActual);
    $sonHermanos = tieneHermanos($con, $jugadorId, $temporadaActual);
    
    // Get special discounts if any
    $descuentosEspeciales = obtenerDescuentosEspeciales($con, $dni, $temporadaActual);
    
    // Calculate price with discounts - Same as in checkImportesV2.php
    $precioQuotaAnual = $importeData['importe'];
    $precioDescuentoAnioPasado = $importeData['importeDescuentoAnioPasado'];
    $porcentajeDescuentoPagoUnico = $importeData['porcentajeDescuentoPagoUnico'];
    $porcentajeDescuentoEsSocio = $importeData['porcentajeDescuentoEsSocio'];
    $porcentajeDescuentoSonHermanos = $importeData['porcentajeDescuentoSonHermanos'];
    
    // Start with base price
    $precioUnitario = $precioQuotaAnual;
    
    // Check if we need to use special discounts from descuentosTemporada
    $descuentoEspecialAplicado = false;
    $porcentajeDescuentoEspecial = 0;
    
    if ($descuentosEspeciales['descuentoEspecial']) {
        $descuentoEspecialAplicado = true;
        $porcentajeDescuentoEspecial = $descuentosEspeciales['porcentaje'];
        
        // If there's a special discount percentage, use it
        if ($porcentajeDescuentoEspecial > 0) {
            $descuentoTemporadaAnteriorAplicado = round(($precioQuotaAnual * $porcentajeDescuentoEspecial) / 100, 2);
            $precioUnitario -= $descuentoTemporadaAnteriorAplicado;
        } 
        // If there's a special year discount flag
        else if ($descuentosEspeciales['desAnioPasado'] == 1) {
            $descuentoTemporadaAnteriorAplicado = $precioDescuentoAnioPasado;
            $precioUnitario -= $descuentoTemporadaAnteriorAplicado;
        }
    } 
    // Apply standard previous season discount if applicable and no special discount
    else if ($temporadaPasadaStatus === "OK") {
        $descuentoTemporadaAnteriorAplicado = $precioDescuentoAnioPasado;
        $precioUnitario -= $descuentoTemporadaAnteriorAplicado;
    } else {
        $descuentoTemporadaAnteriorAplicado = 0;
    }
    
    // Calculate all discounts based on the adjusted base price
    $precioDescuentoHermano = $sonHermanos ? round(($precioUnitario * $porcentajeDescuentoSonHermanos) / 100, 2) : 0;
    $precioDescuentoPagoUnico = round(($precioUnitario * $porcentajeDescuentoPagoUnico) / 100, 2);
    $precioDescuentoEsSocio = $isSocioClub ? round(($precioUnitario * $porcentajeDescuentoEsSocio) / 100, 2) : 0;
    
    // Calculate total discount
    $totalDiscount = $precioDescuentoPagoUnico + $precioDescuentoHermano + $precioDescuentoEsSocio;
    
    // Final price
    $precioFinal = round($precioUnitario - $totalDiscount, 2);
    
    // Calculate remaining amount after inscription
    $precioInscripcion = $importeData['importeQuotaInscripcion'];
    $importeUnitarioFinalOnlineIns = $precioFinal + $precioDescuentoPagoUnico;
    $restanteInscripcion = ($precioFinal + $precioDescuentoPagoUnico) - $precioInscripcion;
    
    // Display information for each player
    echo "<div class='player-card'>";
    echo "<h3>Jugador: {$jugador['nombre']} {$jugador['primer_apellido']} {$jugador['segundo_apellido']}</h3>";
    echo "<table>";
    echo "<tr><th>Información</th><th>Valor</th></tr>";
    
    echo "<tr><td>ID</td><td class='value'>{$jugadorId}</td></tr>";
    echo "<tr><td>DNI</td><td class='value'>{$dni}</td></tr>";
    echo "<tr><td>Edad</td><td class='value'>{$edad}</td></tr>";
    echo "<tr><td>Temporada pasada</td><td class='value'>{$temporadaPasadaStatus}</td></tr>";
    echo "<tr><td>Socio</td><td class='value'>" . ($isSocioClub ? "Sí" : "No") . "</td></tr>";
    echo "<tr><td>Hermanos</td><td class='value'>" . ($sonHermanos ? "Sí" : "No") . "</td></tr>";
    
    // Add special discount information if applicable
    if ($descuentoEspecialAplicado) {
        echo "<tr><td>Descuento especial</td><td class='value'>Sí ({$porcentajeDescuentoEspecial}%)</td></tr>";
    }
    
    echo "<tr><th colspan='2'>Desglose de Precios y Descuentos</th></tr>";
    echo "<tr><td>Cuota anual base (sin descuentos)</td><td class='value'>{$precioQuotaAnual} €</td></tr>";
    
    // Show each discount separately with clear indication
    echo "<tr><td colspan='2'><strong>Aplicación de descuentos:</strong></td></tr>";
    
    // Step 1: Descuento temporada anterior o descuento especial
    if ($descuentoEspecialAplicado) {
        echo "<tr><td>1. Descuento especial</td><td class='value'>-{$descuentoTemporadaAnteriorAplicado} €</td></tr>";
        echo "<tr><td>Precio después de descuento especial</td><td class='value'>{$precioUnitario} €</td></tr>";
    } else if ($temporadaPasadaStatus === "OK") {
        echo "<tr><td>1. Descuento por temporada anterior</td><td class='value'>-{$descuentoTemporadaAnteriorAplicado} €</td></tr>";
        echo "<tr><td>Precio después de descuento temporada anterior</td><td class='value'>{$precioUnitario} €</td></tr>";
    } else {
        echo "<tr><td>1. Descuento por temporada anterior</td><td class='value'>No aplicable</td></tr>";
        echo "<tr><td>Precio base para cálculo de otros descuentos</td><td class='value'>{$precioUnitario} €</td></tr>";
    }
    
    // Step 2: Descuento hermanos
    if ($sonHermanos) {
        echo "<tr><td>2. Descuento por hermanos ({$porcentajeDescuentoSonHermanos}% sobre {$precioUnitario} €)</td><td class='value'>-{$precioDescuentoHermano} €</td></tr>";
    } else {
        echo "<tr><td>2. Descuento por hermanos</td><td class='value'>No aplicable</td></tr>";
    }
    
    // Step 3: Descuento socio
    if ($isSocioClub) {
        echo "<tr><td>3. Descuento por ser socio ({$porcentajeDescuentoEsSocio}% sobre {$precioUnitario} €)</td><td class='value'>-{$precioDescuentoEsSocio} €</td></tr>";
    } else {
        echo "<tr><td>3. Descuento por ser socio</td><td class='value'>No aplicable</td></tr>";
    }
    
    // Step 4: Descuento pago único
    echo "<tr><td>4. Descuento por pago único ({$porcentajeDescuentoPagoUnico}% sobre {$precioUnitario} €)</td><td class='value'>-{$precioDescuentoPagoUnico} €</td></tr>";
    
    // Total descuentos (excluyendo temporada anterior que ya se aplicó al precio base)
    $totalDiscountWithPrevSeason = $totalDiscount + $descuentoTemporadaAnteriorAplicado;
    echo "<tr><td>Total descuentos adicionales (sin incluir temporada anterior)</td><td class='value'>{$totalDiscount} €</td></tr>";
    echo "<tr><td>Total descuentos (incluyendo temporada anterior)</td><td class='value'>{$totalDiscountWithPrevSeason} €</td></tr>";
    
    // Get payment information from database
    $importePagado = 0;
    $importeRestante = $precioFinal;
    
    try {
        $sqlPagos = "SELECT SUM(importe) as total_pagado FROM pago 
                     WHERE id_jugador = ? AND id_temporada = ?";
        $stmtPagos = mysqli_prepare($con, $sqlPagos);
        
        if ($stmtPagos) {
            mysqli_stmt_bind_param($stmtPagos, "ii", $jugadorId, $temporadaActual);
            mysqli_stmt_execute($stmtPagos);
            $resultPagos = mysqli_stmt_get_result($stmtPagos);
            
            if ($resultPagos) {
                $pagosData = mysqli_fetch_assoc($resultPagos);
                $importePagado = $pagosData['total_pagado'] ?: 0;
                $importeRestante = $precioFinal - $importePagado;
            }
        } else {
            // Log the error but continue execution
            error_log("Error preparing payment statement: " . mysqli_error($con));
        }
    } catch (Exception $e) {
        error_log("Exception in payment query: " . $e->getMessage());
    }
    
    echo "<tr><th colspan='2'>Resumen Final</th></tr>";
    echo "<tr><td>Precio sin descuentos</td><td class='value'>{$precioQuotaAnual} €</td></tr>";
    echo "<tr><td>Precio con todos los descuentos</td><td class='value'><strong>{$precioFinal} €</strong></td></tr>";
    echo "<tr><td>Importe pagado hasta la fecha</td><td class='value'>{$importePagado} €</td></tr>";
    echo "<tr><td>Importe restante por pagar</td><td class='value'>" . ($importeRestante <= 0 ? "<span style='color:green;'>0 € (Pagado completo)</span>" : "<span style='color:red;'>{$importeRestante} €</span>") . "</td></tr>";
    
    echo "<tr><th colspan='2'>Información de Inscripción</th></tr>";
    echo "<tr><td>Importe de inscripción</td><td class='value'>{$precioInscripcion} €</td></tr>";
    echo "<tr><td>Restante después de inscripción</td><td class='value'>" . ($restanteInscripcion < 0 ? "<span class='negative'>{$restanteInscripcion} €</span>" : "{$restanteInscripcion} €") . "</td></tr>";
    
    echo "</table>";
    echo "</div>";
    
    // Update jugador_temporada table with corrected quota
    $sqlUpdate = "UPDATE jugador_temporada 
                 SET quota = ?,
                     descuento_hermanos = ?,
                     descuento_socio = ?,
                     descuento_pago_unico = ?,
                     descuento_temporada_anterior = ?
                 WHERE idJugador = ? AND idTemporada = ?";
    $stmtUpdate = mysqli_prepare($con, $sqlUpdate);
    
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, "dddddii", 
            $precioFinal,
            $precioDescuentoHermano,
            $precioDescuentoEsSocio,
            $precioDescuentoPagoUnico,
            $descuentoTemporadaAnteriorAplicado,
            $jugadorId,
            $temporadaActual
        );
        
        if (mysqli_stmt_execute($stmtUpdate)) {
            $updatedCount++;
        }
    }
}

echo "<p>Proceso completado. Se actualizaron $updatedCount registros.</p>";
echo "</body></html>";
?>
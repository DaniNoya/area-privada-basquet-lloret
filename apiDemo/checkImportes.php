<?php
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header("Access-Control-Allow-Methods: *");

    require_once 'dbConnection.php';

    $con = returnConection();
    $response = new Result();
    $importe = new ResultImportesDescuentos();

    $dni = $_POST['dni'];
    if (isset($_POST['dniTutor'])){
        $dniT = $_POST['dniTutor'];
    }
    $nombre = $_POST['nombre'];
    $jugadorMas18 = $_POST['jugadorMas18'];
    $arrayApellidos = array();
    if (isset($_POST['arrayApellidos'])){
        $arrayApellidos = $_POST['arrayApellidos'];
    }

    $jugador = array();
    if ($jugadorMas18 == true) {
        $sqlConsultImportesSenior = "SELECT *, (SELECT importe FROM importes WHERE concepto = 'quotaInscripcion' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS importeQuotaInscripcion,
                                               (SELECT importe FROM importes WHERE concepto = 'descuentoAnioPasadoSenior' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS importeDescuentoAnioPasado,
                                               (SELECT importe FROM importes WHERE concepto = 'descuentoPagoUnico' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoPagoUnico,
                                               (SELECT importe FROM importes WHERE concepto = 'descuentoPrimerEquipo' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoPrimerEquipo FROM importes WHERE concepto = 'quotaAnualSenior' AND idTemporada = (SELECT MAX(id) FROM temporada);";
        if ($result = mysqli_query($con, $sqlConsultImportesSenior)) {
            $importeData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $importe->concepto = $importeData['concepto'];

            $temporadaImporte = $importeData['idTemporada'];

            $precioQuotaAnual = $importeData['importe'];
            $precioInscripcion = $importeData['importeQuotaInscripcion'];
            $precioDescuentoAnioPasado = $importeData['importeDescuentoAnioPasado'];

            $porcentajeDescuentoPagoUnico = $importeData['porcentajeDescuentoPagoUnico'];
            $importe->porcentajeDescuentoUnico = $porcentajeDescuentoPagoUnico;
            $porcentajeDescuentoPrimerEquipo = $importeData['porcentajeDescuentoPrimerEquipo'];
            $importe->porcentajeDescuentoPrimerE = $porcentajeDescuentoPrimerEquipo;
        }
    } else if ($jugadorMas18 == false) {
        $sqlConsultImportes = "SELECT *,(SELECT importe FROM importes WHERE concepto = 'quotaInscripcion' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS importeQuotaInscripcion,
                                        (SELECT importe FROM importes WHERE concepto = 'descuentoAnioPasado' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS importeDescuentoAnioPasado,
                                        (SELECT importe FROM importes WHERE concepto = 'descuentoPagoUnico' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoPagoUnico,
                                        (SELECT importe FROM importes WHERE concepto = 'descuentoSonHermanos' AND idTemporada = (SELECT MAX(id) FROM temporada)) AS porcentajeDescuentoSonHermanos FROM importes WHERE concepto = 'quotaAnualMenor' AND idTemporada = (SELECT MAX(id) FROM temporada);";

        if ($result = mysqli_query($con, $sqlConsultImportes)) {
            $importeData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $importe->concepto = $importeData['concepto'];

            $temporadaImporte = $importeData['idTemporada'];

            $precioQuotaAnual = $importeData['importe'];
            $precioInscripcion = $importeData['importeQuotaInscripcion'];
            $precioDescuentoAnioPasado = $importeData['importeDescuentoAnioPasado'];

            $porcentajeDescuentoPagoUnico = $importeData['porcentajeDescuentoPagoUnico'];
            $importe->porcentajeDescuentoUnico = $porcentajeDescuentoPagoUnico;
            $porcentajeDescuentoSonHermanos = $importeData['porcentajeDescuentoSonHermanos'];
            $importe->porcentajeDescuentoHermanos = $porcentajeDescuentoSonHermanos;
        }
    }

    $precioUnitario = $precioQuotaAnual;
    $fecha = date('Y');
    /*CHECK TEMPORADA PASADA*/
    $sql = "SELECT dni FROM persona WHERE dni = '$dni' AND  id IN((
            SELECT id_jugador FROM equipos_jugadores WHERE id_equipo IN((
            SELECT id FROM equipo WHERE id_temporada = (
            SELECT id FROM temporada WHERE (year(fecha_final) = $fecha))))));";
    if ($result = mysqli_query($con, $sql)) {
        if ($result->num_rows > 0) {
            $userData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            if ($dni == $userData["dni"]) {
                $importe->temporadaPasada = "OK";
                $precioUnitario = ($precioQuotaAnual - $precioDescuentoAnioPasado);
            } else if (!$dni == $userData["dni"]) {
                $importe->temporadaPasada = "KO";
            }
        } else {
            $importe->temporadaPasada = "NoExiste";
        }
    }

    $precioTotalPagar = 0;
    $precioDescunetHermano = 0;
    $precioDescunetPagoUnico = 0;

    $sonGermans = false;
    if(count($arrayApellidos) > 1){
        for ($i=0; $i < count($arrayApellidos); $i++) {
            if($arrayApellidos[0] == $arrayApellidos[$i]){
                $sonGermans = true;
            } else {
                $sonGermans = false;
                $i = count($arrayApellidos);
            }
        }
    }

    /*DESCUENTOS TEMPORADA*/
    $sqlConsult ="SELECT * FROM descuentosTemporada WHERE borrado = 0 AND dni = '$dni' AND idTipo IN (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular' AND idTemporada = (SELECT MAX(id) FROM temporada));";
    if ($result = mysqli_query($con, $sqlConsult)) {
        if ($result->num_rows > 0) {
            $descuentosTemporadaData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $tipoPago = $descuentosTemporadaData['idTipo'];
            $porcentaje = $descuentosTemporadaData['porcentaje'];
            $jugoAnioPasado = $descuentosTemporadaData['desAnioPasado'];

            if($porcentaje == 15){
                $sonGermans = true;
            }

            if($importe->temporadaPasada == "KO" || $importe->temporadaPasada == "NoExiste"){
                if($jugoAnioPasado == 0){
                    $response->anioPasadoDescuento = "0";
                    $importe->anioPasadoDescuento = "0";
                    $importe->temporadaPasada = "No existe";

                    //$importe->importeConDescuentoTemporadaPasda = $precioUnitario;
                } else if ($jugoAnioPasado == 1){
                    $response->anioPasadoDescuento = "20";
                    $importe->anioPasadoDescuento = "20";
                    $importe->temporadaPasada = "OK";

                    $precioUnitario = ($precioQuotaAnual - $precioDescuentoAnioPasado);
                    //$importe->importeConDescuentoTemporadaPasda = $precioUnitario;
                }
            }

            if(!empty($porcentaje)){
                $importe->porcentajeDescuentoTabDescuentos = $porcentaje;
            }
            $response->porcentajeDescuento = $porcentaje;
        } else {$response->resultat = "Tabla vacia";}
    }

    $sqlConsultTutor ="SELECT * FROM descuentosTemporada WHERE borrado = 0 AND dni = '$dniT' AND idTipo IN (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular' AND idTemporada = (SELECT MAX(id) FROM temporada));";
    if ($result = mysqli_query($con, $sqlConsultTutor)) {
        if ($result->num_rows > 0) {
            $descuentosTemporadaData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $porcentaje = $descuentosTemporadaData['porcentaje'];

            if($porcentaje == 15){
                $sonGermans = true;
            }
        } else {$response->resultat = "Tabla vacia";}
    }

    $response->resultat = $sonGermans;
    $importe->sonHermanos = $sonGermans;

    $importe->temporadaImporte = $temporadaImporte;
    $importe->importe = $precioUnitario;

    if($sonGermans == true){$precioDescunetHermano = (($precioUnitario * $porcentajeDescuentoSonHermanos) / 100);}
    $importe->precioDescunetHermano = $precioDescunetHermano;

    $precioDescunetPagoUnico = (($precioUnitario * $porcentajeDescuentoPagoUnico) / 100);
    $importe->precioDescunetPagoUnico = $precioDescunetPagoUnico;

    $restante = 0;
    if($importe->porcentajeDescuento == "100"){
        $precioTotalPagar = 0;
        $importe->restante = $precioTotalPagar;
    } else if ($jugadorMas18 == true) {
        $precioTotalPagar = ($precioUnitario - $precioDescunetPagoUnico);
        $restante = 0;
        $importe->restante = $restante;
    } else if ($jugadorMas18 == false) {
        $precioTotalPagar = ($precioUnitario - $precioDescunetPagoUnico) - $precioDescunetHermano;
        $restante = 0;
        $importe->restante = $restante;
    }

    $importe->importeInscripcion = $precioInscripcion;
    $importe->total = $precioTotalPagar;


    $importeUnitarioFinalOnlineIns = ($precioTotalPagar + $precioDescunetPagoUnico);
    $importeUnitarioFinalPresencial = ($precioTotalPagar + $precioDescunetPagoUnico);
    $restanteInscripcion = (($precioTotalPagar + $precioDescunetPagoUnico) - $precioInscripcion);

    $jugador['nombre'] = $nombre;

    $pagoUnico = [
        "importeUnitario" => $precioUnitario,
        "importeUnitarioFinalOnline" => $precioTotalPagar,
        "importeUnitarioFinalPresencial" => $importeUnitarioFinalPresencial,
        "restante" => $restante,
        "priceDesHermanos" => $precioDescunetHermano,
        "priceDesPagoUnico" => $precioDescunetPagoUnico
    ];
    $jugador['pagoUnico'] = $pagoUnico;

    $inscripcion = [
        "importeUnitario" => $precioUnitario,
        "importeUnitarioFinalOnline" => $importeUnitarioFinalOnlineIns,
        "importeUnitarioFinalPresencial" => $importeUnitarioFinalPresencial,
        "restante" => $restanteInscripcion
    ];
    $jugador['inscripcion'] = $inscripcion;

    $importe->jugador = $jugador;
    header('Content-Type: application/json');
    echo json_encode($response->importe = $importe);
?>

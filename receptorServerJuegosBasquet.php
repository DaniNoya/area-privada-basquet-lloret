<?php
 header('Access-Control-Allow-Origin: *');
 header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
 header("Access-Control-Allow-Methods: *");

 require_once 'api/dbConnection.php';
 require_once __DIR__.'/libs/apiRedsys.php';
 
 $con = returnConection();
 $response = new Result();
 
 try {
    $redsys = new RedsysAPI;

    $version = $_POST["Ds_SignatureVersion"];
	$datos = $_POST["Ds_MerchantParameters"];
    $signatureRecibida = $_POST["Ds_Signature"];

    $decodec = $redsys->decodeMerchantParameters($datos); 

    $claveModuloAdmin = 'sDVP1hGauv0V9F6VOV6Jtpi9Gc1SO5Fw';
    $signatureCalculada = $redsys->createMerchantSignatureNotif($claveModuloAdmin, $datos);
    
    $pedidoID = $redsys->getOrderNotif();
    if ($signatureCalculada === $signatureRecibida) {
        $verified = true;
        $amount = $redsys->getParameter("Ds_Amount");
        $responseAmount = $redsys->getParameter("Ds_Response");
        if(intval($responseAmount) < 100) {
            $sqlUpdatePagoOK = "UPDATE movimientos SET pagoCompletado = 1,fechaTransaccion = CURRENT_TIMESTAMP() WHERE idTransaccion = '$pedidoID' ";
            if (mysqli_query($con, $sqlUpdatePagoOK)) {
                $response->resultat = "FIRMA-OK_UPDATE_PAGO_OK";
                $idDatosIntermedios = substr($pedidoID, 0, -6);
                $idDatosIntermedios = intval($idDatosIntermedios);
                $sqlUpdatePagoOKDatosIntermedios = "UPDATE datos_intermedios SET pagoOK = CURRENT_TIMESTAMP() WHERE id = '$idDatosIntermedios' ";
                if (mysqli_query($con, $sqlUpdatePagoOKDatosIntermedios)) {
                    $response->resultat = "UPDATE_PAGO_DATOS_INTERMEDIOS_OK";

                    $ch = curl_init('https://areaprivada.basquetlloret.com/api/insertDataJuegosBasquet.php');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, array('id' => $idDatosIntermedios));
                    $response = curl_exec($ch);
                    curl_close($ch);
                } else {
                    $response->resultat = "UPDATE_PAGO_DATOS_INTERMEDIOS_KO";
                    $response->causa = mysqli_error($con);
                }
            } else {
                $response->resultat = "FIRMA-OK_UPDATE_PAGO_KO";
                $response->causa = mysqli_error($con);
            }
        } else {
            $sqlUpdatePagoOKDene = "UPDATE movimientos SET pagoCompletado = 0,fechaTransaccion = CURRENT_TIMESTAMP() WHERE idTransaccion = '$pedidoID' ";
            if (mysqli_query($con, $sqlUpdatePagoOKDene)) {
                $response->resultat = "FIRMA-OK-DENE_UPDATE_PAGO_OK";
            } else {
                $response->resultat = "FIRMA-OK-DENE_UPDATE_PAGO_KO";
                $response->causa = mysqli_error($con);
            }
        }
    } else {
        $verified = false;
        $sqlUpdatePagoKO = "UPDATE movimientos SET pagoCompletado = 0,fechaTransaccion = CURRENT_TIMESTAMP() WHERE idTransaccion = '$pedidoID' ";
        if (mysqli_query($con, $sqlUpdatePagoKO)) {
            $response->resultat = "FIRMA-KO_UPDATE_PAGO_OK";
        } else {
            $response->resultat = "FIRMA-KO_UPDATE_PAGO_KO";
            $response->causa = mysqli_error($con);
        }
    }
 } catch(Exception $e) {
    error_log($e->getMessage());
 }
?>
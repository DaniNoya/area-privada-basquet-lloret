<?php
    require_once __DIR__.'/libs/apiRedsys.php';

	$importe = 30000;
	$idTransaccion = rand(1000,999999);
	$descripcionPago = "test";

	$codigoComercio = "347148595"; //Basquet lloret Real: 347148595
	$clave = "sDVP1hGauv0V9F6VOV6Jtpi9Gc1SO5Fw";
	$transCurrency = "978";
	$transType = "0";
	$terminal = "002";
	$urlResponse = "https://areaprivada.basquetlloret.com/receptorServer.php"; //URL dónde el servidor espera la respuesta del pago
	$urlCliente = "https://areaprivada.basquetlloret.com/receptorCliente.php?id=".$idTransaccion; //URL dónde el cliente será redirigido para ver el resumen del pago (OK/KO)
	$pruebas = true;

	try{
		$redsys = new RedsysAPI;

		$redsys->setParameter("DS_MERCHANT_AMOUNT", $importe);
		$redsys->setParameter("DS_MERCHANT_ORDER", $idTransaccion);
		$redsys->setParameter("Ds_Merchant_ProductDescription", $descripcionPago);
		$redsys->setParameter("DS_MERCHANT_MERCHANTCODE", $codigoComercio);
		$redsys->setParameter("DS_MERCHANT_CURRENCY", $transCurrency);
		$redsys->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $transType);
		$redsys->setParameter("DS_MERCHANT_TERMINAL", $terminal);
		$redsys->setParameter("DS_MERCHANT_MERCHANTURL", $urlResponse);
		$redsys->setParameter("DS_MERCHANT_URLOK", $urlCliente);
		$redsys->setParameter("DS_MERCHANT_URLKO", $urlCliente);

		//Datos de configuración
		$version="HMAC_SHA256_V1";
		$params = $redsys->createMerchantParameters();
		$signature = $redsys->createMerchantSignature($clave);

		$form_url = (!$pruebas) ? 'https://sis.redsys.es/sis/realizarPago' : 'https://sis-t.redsys.es:25443/sis/realizarPago';
	?>
		<form action="<?php echo $form_url; ?>" method="post" target="_parent" id="tpv_form" name="tpv_form" style="">
			<input type=""   name="Ds_SignatureVersion"   value="<?php echo $version; ?>"/>
			<input type=""   name="Ds_MerchantParameters" value="<?php echo $params; ?>"/>
			<input type=""   name="Ds_Signature"          value="<?php echo $signature; ?>"/>
			<input type="submit"   name="enviar" />
		</form>
		<script type="text/javascript">
			//document.tpv_form.submit();
		</script>
	<?php
	} catch(Exception $e) {
		echo $e->getMessage();
	}
?>

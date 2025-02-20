<?php
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header("Access-Control-Allow-Methods: *");

    require_once 'api/dbConnection.php';

    $con = returnConection();
    $response = new Result();

    $idTransaccionPago = $_GET['id'];
    $sqlConsultPagos = "SELECT * FROM movimientos WHERE idTransaccion = '$idTransaccionPago' LIMIT 1;";
    if ($result = mysqli_query($con, $sqlConsultPagos)) {
        if ($result->num_rows > 0) {
            $pagosData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $descripcionPago = $pagosData['descripcion'];
            $completadoCorrecto = json_decode($pagosData['pagoCompletado']);
            if ($completadoCorrecto === 1) {
                $msgTitulo = 'El pago se ha realizado correctamente';
            } else if ($completadoCorrecto === 0) {
                $msgTitulo = 'El pago no se ha realizado correctamente';
            } else if ($completadoCorrecto === NULL) {
                $msgTitulo = 'Vuelve a recargar la pagina para ver la respuesta del pago';
            }
?>
            <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Google' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'><?php echo $msgTitulo;?> </div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td align='center'> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'><?php echo $descripcionPago;?></a> </td></tr></tbody> </table> </div><div style='padding-top:32px;text-align:center'> <a href='https://basquetlloret.artjoc.com/' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Ir al perfil</a> </div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
<?php
        } else {$response->resultat = "Tabla vacia";}
    }
?>
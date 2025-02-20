<?php
 require_once 'api/dbConnection.php';
 require_once __DIR__ . "/libs/phpmailer/config.php";

 if(isset($_POST['tipo'])){
   emailConf($_POST['name'], $_POST['firstSurname'], $_POST['secondSurname'], $_POST['email'], $_POST['dni']);
 }

 function emailConf($name, $firstSurname, $secondSurname, $email, $dni){
    $con = returnConection();
    $response = new Result();

    $fullName = $name.' '.$firstSurname.' '.$secondSurname;

    $caracteres_permitidos = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $longitud = 30;
    $enlaceConfirm = substr(str_shuffle($caracteres_permitidos), 0, $longitud);

    $fechaActual = date('Y-m-d H:i:s');
    $fechaCaducidad = date("Y-m-d H:i:s",strtotime($fechaActual."+ 2 days"));

    $sqlUpdateUsuario = "UPDATE usuario SET enlaceConfirm = '$enlaceConfirm', caducidadConfirm = '$fechaCaducidad' WHERE id = (SELECT id FROM persona WHERE dni = '$dni')";
    if (mysqli_query($con, $sqlUpdateUsuario)) {
      $response->resultat = "UPDATE_USUARIO_OK";

      $mail = getMailObject();
      $mail->addAddress($email, $fullName);

      $mail->Subject = 'Confirmar dirección de correo electrónico (Básquet Lloret)';
      $mail->Body = "<div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Básquet Lloret' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'>Verificación del correo electrónico </div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'>{email}</a> </td></tr></tbody> </table> </div><div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;font-size:14px;color:rgba(0,0,0,0.87);line-height:20px;padding-top:20px;text-align:center'>Se ha registrado una cuenta de Básquet Lloret con este correo. Te hemos enviado este correo electrónico para verificar que eres dueño de dicho correo. <div style='padding-top:32px;text-align:center'> <a href='{url}' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px' target='_blank'>Validar correo</a> </div></div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>";
      
      $mail->Body = str_replace('{email}', $email, $mail->Body);
      $mail->Body = str_replace('{url}', 'https://areaprivada.basquetlloret.com/confirmMail.php?id='.$enlaceConfirm, $mail->Body);

      try{
        $mail->send();
      } catch (Exception $ex){
        //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
      }
    } else {
      $response->resultat = "UPDATE_USUARIO_KO";
      $response->causa = mysqli_error($con);
    }
  }
?>
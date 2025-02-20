<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'api/dbConnection.php';

  $con = returnConection();
  $response = new Result();

  $id = $_GET['id'];
  $sqlConsultUsuario = "SELECT * FROM usuario WHERE enlaceConfirm = '$id';";
  
  if ($result = mysqli_query($con, $sqlConsultUsuario)) {
    if ($result->num_rows > 0) {
        $userData = mysqli_fetch_array($result, MYSQLI_ASSOC);

        if (date('Y-m-d H:i:s') <= $userData["caducidadConfirm"]) {
            $response->resultat = "USUARIO_FECHA-VALIDA_OK";

            $sqlUpdateValido = "UPDATE usuario SET valido = 1, fechaConfirm = CURRENT_TIMESTAMP WHERE enlaceConfirm = '$id'";
            if (mysqli_query($con, $sqlUpdateValido)) {
                $response->resultat = "UPDATE_USUARIO-VALIDO_OK";
                header('Location: http://areaprivada.basquetlloret.com');
              } else {
                $response->resultat = "UPDATE_USUARIO-VALIDO_KO";
                $response->causa = mysqli_error($con);
              }
        } else {
            $idUsuario = $userData["id"];
            $sqlConsultPersona = "SELECT * FROM persona WHERE id = '$idUsuario'";
            if ($result = mysqli_query($con, $sqlConsultPersona)) {
                if ($result->num_rows > 0) {
                    $personaData = mysqli_fetch_array($result, MYSQLI_ASSOC);
                } else {
                    $response->resultat = "No existe";
                }
            }
            ?>
            <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Básquet Lloret' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'>Error en la validación del correo electrónico </div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'><?php echo $personaData["email"];?></a> </td></tr></tbody> </table> </div><div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;font-size:14px;color:rgba(0,0,0,0.87);line-height:20px;padding-top:20px;text-align:center'>Enlace de validación caducado, haga clic en "Volver a validar correo" y revise su bandeja de entrada. <div style='padding-top:32px;text-align:center'> <a href='javascript:sendForm()' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Volver a validar correo</a> </div></div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
            <form name="formConfirmMail" action="sendMail.php" method="post">
              <input type="hidden" name="tipo" value='volverConfirmarMail'>
              <input type="hidden" name="name" value='<?php echo $personaData["nombre"];?>'>
              <input type="hidden" name="firstSurname" value='<?php echo $personaData["primer_apellido"];?>'>
              <input type="hidden" name="secondSurname" value='<?php echo $personaData["segundo_apellido"];?>'>
              <input type="hidden" name="email" value='<?php echo $personaData["email"];?>'>
              <input type="hidden" name="dni" value='<?php echo $personaData["dni"];?>'>
            </form>
            <script>
              function sendForm(){
                document.formConfirmMail.submit();
              }
            </script>
        <?php
        }
    } else {$response->resultat = "No existe";}
  }
  //print_r($response);
?>
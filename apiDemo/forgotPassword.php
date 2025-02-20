<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");
  
  require_once 'vendor/autoload.php';
  require_once 'dbConnection.php';
  require_once "../libs/phpmailer/config.php";
  require_once __DIR__ . "/../sendMailDemo.php";

  $con = returnConection();
  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));

      if(isset($params->tipo)){
        /* Send email (Validar mail) */
        $queryUser = "SELECT u.*, (SELECT p.nombre FROM persona p WHERE p.id = u.id) AS nombre, ".
        "(SELECT p.primer_apellido FROM persona p WHERE p.id = u.id) AS primer_apellido, ".
        "(SELECT p.segundo_apellido FROM persona p WHERE p.id = u.id) AS segundo_apellido, ".
        "(SELECT p.email FROM persona p WHERE p.id = u.id) AS email, ".
        "(SELECT p.dni FROM persona p WHERE p.id = u.id) AS dni FROM usuario u WHERE username = '".$params->dataUsuario->username."';";
        if ($result = mysqli_query($con, $queryUser)) {
          $userData = $result->fetch_assoc();

          $name = $userData['nombre'];
          $firstSurname = $userData['primer_apellido'];
          $secondSurname = $userData['segundo_apellido'];
          $email = $userData['email'];
          $dni = $userData['dni'];

          //$response->params = $name."/".$dni;
          emailConf($name, $firstSurname, $secondSurname, $email, $dni);
        } else {
          $response->resultat = "QUERY_USUARIO_KO";
          $response->causa = mysqli_error($con);
        }
      } else {
        /* Check credentials */
        $queryUser = "SELECT u.*, (SELECT email FROM persona p WHERE p.id = u.id) AS email FROM usuario u WHERE username = '".$params->username."';";
        if ($result = mysqli_query($con, $queryUser)) {
          if ($result->num_rows > 0){
            $userData = $result->fetch_assoc();
            if ($params->email == $userData['email']) {
              if ($userData['valido'] == 1) {
                $response->resultat = 'OK';
              } else {
                $response->resultat = 'Error';
                $response->causa = "Falta validar el email";
              }
            } else {
              $response->resultat = 'Error';
              $response->causa = "La combinación de Usuario/Correo no es correcta";
            }
          } else {
            $response->resultat = 'Error';
            $response->causa = "El usuario no existe";
          }
        }
      }
      break;
    case 'PUT':
      /* Send email (Reset password) */
      $params = json_decode(file_get_contents("php://input"));

      $queryUser = "SELECT u.*, (SELECT p.nombre FROM persona p WHERE p.id = u.id) AS nombre, ".
      "(SELECT p.primer_apellido FROM persona p WHERE p.id = u.id) AS primer_apellido, ".
      "(SELECT p.segundo_apellido FROM persona p WHERE p.id = u.id) AS segundo_apellido, ".
      "(SELECT p.email FROM persona p WHERE p.id = u.id) AS email, ".
      "(SELECT p.dni FROM persona p WHERE p.id = u.id) AS dni FROM usuario u WHERE username = '".$params->username."';";
      if ($result = mysqli_query($con, $queryUser)) {
        $userData = $result->fetch_assoc();

        $idUser = $userData['id'];
        $name = $userData['nombre'];
        $firstSurname = $userData['primer_apellido'];
        $secondSurname = $userData['segundo_apellido'];
        $email = $userData['email'];
        $dni = $userData['dni'];
      } else {
        $response->resultat = "QUERY_USUARIO_KO";
        $response->causa = mysqli_error($con);
      }

      $fullName = $name.' '.$firstSurname.' '.$secondSurname;

      /*$caracteres_permitidos = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $longitud = 30;
      $enlaceConfirm = substr(str_shuffle($caracteres_permitidos), 0, $longitud);*/

      $fechaActual = date('Y-m-d H:i:s');
      $fechaCaducidad = date("Y-m-d H:i:s",strtotime($fechaActual."+ 1 days"));

      $sqlUpdateUsuario = "UPDATE usuario SET expirationValidatePassword = '$fechaCaducidad', datePasswordConfirmation = NULL WHERE id = (SELECT id FROM persona WHERE dni = '$dni');";
      if (mysqli_query($con, $sqlUpdateUsuario)) {
        $response->resultat = "UPDATE_USUARIO_OK";

        $mail = getMailObject();
        // $mail->addAddress($email, $fullName);osalome@recreativoslloret.com
        $mail->addAddress("oscar.salome11@gmail.com", $fullName);

        $mail->Subject = 'Restablecer contraseña (Básquet Lloret)';
        $mail->Body = "<div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody> <tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Básquet Lloret' class='CToWUd'> <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> <div style='font-size:24px'>Restablecer contraseña del usuario con correo electrónico</div><table align='center' style='margin-top:8px'> <tbody> <tr style='line-height:normal'> <td align='right' style='padding-right:8px'> <td> <a style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.87);font-size:14px;line-height:20px'>{email}</a> </td></tr></tbody> </table> </div><div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;font-size:14px;color:rgba(0,0,0,0.87);line-height:20px;padding-top:20px;text-align:center'>Se está intentando restablecer la contraseña de usuario asociado a esta dirección de correo electrónico, si es así haz clic en el botón que dice 'Restablecer contraseña'. <div style='padding-top:32px;text-align:center'> <a href='{url}' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px' target='_blank'>Restablecer contraseña</a> </div></div></div></div><div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>";

        $mail->Body = str_replace('{email}', $email, $mail->Body);
        $mail->Body = str_replace('{url}', 'https://areaprivada.basquetlloret.com/apiDemo/restorePassword.php?id='.$idUser, $mail->Body);

        try {
          $mail->send();
        } catch (Exception $ex) {
          echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
          error_log($mail->ErrorInfo);
        }
      } else {
        $response->resultat = "UPDATE_USUARIO_KO";
        $response->causa = mysqli_error($con);
      }
      break;
    default:
      break;
  };

  header('Content-Type: application/json');
  echo json_encode($response);
?>

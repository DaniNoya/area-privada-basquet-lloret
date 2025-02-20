<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  //require_once 'apiDemo/dbConnection.php';
  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();

  $idUser = $_GET['id'];
  $queryUser = "SELECT * FROM usuario WHERE id = '$idUser';";

  if ($result = mysqli_query($con, $queryUser)) {
    if ($result->num_rows > 0) {
      $userData = mysqli_fetch_array($result, MYSQLI_ASSOC);

      if (date('Y-m-d H:i:s') <= $userData["expirationValidatePassword"]) {
        $response->resultat = "USUARIO_FECHA-VALIDA_OK";

        ?>
        <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody><tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> 
          <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Básquet Lloret' class='CToWUd'>
          <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> 
            <div style='font-size:24px'>Restablecer contraseña</div>
          </div>
          <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;font-size:14px;color:rgba(0,0,0,0.87);line-height:20px;padding-top:20px;text-align:center'>
            <form name="restorePassword" action="updatePasswordUser.php" method="post">
              <input type="hidden" name="tipo" value='confirmarNewPassw'>
              <input type="hidden" name="id" value='<?php echo $idUser;?>'>
              <label style="font-size: 16px;"><strong>Nueva contraseña: </strong></label><br>
              <input type="password" onclick="hiddenError()" id="password" name="password" placeholder="Contraseña" style="height: 35px;margin-top:5px;" required><br>
              <div class="centrarContent">
                <div class="error" aria-live="polite">
                  <span id="errorFrom"></span>
                </div>
              </div>
            </form>
            <div style='padding-top:20px;text-align:center'> 
              <a href='javascript:sendForm()' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Restablecer</a> 
            </div>
          </div>
        </div></div> <div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
        <?php

      } else {
        ?>
        <div style='margin:0;padding:0' bgcolor='#FFFFFF'> <table width='100%' height='100%' style='min-width:348px' border='0' cellspacing='0' cellpadding='0' lang='en'> <tbody><tr height='32' style='height:32px'> <td></td></tr><tr align='center'> <td> <div> <div></div></div><table border='0' cellspacing='0' cellpadding='0' style='padding-bottom:20px;max-width:516px;min-width:220px'> <tbody> <tr> <td width='8' style='width:8px'></td><td> <div style='border-style:solid;border-width:thin;border-color:#dadce0;border-radius:8px;padding:40px 20px' align='center' class='m_-5434700725290117782mdv2rw'> 
          <img src='https://basquetlloret.com/wp-content/uploads/2019/08/Escut-Lloret-v1-Verd-Exterior-150x150.png' width='60' height='60' aria-hidden='true' style='margin-bottom:16px' alt='Básquet Lloret' class='CToWUd'>
          <div style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;border-bottom:thin solid #dadce0;color:rgba(0,0,0,0.87);line-height:32px;padding-bottom:24px;text-align:center;word-break:break-word'> 
            <div style='font-size:24px'>El tiempo para restablecer la contraseña ha expirado</div>
          </div>
          <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;font-size:14px;color:rgba(0,0,0,0.87);line-height:20px;padding-top:20px;text-align:center'>
            <p style="font-size: 18px;">Para poder restablecer la contraseña nuevamente, haga clic en el botón.</p><br>
            <div style='padding-top:20px;text-align:center'> 
              <a href='https://areaprivada.basquetlloret.com/login' style='font-family:Google Sans,Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:16px;color:#ffffff;font-weight:400;text-decoration:none;font-size:14px;display:inline-block;padding:10px 24px;background-color:#00621b;border-radius:5px;min-width:90px'>Restablecer</a> 
            </div>
          </div>
        </div></div> <div style='text-align:left'> <div style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'> <div style='direction:ltr'>© 2021 Básquet Lloret, <a class='m_-5434700725290117782afal' style='font-family:Roboto-Regular,Helvetica,Arial,sans-serif;color:rgba(0,0,0,0.54);font-size:11px;line-height:18px;padding-top:12px;text-align:center'>Lloret de Mar, 17310, Girona, España</a> </div></div></div></td><td width='8' style='width:8px'></td></tr></tbody> </table> </td></tr><tr height='32' style='height:32px'> <td></td></tr></tbody> </table></div>
        <?php
      }

    } else {$response->resultat = "No existe";}
  }
?>
<style>
/* Este es el diseño para nuestros mensajes de error */
.centrarContent {
  display: flex;
  justify-content: center;
}
.error {
  width : 78%;
  padding: 0;
  margin-top: 8px;

  color: white;
  background-color: #900;
  border-radius: 5px 5px 5px 5px;
  box-sizing: border-box;
}
.error.active {
  padding: 1em;
}
.error.hidden {
  display: none;
}
</style>
<script>
  const password = document.getElementById("password");
  const passwordError = document.getElementById('errorFrom');
  function sendForm(){
    if (password.validity.valid) {
        document.restorePassword.submit();
    } else {
      //email.setCustomValidity("");
      showError();
    }
  }

  function showError() {
    if(password.validity.valueMissing) {
      // Si el campo está vacío
      // muestra el mensaje de error siguiente.
      passwordError.textContent = 'El campo no puede estar vacío';
    } /*else if(email.validity.tooShort) {
      // Si los datos son demasiado cortos
      // muestra el mensaje de error siguiente.
      passwordError.textContent = 'El correo electrónico debe tener al menos ${ password.minLength } caracteres; ha introducido ${ password.value.length }.';
    }*/

    // Establece el estilo apropiado
    passwordError.className = 'error activo';
  }

  function hiddenError() {
    passwordError.classList.remove('activo');
    passwordError.classList.add('hidden');
  }
</script>

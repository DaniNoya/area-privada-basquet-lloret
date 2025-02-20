<?php
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: POST");
header("Allow: POST");

require_once 'dbConnection.php';
require_once __DIR__ . "/../sendMailDemo.php";

$con = returnConection();
$response = new Result();

$id = $_POST['id'];
$sqlConsult = "SELECT * FROM datos_intermedios WHERE id = '$id';";
if ($result = mysqli_query($con, $sqlConsult)) {
  if ($result->num_rows > 0) {
    $bdData = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
    $idTipo_pago = $bdData['idTipo_pago'];
    $dataForm = json_decode($bdData['data']);
    $importeDatosIntermedios = $bdData['pago_Importe'];

    foreach($dataForm as $k => $value) {
      if ($k == 'form_fields[nomJugador]') {$namePlayer = $value;}
      if ($k == 'form_fields[primerCognomJugador]') { $firstSurnamePlayer = $value;}
      if ($k == 'form_fields[segonCognomJugador]') {$secondSurnamePlayer = $value;}
      if ($k == 'form_fields[dniJugador]') {$dniPlayer = $value;}
      if ($k == 'form_fields[dataNaixementJugador]') {$dateOfBirthPlayer = $value;}
      if ($k == 'form_fields[emailJugador]') {$emailPlayer = $value;}
      if ($k == 'form_fields[telefonJugador]') {$phonePlayer = $value;}
      if ($k == 'form_fields[domiciliJugador]') {$addressPlayer = $value;}
      if ($k == 'form_fields[poblacioJugador]') {$populationPlayer = $value;}
      if ($k == 'form_fields[codiPostalJugador]') { $postalCodePlayer = $value;}
      if ($k == 'form_fields[categoriaSexoJugador]') {$sexoPlayer = $value;}
      if ($k == 'form_fields[campoUserPassword]') {$userPassword = $value;}
    }

    $quotas = array();
    $presupuestoForm = json_decode($bdData['presupuesto']);
    foreach($presupuestoForm as $k => $value){
      if ($k == 'lineas') {
        foreach($value as $persona){
          foreach($persona as $k2 => $value2){
            if ($k2 == 'nombre') {

              $importeJugador = $persona->importe;
              $restanteJugador = $persona->restante;

              $quotas[$dniPlayer] = $importeJugador;
            }
          }
        }
      }
    }

    $sqlExistDNI = "SELECT dni FROM persona WHERE dni = '$dniPlayer'";
    if ($result = mysqli_query($con, $sqlExistDNI)) {
      if ($result->num_rows > 0) {
          $sqlUpdatePerona = "UPDATE persona SET nombre = '$namePlayer', primer_apellido = '$firstSurnamePlayer', segundo_apellido = '$secondSurnamePlayer', fecha_nacimiento = '$dateOfBirthPlayer', direccion = '$addressPlayer', codigo_postal = '$postalCodePlayer', localidad = '$populationPlayer', telefono1 = '$phonePlayer', email = '$emailPlayer', id_sexo = '$sexoPlayer' WHERE dni = '$dniPlayer'";
          if (mysqli_query($con, $sqlUpdatePerona)) {
              $response->resultat = "UPDATE_PER_OK";
          } else {
              $response->resultat = "UPDATE_PER_KO";
              $response->causa = mysqli_error($con);
          }
      } else {
        //$response->resultat = "No existe";
        $sqlExistNombreCompleto = "SELECT * FROM persona WHERE nombre = '$namePlayer' AND primer_apellido = '$firstSurnamePlayer' AND segundo_apellido = '$secondSurnamePlayer' AND fecha_nacimiento = '$dateOfBirthPlayer'";
        if ($result = mysqli_query($con, $sqlExistNombreCompleto)) {
          if ($result->num_rows > 0) {
            $personaData = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $idPersonaMas18 = $personaData['id'];
            $sqlUpdatePerona = "UPDATE persona SET nombre = '$namePlayer', primer_apellido = '$firstSurnamePlayer', segundo_apellido = '$secondSurnamePlayer', dni = '$dniPlayer', fecha_nacimiento = '$dateOfBirthPlayer', direccion = '$addressPlayer', codigo_postal = '$postalCodePlayer', localidad = '$populationPlayer', telefono1 = '$phonePlayer', email = '$emailPlayer', id_sexo = '$sexoPlayer' WHERE id = '$idPersonaMas18'";
            if (mysqli_query($con, $sqlUpdatePerona)) {
              $response->resultat = "UPDATE_PER_OK";
            } else {
              $response->resultat = "UPDATE_PER_KO";
              $response->causa = mysqli_error($con);
            }
          } else {
            $sqlInsertPersona = "INSERT INTO persona VALUES (NULL, NULL,'$namePlayer','$firstSurnamePlayer','$secondSurnamePlayer','$dniPlayer','$dateOfBirthPlayer','$addressPlayer','$postalCodePlayer','$populationPlayer','$phonePlayer',NULL,'$emailPlayer','','$sexoPlayer')";
            if (mysqli_query($con, $sqlInsertPersona)) {
              $response->resultat = "INSERT_PER_OK";
              $idLastPerson = mysqli_insert_id($con);
            } else {
              $response->resultat = "INSERT_PER_KO";
              $response->causa = mysqli_error($con);
            }
          }
        }
      }
    }

    $id_socio = 0;
    if(strpos($response->resultat, "KO") === false){
      $sqlConsultaIdPersona = "SELECT * FROM persona WHERE dni = '$dniPlayer'";
      if ($resultPersona = mysqli_query($con, $sqlConsultaIdPersona)) {
        $personaData = mysqli_fetch_array($resultPersona, MYSQLI_ASSOC);
        $idPersonaMas18 = $personaData['id'];

        $sqlConsultaSocio = "SELECT id FROM socio WHERE id_persona = '$idPersonaMas18'";
        if ($resultSocio = mysqli_query($con, $sqlConsultaSocio)) {
          if ($resultSocio->num_rows > 0) {
            $socioData = mysqli_fetch_array($resultSocio, MYSQLI_ASSOC);
            $id_socio = $socioData['id'];

            $sqlUpdateSocio = "UPDATE socio SET baja = 0, fecha_baja = null WHERE id = '$id_socio'";
            if (mysqli_query($con, $sqlUpdateSocio)) {
              $response->resultat = "UPDATE_SOCIO_OK";
            } else {
              $response->resultat = "UPDATE_SOCIO_KO";
              $response->causa = mysqli_error($con);
            }
          } else {
            $sqlInsertSocio = "INSERT INTO socio(`id_persona`) VALUES ('$idPersonaMas18');";
            if (mysqli_query($con, $sqlInsertSocio)) {
              $response->resultat = "INSERT_SOCIO_OK";
              $id_socio = mysqli_insert_id($con);
            } else {
              $response->resultat = "INSERT_SOCIO_KO";
              $response->causa = mysqli_error($con);
            }
          }
        }
      }
    }

    if(strpos($response->resultat, "KO") === false){
      $sqlInsertJugadorTemporada = "INSERT INTO socio_temporada(`id_socio`,`id_temporada`,`quota`) VALUES ('$id_socio',(SELECT MAX(id) FROM temporada),$quotas[$dniPlayer]);";
      if (mysqli_query($con, $sqlInsertJugadorTemporada)) {
        $response->resultat = "INSERT_SOCIO_TEMPORADA_OK";
      } else {
        $response->resultat = "INSERT_SOCIO_TEMPORADA_KO";
        $response->causa = mysqli_error($con);
      }
    }

    if(strpos($response->resultat, "KO") === false){
      if (!empty($userPassword)) {
        $sqlInsertUsuarioJugador = "INSERT INTO usuario VALUES ((SELECT id FROM persona WHERE dni = '$dniPlayer'),2,'$dniPlayer',AES_ENCRYPT('$userPassword', UNHEX(SHA2('W1f1Nu7s2017',512))),NULL,0,NULL,NULL,NULL,NULL,NULL)";
        if (mysqli_query($con, $sqlInsertUsuarioJugador)) {
          $response->resultat = "INSERT_USUARIO_OK";
          emailConf($namePlayer, $firstSurnamePlayer, $secondSurnamePlayer, $emailPlayer, $dniPlayer);
        } else {
          $response->resultat = "INSERT_USUARIO_KO";
          $response->causa = mysqli_error($con);
        }
      }
    }
  } else {$response->resultat = "Tabla vacia";}
}
?>

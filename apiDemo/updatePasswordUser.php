<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  //require_once 'apiDemo/dbConnection.php';
  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();

  $idUser = $_POST['id'];
  $newPassword = $_POST['password'];

  $sqlUpdatePassword = "UPDATE usuario u SET u.password = AES_ENCRYPT('$newPassword', UNHEX(SHA2('W1f1Nu7s2017',512))), u.datePasswordConfirmation = CURRENT_TIMESTAMP WHERE u.id = $idUser;";
  if (mysqli_query($con, $sqlUpdatePassword)) {
      $response->resultat = "UPDATE_PASSWORD_OK";

      header('Location: http://areaprivada.basquetlloret.com');
  } else {
    $response->resultat = "UPDATE_PASSWORD_KO";
    $response->causa = mysqli_error($con);
  }
  //print_r($response);
?>
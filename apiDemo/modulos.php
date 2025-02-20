<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();

  //$params = json_decode(file_get_contents("php://input"));

  $idUsuario = $_GET['idUsuario'];
  $sql = "SELECT nombre, ruta FROM modulo WHERE id IN(SELECT idModulo FROM permisos WHERE idPerfil = (SELECT idPerfil FROM usuario WHERE id='$idUsuario'));";
  if($result = mysqli_query($con, $sql)){
      if ($result->num_rows > 0) {
          $response->modulos = array();
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
              $modulo = array();
              $modulo['name'] = $userData['nombre'];
              $modulo['ruta'] = $userData['ruta'];
              $response->modulos[] = $modulo;
          }
      } else {
          $response->resultat = "No existe";
      }
  }
  header('Content-Type: application/json');
  echo json_encode($response);
?>
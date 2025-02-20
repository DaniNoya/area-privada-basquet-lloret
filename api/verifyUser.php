<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
      $dataFamily = array();
      $userCredentials = json_decode(file_get_contents("php://input"));

      $usernameUser = $userCredentials->username;
      $passwdUser = $userCredentials->passwd;

      $queryUser = "SELECT * FROM usuario WHERE username = '$usernameUser'";   
      if ($resultQueryUser = mysqli_query($con, $queryUser)) {
        if ($resultQueryUser->num_rows > 0) {
          $userData = mysqli_fetch_array($resultQueryUser, MYSQLI_ASSOC);

          $passwordDB = $userData['password'];
          $iduser = $userData['id'];

          $passwordEncrypt = "SELECT AES_ENCRYPT('$passwdUser', UNHEX(SHA2('W1f1Nu7s2017',512))) AS passEncrypt";
          if ($resultPasswordEncrypt = mysqli_query($con, $passwordEncrypt)) {
            $passwordEncryptData = mysqli_fetch_array($resultPasswordEncrypt, MYSQLI_ASSOC);

            if($passwordEncryptData['passEncrypt'] == $passwordDB){
              $response->resultat = "OK";
              $querypersona="SELECT * FROM persona WHERE id='$iduser'";
              if ($resultQueryPersona = mysqli_query($con, $querypersona)) {
                  if ($resultQueryPersona->num_rows > 0) {
                      $tutorData = mysqli_fetch_array($resultQueryPersona, MYSQLI_ASSOC);
                      $dataFamily['tutor'] = $tutorData;

                      $queryPlayers = "SELECT p.*, j.tarjetaSanitaria, j.escuela, j.curso, (SELECT sexo FROM sexo WHERE id = p.id_sexo) as sexo ".
                      "FROM persona p INNER JOIN jugador j ON p.id = j.id ".
                      "WHERE DATE_SUB(DATE(NOW()), INTERVAL 18 YEAR) <= p.fecha_nacimiento AND p.id IN(SELECT id_jugador FROM familiar_jugador WHERE id_familiar = $iduser);";

                      if ($resultPlayers = mysqli_query($con, $queryPlayers)) {
                        $dataFamily['players'] = array();
                        while ($playersData = mysqli_fetch_array($resultPlayers, MYSQLI_ASSOC)){
                          $dataFamily['players'][] = $playersData;
                        }
                      }
                      $response->familiares = $dataFamily;
                  }
              }
            }else{
                $response->resultat = "KO-La convinacion no es correcta.";
            }
          }
        } else {
          $response->resultat = "KO-El usuario no existe";
          $response->causa = mysqli_error($con);
        }

      }
      break;
    default:
      break;
  };

  header('Content-Type: application/json');
  echo json_encode($response);
?>

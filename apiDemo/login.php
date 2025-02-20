<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

  require_once 'vendor/autoload.php';
  require_once 'dbConnection.php';

  use Firebase\JWT\JWT;
  const KEY = "W1f1Nu7s2017";

  $json = file_get_contents('php://input');

  $params = json_decode($json);

  $con = returnConection();

  $response = new Result();

  if (isset($params->token)) {
    $jwt = JWT::decode($params->token,KEY,array('HS256'));
    $response->idUsuario = $jwt->data->id;
  } else {
	$sql = "SELECT *, AES_ENCRYPT('" . $params->password . "', UNHEX(SHA2('W1f1Nu7s2017',512))) as passwordEncrypted FROM usuario WHERE username = '" . $params->username . "'";
  	$result = mysqli_query($con, $sql);
  	if ($result->num_rows > 0){
		$userData = $result->fetch_assoc();
		if ($userData['valido'] == 1) {
	  		if ($userData['password'] == $userData['passwordEncrypted']){
	    		$response->resultat = 'OK';
	    		$time = time();
	    		$token = array(
		      		'iat' => $time, // Tiempo que inició el token
		      		'exp' => $time + (60*60), // Tiempo que expirará el token (+1 hora)
		      		'data' => [ // información del usuario
		        		'id' => $userData['id'],
		        		'name' => $userData['username']
		      		]
		    	);
		    	$jwt = JWT::encode($token,KEY);
		    	$response->token = $jwt;
		    	$response->idUsuario = $userData['id'];
	      	} else {
	        	$response->resultat = 'Error';
	        	$response->causa = "La combinación de Usuario/Contraseña no es correcta";
	     	}
	    } else {
	      $response->resultat = 'Error';
	      $response->causa = "Falta validar el email";
	    }
	} else{
		$response->resultat = 'Error';
		$response->causa = "El usuario no existe";
	}
  }

  header('Content-Type: application/json');
  echo json_encode($response);
?>

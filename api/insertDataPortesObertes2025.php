<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");
  header("Content-Type: application/json");

  require_once 'dbConnection.php';

  // Clase específica para respuestas del formulario de puertas abiertas
  class ResultPortesObertes {
    public $resultat = "";
    public $causa = "";
    public $sql = ""; 
  }

  // Función para registrar logs
  function writeLog($message, $type = 'INFO') {
    $logFile = __DIR__ . '/../logs/portesObertes.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
  }

  // Inicializar respuesta
  $response = new ResultPortesObertes();

  try {
    // Obtener el contenido JSON enviado en la solicitud
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData);
    
    // Verificar si se recibieron datos
    if (!$data) {
      writeLog("Error: No se recibieron datos o formato JSON inválido", 'ERROR');
      $response->resultat = "ERROR";
      $response->causa = "No se recibieron datos o formato JSON inválido";
      echo json_encode($response);
      exit;
    }
    
    writeLog("Datos recibidos para registro de puertas abiertas: " . $jsonData);
    
    // Obtener conexión a la base de datos
    $con = returnConection();
    
    if (!$con) {
      writeLog("Error de conexión a la base de datos: " . mysqli_connect_error(), 'ERROR');
      $response->resultat = "ERROR";
      $response->causa = "Error de conexión a la base de datos";
      echo json_encode($response);
      exit;
    }
    
    // Preparar la consulta SQL con consultas preparadas para evitar inyección SQL
    $query = "INSERT INTO jugador_portasobertes (nombre, primer_apellido, segundo_apellido, fecha_nacimiento, 
              email, telefono1, telefono2, genero, pastClubBool, pastClubName) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($con, $query);
    
    if (!$stmt) {
      writeLog("Error al preparar la consulta: " . mysqli_error($con), 'ERROR');
      $response->resultat = "ERROR";
      $response->causa = "Error al preparar la consulta";
      $response->sql = $query;
      echo json_encode($response);
      exit;
    }
    
    // Vincular parámetros
    mysqli_stmt_bind_param($stmt, "ssssssssss", 
      $data->nom, 
      $data->primerCognom, 
      $data->segonCognom, 
      $data->dataNaixement, 
      $data->correuElectronic, 
      $data->telefon1, 
      $data->telefon2, 
      $data->genere, 
      $data->hasJugatAnteriorment, 
      $data->indicaQuin
    );
    
    // Ejecutar la consulta
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
      writeLog("Registro exitoso para: {$data->nom} {$data->primerCognom}");
      $response->resultat = "OK";
      $response->causa = "Datos registrados correctamente";
    } else {
      writeLog("Error al insertar datos: " . mysqli_stmt_error($stmt), 'ERROR');
      $response->resultat = "ERROR";
      $response->causa = "Error al insertar datos en la base de datos";
    }
    
    // Cerrar la consulta y la conexión
    mysqli_stmt_close($stmt);
    mysqli_close($con);
    
  } catch (Exception $e) {
    writeLog("Excepción: " . $e->getMessage(), 'ERROR');
    $response->resultat = "ERROR";
    $response->causa = "Error interno del servidor: " . $e->getMessage();
  }
  
  // Devolver respuesta en formato JSON
  echo json_encode($response);
?>
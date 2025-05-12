<?php
// Encabezados para CORS (Angular - http://localhost:4200)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:4200"); // Permite Angular en localhost
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Allow: GET, OPTIONS");

// Manejo de la solicitud OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbConnection.php';

$response = [
    "status" => "error",
    "message" => "No se pudo conectar a la base de datos."
];

// Probar la conexión
$con = returnConection();
if ($con) {
    $response["status"] = "success";
    $response["message"] = "✅ Conexión exitosa a la base de datos.";
} else {
    $response["message"] = "❌ Error de conexión: " . mysqli_connect_error();
}

// Devolver la respuesta en formato JSON
echo json_encode($response);
?>

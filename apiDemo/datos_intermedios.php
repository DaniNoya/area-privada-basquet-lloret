<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");

  require_once 'dbConnection.php';

  $con = returnConection();
  $response = new Result();

  switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':

        $persona = json_decode(file_get_contents("php://input"));

        $datosPersona = mysqli_real_escape_string($con,json_encode($persona->datos));
        $importeInscripcion = $persona->importe;
        $datosPresupuesto = mysqli_real_escape_string($con,json_encode($persona->presupuesto));
        $paginaReferente = $persona->pagina;
        $idTipo_pago = $persona->idTipo_pago;

        $sql = "INSERT INTO datos_intermedios VALUES (NULL,'$datosPersona',CURRENT_TIMESTAMP(),NULL,'$importeInscripcion','$datosPresupuesto','$paginaReferente',$idTipo_pago)";

        if (mysqli_query($con, $sql)) {
            $response->resultat = "INSERT_OK";
            $id = mysqli_insert_id($con);
            $response->causa = $id;
        } else {
            $response->resultat = "INSERT_KO";
            $response->causa = mysqli_error($con);
        }
      break;
    default:
      break;
  };

  header('Content-Type: application/json');
  echo json_encode($response);
?>

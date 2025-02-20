<?php
  header('Access-Control-Allow-Origin: *');
  header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
  header("Access-Control-Allow-Methods: *");
  
  require_once 'dbConnection.php';
  
  $con = returnConection();
  $response = new Result();

  $dni = $_POST['dni'];
  if (isset($_POST['arrayApellidos'])){
    $arrayApellidos = $_POST['arrayApellidos'];
  }
  $sqlConsult ="SELECT * FROM descuentosTemporada WHERE dni = '$dni' AND borrado = 0 AND idTipo IN (SELECT id FROM tipo_pago WHERE concepto = 'Temporada Regular' AND idTemporada  = (SELECT MAX(id) FROM temporada));";
  if ($result = mysqli_query($con, $sqlConsult)) {
    if ($result->num_rows > 0) {
        $descuentosTemporadaData = mysqli_fetch_array($result, MYSQLI_ASSOC);

        $tipoPago = $descuentosTemporadaData['idTipo'];
        $porcentaje = $descuentosTemporadaData['porcentaje'];
        $jugoAnioPasado = $descuentosTemporadaData['desAnioPasado'];

        $sonGermans = false;
        if(count($arrayApellidos) > 1){
          for ($i=0; $i < count($arrayApellidos); $i++) {
            if($arrayApellidos[0] == $arrayApellidos[$i]){
              $sonGermans = true;
            } else {
              $sonGermans = false;
              $i = count($arrayApellidos);
            }
          }
          if($porcentaje == 15){
            $sonGermans = true;
          }
        }
        $response->resultat = $sonGermans;

        if($jugoAnioPasado == 0){
            $response->anioPasadoDescuento = "0";
        } else if ($jugoAnioPasado == 1){
            $response->anioPasadoDescuento = "20";
        }
        $response->porcentajeDescuento = $porcentaje;
    } else {$response->resultat = "Tabla vacia"; $response->causa = $sqlConsult;}
  }
  header('Content-Type: application/json');
  echo json_encode($response);
?>
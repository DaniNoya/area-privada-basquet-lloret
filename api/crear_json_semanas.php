<!DOCTYPE html>
<html>
  <head></head>
  <body>
<?php

require_once 'dbConnection.php';

$conn = returnConection();
$response = new Result();

$sql = "SELECT di.data FROM datos_intermedios di WHERE di.pagoOK IS NOT NULL AND di.idTipo_pago = ". $_GET['idtipopago'];
$result = mysqli_query($conn, $sql);

$arrayJugadores = [];
foreach ($result as $key => $listadoInscripciones) {
  
  foreach( $listadoInscripciones as $key2 => $listadoPersonas) {
    
    foreach (json_decode($listadoPersonas) as $key3 => $value3) {

      if ($key3 == 'Jugadores') {
        foreach($value3 as $jugador) {
          $arrayJugador = [];
          foreach($jugador as $k2 => $value2){
            if ($k2 == 'form_fields[nomJugador]') {$arrayJugador['Nom'] = $value2;}
            if ($k2 == 'form_fields[primerCognomJugador]') {$arrayJugador['Primer Cognom'] = $value2;}
            if ($k2 == 'form_fields[segonCognomJugador]') {$arrayJugador['Segon Cognom'] = $value2;}
            if ($k2 == 'form_fields[dniJugador]') {$arrayJugador['DNI'] = $value2;}
            if ($k2 == 'form_fields[camisetaCampusBullsJugador]') {$arrayJugador['Talla Samarreta Campus Bulls'] = $value2;}
            if ($k2 == 'form_fields[camisetaSummerWorkoutJugador]') {$arrayJugador['Talla Samarreta Summer Workout'] = $value2;}

            if ($k2 == 'form_fields[setmana1Jugador]') {$arrayJugador['Setmana 1'] = $value2;}
            if ($k2 == 'form_fields[setmana2Jugador]') {$arrayJugador['Setmana 2'] = $value2;}
            if ($k2 == 'form_fields[setmana3Jugador]') {$arrayJugador['Setmana 3'] = $value2;}
            if ($k2 == 'form_fields[setmana4Jugador]') {$arrayJugador['Setmana 4'] = $value2;}

            if ($k2 == 'form_fields[setmana1JulJugador]') {$arrayJugador['Setmana 1 Juliol'] = $value2;}
            if ($k2 == 'form_fields[setmana2JulJugador]') {$arrayJugador['Setmana 2 Juliol'] = $value2;}
            if ($k2 == 'form_fields[setmana3JulJugador]') {$arrayJugador['Setmana 3 Juliol'] = $value2;}
            if ($k2 == 'form_fields[setmana4JulJugador]') {$arrayJugador['Setmana 4 Juliol'] = $value2;}

            if ($k2 == 'form_fields[setmana1AgtJugador]') {$arrayJugador['Setmana 1 Agost'] = $value2;}
            if ($k2 == 'form_fields[setmana2AgtJugador]') {$arrayJugador['Setmana 2 Agost'] = $value2;}
            if ($k2 == 'form_fields[setmana3AgtJugador]') {$arrayJugador['Setmana 3 Agost'] = $value2;}
            if ($k2 == 'form_fields[setmana4AgtJugador]') {$arrayJugador['Setmana 4 Agost'] = $value2;}
            if ($k2 == 'form_fields[setmana5AgtJugador]') {$arrayJugador['Setmana 5 Agost'] = $value2;}
      
          }
          array_push($arrayJugadores, $arrayJugador);
        }
      }
     
    }
  }
}
echo json_encode($arrayJugadores);
?>
  </body>
</html>

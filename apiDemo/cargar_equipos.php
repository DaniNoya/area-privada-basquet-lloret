<?php
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: POST");
header("Allow: POST");

require_once 'dbConnection.php';

$conn = returnConection();
$response = new Result();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'POST':
    $teamsList = array();

    $queryTeams = "SELECT e.id_categoria, e.id_temporada, e.id_competicion, e.id_tipo_categoria, e.nacidos_desde_anyo, e.nacidos_hasta_anyo, e.foto, e.descripcion, e.id_fcbq, e.activoWeb, (SELECT MAX(t.id) FROM temporada t) AS lastSeason
                   FROM equipo e
                   WHERE e.id_temporada = (SELECT (MAX(t.id) - 1) FROM temporada t);";

    if ($resultTeams = mysqli_query($conn, $queryTeams)) {
      while ($teamData = mysqli_fetch_array($resultTeams, MYSQLI_ASSOC)) {
        $teamsList[] = [
          "id_categoria" => $teamData['id_categoria'],
          "id_temporada" => $teamData['lastSeason'],
          "id_competicion" => $teamData['id_competicion'],
          "id_tipo_categoria" => $teamData['id_tipo_categoria'],
          "nacidos_desde_anyo" => $teamData['nacidos_desde_anyo'],
          "nacidos_hasta_anyo" => $teamData['nacidos_hasta_anyo'],
          "foto" => $teamData['foto'],
          "descripcion" => $teamData['descripcion'],
          "id_fcbq" => $teamData['id_fcbq'],
          "activoWeb" => 0
        ];
      }

      $inserts = array();
      for ($i = 0; $i < count($teamsList); $i++) {
        $insert = "INSERT INTO equipo (";
        $values = " VALUES (";

        foreach ($teamsList[$i] as $key => $value) {
          $insert .= "$key, ";
          $values .= " '$value', ";
        }

        // Eliminar las ultimas comas y cerrar los parentesis
        $insert = substr($insert, 0, -2).')';
        $values = substr($values, 0, -2).');';

        $insertSQL = $insert.$values;
        $inserts[] = $insertSQL;

        mysqli_query($conn, $insertSQL);
        /*if (mysqli_query($conn, $insertSQL)) {
          return http_response_code(200);
        } else {
          return http_response_code(422);
        }*/
      }
      $response->resultat = $inserts;
    }
    break;
  default:
    break;
}

echo json_encode($response);
?>
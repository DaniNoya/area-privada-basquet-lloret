<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");

require_once 'dbConnection.php';

$con = returnConection();

$response = new Result();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $json = json_encode($_GET);
    $params = json_decode($json);
    
    // Obtener filtros si existen
    $filtroNombre = isset($params->filtroNombre) ? mysqli_real_escape_string($con, $params->filtroNombre) : '';
    $filtroSemana = isset($params->filtroSemana) ? intval($params->filtroSemana) : 0;
    
    // Consulta base para obtener jugadores inscritos en el casal d'estiu
    $sql = "SELECT 
            p.id, p.nombre, p.primer_apellido, p.segundo_apellido, p.dni, 
            p.fecha_nacimiento, p.direccion, p.codigo_postal, p.localidad, 
            p.telefono1, p.telefono2, p.email, p.id_sexo,
            j.baja, j.fecha_baja, j.tarjetaSanitaria, j.escuela, j.curso, 
            j.numero_dorsal, j.talla_ropa_juego, j.nombre_dorsal, 
            j.talla_camiseta_tecnica_escoleta, j.talla_camiseta_regalo_CH, 
            j.talla_camiseta_campus_bulls, j.talla_camiseta_summer_workout,
            (SELECT sexo FROM sexo WHERE id = p.id_sexo) as sexo, 
            (SELECT foto FROM fotos WHERE id_persona = p.id AND id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1)) as foto 
            FROM persona p 
            INNER JOIN jugador j ON p.id = j.id 
            INNER JOIN jugador_semana js ON j.id = js.jugador_id 
            INNER JOIN semanas_campus sc ON js.semana_id = sc.id_semana 
            WHERE sc.temporada_id = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1) 
            AND sc.tipo_pago_id = 28 ";
    
    // Aplicar filtros si existen
    if (!empty($filtroNombre)) {
      $sql .= "AND (p.nombre LIKE '%$filtroNombre%' OR p.primer_apellido LIKE '%$filtroNombre%' OR p.segundo_apellido LIKE '%$filtroNombre%') ";
    }
    
    if ($filtroSemana > 0) {
      $sql .= "AND sc.id_semana = $filtroSemana ";
    }
    
    $sql .= "GROUP BY j.id ORDER BY p.primer_apellido ASC, p.nombre ASC";
    
    $response->jugadores = array();
    
    if ($result = mysqli_query($con, $sql)) {
      while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        // Para cada jugador, obtener las semanas en las que está inscrito
        $idJugador = $userData['id'];
        $sqlSemanas = "SELECT sc.id_semana, sc.fecha_inicio, sc.fecha_fin, sc.precio, js.fecha_inscripcion 
                      FROM jugador_semana js 
                      INNER JOIN semanas_campus sc ON js.semana_id = sc.id_semana 
                      WHERE js.jugador_id = $idJugador AND sc.temporada_id = 7 AND sc.tipo_pago_id = 28";
        
        $userData['semanas'] = array();
        
        if ($resultSemanas = mysqli_query($con, $sqlSemanas)) {
          while ($semanaData = mysqli_fetch_array($resultSemanas, MYSQLI_ASSOC)) {
            $userData['semanas'][] = $semanaData;
          }
        }
        
        $response->jugadores[] = $userData;
      }
    }
    break;
    
  default:
    $response->resultat = "KO";
    $response->errMessage = "Método no soportado";
    break;
}

echo json_encode($response);

?>
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

      if (isset($params->equipos)) {
        $response->equipos = array();

        $sql = "SELECT e.*, c.categoria, t.temporada, co.competicio as competicion, tc.tipo as tipoCategoria, e.nacidos_desde_anyo as nacidosDesdeAnyo, ".
          "e.nacidos_hasta_anyo as nacidosHastaAnyo FROM equipo e ".
          "INNER JOIN categoria c ON e.id_categoria = c.id ".
          "INNER JOIN temporada t ON e.id_temporada = t.id ".
          "INNER JOIN competicion co ON e.id_competicion = co.id ".
          "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
          "WHERE e.id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1) ".
          "ORDER BY c.id ASC";

        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->equipos[] = $userData;
          }
        }
      } else if (isset($params->jugadores)) {
        $response->jugadoresAsignados = array();
        $response->jugadoresDisponibles = array();
        $response->jugadoresDisponiblesJovenes = array();

        $id_equipo = $params->equipo;
        $filtro = $params->filtro;

        $sql = "SELECT j.*, p.*, (SELECT foto FROM fotos WHERE id_persona = p.id AND id_temporada = (SELECT id_temporada FROM equipo WHERe id = $id_equipo)) as foto, ej.dorsal FROM jugador j ".
          "INNER JOIN persona p ON j.id = p.id INNER JOIN equipos_jugadores ej ON j.id = ej.id_jugador WHERE ej.id_equipo = $id_equipo AND j.baja = 0 ORDER BY p.primer_apellido ASC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->jugadoresAsignados[] = $userData;
          }
        }

        $sql = "SELECT j.*, p.*, (SELECT foto FROM fotos WHERE id_persona = p.id AND id_temporada = (SELECT id_temporada FROM equipo WHERe id = $id_equipo)) as foto FROM jugador j INNER JOIN persona p ON j.id = p.id ".
            "WHERE j.id NOT IN (SELECT id_jugador FROM equipos_jugadores WHERE id_equipo IN (SELECT id FROM equipo WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1))) AND j.baja = 0 ".
            "AND IF(((SELECT id_tipo_categoria FROM equipo WHERE id = $id_equipo) != 3),p.id_sexo = (SELECT id_tipo_categoria FROM equipo WHERE id = $id_equipo),'1=1') ".
            "AND YEAR(p.fecha_nacimiento) >= (SELECT nacidos_desde_anyo FROM equipo WHERE id = $id_equipo) AND YEAR(p.fecha_nacimiento) <= (SELECT nacidos_hasta_anyo FROM equipo WHERE id = $id_equipo) ";
        if ($filtro != ''){
          $sql .= " AND (p.nombre LIKE '%$filtro%' OR p.primer_apellido LIKE '%$filtro%' OR p.segundo_apellido LIKE '%$filtro%') ";
        }
        $sql .= "ORDER BY p.primer_apellido ASC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->jugadoresDisponibles[] = $userData;
          }
        }

        $sql = "SELECT j.*, p.*, (SELECT foto FROM fotos WHERE id_persona = p.id AND id_temporada = (SELECT id_temporada FROM equipo WHERe id = $id_equipo)) as foto FROM jugador j INNER JOIN persona p ON j.id = p.id ".
          "WHERE j.id NOT IN (SELECT id_jugador FROM equipos_jugadores WHERE id_equipo IN (SELECT id FROM equipo WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1))) AND j.baja = 0 ".
          "AND IF(((SELECT id_tipo_categoria FROM equipo WHERE id = $id_equipo) != 3),p.id_sexo = (SELECT id_tipo_categoria FROM equipo WHERE id = $id_equipo),'1=1') ".
          "AND YEAR(p.fecha_nacimiento) > (SELECT nacidos_hasta_anyo FROM equipo WHERE id = $id_equipo) ";
        if ($filtro != ''){
          $sql .= " AND (p.nombre LIKE '%$filtro%' OR p.primer_apellido LIKE '%$filtro%' OR p.segundo_apellido LIKE '%$filtro%') ";
        }
        $sql .= "ORDER BY p.primer_apellido ASC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->jugadoresDisponiblesJovenes[] = $userData;
          }
        }
      } else if (isset($params->entrenadores)) {
        $response->entrenadoresAsignados = array();
        $response->entrenadoresDisponibles = array();

        $id_equipo = $params->equipo;
        $filtro = $params->filtro;

        $sql = "SELECT p.*, e.*, nf.nivel_formacion, ee.tipo as id_tipo_entrenador, ".
          "(SELECT tipo FROM tipo_entrenador WHERE id = ee.tipo) as tipo_entrenador FROM entrenador e ".
          "INNER JOIN persona p ON e.id = p.id INNER JOIN nivel_formacion nf ON e.id_nivel_formacion = nf.id INNER JOIN entrenadores_equipos ee ON e.id = ee.id_entrenador ".
          "WHERE ee.id_equipo = $id_equipo AND ee.tipo != 3 ORDER BY ee.tipo ASC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->entrenadoresAsignados[] = $userData;
          }
        }

        $sql = "SELECT p.*, e.*, nf.nivel_formacion FROM entrenador e ".
          "INNER JOIN persona p ON e.id = p.id INNER JOIN nivel_formacion nf ON e.id_nivel_formacion = nf.id ".
          "WHERE e.baja = 0 AND p.id NOT IN (SELECT id_entrenador FROM entrenadores_equipos WHERE id_equipo = $id_equipo) ";
        if ($filtro != ''){
          $sql .= " AND (p.nombre LIKE '%$filtro%' OR p.primer_apellido LIKE '%$filtro%' OR p.segundo_apellido LIKE '%$filtro%') ";
        }
        $sql .= "ORDER BY id_nivel_formacion DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->entrenadoresDisponibles[] = $userData;
          }
        }
      } else if (isset($params->delegados)) {
        $response->directivosAsignados = array();
        $response->familiaresAsignados = array();
        $response->directivosDisponibles = array();
        $response->familiaresDisponibles = array();

        $id_equipo = $params->equipo;
        $filtro = $params->filtro;

        $sql = "SELECT p.*, d.* FROM persona p INNER JOIN directivo d on p.id = d.id WHERE p.id IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo) ORDER BY primer_apellido ASC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->directivosAsignados[] = $userData;
          }
        }
        $sql = "SELECT p.*, f.* FROM persona p INNER JOIN familiar f on p.id = f.id WHERE p.id IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo) ".
          " AND p.id NOT IN (SELECT p.id FROM persona p INNER JOIN directivo d on p.id = d.id WHERE p.id IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo)) ORDER BY primer_apellido ASC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->familiaresAsignados[] = $userData;
          }
        }

        $sql = "SELECT p.*, d.* FROM persona p INNER JOIN directivo d on p.id = d.id WHERE p.id NOT IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo) ";
        if ($filtro != ''){
          $sql .= " AND (p.nombre LIKE '%$filtro%' OR p.primer_apellido LIKE '%$filtro%' OR p.segundo_apellido LIKE '%$filtro%') ";
        }
        $sql .= "ORDER BY p.primer_apellido DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->directivosDisponibles[] = $userData;
          }
        }
        $sql = "SELECT p.*, f.* FROM persona p INNER JOIN familiar f on p.id = f.id WHERE p.id NOT IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo) ".
			"AND p.id NOT IN (SELECT p.id FROM persona p INNER JOIN directivo d on p.id = d.id WHERE p.id NOT IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo)) ";
        if ($filtro != ''){
          $sql .= " AND (p.nombre LIKE '%$filtro%' OR p.primer_apellido LIKE '%$filtro%' OR p.segundo_apellido LIKE '%$filtro%') ";
        }
        $sql .= "ORDER BY p.primer_apellido DESC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->familiaresDisponibles[] = $userData;
          }
        }

      } else if (isset($params->id)) {
        $response->equipos = array();

        $sql = "SELECT e.*, c.categoria, t.temporada, co.competicio as competicion, tc.tipo as tipoCategoria, e.nacidos_desde_anyo as nacidosDesdeAnyo, e.nacidos_hasta_anyo as nacidosHastaAnyo ".
          "FROM equipo e ".
          "INNER JOIN categoria c ON e.id_categoria = c.id ".
          "INNER JOIN temporada t ON e.id_temporada = t.id ".
          "INNER JOIN competicion co ON e.id_competicion = co.id ".
          "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
          "WHERE e.id_temporada = $params->id ".
          "AND e.activoWeb = 1 ".
          "ORDER BY c.id ASC";

        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->equipos[] = $userData;
          }
        }
        } else if (isset($params->idequipo)) {
        $id_equipo = $params->idequipo;
        $response-> jugadoresAsignados = array();
        $response->entrenadoresAsignados = array();
        $response->directivosAsignados = array();
            $response->familiaresAsignados = array();

        $sql = "SELECT j.*, p.*, (SELECT foto FROM fotos WHERE id_persona = p.id AND id_temporada = (SELECT id_temporada FROM equipo WHERe id = $id_equipo)) as foto, ej.dorsal FROM jugador j ".
              "INNER JOIN persona p ON j.id = p.id INNER JOIN equipos_jugadores ej ON j.id = ej.id_jugador WHERE ej.id_equipo = $id_equipo AND j.baja = 0";
            if ($result = mysqli_query($con, $sql)) {
              while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $response->jugadoresAsignados[] = $userData;
              }
            }
        $sql = "SELECT p.*, e.*, nf.nivel_formacion, ee.tipo as id_tipo_entrenador, ".
              "(SELECT tipo FROM tipo_entrenador WHERE id = ee.tipo) as tipo_entrenador FROM entrenador e ".
              "INNER JOIN persona p ON e.id = p.id INNER JOIN nivel_formacion nf ON e.id_nivel_formacion = nf.id INNER JOIN entrenadores_equipos ee ON e.id = ee.id_entrenador ".
              "WHERE ee.id_equipo = $id_equipo AND ee.tipo != 3 ORDER BY ee.tipo ASC";
            if ($result = mysqli_query($con, $sql)) {
              while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $response->entrenadoresAsignados[] = $userData;
              }
            }
        $sql = "SELECT p.*, d.* FROM persona p INNER JOIN directivo d on p.id = d.id WHERE p.id IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo)";
            if ($result = mysqli_query($con, $sql)) {
              while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $response->directivosAsignados[] = $userData;
              }
            }
            $sql = "SELECT p.*, f.* FROM persona p INNER JOIN familiar f on p.id = f.id WHERE p.id IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo) ".
              "AND p.id NOT IN (SELECT p.id FROM persona p INNER JOIN directivo d on p.id = d.id WHERE p.id IN (SELECT id_delegado FROM delegados_equipos WHERE id_equipo = $id_equipo)) ORDER BY primer_apellido ASC";
            if ($result = mysqli_query($con, $sql)) {
              while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $response->familiaresAsignados[] = $userData;
              }
            }
        $sql = "SELECT e.*, c.categoria, t.temporada, co.competicio as competicion, tc.tipo as tipoCategoria, e.nacidos_desde_anyo as nacidosDesdeAnyo, e.nacidos_hasta_anyo as nacidosHastaAnyo ".
              "FROM equipo e ".
              "INNER JOIN categoria c ON e.id_categoria = c.id ".
              "INNER JOIN temporada t ON e.id_temporada = t.id ".
              "INNER JOIN competicion co ON e.id_competicion = co.id ".
              "INNER JOIN tipo_categoria tc ON e.id_tipo_categoria = tc.id ".
              "WHERE e.id = $id_equipo ".
              "ORDER BY c.id ASC";
        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			      $sql2 = "SELECT foto FROM galeria_equipos WHERE id_equipo = " . $userData['id'];
            if ($result2 = mysqli_query($con, $sql2)) {
              $fotos = array();
              while ($fotosData = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
                $fotos[] = $fotosData['foto'];
              }
              $userData['imagenes'] = $fotos;
            }
            $response->equipos[] = $userData;
          }
        }
      } else {
        $response->temporadas = array();

        $sql = "SELECT * FROM temporada ORDER BY id DESC";

        if ($result = mysqli_query($con, $sql)) {
          while ($userData = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $response->temporadas[] = $userData;
          }
        }
      }
      break;
    case 'POST':
      $params = json_decode(file_get_contents("php://input"));

      $temporada = mysqli_real_escape_string($con, $params->temporada);
      $fecha_inicio = $params->fecha_inicio;
      $fecha_final = $params->fecha_final;
      $observaciones = mysqli_real_escape_string($con, $params->observaciones);

      $sql = "INSERT INTO temporada VALUES (NULL,'$temporada','$fecha_inicio','$fecha_final','$observaciones')";
      if(mysqli_query($con, $sql)) {
        $sql = "INSERT INTO fotos (SELECT id_persona, " . mysqli_insert_id($con) . ",  foto FROM fotos)";
        if(mysqli_query($con, $sql)) {
          $sql = "SELECT * FROM temporada ORDER BY id DESC LIMIT 1";
          if ($result = mysqli_query($con, $sql)) {
           return http_response_code(200);
          }
        } else {
          return http_response_code(422);
        }
      } else {
        return http_response_code(422);
      }
      break;
    default:
      break;
  };

  //error_log(print_r($response->tipos_parentesco, TRUE));
  header('Content-Type: application/json');
  echo json_encode($response);

?>

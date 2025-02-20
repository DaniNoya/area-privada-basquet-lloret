<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");

require_once __DIR__ . "/libs/phpmailer/config.php";

$con=mysqli_connect("134.0.8.92","mybasquetl","sj8i47XD","cblloretdb");
$log=[];

$urlClub ="https://esb.optimalwayconsulting.com/fcbq/1/NH5YXrzkttZb03yAwhUurTwaAakiYZ6K/OpenDataClub/getDataClub/2520";
$json = file_get_contents($urlClub);
$json_dataClub = json_decode($json);

$urlMatch ="https://esb.optimalwayconsulting.com/fcbq/1/NH5YXrzkttZb03yAwhUurTwaAakiYZ6K/OpenDataClub/getAllMatchClub/2520";
$jsonM = file_get_contents($urlMatch);
$json_dataMatch = json_decode($jsonM);

// coge info api hi la guarda en variables

foreach ($json_dataMatch as $info=>$data) {
    if(is_array($data)) {
        for ($cont=0;$cont<sizeof($data);$cont++) {
            if (is_object($data[$cont])) {
                foreach ($data[$cont] as $in => $da) {
                    if (!is_object($da)) {
                        if ($in == "idMatch") {
                            $idPartido = mysqli_real_escape_string($con,$da);
                        }
                        if ($in == "idLocalTeam") {
                            $idEquipoLocal = mysqli_real_escape_string($con,$da);
                        }
                        if ($in == "nameLocalTeam") {
                            $nombreEquipoLocal = mysqli_real_escape_string($con,$da);
                        }
                        if ($in == "idVisitorTeam") {
                            $idEquipoVisitante = mysqli_real_escape_string($con,$da);
                        }
                        if ($in == "nameVisitorTeam") {
                            $nombreEquipoVisitante = mysqli_real_escape_string($con,$da);
                        }
                        if ($in == "matchDay") {
                            $HoraDia = mysqli_real_escape_string($con,$da);
                        }
                        if ($in == "localScore") {
                            $puntuacionLocal = mysqli_real_escape_string($con,$da);
                        }
                        if ($in == "visitorScore") {
                            $puntuacionVisitante = mysqli_real_escape_string($con,$da);
                        }
                    }
                }
            }

// modifica el dia para obtener dia i hora
            $dia = substr($HoraDia, 0, 10);
            $hora = substr($HoraDia, 11);

// coge informacion del club
            foreach ($json_dataClub as $inf => $dat) {
                if (is_object($dat)) {
                    foreach ($dat as $in => $da) {
                        if ($in == "categories") {
                            foreach ($da as $infor => $datas) {
                                foreach ($datas as $inform => $date) {
                                    if ($inform == "teams") {
                                        foreach ($date as $i => $d) {
                                            if ($i == $idEquipoLocal || $i == $idEquipoVisitante) {
                                                foreach ($d as $v => $a) {
                                                    if ($v == "idCategoriesRegistred") {
                                                        $idgrupo = mysqli_real_escape_string($con,$a);
                                                    }
                                                    if($v == "idSignedTeam"){
                                                        $id_equipo = mysqli_real_escape_string($con,$a);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

// mira si existe el partido en la tabla
// si existe lo actualiza
// si no existe lo inserta
            $query="SELECT * FROM partidos WHERE id_partido='$idPartido'";
            if($result=mysqli_query($con, $query)){
                if ($result->num_rows > 0) {
                    $row=mysqli_fetch_array($result);
                    $queryupdatepartido = "UPDATE partidos SET `idEquipo`=(SELECT id FROM equipo WHERE id_fcbq='$id_equipo'),`id_equipo_local`='$idEquipoLocal',`nombre_equipo_local`='$nombreEquipoLocal',`id_equipo_visitante`='$idEquipoVisitante',`nombre_equipo_visitante`='$nombreEquipoVisitante',`id_grupo`=$idgrupo,`fecha_partido`='$dia',`hora_partido`='$hora',`puntos_local`='$puntuacionLocal',`puntos_visitante`='$puntuacionVisitante',`borrado`=0 WHERE id_partido='$idPartido'";
                    if (!mysqli_query($con, $queryupdatepartido)) {
                       $log[] = "(update) ".mysqli_error($con);
                    }
                }else{
                    $queryinsertpartido="INSERT INTO partidos(`id_partido`,`idEquipo`,`id_equipo_local`,`nombre_equipo_local`,`id_equipo_visitante`,`nombre_equipo_visitante`,`id_grupo`,`fecha_partido`,`hora_partido`,`puntos_local`,`puntos_visitante`,`borrado`) VALUES('$idPartido',(SELECT id FROM equipo WHERE id_fcbq='$id_equipo'),'$idEquipoLocal','$nombreEquipoLocal','$idEquipoVisitante','$nombreEquipoVisitante','$idgrupo','$dia','$hora','$puntuacionLocal','$puntuacionVisitante',0)";
                    if (!mysqli_query($con, $queryinsertpartido)) {
                        $log[] = "(insert) ".mysqli_error($con);
                    }
                }
            }
        }
    }
}

//enviar mail con errores
$info="";
if(sizeof($log)!=0) {
    for($i=0;$i<sizeof($log);$i++){if($info!=""){$info=$info." ;   ".$i." - ".$log[$i];}else{$info=$i." - ".$log[$i];}}
    $mail = getMailObject();
    $mail->addAddress("informatica@recreativoslloret.com", "informatica");
    $mail->Subject = '(Básquet Lloret) error carga tabla partido';
    $mail->Body = $info;
    try {
        $mail->send();
    } catch (Exception $ex) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        error_log($mail->ErrorInfo);
    }
}

?>
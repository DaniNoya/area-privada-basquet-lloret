<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");

require_once 'dbConnection.php';
//twitter
require_once('./vendor/twitteroauth/autoload.php');
//facebook
require_once(__DIR__.'/vendor/Facebook/autoload.php');

// define the classes
//facebook
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
//twitter
use Abraham\TwitterOAuth\TwitterOAuth;


$con = returnConection();
$response = new Result();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':

        $params = json_decode(file_get_contents("php://input"));
        $noticia = $params->noticia;
        $tipo = $noticia->tipo;
        $imagenPortada = $noticia->imagenPortada;
        $media = $noticia->media;
        $titol = $noticia->titol;
        $texto = mysqli_real_escape_string($con,$noticia->text);

        if ($tipo == 'cronica') {
            $equipo = $noticia->equipo;
            $sql = sprintf("INSERT INTO noticias VALUES (NULL,'%s',%d,'%s','%s','%s',current_timestamp(),0,NULL,NULL,NULL)", $tipo, $equipo, $imagenPortada, $titol, $texto);
        } else {
            $sql = sprintf("INSERT INTO noticias VALUES (NULL,'%s',NULL,'%s','%s','%s',current_timestamp(),0,NULL,NULL,NULL)", $tipo, $imagenPortada, $titol, $texto);
        }
        if (!mysqli_query($con, $sql)){

            return http_response_code(400);

        } else {
            $id_bbdd = mysqli_insert_id($con);
            foreach ($media as $m){
                $sqlMedia = sprintf("INSERT INTO media VALUES (NULL,'%s','%s','%s','%s')", $m->tipoMedia, $m->mediaURL, $m->filetype, $m->filename);
                if (mysqli_query($con, $sqlMedia)){
                    $sql2 = sprintf("INSERT INTO noticias_media VALUES (%d,%d)", $id_bbdd, mysqli_insert_id($con));
                    if (!mysqli_query($con, $sql2)){
                        $response->resultat = "ERROR";
                        $response->causa .= "Error al asignar la imagen $m->filename a la noticia\r\n";
                    }

                } else{
                    $response->resultat = "ERROR";
                    $response->causa .= "Error al guardar la imagen $m->filename\r\n";
                }
            }

            if ((isset($params->facebook)) && ($params->facebook == true)) {
                $error = publishToFacebook($noticia, $id_bbdd);
                if ($error != "OK"){
                    $response->resultat = "ERROR";
                    $response->causa = $error;
                }
            }

            if ((isset($params->instagram)) && ($params->instagram == true)) {

            }

            if ((isset($params->twitter)) && ($params->twitter == true)) {
                $error = publishToTwitter($noticia, $id_bbdd);
                if ($error != "OK"){
                    $response->resultat = "ERROR";
                    $response->causa = $error;
                }
            }
        }
        break;

    case 'PUT':

        $params = json_decode(file_get_contents("php://input"));
        $noticia = $params->noticia;
        $tipo = $noticia->tipo;
        $imagenPortada = $noticia->imagenPortada;
        $titol = $noticia->titol;
        $texto = mysqli_real_escape_string($con,$noticia->text);


        if (isset($params->delete)){

            $errorFacebook = "OK";
            $errorInstagram = "OK";
            $errorTwitter = "OK";

            if ($noticia->facebook != ""){
                $errorTwitter = deleteFromFacebook($noticia);
                if ($errorTwitter != "OK"){
                    $response->resultat = "ERROR";
                    $response->causa = $errorTwitter;
                }
            }

            if ($noticia->instagram != ""){
                //deleteFromInstagram($noticia);
            }

            if ($noticia->twitter != ""){
                $errorTwitter = deleteFromTwitter($noticia);
                if ($errorTwitter != "OK"){
                    $response->resultat = "ERROR";
                    $response->causa = $errorTwitter;
                }
            }

            if ($errorFacebook == "OK" && $errorInstagram == "OK" && $errorTwitter == "OK"){
                $sql = "UPDATE noticias SET borrado = 1 WHERE id = $noticia->id";
                if (!mysqli_query($con, $sql)){
                    return http_response_code(400);
                }
            }
        } else if (isset($params->enableDisable)){

            switch ($params->xarxa) {

                case 'facebook':
                    if ($noticia->facebook != ""){
                        updateToTwitter($noticia);
                        //deleteFromFacebook($noticia);
                    //} else{
                        //enableToFacebook($noticia);
                    }
                    break;

                case 'instagram':
                    if ($noticia->instagram != ""){
                        //deleteFromInstagram($noticia);
                    } else{
                        //enableToInstagram($noticia);
                    }
                    break;

                case 'twitter':
                    if ($noticia->twitter != ""){
                        updateToTwitter($noticia);
//                        if(deleteFromTwitter($noticia) != "OK"){
//                            $response->resultat = "ERROR";
//                            $response->causa = "Error al eliminar de Twitter";
//                        }
//                    } else{
//                        $error = publishToTwitter($noticia);
//                        if ($error != "OK"){
//                            $response->resultat = "ERROR";
//                            $response->causa = $error;
//                        }
                    }
                    break;

                default:
                    break;
            }

        } else {

            // En cas d'una modificaci� nom�s actualitzarem xarxes si anteriorment ja estava publicat

            $errorFacebook = "OK";
            $errorInstagram = "OK";
            $errorTwitter = "OK";
//          facebook
            if ($noticia->facebook != "") {
                $errorTwitter = updateToFacebook($noticia);
            }
//          instagram

//          twitter
            if ($noticia->twitter != ""){
                $errorTwitter = updateToTwitter($noticia);
//                $errorTwitter = deleteFromTwitter($noticia);
//                if ($errorTwitter != "OK"){
//                    $response->resultat = "ERROR";
//                    $response->causa = $errorTwitter;
//                } else {
//                    $errorTwitter = publishToTwitter($noticia);
//                    if ($errorTwitter != "OK"){
//                        $response->resultat = "ERROR";
//                        $response->causa = $errorTwitter;
//                    }
//                }
            }

            if ($errorFacebook == "OK" && $errorInstagram == "OK" && $errorTwitter == "OK"){
                $sql = "UPDATE noticias SET imagenPortada = '$imagenPortada', titol = '$titol', text = '$texto' WHERE id = $noticia->id";
                if (!mysqli_query($con, $sql)){
                    $response->resultat = "ERROR";
                    $response->causa = "Error al modificar la not�cia\r\n";
                }
                $sqlDeleteMedia = sprintf("DELETE FROM media WHERE id IN (SELECT idMedia from noticias_media WHERE idNoticia = %d)", $noticia->id);
                if (!mysqli_query($con, $sqlDeleteMedia)){
                    $response->resultat = "ERROR";
                    $response->causa = "Error al eliminar las im�genes anteriormente seleccionadas\r\n";
                }

                foreach ($noticia->media as $m){
                    $sqlMedia = sprintf("INSERT INTO media VALUES (NULL,'%s','%s','%s','%s')", $m->tipoMedia, $m->mediaURL, $m->filetype, $m->filename);
                    if (mysqli_query($con, $sqlMedia)){
                        $sql2 = sprintf("INSERT INTO noticias_media VALUES (%d,%d)", $noticia->id, mysqli_insert_id($con));
                        if (!mysqli_query($con, $sql2)){
                            $response->resultat = "ERROR";
                            $response->causa .= "Error al asignar la imagen $m->filename a la noticia\r\n";
                        }
                    } else{
                        $response->resultat = "ERROR";
                        $response->causa .= "Error al guardar la imagen $m->filename\r\n";
                    }
                }
            }
        }
        break;

    case 'GET':

        $json = json_encode($_GET);

        $params = json_decode($json);
        $response->noticias = array();

        if (isset($params->tipo)) {
            if ($params->tipo == "noticia"){
                $sql = "SELECT * FROM noticias WHERE borrado = 0 AND tipo = '$params->tipo' ORDER BY fecha DESC";
            } else if ($params->tipo == "cronica") {
                $sql = "SELECT * FROM noticias WHERE borrado = 0 AND equipo IN (SELECT id FROM equipo WHERE id_temporada = (SELECT id FROM temporada ORDER BY id DESC LIMIT 1)) ORDER BY fecha DESC";
            }
        }

        else if (isset($params->id)){
            $sql = "SELECT n.* FROM noticias n LEFT JOIN noticias n2 ON (n.equipo = n2.equipo AND n.fecha < n2.fecha) WHERE n.borrado = 0 AND n2.fecha IS NULL AND n.equipo IN (SELECT id FROM equipo WHERE id_temporada = $params->id) ORDER BY n.fecha DESC";
        } else{
            $sql = "SELECT * FROM noticias WHERE borrado = 0 ORDER BY fecha DESC";
        }

        if ($result = mysqli_query($con, $sql)){
            while($noticiasData = mysqli_fetch_array($result,MYSQLI_ASSOC)){

                $sqlMedia = sprintf("SELECT m.* FROM media m INNER JOIN noticias_media nm on m.id = nm.idMedia WHERE nm.idNoticia = %d",$noticiasData['id']);
                $noticiasData['media'] = array();

                if ($resultMedia = mysqli_query($con, $sqlMedia)) {
                    if (mysqli_num_rows($resultMedia) > 0){
                        while ($mediaData = mysqli_fetch_array($resultMedia, MYSQLI_ASSOC)) {

                            $med = array();
                            $med['tipoMedia'] = $mediaData['tipoMedia'];
                            $med['mediaURL'] = $mediaData['mediaURL'];
                            $med['filetype'] = $mediaData['filetype'];
                            $med['filename'] = $mediaData['filename'];
                            $noticiasData['media'][] = $med;

                        }
                    }
                }
                $response->noticias[] = $noticiasData;
            }
        }
        break;

    default:
        break;
}



// functions



//  twitter
function twitterConnection() {
    // Twitter OAuth keys
    $api_key="suW7gAUfRPTK6bIXHES0azHPM";
    $api_secret="R2MhzfQrseDQTFsQbLay6y3RYtiaQvJSScNbKLC261tFowpqCT";

    $con = returnConection();

    $sql = "SELECT * FROM club";
    if($result=mysqli_query($con, $sql)) {
        $row = mysqli_fetch_array($result);
        $access_token = $row['twitter_oauth_token'];
        $access_token_secret =  $row['twitter_oauth_token_secret'];
        return (new TwitterOAuth($api_key,$api_secret,$access_token,$access_token_secret));
    }

   // $access_token="1204429277564981255-zY61KwQk6JCee81FKdDBMhbW4QUBKG";
   // $access_token_secret="Mg50W8ngNnd8DD9vOwyUPBixsvkSYGPlEGqJLLM896uSq";

}

function publishToTwitter($noticia, $id_bbdd=NULL) {
    $titulo=$noticia->titol;
    $texto=$noticia->text;
    $status = $titulo.$texto;
    if (strlen($status) >= 130) {
        $texto = substr($texto, 0, 130-strlen($titulo));
    }
    // init API
    $connection = twitterConnection();
    $content = $connection->get("account/verify_credentials");

    if (is_array($noticia->media)) {
        $mediaIDS = array();
        foreach ($noticia->media as $key => $media_path) {
            // Upload media to twitter API and get media ID back
            $mediaOBJ = $connection->upload('media/upload', ['media' => $media_path]);
            // push uploaded media ID to array
            array_push($mediaIDS, $mediaOBJ->media_id_string);
        }
        // create comma delimited list of media ID:s
        $mediaIDstr = implode(',', $mediaIDS);
    }
    // API params
    $arrayCfg['status'] = str_replace("&nbsp;", "", strip_tags(str_replace("<div>","\r\n",$titulo . "\r\n" . $texto)));
    $arrayCfg['media_ids'] = $mediaIDstr;
    // Make POST request to Twitter API
    $statuses = $connection->post("statuses/update", $arrayCfg);

    //base de dadas
    if ($statuses->id_str != ""){

        $con = returnConection();

        if ($id_bbdd != NULL){
            $sql = "UPDATE noticias SET twitter = '$statuses->id_str' WHERE id = $id_bbdd";
        } else{
            $sql = "UPDATE noticias SET twitter = '$statuses->id_str' WHERE id = $noticia->id";
        }
        if (!mysqli_query($con,$sql)){
            return "No se ha podido guardar el ID de Twitter en Base de datos";
        }
        return "OK";
    } else{
        return "No se ha podido cargar en Twitter";
    }
}

function deleteFromTwitter($noticia) {
    // init API
    $connection = twitterConnection();
    $content = $connection->get("account/verify_credentials");
    // API params
    $arrayCfg['id'] = (int)$noticia->twitter;
    // Make POST request to Twitter API
    $statuses = $connection->post("statuses/destroy", $arrayCfg);

    //base de dadas
    if ($statuses){
        $con = returnConection();
        $sql = "UPDATE noticias SET twitter = NULL WHERE id = $noticia->id";
        if (!mysqli_query($con,$sql)){
            return "No se ha podido eliminar el ID de Twitter en Base de datos";
        }
        return "OK";
    } else {
        return "No se ha podido eliminar de Twitter";
    }
}

function updateToTwitter($noticia) {
    $delete=deleteFromTwitter($noticia);
    if ($delete != "OK"){
        return  "ERROR";
    }else{
        $publish=publishToTwitter($noticia);
        if ($publish != "OK"){
            return "ERROR";
        }else{
            return "OK";
        }
    }
}

//facebook
function facebookConnection() {
    $appId = '313974587217168'; //Facebook App ID
    $appSecret = '9163725a730443f07bdc76fb6cfaf42b'; //Facebook App Secret

    $fb = new Facebook([
        'app_id' => $appId,
        'app_secret' => $appSecret,
        'default_graph_version' => 'v2.12',
    ]);

    return $fb;
}
function publishToFacebook($noticia, $id_bbdd=NULL) {

    $fb=facebookConnection();

    $con = returnConection();
    $sql = "SELECT * FROM club";
    if($result=mysqli_query($con, $sql)) {
        $row = mysqli_fetch_array($result);
        $accessToken=$row['facebook_token'];
    }

    //$accessToken = "EAAEdjue9VRABAG8NhVjEssy8AUG03OiO7xQcVm3MA0A3ryyxp6N3N2IBGDm9DjVCZAz6lRFVyN6I3Io4IyxbCLUbxOzWGqMp05bAMRJW2mHOmQhwf1ZBZAd43MtNZA6ZCtGErmQAouD4LUqjZB3Vm3iZBhvGI85qTVtZC8vsVs3kMnByJ8SmFL8e2vXTZADu2Y0nTfTKcPNQIoMqrC23AvlvV";

    //FB post content
    $titulo=$noticia->titol;
    $texto=$noticia->text;
    $message = str_replace("&nbsp;", "", strip_tags(str_replace("<div>","\r\n",$titulo . "\r\n" . $texto)));
    $picture = '';

    $attachment = [
        'message' => $message,
    ];

    $pageid=getpageid();

    try {
        $response = $fb->post("/".$pageid."/feed",$attachment,$accessToken
        );
    } catch(FacebookResponseException $e) {
        return 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        return 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    $graphNode = $response->getGraphNode();

    //base de dadas
    if ($graphNode['id'] != ""){
        $id=$graphNode['id'];
        $con = returnConection();

        if ($id_bbdd != NULL){
            $sql = "UPDATE noticias SET facebook = '$id' WHERE id = $id_bbdd";
        } else{
            $sql = "UPDATE noticias SET facebook = '$id' WHERE id = $noticia->id";
        }
        if (!mysqli_query($con,$sql)){
            return "No se ha podido guardar el ID de Facebook en Base de datos";
        }
        return "OK";
    } else{
        return "No se ha podido cargar en Facebook";
    }
}
function deleteFromFacebook($noticia) {
    $fb=facebookConnection();

    $con = returnConection();
    $sql = "SELECT * FROM club";
    if($result=mysqli_query($con, $sql)) {
        $row = mysqli_fetch_array($result);
        $accessToken=$row['facebook_token'];
    }
    //$accessToken = "EAAEdjue9VRABAG8NhVjEssy8AUG03OiO7xQcVm3MA0A3ryyxp6N3N2IBGDm9DjVCZAz6lRFVyN6I3Io4IyxbCLUbxOzWGqMp05bAMRJW2mHOmQhwf1ZBZAd43MtNZA6ZCtGErmQAouD4LUqjZB3Vm3iZBhvGI85qTVtZC8vsVs3kMnByJ8SmFL8e2vXTZADu2Y0nTfTKcPNQIoMqrC23AvlvV";
    $statuses = $fb->delete('/'.$noticia->facebook, array(), $accessToken);

    //data base
    if ($statuses){
        $con = returnConection();
        $sql = "UPDATE noticias SET facebook = NULL WHERE id = $noticia->id";
        if (!mysqli_query($con,$sql)){
            return "No se ha podido eliminar el ID de Facebook en Base de datos";
        }
        return "OK";
    } else {
        return "No se ha podido eliminar de Facebook";
    }
}
function updateToFacebook($noticia) {
    $delete=deleteFromFacebook($noticia);
    if ($delete != "OK"){
        return  "ERROR";
    }else{
        $publish=publishToFacebook($noticia);
        if ($publish != "OK"){
            return "ERROR";
        }else{
            return "OK";
        }
    }
}

//  instagram
function instagramConnection() {

}
function publishToInstagram($noticia, $id_bbdd=NULL) {

}
function deleteFromInstagram($noticia) {

}
function updateToInstagram($noticia) {

}



header('Content-Type: application/json');
echo json_encode($response);
?>
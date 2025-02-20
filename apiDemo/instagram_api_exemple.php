<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");

require_once 'dbConnection.php';
require_once 'defines_networks1.php';

$conn = returnConection();
$response = new Result();

publishToInstagram("titol1","text1","img",$conn);
//deleteFromInstagram("17907921638288766",$conn);

function instagramObtainingAccessToken($conn) {
    $queryClub = "SELECT instagram_token FROM club;";
    if ($resultClub = mysqli_query($conn, $queryClub)) {
        $clubData = $resultClub->fetch_assoc();

        $accessToken = $clubData['instagram_token'];
    }

    return $accessToken;
}

//function instagramConnection() {
//    // Twitter OAuth keys
//    $apikey='4f34c449ffd94fe9960d61d761d5caf7';
//    $apisecret='a7a585b771044a5dae6eb10edba4dfda';
//    $apicallback='';
//
//    return ( new Instagram(['apiKey' => $apikey, 'apiSecret' => $apisecret, 'apiCallback' => $apicallback]) );
//}
function makeApiCall( $endpoint, $type, $params ) {
    $ch = curl_init();

    if ( 'POST' == $type ) {
        curl_setopt( $ch, CURLOPT_URL, $endpoint );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
        curl_setopt( $ch, CURLOPT_POST, 1 );
    } elseif ( 'GET' == $type ) {
        curl_setopt( $ch, CURLOPT_URL, $endpoint . '?' . http_build_query( $params ) );
    }elseif ( 'DELETE' == $type ) {
        curl_setopt($ch, CURLOPT_URL, $endpoint . '?' . http_build_query( $params ) );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    $response = curl_exec( $ch );
    curl_close( $ch );

    return json_decode( $response, true );
}
function publishToInstagram($titol, $text, $img_url, $conn) {
    $accesstoken = instagramObtainingAccessToken($conn);

    // endpoint formats
//    $imagesEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/media?image_url={image-url}&caption={caption}&access_token={access-token}';
//    $videoEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/media?video_url={video-url}&media_type&caption={caption}&access_token={access-token}';
//    $publishMediaEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/media_publish?creation_id={creation-id}&access_token={access-token}';
//    $userApiLimitEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/content_publishing_limit';
//    $mediaObejctStatusEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-container-id}?fields=status_code';

    $message=$titol.'  ,  '.$text;


    //  IMAGE

    // create media object for image
    $imageMediaObjectEndpoint = 'https://graph.facebook.com/v5.0/' . INSTAGRAM_ACCOUNT_ID . '/media';

    $imageMediaObjectEndpointParams = array( // POST variables
        'image_url' => 'https://i1.wp.com/developerfriendly.dev/wp-content/uploads/2020/02/logo1080x1080.png'
        ,'caption' => $message  //' This image was posted through the Instagram Graph API with a script I wrote!.#instagram #graphapi #instagramgraphapi #code #coding #programming #php #api #webdeveloper #codinglife #developer #coder #tech #developerlife #webdev #instgramgraphapi'
        ,'access_token' => $accesstoken
    );
    $imageMediaObjectResponseArray = makeApiCall( $imageMediaObjectEndpoint, 'POST', $imageMediaObjectEndpointParams );

    // set status to in progress
    $imageMediaObjectStatusCode = 'IN_PROGRESS';

    $imageMediaObjectStatusEndpoint = 'https://graph.facebook.com/v5.0/' . $imageMediaObjectResponseArray['id'];
    $imageMediaObjectStatusEndpointParams = array( // endpoint params
        'fields' => 'status_code',
        'access_token' => $accesstoken
    );

  //  while( $imageMediaObjectStatusCode != 'FINISHED' ) { // keep checking media object until it is ready for publishing
        $imageMediaObjectResponseArray = makeApiCall( $imageMediaObjectStatusEndpoint, 'GET', $imageMediaObjectStatusEndpointParams );
        $imageMediaObjectStatusCode = $imageMediaObjectResponseArray['status_code'];
  //      sleep( 5 );
  //  }

    // publish image
    $imageMediaObjectId = $imageMediaObjectResponseArray['id'];
    $publishImageEndpoint = 'https://graph.facebook.com/v5.0/' . INSTAGRAM_ACCOUNT_ID . '/media_publish';
    $publishEndpointParams = array(
        'creation_id' => $imageMediaObjectId,
        'access_token' => $accesstoken
    );
    $publishImageResponseArray = makeApiCall( $publishImageEndpoint, 'POST', $publishEndpointParams );

print_r("00--".$imageMediaObjectResponseArray['id']);
print_r("11--".$imageMediaObjectId);
print_r($publishImageResponseArray);
}

function deleteFromInstagram($noticia, $conn) {
    $accesstoken = instagramObtainingAccessToken($conn);

    // init API
    //$connection = instagramConnection();
    //$deleteEndPointFormat='https://graph.facebook.com/v5.0/media/{media-id}?access_token={MYACCESSTOKEN}';
    $deleteEndPoint='https://graph.facebook.com/v5.0/'. INSTAGRAM_ACCOUNT_ID . '/media/'.$noticia;
    $deleteEndPointParams=array('access_token' => $accesstoken);

    $deleteImageResponse=makeApiCall($deleteEndPoint,'DELETE',$deleteEndPointParams);

//    $Curl_Session = curl_init('https://graph.facebook.com/v5.0/'.$noticia);
//    curl_setopt ($Curl_Session, CURLOPT_POST, 1);
//    curl_setopt ($Curl_Session, CURLOPT_POSTFIELDS, 'method=DELETE&access_token='.$accesstoken);
//    curl_setopt ($Curl_Session, CURLOPT_FOLLOWLOCATION, 1);
//    curl_setopt($Curl_Session, CURLOPT_RETURNTRANSFER, 1);
//
//    echo $delete=curl_exec ($Curl_Session);
//    curl_close ($Curl_Session);

print_r($deleteImageResponse);
}

function updateToInstagram($noticia, $mediaArray, $conn) {
    deleteFromInstagram($noticia, $conn);

    publishToInstagram($noticia->titol,$noticia->text,$mediaArray, $conn);
}

header('Content-Type: application/json');
echo json_encode($response);
?>
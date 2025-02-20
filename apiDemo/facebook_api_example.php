<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");

require_once 'dbConnection.php';

$con = returnConection();
//$result = new Result();


//if(!session_id()){
//  session_start();
//}

require_once(__DIR__.'/vendor/Facebook/autoload.php');

// define the classes
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

function facebookconn(){

     // Configuration and setup Facebook SDK

    $appId = '265239302409966'; //Facebook App ID
    $appSecret = 'f2eb8dcd3f39254feab2d8ac09c9b591'; //Facebook App Secret
    $redirectURL = 'http://localhost/post_to_facebook_from_website/'; //Callback URL
    $fbPermissions = array('publish_actions'); //Facebook permission

    $fb = new Facebook([
        'app_id' => $appId,
        'app_secret' => $appSecret,
        'default_graph_version' => 'v2.12',
    ]);
    return $fb;
}
//$accessToken = "EAAEdjue9VRABAFCy5W7xZBB4zmKgs01gSoviARQZA4kqC4WMntPqe59keBmWzpMzP2SOE4bqjQPkMGXSBFnmHZAhsNFPY98va3mjjyGjRor91EURMavQfdHIwhBeZACqTR21K7kY732QuZBBP30qJfcwZAlQPZBp0mFfQeSmDz2H9YoDVmh7fEMJdcxYZAbhDL7ELzhKn701FBN5SM30wkyC";

//getUserInfo();
//publishToFacebook();
//getpageid();
//facebook_delete("107787275060544_128567426315862");

function getpageid() {
    $endpointFormat = 'https://graph.facebook.com/v5.0/' . 'me/accounts?access_token={access-token}';
    $pagesEndpoint  = 'https://graph.facebook.com/v5.0/' . 'me/accounts';

    $accessToken = "EAADxO9ZAmju4BAGsnIXf1iNrpWqlZCZA1zODNZCoeaZCcSHcHvTZBiln7TuZBr7jQQyQGRJRDk5UQ07LxazWexXiG2Vxyx0QUSNiI87zHxSAHARcZBqosPDgQHqWT0QOUWdlDLj7fMWsF2fk0oCwfAyPNwLYZCkE2MwJvU0ZACDpfoMLwsxijzuHJMZCZBYwyiS3zWeiOX5N9uwo8YVkfICSfIdq";

    // endpoint params
    $pagesParams = array(
        'access_token' => $accessToken
    );

    // add params to endpoint
    $pagesEndpoint .= '?' . http_build_query( $pagesParams );

    // setup curl
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $pagesEndpoint );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    // make call and get response
    $response = curl_exec( $ch );
    curl_close( $ch );
    $responseArray = json_decode( $response, true );
    unset( $responseArray['data'][0]['access_token'] );

    print_r($responseArray['data'][0]['id']);
    return $responseArray['data'][0]['id'];
}
function getUserInfo() {
    $result = new Result();
    $fb=facebookconn();
    $accessToken = "EAADxO9ZAmju4BAGsnIXf1iNrpWqlZCZA1zODNZCoeaZCcSHcHvTZBiln7TuZBr7jQQyQGRJRDk5UQ07LxazWexXiG2Vxyx0QUSNiI87zHxSAHARcZBqosPDgQHqWT0QOUWdlDLj7fMWsF2fk0oCwfAyPNwLYZCkE2MwJvU0ZACDpfoMLwsxijzuHJMZCZBYwyiS3zWeiOX5N9uwo8YVkfICSfIdq";
    try {
        $response = $fb->get('/me', $accessToken);
    } catch(FacebookResponseException $e) {
        $result->causa = 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        $result->causa = 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    $me = $response->getGraphUser();
    $result->resultat = 'Logged in as ' . $me->getName();

    header('Content-Type: application/json');
    echo json_encode($result);
}

function publishToFacebook() {
    $result = new Result();

    $fb=facebookconn();
    $accessToken = "EAADxO9ZAmju4BAGsnIXf1iNrpWqlZCZA1zODNZCoeaZCcSHcHvTZBiln7TuZBr7jQQyQGRJRDk5UQ07LxazWexXiG2Vxyx0QUSNiI87zHxSAHARcZBqosPDgQHqWT0QOUWdlDLj7fMWsF2fk0oCwfAyPNwLYZCkE2MwJvU0ZACDpfoMLwsxijzuHJMZCZBYwyiS3zWeiOX5N9uwo8YVkfICSfIdq";

    //FB post content
    $message = 'Test message from reclloret.com website3';
    //$link = 'http://www.reclloret.com/';
    $description = 'Rec Lloret is a programming blog.';
    $picture = '';

    $attachment = [
        'message' => $message,
    ];

    $pageid="107787275060544";

    try {
        $response = $fb->post("/".$pageid."/feed",$attachment, $accessToken);

        //$response = $fb->post("/".$pageid."/feed", array('message'=>'message',), $accessToken);
    } catch(FacebookResponseException $e) {
        $result->causa = 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        $result->causa = 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    $graphNode = $response->getGraphNode();

    $result->resultat = "ok";

    header('Content-Type: application/json');
    echo json_encode($result);
}

function facebook_delete($postIDtoDelete){
    $fb=facebookconn();
    $accessToken = "EAADxO9ZAmju4BAGsnIXf1iNrpWqlZCZA1zODNZCoeaZCcSHcHvTZBiln7TuZBr7jQQyQGRJRDk5UQ07LxazWexXiG2Vxyx0QUSNiI87zHxSAHARcZBqosPDgQHqWT0QOUWdlDLj7fMWsF2fk0oCwfAyPNwLYZCkE2MwJvU0ZACDpfoMLwsxijzuHJMZCZBYwyiS3zWeiOX5N9uwo8YVkfICSfIdq";

    $fb->delete('/'.$postIDtoDelete, array(), $accessToken);
}

function facebook_update($noticia,$picture){
    facebook_delete($noticia);

    facebook_post($noticia,$picture);
}


publishToFacebook();

//header('Content-Type: application/json');
//echo json_encode($result);
?>

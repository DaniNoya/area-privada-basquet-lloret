<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");

require_once 'dbConnection.php';
include 'defines_networks.php';

$conn = returnConection();
$result = new Result();

/* BDD News */
/*$queryMedia = "SELECT * FROM media;";
$mediaIMGs = array();

if ($resultMedia = mysqli_query($conn, $queryMedia)) {
  if (mysqli_num_rows($resultMedia) > 0) {
    while ($mediaData = mysqli_fetch_array($resultMedia, MYSQLI_ASSOC)) {
      
      $med = array();
      $med['mediaURL'] = $mediaData['mediaURL'];
      $med['filename'] = $mediaData['filename'];
      $mediaIMGs[] = $med;

      //$mediaIMGs[] = $mediaData['mediaURL'];
    }
  }


  foreach ($mediaIMGs as $mediaInfo) {
    $media_path = $mediaInfo['mediaURL'];
    $imgName = $mediaInfo['filename'];

    print_r($imgName);

    // Eliminamos data:image/jpeg; y base64, de la cadena que tenemos
    list(, $media_path) = explode(';', $media_path);
    list(, $media_path) = explode(',', $media_path);
    
    // Decodificamos $media_path codificada en base64.
    $media_path = base64_decode($media_path);
    // Save
    file_put_contents('../imagesTemp/'.$imgName, $media_path);
  }
}*/

$imagesArray = [];
$files = glob('../tempImageSN/*.{jpg,png}', GLOB_BRACE);
foreach($files as $file){
  if(is_file($file)){
    print_r($file);
    array_push($imagesArray, $file);
  }
}

header('Content-Type: application/json');
echo json_encode($result);
?>

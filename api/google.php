<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/dbConnection.php';
include_once __DIR__ . '/constants.php';
$con = returnConection();

$client = new Google_Client(array("client_id"=>GOOGLE_OAUTH2_CLIENT_ID,"client_secret"=>GOOGLE_OAUTH2_CLIENT_SECRET, "developer_key"=>GOOGLE_API_KEY));
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);
$client->setAccessType("offline");
$client->setPrompt("consent");

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

$sql = "SELECT google_token FROM club";
if($result = mysqli_query($con,$sql)){
  $data = $result->fetch_assoc();
  $client->setAccessToken($data['google_token']);
  if ($client->isAccessTokenExpired()) {
    echo "Expired\r\n";
    $token = $client->refreshToken(json_decode($data['google_token'])->refresh_token);
    if ($client->isAccessTokenExpired()) {
      echo "Still expired\r\n";
    } else{
      $sql = "UPDATE club SET google_token = '" . json_encode($token) . "'";
      if(!mysqli_query($con,$sql)){
        error_log(mysqli_error($con));
      }
    }
  }
  if ($client->getAccessToken()) {
    try {
      // Call the channels.list method to retrieve information about the
      // currently authenticated user's channel.
      $channelsResponse = $youtube->channels->listChannels('contentDetails', array(
        'mine' => 'true',
      ));

      $htmlBody = '';
      foreach ($channelsResponse['items'] as $channel) {
        print_r($channel);
        // Extract the unique playlist ID that identifies the list of videos
        // uploaded to the channel, and then call the playlistItems.list method
        // to retrieve that list.
        $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

        $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
          'playlistId' => $uploadsListId,
          'maxResults' => 50
        ));

        $htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
        foreach ($playlistItemsResponse['items'] as $playlistItem) {
          $htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
            $playlistItem['snippet']['resourceId']['videoId']);
        }
        $htmlBody .= '</ul>';
      }
      echo $htmlBody;
    } catch (Google_Service_Exception $e) {
      $htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
      $htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
    }
  } else {
    echo "algo";
  }
} else{
  error_log(mysqli_error($con));
}
/*

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try {
    // Call the channels.list method to retrieve information about the
    // currently authenticated user's channel.
    $channelsResponse = $youtube->channels->listChannels('contentDetails', array(
      'mine' => 'true',
    ));

    $htmlBody = '';
    foreach ($channelsResponse['items'] as $channel) {
      // Extract the unique playlist ID that identifies the list of videos
      // uploaded to the channel, and then call the playlistItems.list method
      // to retrieve that list.
      $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

      $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
        'playlistId' => $uploadsListId,
        'maxResults' => 50
      ));

      $htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
      foreach ($playlistItemsResponse['items'] as $playlistItem) {
        $htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
          $playlistItem['snippet']['resourceId']['videoId']);
      }
      $htmlBody .= '</ul>';
    }
  } catch (Google_Service_Exception $e) {
    $htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  }

  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
} else {
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}*/
?>

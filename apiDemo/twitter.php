<?php
require './vendor/twitteroauth/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth("suW7gAUfRPTK6bIXHES0azHPM",
  "R2MhzfQrseDQTFsQbLay6y3RYtiaQvJSScNbKLC261tFowpqCT",
  "1204429277564981255-zY61KwQk6JCee81FKdDBMhbW4QUBKG",
  "Mg50W8ngNnd8DD9vOwyUPBixsvkSYGPlEGqJLLM896uSq");
$content = $connection->get("account/verify_credentials");

$statuses = $connection->get("statuses/home_timeline", ["screen_name" => "alex_esquiva", "count" => 25, "exclude_replies" => true]);

echo $statuses;

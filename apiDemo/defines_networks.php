<?php
session_start();

define("TEST_APP",false);
define("BASE_API_URL", (TEST_APP) ? "http://localhost/Basquet Lloret/api/" : __DIR__."/");

define("GOOGLE_OAUTH2_CLIENT_ID","113738072649-g1q3qqe69lgrekuk0970f9fchq0dadeo.apps.googleusercontent.com");
define("GOOGLE_OAUTH2_CLIENT_SECRET","44PqmPzTTJgfC3GJUwj3Ml0W");
define("GOOGLE_API_KEY","AIzaSyA9gz4WVAs4fMw-uNDaouR9uhg01c0CF9A");

define( 'TWITTER_API_KEY', 'suW7gAUfRPTK6bIXHES0azHPM' );
define( 'TWITTER_API_SECRET', 'R2MhzfQrseDQTFsQbLay6y3RYtiaQvJSScNbKLC261tFowpqCT' );

define( 'FACEBOOK_APP_ID', '459335299020751' );
define( 'FACEBOOK_APP_SECRET', '3254af72b3d8ee2dfce7db568301c968' );
define( 'FACEBOOK_PAGE_ID', '107787275060544' );
define( 'FACEBOOK_REDIRECT_URI', 'https://areaprivada.basquetlloret.com/apiDemo/news_test.php' );
define( 'ENDPOINT_BASE', 'https://graph.facebook.com/v5.0/' );

define( 'INSTAGRAM_ACCOUNT_ID', '17841425702634436' );
<?php

$url = 'http://sendgrid.com/';
$user = '[[SendGrid Username]]';
$pass = '[[SendGrid Password]]'; 

$openTokID = file_get_contents('temp.txt'); 

$message = "To view your message click http://thinkingserious.com/hollywood/thinglink/video/player.php?id=".$openTokID."";
 
$params = array(
    'api_user'  => $user,
    'api_key'   => $pass,
    'to'        => $_GET['email'],
    'subject'   => 'You just received a video message!',
    'html'      => $message,
    'text'      => $message,
    'from'      => '[[Your Email Address]]',
  );
 
 
$request =  $url.'api/mail.send.json';
 
// Generate curl request
$session = curl_init($request);
// Tell curl to use HTTP POST
curl_setopt ($session, CURLOPT_POST, true);
// Tell curl that this is the body of the POST
curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
// Tell curl not to return headers, but do return the response
curl_setopt($session, CURLOPT_HEADER, false);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
 
// obtain response
$response = curl_exec($session);
curl_close($session);    
    
?>
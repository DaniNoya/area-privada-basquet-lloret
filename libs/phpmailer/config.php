<?php
//Carga de librerias
require_once "PHPMailer.php";
require_once "SMTP.php";
require_once "Exception.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function getMailObject(){
	$mail = new PHPMailer(true);
	//$mail->SMTPDebug = 3;							//Enable verbose debug output
	$mail->isSMTP();									//Send using SMTP
	$mail->Host       = 'smtp.gmail.com';				//Set the SMTP server to send through
	$mail->SMTPAuth   = true;							//Enable SMTP authentication
	$mail->Username   = 'smtp.artjoc@gmail.com';		//SMTP username
	$mail->Password   = 'pvqbrceqjjhigrau';			//SMTP password
	$mail->SMTPSecure = "tls";						//Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
	$mail->Port       = 587;							//TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
	$mail->CharSet = 'UTF-8';

	$mail->setFrom('smtp.artjoc@gmail.com', 'Basquet Lloret Web');
	
	$mail->isHTML(true);	//Set email format to HTML
	
	return $mail;
}
?>
<?php
/*
 *  little test for the SMTP mailer
 */

include('email.php');
use Snipworks\Smtp\Email;

$port = 25;
$mail = new Email('somehost.net', $port);
//if ($port != 25) {
  $mail->setProtocol(Email::TLS);
//}
$mail->setLogin('contact@peer2product.com', 'somePassword');
$mail->addTo('agent725@peer2product.com', 'To User');
$mail->setFrom('contact@hybrix.io', 'From User');
$mail->setSubject('TEST ME!');
$mail->setHtmlMessage('<b>TEST</b>...');

if ($mail->send()){
    echo 'Success!';
} else {
    echo 'An error occurred.';
}

?>

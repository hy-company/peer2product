<?php

/*
 *   THIS IS WHAT PAY.NL GIVES US:
 *   http://p2pwebshop/data/gateways/pay_nl/return.php?orderId=538166497Xd0d2e3&orderStatusId=100&paymentSessionId=538166497
 * 
 *   WE SIMPLY RETURN THE ORDER ID AND THE STATUS
 */

if (isset($_SERVER['HTTPS']) &&
	($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
	isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
	$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
		$protocol = 'https://';
}
else {
	$protocol = 'http://';
}

header('Location: '. $protocol.$_SERVER['HTTP_HOST']. dirname(dirname(dirname(dirname($_SERVER['PHP_SELF'])))).'?checkout&x='.$_GET['orderId'].'&s='.$_GET['orderStatusId']);

?>

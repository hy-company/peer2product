<?php

// jCart v1.3
// http://conceptlogic.com/jcart/

// This file is called when any button on the checkout page (PayPal checkout, update, or empty) is clicked

// Include jcart before session start
include_once('jcart.php');

$config = $jcart->config;

// The update and empty buttons are displayed when javascript is disabled 
// Re-display the cart if the visitor has clicked either button
if ($_POST['jcartUpdateCart'] || $_POST['jcartEmpty']) {

	// Update the cart
	if ($_POST['jcartUpdateCart']) {
		if ($jcart->update_cart() !== true)	{
			$_SESSION['quantityError'] = true;
		}
	}

	// Empty the cart
	if ($_POST['jcartEmpty']) {
		$jcart->empty_cart();
	}

	// Redirect back to the checkout page
	$protocol = 'http://';
	if (!empty($_SERVER['HTTPS'])) {
		$protocol = 'https://';
	}

	header('Location: ' . $protocol . $_SERVER['HTTP_HOST'] . $config['checkoutPath']);
	exit;
}

?>

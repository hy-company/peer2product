<?php
/*
 * Settings file of the Pay.nl PPT module
 * 
 * @author      Kelvin Huizing
 * @copyright   2013 - Pay.nl
 * @version     1.0
 * @link        http://www.pay.nl
 */

#######################################
#
# Required settings: Please enter below
#
#######################################

// get gateway variables
$json = json_decode(file_get_contents($GATEWAY['directory'].'gateway.json'));
foreach($json as $key => $val) {
	$GATEWAY[$key] = $val;
}

/**
 * Your Pay.nl Account id
 */
$iAccountId = $GATEWAY['Account_ID'];

/**
 * Pay.nl program id
 */
$iProgramId = $GATEWAY['Program_ID'];;

/**
 * Pay.nl website id
 */
$iWebsiteId = 1;

/**
 * Pay.nl website location id
 */
$iWebsiteLocationId = 1;

/**
 * Your Pay.nl token
 * Your token can be generated at: admin.pay.nl/api_token
 */
$strToken = $GATEWAY['API_Token'];;

/**
 * Your emailaddres to receive notifications
 * Multiple emailaddress must be seperated by a comma
 * e.g: 'info@pay.nl, error@pay.nl'
 */
$strEmailAddress = $GATEWAY['Notification_E-mail'];

/**
 * The absolute url of your exchange file
 * this file will be called by Pay.nl 
 * e.g: http://www.domain/exchange.php
 */
$strExchangeUrl = $GATEWAY['host'].$GATEWAY['directory'].'exchange.php';

/**
 * The absolute url where you want your visitors
 * to be send to after the payment
 * e.g: http://www.domain/return.php
 */
$strReturnUrl = $GATEWAY['host'].$GATEWAY['directory'].'return.php';

#######################################
#
# Optional settings: no need to change
#
#######################################

/**
 * This only effects payments when your site is in live mode
 * Possible values: true (to enable) false (to disable)
 */
$boolTestMode = false;

/**
 * Show the debug info for development purpose
 * Possible values: true (to enable) false (to disable)
 */
$boolDebugMode = false;

/**
 *  Receive a notification by mail after a payment
 *  Possible values: true (to enable) false (to disable)
 *  It's recommended to set this value to true
 */
$boolReveiveExchangeMail = true;

#######################################
#
# Do not edit below this line
#
#######################################

/**
 * Include trasnaction Class
 */
require_once('includes/classes/Transaction.php');

/**
 * Include exchange Class
 */
require_once('includes/classes/Exchange.php');

/**
 * set emailaddress to a proper array
 */
$arrEmailAddress = explode(',', trim($strEmailAddress));

/** eof **/

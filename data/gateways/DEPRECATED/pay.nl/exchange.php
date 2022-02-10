<?php
// include the settings file
require('settings.php');

/**
 * This exchange file will be invoked by Pay.nl in case of a payment or refund.
 * If we return true this exchange file will not be invoked again for the transaction concerned.
 * We only return false if a temp error occured and we want the exchange to be invoked again.
 */

try
{
    $paynl = new Exchange($_GET, $iAccountId, $strToken, $arrEmailAddress, $boolReveiveExchangeMail);
}
catch(Exception $exc)
{
    echo "TRUE|Error occured: ".$exc->getMessage();
}

/** eof **/
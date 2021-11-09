<?php

/*
 *
 *  GATEWAY FOR PAYING BY MOLLIE.NL
 *
 */

$array['payment_method']='mollie.nl';
$array['forwardurl'] = $SITE.'?checkout&x='.$array['ordernumber'].'&s=99';

//        if(isset($_POST['next']) && $_POST['next']=='Pay') {
function paymentgate($array,$shop) {
    global $GATEWAY,$SET,$STR;
    // get gateway variables
    require($GATEWAY['directory'].'settings.php');

    if (!isset($array['amount']) || !isset($array['ordernumber'])) {
      echo 'Missing amount or ordernumber!';
      die();
    }

    /*
     * Make sure to disable the display of errors in production code!
     */
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once __DIR__ . "lib/vendor/autoload.php";
    /* require_once __DIR__ . "/functions.php";*/

    /*
     * Initialize the Mollie API library with your API key.
     *
     * See: https://www.mollie.com/dashboard/developers/api-keys
     */
    $mollie = new \Mollie\Api\MollieApiClient();
    $mollie->setApiKey($GATEWAY['API_key']);
    $payment = $mollie->payments->create([
        "amount" => [
            "currency" => $SET['shopcurrency'],
            "value" => $array['amount']
        ],
        "description" => $array['ordernumber'],
        "redirectUrl" => ,
        "webhookUrl"  => $shop->tx($array)
    ]);
}

function paymentform($array,$shop) {
    global $GATEWAY,$SET,$STR;
    // get gateway variables
    require($GATEWAY['directory'].'settings.php');


    if (!isset($array['amount']) || !isset($array['ordernumber'])) {
      echo 'Missing amount or ordernumber!';
      die();
    }

    echo '<div style="width: 100%; margin-top: 48px; text-align: center;"><h4>'.$STR['Amount_to_pay'].': <span style="font-weight: bold;">'.$SET['shopcurrency'].' '.$shop->formatn($array['amount']).'</span></h4><br>'.
       '<span>'.$GATEWAY['description'].'</span><br><br>'.
       '<div style="display: inline-block; text-align: left; background: #FFF none repeat scroll 0% 0%; font-weight: bold; margin: 12px 0; padding: 12px; min-width: 320px; border: 3px dashed #777;"><table>'.
       '<tr><td style="width: 200px;">SomeTitle: </td><td>SomeData</td></tr>'.
       '</table></div></div>';

    // JUMP WITH THIS FORM DATA: echo '<input type="hidden" name="x" value="'.$shop->tx($array).'" />';

}

?>
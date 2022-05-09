<?php

/*
 *
 *  GATEWAY FOR PAYING BY MOLLIE.NL
 *
 */

$array['payment_method']='stripe.com';

$array['forwardurl'] = $SITE.'?checkout&x='.$array['ordernumber'];

function paymentgate($array,$shop) {
  global $GATEWAY,$SITE,$SET,$STR;
  // get gateway variables
  require($GATEWAY['directory'].'settings.php');

  if (!isset($array['amount']) || !isset($array['ordernumber'])) {
    echo 'Missing amount or ordernumber!';
    die();
  }

  try {
    /*
     * Initialize the Stripe API library with API key.
     *
     * See: https://github.com/stripe/stripe-php
     */
    // === STRIPE INIT === //
    require_once($GATEWAY['directory'].'lib/vendor/stripe/stripe-php/init.php');

    $stripe = new \Stripe\StripeClient($GATEWAY['API_Key']);
    $charge = $stripe->charges->create([
      'amount' => $shop->formatn($array['amount']),
      'currency' => strtolower($SET['shopcurrency']),
      'source' => 'tok_amex', // obtained with Stripe.js
      'description' => $array['ordernumber']
    ], [
      'idempotency_key' => hash('sha256',$array['ordernumber'].$array['amount'].$date->format('Y-m-d'))  // unique ID to avoid charge collisions
    ]);

    /* DO WE NEED THIS???
    $customer = $stripe->customers->create([
        'description' => $array['ordernumber'],
        'email' => 'email@example.com',
        'payment_method' => 'pm_card_visa',
    ]);
    */

// //

/*
    $payment = $mollie->payments->create([
        "amount" => [
            "currency" => $SET['shopcurrency'],
            "value" => $shop->formatn($array['amount'])
        ],
        "description" => $array['ordernumber'],
        "redirectUrl" => $array['forwardurl'].'&s=99',
        "webhookUrl"  => $array['forwardurl'].'&s=100'

    ]);

    $array['forwardurl'] = $payment->getCheckoutUrl();
*/

    /* DEBUG
     * If you want to forward the user:
       // This header location should always be a GET, thus we enforce 303 http response code
       //header("Location: " . $payment->getCheckoutUrl(), true, 303);
       * // JUMP WITH THIS FORM DATA: echo '<input type="hidden" name="x" value="'.$shop->tx($array).'" />';
     * Some debug info:
    echo '<div style="display: inline-block; text-align: left; background: #FFF none repeat scroll 0% 0%; font-weight: bold; margin: 12px 0; padding: 12px; min-width: 320px; border: 3px dashed #777;"><table>'.
       '<tr><td style="width: 200px;">API key: </td><td>'.$GATEWAY['API_Key'].'</td></tr>'.
       '<tr><td style="width: 200px;">Currency: </td><td>'.$SET['shopcurrency'].'</td></tr>'.
       '<tr><td style="width: 200px;">Amount: </td><td>'.$array['amount'].'</td></tr>'.
       '<tr><td style="width: 200px;">Ordernumber: </td><td>'.$array['ordernumber'].'</td></tr>'.
       '<tr><td style="width: 200px;">Payment Url: </td><td>'.$array['forwardurl'].'&s=99</td></tr>'.
       '<tr><td style="width: 200px;">Webhook Url: </td><td>'.$array['forwardurl'].'&s=100</td></tr>'.
       '</table></div>'
    */


  } catch(Exception $exc) {
    $array['forwardurl'] = $strReturnUrl.'?orderId='.$array['ordernumber'].'&orderStatusId=0&error='.$exc->getMessage();
    return $array;
  }

  return $array;

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
       $STR['Paying_via'].':<br><img class="img-responsive" style="margin: 0 auto;" src="'.$GATEWAY['directory'].'stripe.jpeg" /></div>';

  echo "<input type='hidden' name='x' value='".$shop->tx($array)."' />";

}

?>

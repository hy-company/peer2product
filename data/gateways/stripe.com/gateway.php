<?php

/*
 *
 *  GATEWAY FOR PAYING WITH STRIPE.COM
 *
 */

$array['payment_method']='stripe.com';
$array['forwardurl'] = $SITE.'?checkout&x='.$array['ordernumber'];

function paymentgate($array,$shop) {
  global $GATEWAY,$SITE,$SET,$STR;
  require($GATEWAY['directory'].'settings.php'); // get gateway variables
  if (!isset($array['amount']) || !isset($array['ordernumber'])) {
    echo 'Missing amount or ordernumber!';
    die();
  }
  $amountCents = $shop->formatn($array['amount'])*100;
  $array['forwardurl'] = $SITE.$GATEWAY['directory'].'forwarder.php?currency='.strtolower($SET['shopcurrency']).'&amount='.$amountCents.'&order='.$array['ordernumber'];
  return $array;
}


function paymentform($array,$shop) {
  global $GATEWAY,$SITE,$SET,$STR;
  require($GATEWAY['directory'].'settings.php'); // get gateway variables
  if (!isset($array['amount']) || !isset($array['ordernumber'])) {
    echo 'Missing amount or ordernumber!';
    die();
  }
  echo '<div style="width: 100%; margin-top: 48px; text-align: center;"><h4>'.$STR['Amount_to_pay'].': <span style="font-weight: bold;">'.$SET['shopcurrency'].' '.$shop->formatn($array['amount']).'</span></h4><br>'.
       '<span>'.$GATEWAY['description'].'</span><br><br>'.
       $STR['Paying_via'].':<br><img class="img-responsive" style="margin: 0 auto;" src="'.$GATEWAY['directory'].'stripe.jpeg" /></div>';
  echo "<input type='hidden' name='x' value='".$shop->tx($array)."' />";
  // [!] click submit to forward user to Stripe's own gateway in 3000ms
  echo "<script>setTimeout( () => { document.querySelector('.submit').click(); }, 3000);</script>";
}

?>

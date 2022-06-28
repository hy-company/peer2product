<?php

  /*
   * Stripe's libraries are very picky and get into a fight with FF3 framework.
   * So we let this simple forwarder load the library and then forward us instead.
   */

  // set protocol and base site
  if(isset($_GET['currency']) && isset($_GET['amount']) && isset($_GET['order'])) {
    if (isset($_SERVER['HTTPS']) &&
      ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
      isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
      $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $SITE = 'https://';
    } else { $SITE = 'http://'; }
    $tmp = dirname($_SERVER['REQUEST_URI']);
    $SITE .= (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'').($tmp=='/'?'/':$tmp.'/');

    $json = json_decode(file_get_contents('gateway.json'));
    foreach($json as $key => $val) {
      $GATEWAY[$key] = $val;
    }
    $forwardURL = $SITE.'../../..?checkout&x='.$_GET['order'];

    require('lib/vendor/autoload.php');
    header('Content-Type: application/json');
    \Stripe\Stripe::setApiKey($GATEWAY['API_Key']);
    $checkout_session = \Stripe\Checkout\Session::create([
      'payment_method_types' => ['card', 'ideal', 'bancontact'],  //payment methods
      'line_items' => [
        [
          'price_data' => [
            'currency' => strtolower($_GET['currency']),    // defines the currency used
            'unit_amount' => $_GET['amount'],               // defines the price charged

            'product_data' => [
              'name' => $_GET['order']                      // display name on checkout screen
            ],
          ],
          'quantity' => 1
        ]
      ],
      'mode' => 'payment',
      'success_url' => $forwardURL.'&s=100',                // redirect on success
      'cancel_url' => $SITE                                 // redirect on cancel
    ]);

    header("Location: " .$checkout_session->url);
  }
?>

<?php
session_start();

require 'vendor/autoload.php';

//stripe API key
\Stripe\Stripe::setApiKey('#'); //secret key, See stripe dashboard ->developers -> API keys.

header('Content-Type: application/json');

$YOUR_DOMAIN = '#'; //domain used for redirects.


//logic for price calculations.
  //here goes the code that defines a $price variable. Should be a total price for the order in cents. 
    //example 1€ is 100  // 51,50€ is 5150.


$checkout_session = \Stripe\Checkout\Session::create([
  'payment_method_types' => ['card', 'ideal', 'bancontact'],    //payment methods
  'line_items' => [[
      
      'price_data' => [
      'currency' => 'eur',    //defines the currency ysed
      'unit_amount' => $price,  //defines the price charged
      
      'product_data' => [
        'name' => '',  //display name on checkout screen, should be the store name.
//        'images' => [""],  //image displayed on checkout screen, currently bugs and shows a broken link. 
      ],
    ],
    'quantity' => 1,
  ]],
  'mode' => 'payment',
  'success_url' => $YOUR_DOMAIN . '/success.html',    //redirect on success
  'cancel_url' => $YOUR_DOMAIN ,                      //redirect on cancel
]);

header("HTTP/1.1 303 See Other");
header("Location: " . $checkout_session->url);

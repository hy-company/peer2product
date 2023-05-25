<?php

// Values below serve as temporary placeholder values that are necessary the make the required 
// calculations to serve a quote back to hybrix. In due time these values should become
// dynamic based on shop settings with a frontend implementation. 

// CONFIGURATION SETTINGS
$minimum_amount = 10;   //minimum amount the store checks out.
$maximum_amount = 500;  //maximum amount the store checks out.
$flat_fee_ceiling = 50; //use flat fees below this number
$flat_fee_rate = 5;     //use this flat fee when the request is below the flat fee ceiling
$fee_percentage = 5;    //use this percentage when the request is higher than the flat_fee_ceiling.

//AT THIS POINT A  POST REQUEST IS RECIEVED FROM HYBRIX ASKING FOR A QUOTE BASED ON A USER'S REQUEST.

// this script determines if a shop is capable/willing to handle an order based on the amount the user
// is requesting. If the values match the shop's parameters it returns an order number and 
// total price to hybrix and creates a new order for a flex voucher on the P2P shop with a pending status. 
$_POST['get_quote'] = 1;
if(isset($_POST['get_quote'])){
    $request = json_decode('{"amount" : 12}');
   
    //check to determine the validity of the amount.
    if($request->amount>$minimum_amount &&$request->amount<$maximum_amount){
        $viable_amount = TRUE;
    }

    if(!isset($viable_amount)){
        die("Invalid amount");
    } 

    //determining under which fees apply to the requested amount
    if($request->amount < $flat_fee_ceiling){
        $flat_fee_transaction = TRUE;
    } else {
        $flat_fee_transaction = FALSE;
    }

    //calulate total after fees.
    if($flat_fee_transaction)    {
        $subtotal = $request->amount + $flat_fee_rate;
    }

    if(!$flat_fee_transaction){
        $rate = 100 + $fee_percentage;
        $subtotal = $request->amount * $rate / 100;
    }

// create_order with pending status
$order['id'] = uniqid();
$order_file = $order['id'] . ".json";
$order['amount'] = $request->amount;
$order['total'] = $subtotal;
$order['status'] = 90; 
$order = json_encode($order);
$file = fopen("example_orders/$order_file", "w");
fwrite($file, $order);

//send response to hybrix
echo $order;
    
}
?>
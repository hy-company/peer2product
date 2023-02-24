<?php

// this is a very early testing build for this script. This script will eventualy allow hybrix to create
// orders on the peer2product platform for vouchers.

function create_order_deposit($payload){
    $order_id = uniqid();
    $array = (array) $payload;
    $order = '{"'.$order_id . '" :' . $payload .  '}';
    $order_file = fopen("../../../orders/$order_id.json", "w");
    fwrite($order_file, $order);
    
    echo $order_id; //return the order id to hybrix
}

function create_order_withdraw($payload){
    //this function seems redundant as users can simply use the gateway to buy hy euro.  
}

function create_order_transfer($payload){
    //this function seems redundant as users can use the deposit function with the transfer target's adress.
}

if(isset($_POST['hybrix_request'])){ //
    
    $request = json_decode($_POST['payload']);

    switch($request->type){
        case "deposit";
            create_order_deposit($request);
            break;

        case "withdraw";
            create_order_withdraw($request);
            break;
        
        case "transfer";
            create_order_transfer($request);
            break;
        
        default;
            die("invalid request");
            break;
    }
}


// Below you'll find an example payload to send to peer2product. Using this format allows us to use the system
// as is instead of having to reprogram large parts of it in order to behave nicely. 
// Sending in a POST request with $_POST['hybrix_request] : true allows this script to execute.
// attach the JSON example format below to $_POST['payload'] to create an order. 

$time = time(); 
$payload =  // IMPORTANT: [] in the target adress BREAKS THE ADMIN ->orders foreach loop. (lib/functions.php:616)
            // IMPORTANT: TIME MUST BE UNIX TIMESTAMP.
            // USERS TARGET ADRESS IS SET UNDER STREETNAME. THIS ALLOWS FOR AN EASIER VIEWING OF THE ORDER.
            // USERS TARGET BANK ACCOUNT SHOULD BE PLACED UNDER REMARKS.
'
{
    "firstname" : "ENTER USER INITIALS/FIRSTNAME AS ON BANK ACCOUNT",
    "lastname" : "ENTER USER LASTNAME AS ON BANK ACCOUNT",
    "company" : "hybrix",
    "time" : '. $time . ', 
    "notransport": 0,
    "transport": 0,
    "type" : "deposit",
    "amount" : 10,
    "margin" : 2.5,
    "tariff" : 0,
    "e-mail" : "ENTER USER EMAIL ADRESS",
    "streetname" : "0xa12b3c4d5e...",
    "remarks"     : "USERS TARGET ADRESS FOR A DEPOSIT,WITHDRAWAL OR TRANSFER.",
    "product-table" : "<div>ORDER TYPE: {deposit}<br> TO: {target adress}<br> AMOUNT: {amount}<br> NAME: {BANK ACC OWNER NAME}</div>"
  }
';
create_order_deposit($payload);


?>


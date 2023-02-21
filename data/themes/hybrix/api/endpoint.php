<?php

// this is a very early testing build for this script. This script will eventualy allow hybrix to create
// orders on the peer2product platform for vouchers.
//currently accepts a post request to execute a function that places a custom order into the orders folder.
//current itteration places the script and allows it to be seen and used from the admin dashboard.
//It currently does not yet show a name, last name or adress etc. just yet. This is the next todo.

$config_json = file_get_contents("config.json");
$config = json_decode($config_json);

function create_order_deposit($payload){
    $order_id = uniqid();
    $order = '{"'.$order_id . '" :' . $payload .  '}';
    $order_file = fopen("../../../orders/$order_id.json", "w");
    fwrite($order_file, $order);
    
    echo $order_id; //return the order id to hybrix
}

function create_order_withdraw($payload){
//create a withdraw order   
}

function create_order_transfer($payload){
    // create a transfer order.
}

if(isset($_POST['hybrix_request'])){
//recieved data is in JSON format:
    $request = json_decode($_POST['payload']);

    switch(request->type){
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

$payload =  // IMPORTANT: [] in the target adress BREAKS THE ADMIN ->orders foreach loop. (lib/functions.php:616)
'
{
    "type" : "deposit",
    "amount" : 10,
    "margin" : 2.5,
    "tariff" : 0,
    "e-mail" : "agent725@725.be",
    "target" : "0xa12b3c4d5e..."    
  }
';
create_order_deposit($payload);


?>


<?php

// this is a very early testing build for this script. This script will eventualy allow hybrix to create
// orders on the peer2product platform for vouchers.

$config_json = file_get_contents("config.json");
$config = json_decode($config_json);

//METHOD 1: Recieve a ready to use JSON payload that's ready to be inserted as an order.
function create_order_deposit($payload){
    $order_id = uniqid();
    $order = '{"'.$order_id . '" :' . $payload .  '}';
    $order_file = fopen("../../../orders/$order_id.json", "w");
    fwrite($order_file, $order);
    echo "Method 1: ";
    echo $order_id; //return the order id to hybrix
}

//METHOD 2: Recieve the nessecary data and merge it with additional data required to make the order work.
function create_order_deposit_method_2($payload){
    $order_id = uniqid();
    $peer2product_data = [];
    $peer2product_data['firstname'] = 'D K V';
    $peer2product_data['lastname'] = "van Kleef";
    $peer2product_data['company'] = "hybrix";
    $peer2product_data['time'] = time();
    $peer2product_data['notransport'] = 0;
    $peer2product_data['transport'] = 0;
    $peer2product_data['remarks'] = "1195583";
    $peer2product_data['product-table'] = "<div>ORDER TYPE: {deposit}<br> TO: {target adress}<br> AMOUNT: {amount}<br> NAME: {BANK ACC OWNER NAME}</div>";

    $payload = json_decode($payload);
    $payload = (array) $payload;
    $order_array = array_merge($payload, $peer2product_data);
    $order_json = json_encode($order_array);

    $order = '{"'.$order_id . '" :' . $order_json .  '}';
    $order_file = fopen("../../../orders/$order_id.json", "w");
    fwrite($order_file, $order);
    echo "<br>Method 2: ";
    echo $order_id; //return the order id to hybrix
}

//METHOD 3: Recieve the minimum required data, build a new object with this data that can be interpreted by p2p.
require("obj/flex_voucher.php");
function create_order_deposit_method_3($payload){
   $voucher = new OrderDetails($payload);
   $order_json = json_encode($voucher);
   $order = '{"'.$voucher->order_id . '" :' . $order_json .  '}';
   $order_file = fopen("../../../orders/$voucher->order_id.json", "w");
   fwrite($order_file, $order);

   echo "<br>Method 3: ";
   echo $voucher->order_id;  
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


// Below you'll find an example payloads to send to peer2product. Using this format allows us to use the system
// as is instead of having to reprogram large parts of it in order to behave nicely. 
// Sending in a POST request with $_POST['hybrix_request] : true allows this script to execute.
// attach the JSON example format below to $_POST['payload'] to create an order. 

// IMPORTANT: [] in the target adress BREAKS THE ADMIN ->orders foreach loop. (lib/functions.php:616)
// IMPORTANT: DO NOT USE - (DASH) IN LABELS. THIS CAUSES ISSUES WITH PHP VARIABLES. INSTEAD USE _ (UNDERSCORE)
// IMPORTANT: TIME MUST BE UNIX TIMESTAMP.
// USERS TARGET ADRESS IS SET UNDER STREETNAME. THIS ALLOWS FOR AN EASIER VIEWING OF THE ORDER.
// USERS TARGET BANK ACCOUNT SHOULD BE PLACED UNDER REMARKS.

//EXAMPLE 1 
$time = time(); 
$payload =  '
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

$method_1_start = microtime(true);
create_order_deposit($payload);
$method_1_end = microtime(true);
$method_1_execution_time = $method_1_end - $method_1_start;

// EXAMPLE 2
$payload = "";
$payload = '
{
    "type" : "deposit",
    "amount" : 10,
    "margin" : 2.5,
    "tariff" : 0,
    "e-mail" : "agent725@725.be",
    "target" : "0xa12b3c4d5e..."
  }
';

$method_2_start = microtime(true);
create_order_deposit_method_2($payload);
$method_2_end = microtime(true);
$method_2_execution_time = $method_2_end - $method_2_start;

// EXAMPLE 3
$payload = "";
$payload = '
{
    "firstname" : "TEST",
    "lastname"  : "NAME",
    "type" : "deposit",
    "amount" : 10,
    "margin" : 2.5,
    "tariff" : 0,
    "e-mail" : "agent725@725.be",
    "target" : "0xa12b3c4d5e..."
  }
';
$method_3_start = microtime(true);
 create_order_deposit_method_3($payload);
$method_3_end = microtime(true);
$method_3_execution_time = $method_3_end - $method_3_start;
echo "<br><br>Performance metrics:";
echo "<br>Method 1 execution time: $method_1_execution_time";
echo "<br>Method 2 execution time: $method_2_execution_time";
echo "<br>Method 3 execution time: $method_3_execution_time";
?>


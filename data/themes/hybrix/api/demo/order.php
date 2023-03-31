<?php

//send a POST request with finalize_order = true, a payload with the returned ordernumber from a quote
//and any additional information such as account number, account holder's name etc.


if(!isset($_POST['finalize_order'])){
    die('Invalid request');
}

$request = json_decode('{"id":"6426aa0d04564","amount":12,"total":17,"status":98, "account_name" : "D K V van Kleef","target_adress" : "NL INGB 0004 1194 538", "email" : "davidvankleef@gmail.com"}');
$file = $request->id . ".json";
echo "$file <br>";

$order = fopen("example_orders/$file", "w") or die('unable to open file');
fwrite($order, json_encode($request));
echo '{"response":"required response for hybrix to process the request."}';





?>
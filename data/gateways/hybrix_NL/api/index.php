<?php
$request = file_get_contents('php://input');
if ($request) {
  $data = json_decode($request);
  if (isset($data->action)) {
    chdir('../../../../');
    include('lib/init.php');
    $array = create_order($data);
    $array = report_order($array);
    $array = choose_target($array,$data->action);
    $paymentTarget = $array['payment_target_API']; unset($array['payment_target_API']);
    // write order to file
    $order_json = json_encode($array);
    $order = '{"'.$array['ordernumber'].'":'.$order_json.'}';
    $order_file = fopen($SET['data/'].$SET['ordr/'].$array['ordernumber'].".json", "w");
    fwrite($order_file, $order);
    echo '{"id":"'.$array['ordernumber'].'","target":"'.$paymentTarget.'"}'; //return the order id and payment target to hybrix (TODO: deadline?)
  } else die('Error: Expecting proper JSON request from remote node!');
} else die('Error: Expecting POST request from remote node!');

function choose_target($array,$action) {
  global $GATEWAY,$SET,$STR;
  if ($action == 'deposit') {
    $json = json_decode(file_get_contents($SET['data/'].$SET['gate/'].'/bank_NL/gateway.json'));
    foreach($json as $key => $val) {
      $GATEWAY[$key] = $val;
    }
    $paymentTargets = explode('|',$GATEWAY['Bank_account']);
    $paymentNames = explode('|',$GATEWAY['Bank_beneficiary']);
    $select = random_int(1,count($paymentTargets))-1;
    $array['payment_target'] = $paymentTargets[ $select ].', '.$paymentNames[ $select ];
    $array['payment_target_API'] = $paymentTargets[ $select ].'^'.$paymentNames[ $select ];
  } else {
    $json = json_decode(file_get_contents($SET['data/'].$SET['gate/'].'/hybrix_NL/gateway.json'));
    foreach($json as $key => $val) {
      $GATEWAY[$key] = $val;
    }
    $paymentTargets = explode('|',$GATEWAY['payment_target']);
    $paymentTarget = $paymentTargets[ random_int(1,count($paymentTargets))-1 ];
    $array['payment_target'] = $paymentTarget;
    $array['payment_target_API'] = trim(explode('<',$paymentTarget)[0]);
  }
  return $array;
}

// Send e-mail to client and shopadministrators
function report_order($payload) {
  global $shop,$SET,$STR;
  try {
    $users = array();
    $users = $shop->get_users($SET['data/'].$SET['user/']);
    if($payload['email']) {
      $username = explode('@',$payload['email'])[0];
      $users['_'] = array('username'=>$username,'e-mail'=>$payload['email'],'receive_notifications'=>'on');
    }
    $shop->reporting($SET['data/'],$payload,$users,'order_complete');
    $payload['sequence'] = 4;
  } catch (Exception $ex) {
    $payload['remarks'] = $payload['remarks'].' WARNING: There was an error mailing the user!';
    $payload['sequence'] = 2;
  }
  return $payload;
}

// Create and save order file
function create_order($payload) {
  global $shop,$SET,$STR;
  $order_id = uniqid();
  // {"firstname":"Ko","preposition":"van","lastname":"Dijk","company":"ACME","taxnumber":"12345","streetname":"KVD straat","housenumber":"25","zipcode":"1234AB","city":"Abcoude","countrycode":"nl","country":"Netherlands","telephone":"","email":"joachim@sheraga.net","remarks":"","notransport":0,"quantities":{"555499012387932":"1"},"amount":15.8149999999999995026200849679298698902130126953125,"maxsize":0,"weight":3,"transport":0.689999999999999946709294817992486059665679931640625,"quantity":1,"modifiers":0,"taxes":2.625,"subtotal":12.5,"product-table":"
  // "orderpaid":1, "ordersent":1  ??
  $peer2product_data = [];
  $peer2product_data['ordernumber'] = $order_id;
  $peer2product_data['firstname'] = '';
  $peer2product_data['preposition'] = '';
  $peer2product_data['lastname'] = explode('@',$payload->email)[0];
  $peer2product_data['company'] = 'hybrix.io';
  $peer2product_data['taxnumber'] = '';
  $peer2product_data['streetname'] = '';
  $peer2product_data['housenumber'] = '';
  $peer2product_data['zipcode'] = '';
  $peer2product_data['city'] = '';
  $peer2product_data['country'] = '';
  $peer2product_data['amount'] = $payload->amount;
  $quantitykey = 'flex_voucher: '.$payload->action.' '.$payload->type;
  $peer2product_data['quantities'][$quantitykey] = 1;
  $peer2product_data['maxsize'] = 0;
  $peer2product_data['weight'] = 0;
  $peer2product_data['transport'] = 0;
  $peer2product_data['modifiers'] = 0;
  $peer2product_data['taxes'] = 0;
  $peer2product_data['email'] = $payload->email;
  $peer2product_data['time'] = time();
  $peer2product_data['notransport'] = 0;
  $peer2product_data['transport'] = 0;
  $peer2product_data['remarks'] = ($payload->action == 'deposit'?'This is a deposit from fiat to crypto!':'This is a withdrawal from crypto to fiat!').' '.($payload->action == 'transfer'?'Important: For this third-party transfer, add the description to the transaction and not the voucher ID!':'Specify the voucher ID in this transaction using the format: voucher {ID}!');
  $peer2product_data['gateway'] = "hybrix";
  $peer2product_data['payment_method'] = $payload->type;

  $peer2product_data['product-table'] = '<table id="checkout-table" class="table table-striped"><tbody>'.
  '<tr><td><b>ACTION</b></td><td> </td><td> </td><td NOWRAP align="right">'.$payload->action.' '.$payload->type.'</td></tr>'.
  '<tr><td><b>TARGET</b></td><td> </td><td> </td><td NOWRAP align="right">'.implode('<br>',$payload->target).'</td></tr>'.
  '<tr><td><b>DESCRIPTION</b></td><td> </td><td> </td><td NOWRAP align="right">'.($payload->action == 'transfer'?str_replace('{ID}',$order_id,$payload->description):'voucher '.$order_id).'</td></tr>'.
  '<tr class="checkout-table-total"><td><b>AMOUNT TO SEND</b></td><td> </td><td> </td><td nowrap="" align="right"><b>'.strtoupper($payload->symbol).' '.$shop->formatn($payload->amount).'</b></td>'.
  '</tbody></table>';

  $peer2product_data['sequence'] = 1;

  return $peer2product_data;
}

?>

<?php
  // set short default socket timeout
  ini_set('default_socket_timeout', 8);
  // set protocol and base site
  if (isset($_SERVER['HTTPS']) &&
    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
      $SITE = 'https://';
  } else { $SITE = 'http://'; }
  $tmp = dirname($_SERVER['PHP_SELF']);
  $SITE .= (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'').($tmp=='/'?'/':$tmp.'/');
  // set static settings and directories
  $SET['data/'] = 'data/';
  $SET['stat/'] = 'statistics/';
  $SET['user/'] = 'users/';
  $SET['prod/'] = 'products/';
  $SET['ordr/'] = 'orders/';
  $SET['vend/'] = 'vendors/';
  $SET['gate/'] = 'gateways/';
  $SET['sett/'] = 'settlements/';
  $SET['them/'] = 'themes/';
  // requires and includes
  include_once('lib/functions.php');  // quick 'n dirty injection of shop/product functions class
  $shop = new functions;
  // get and merge settings
  $DEF = $shop->get_json($SET['data/'].'settings.def');
  $SET = $shop->update_array($SET,$shop->get_json($SET['data/'].'settings.json'));
  // NOT NEEDED: $SET = $shop->merge_arrays($DEF,$SET);

  // merge translation default and customizations
  $STR = $shop->update_array($shop->get_json($SET['data/'].'translation.def'),$shop->get_json($SET['data/'].'translation.json'));
?>

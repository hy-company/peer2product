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

  // requires and includes
  include_once('lib/functions.php');  // quick 'n dirty injection of shop/product functions class
  include_once('lib/paths.php');      // define static paths
  $shop = new functions;
  $DEF = $shop->get_json($SET['data/'].'settings.def');  // get default settings
  $SET = $shop->update_array($SET,$shop->get_json($SET['data/'].'settings.json')); // get custom settings

  // merge translation default and customizations
  $STR = $shop->update_array($shop->get_json($SET['data/'].'translation.def'),$shop->get_json($SET['data/'].'translation.json'));
?>

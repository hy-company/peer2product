<?php

// injection of product-related functions
class functions {

  //
  //  PRODUCT AND VENDOR MANAGEMENT FUNCTIONS
  //

  // check if any non-writeable areas exist that make the webshop non-functional
  function notifywriteable($directories,$notify = array('type' => FALSE, 'message' => '')) {
    foreach($directories as $directory) {
      if(!is_writable($directory)) {
        shell_exec('chmod -R 777 '.$directory);
        if(!is_writable($directory)) {
          $nonwriteable[] = $directory;
        }
      }
    }
    if(isset($nonwriteable)) {
      $notify['type'] = 'danger';
      $notify['message'] = 'Cannot write to these locations: ';
      foreach($nonwriteable as $directory) {
        $notify['message'] .= $directory.', ';
      }
      $notify['message'] = rtrim($notify['message'],', ');
      $notify['message'] .= ' -- Peer2Product will not function if this is left unsolved!';
    }
    return $notify;
  }


  function get_json($file) {
    // get categories
    if(file_exists($file)) {
      $json = json_decode(file_get_contents($file), true);
    } else { $json = array(); }
    return $json;
  }

  function put_json($file,$array) {
    // write json data - TODO: error checking for successful write?!
    file_put_contents($file,json_encode($array));
    return TRUE;
  }

  // update array or merge keys
  function update_array($array,$update) {
    if(is_array($update) && is_array($update)) {
      foreach($update as $key => $value) {
        $array[$key]=$value;
      }
    } else {
      die('Fatal error: cannot merge array data!');
    }
    return $array;
  }

  // fill an array according to a prototype map
  function mapfill_array($array,$update) {
    if(is_array($array)) {
      foreach($array as $key => $value) {
        if(!is_array($value)) {
          $array[$key]=FALSE;
        }
      }
    }
    return $this->update_array($array,$update);
  }

  // create array of differences between original and updated array
  function diff_array($array,$update) {
    $result = array();
    if(is_array($update) && is_array($update)) {
      foreach($update as $key => $value) {
        if($array[$key]!==$value) {
          $result[$key]=$value;
        }
      }
    } else {
      die('Fatal error: cannot diff array data!');
    }
    return $result;
  }

  // trims and sorts a string of comma-separated elements
  function trimsortstring($string) {
    $tmp = explode(',',$string);
    $tmp = array_unique($tmp);    // remove doubles
    foreach($tmp as $key => $val) {
      if($val) {
        $tmp[$key] = trim($val);  // trims whitespace
      } else {
        unset($tmp[$key]);
      }
    }
    asort($tmp);          // sorts entries
    return implode(',',$tmp);
  }

  // sorting
  function setsorting($session,$key) {
    // same key? reverse sort order, else set new key
    if(isset($session['sort_key']) && $session['sort_key'] == $key) {
      if(isset($session['sort_asc']) && $session['sort_asc']) {
        $session['sort_asc'] = FALSE;
      } else {
        $session['sort_asc'] = TRUE;
      }
    } else {
      $session['sort_key'] = $key;
      $session['sort_asc'] = TRUE;
    }
    return $session;
  }

  // sort id-based arrays using deep nesting
  function deepsort($array,$key,$asc = TRUE) {
    if($key=='date' || $key=='time') { // reverse sorting order for time (newest first)
      if($asc) { $asc=FALSE; } else { $asc=TRUE; }
    }
    if(count($array)) {
      // create quick sort index
      foreach($array as $id => $value) {
        if(isset($value[$key])) {
          $index[$id]=strtolower($value[$key]);
        } else {
          reset($value);
          $key = key($value);
          $index[$id]=strtolower($value[$key]);
        }
      }
      // sort index
      if($asc) {
        asort($index);
      } else {
        arsort($index);
      }
      // reorganize array
      foreach($index as $id => $value) {
        $sorted[$id] = $array[$id];
      }
    } else {
      $sorted = array();
    }
    return $sorted;
  }

  function chart_array($chartarray = array(),$starttime = '3 months ago',$dateformat = 'Y-m-d',$timestep = 86400) {
    $chart = array('labels'=>array(),'data'=>array());
    $startfrom = strtotime($starttime);
    $lastdate = FALSE;
    foreach($chartarray as $key => $val) {
      $keytime = strtotime($key);
      if($keytime>$startfrom) {
        if($lastdate) {
          $heredate = $lastdate;
          while($heredate + $timestep<$keytime) {
            $heredate = $heredate + $timestep;
            $chart['labels'][] = date($dateformat,$heredate);
            $chart['data'][] = 0;
          }
        }
        $chart['labels'][] = date($dateformat,$keytime);
        $chart['data'][] = $val;
        $lastdate = $keytime;
      }
    }
    return $chart;
  }

  // count amount of visitors and put in array
  function get_stat_visitors($directory) {
    $directory = $directory.'/visitors/';
    if(file_exists($directory)) {
      $subhandle = opendir($directory);
      $array = array();
      while ($file = readdir($subhandle)) if (!in_array($file, array('.', '..','README.md'))) {
        $date = substr($file,0,-4);
        $count = substr_count(file_get_contents($directory.$file),"\n"); // count amount of IP entries in file
        $array[$date] = $count;
      }
      ksort($array);
    } else {
      $array[date('Y-m-d')] = 0;
    }
    return $array;
  }

  // count amount of visitors and put in array
  function get_stat_sales($directory) {
    $directory = $directory.'/sales/';
    if(file_exists($directory)) {
      $subhandle = opendir($directory);
      $array = array();
      while ($file = readdir($subhandle)) if (!in_array($file, array('.', '..','README.md'))) {
        $date = substr($file,0,-4);
        $count = file_get_contents($directory.$file); // get total sales count per date
        $array[$date] = $count;
      }
      ksort($array);
    } else {
      $array[date('Y-m-d')] = 0;
    }
    return $array;
  }

  // chart product popularity
  function chart_stat_mostsold($directory) {
    $handle = opendir($directory);
    $array = array();
    while ($file = readdir($handle)) if (!in_array($file, array('.', '..','README.md'))) {
      $product = $this->get_product($directory,$file);
      $array[ substr($product[$file]['name'],0,16).(strlen($product[$file]['name'])>16?'...':'') ] = $product[$file]['sales'];
      arsort($array);
    }
    // limit amount of elements for the chart
    $array = array_slice($array,0,24);
    // format for chart.js
    foreach($array as $key => $val) {
      $chart['labels'][] = $key;
      $chart['data'][] = $val;
    }
    return $chart;
  }

  // chart tag popularity
  function chart_stat_toptags($directory) {
    $directory = $directory.'/tags/';
    $array = array();
    if(file_exists($directory)) {
      $subhandle = opendir($directory);
      while ($file = readdir($subhandle)) if (!in_array($file, array('.', '..','README.md'))) {
        $date = substr($file,0,-4);
        $entries = file($directory.$file,FILE_SKIP_EMPTY_LINES); // get entries
        foreach($entries as $entry) {
          $entry = explode(':',$entry);
          $tag = $entry[0];
          if(!isset($array[$tag])) {
            $array[$tag] = 1;
          } else {
            $array[$tag]++;
          }
        }
      }
      arsort($array);
    }
    // limit amount of elements for the chart
    $array = array_slice($array,0,24);
    // format for chart.js
    $chart = array('labels'=>array(),'data'=>array());
    foreach($array as $key => $val) {
      $chart['labels'][] = $key;
      $chart['data'][] = $val;
    }
    return $chart;
  }

  // get product stock from disk
  function get_stock($directory,$id) {
    $array = FALSE;
    if(file_exists($directory.$id.'/stock.asc')) {
      $array[$id]['stock'] = file_get_contents($directory.$id.'/stock.asc');
    } else {
      $array[$id]['stock'] = 0;
    }
    /* DEPRECATED
    if(file_exists($directory.$id.'/sales.asc')) {
      $array[$id]['sales'] = file_get_contents($directory.$id.'/sales.asc');
    } else {
      $array[$id]['sales'] = 0;
    }*/
    return $array;
  }

  // write product stocks to disk
  function put_stocks($directory,$array) {
    foreach($array as $id => $product) {
      if(file_exists($directory.$id)) {
        file_put_contents($directory.$id.'/stock.asc',$product['stock']);
        file_put_contents($directory.$id.'/sales.asc',$product['sales']);
      }
    }
  }

  function put_settlement($directory,$type,$vendorid,$orderid,$subtotal,$settlement,$transport,$modifiers,$margin,$description=FALSE) {
    if(!file_exists($directory.$vendorid)) {
      mkdir($directory.$vendorid);
    }
    if(!$description) {
      if($type=='d') {
        $description = '[resold for vendor]';
      } elseif($type=='c') {
        $description = '[bought via vendor]';
      } else {
        $description = '[clearing]';
      }
    }
    $array = array($vendorid => array('type'=>$type,'time'=>time(),'type'=>$type,'orderid'=>$orderid,'subtotal'=>$subtotal,'settlement'=>$settlement,'margin'=>$margin,'transport'=>$transport,'modifiers'=>$modifiers,'description'=>$description));
    $this->put_json($directory.$vendorid.'/'.$orderid.'.json',$array);
  }

  function get_settlements($directory,$id=FALSE) {
    if($id) {
      $settlements = array( $id => array() );
    } else {
      $settlements = array();
    }
    // get settlements (single batch by vendorid, or all recursively)
    if($id) {
      $subhandle = opendir($directory.'/'.$id);
      while ($file = readdir($subhandle)) if (!in_array($file, array('.', '..','README.md'))) {
        $json = $this->get_json($directory.'/'.$id.'/'.$file);
        foreach($json as $vendorid => $item) {
          if(!isset($item['subtotal'])) { $item['subtotal']=0; }
          if(!isset($item['modifiers'])) { $item['modifiers']=0; }
          if(!isset($item['transport'])) { $item['transport']=0; }
          if(!isset($item['margin'])) { $item['margin']=0; }
          if($item['type']!='x') $item['profit'] = $item['settlement']-($item['subtotal']-$item['modifiers']+$item['transport']);
          $orderid = $item['orderid']; unset($item['orderid']);
          $settlements[$vendorid][$orderid] = $item;
        }
      }
    } else {
      $handle = opendir($directory);
      while ($subdir = readdir($handle)) if (!in_array($subdir, array('.', '..','README.md'))) {
        $subhandle = opendir($directory.$subdir);
        while ($file = readdir($subhandle)) if (!in_array($file, array('.', '..','README.md'))) {
          $json = $this->get_json($directory.$subdir.'/'.$file);
          foreach($json as $vendorid => $item) {
            if(isset($item['type'])) {
              if(!isset($item['subtotal'])) { $item['subtotal']=0; }
              if(!isset($item['modifiers'])) { $item['modifiers']=0; }
              if(!isset($item['transport'])) { $item['transport']=0; }
              if(!isset($item['margin'])) { $item['margin']=0; }
              if($item['type']!='x') $item['profit'] = $item['settlement']-($item['subtotal']-$item['modifiers']+$item['transport']);
              $orderid = $item['orderid']; unset($item['orderid']);
              $settlements[$vendorid][$orderid] = $item;
            }
          }
        }
        closedir($subhandle);
      }
      closedir($handle);
    }
    return $settlements;
  }

  function calc_settlements_totals($vendorid,$settlements,$array=array(),$session=FALSE) {
    $array[$vendorid]['settlements'] = 0;
    $array[$vendorid]['subtotals'] = 0;
    $array[$vendorid]['margin'] = 0;
    $array[$vendorid]['transport'] = 0;
    $array[$vendorid]['modifiers'] = 0;
    $array[$vendorid]['profit'] = 0;
    $cnt = 0;
    foreach($settlements as $orderid => $val) {
      if($val['type']=='x') {
        $val['subtotal']=0;
      } else {
        // only count settlements and not clearings for averaging
        $cnt++;
        $array[$vendorid]['margin'] = $array[$vendorid]['margin'] + $val['margin'];
        $array[$vendorid]['transport'] = $array[$vendorid]['transport'] + $val['transport'];
        $array[$vendorid]['modifiers'] = $array[$vendorid]['modifiers'] + $val['modifiers'];
        $array[$vendorid]['profit'] = $array[$vendorid]['profit']+$val['settlement']-($val['subtotal']-$val['modifiers']+$val['transport']);
      }
      $array[$vendorid]['settlements'] = $array[$vendorid]['settlements'] + $val['settlement'];
      $array[$vendorid]['subtotals'] = $array[$vendorid]['subtotals'] + $val['subtotal'];
    }
    if($cnt) { $array[$vendorid]['margin'] = $array[$vendorid]['margin']/$cnt; }
    // sort orders if session variables are passed (backend related!)
    if($session) {
      $sort_key = (isset($session['sort_key'])?$session['sort_key']:'settlements');
      $sort_asc = (isset($session['sort_asc'])?$session['sort_asc']:TRUE);
      $array = $this->deepsort($array,$sort_key,$sort_asc);
    }
    return $array;
  }

  function calc_taxes($directory,$products) {
    // get modifier rules
    $modifiersmath = $this->get_json($directory.'modifiersmath.json');
    foreach($modifiersmath as $key => $val) {
      if(!($val['tax_modifier'] && $val['enabled_locally'])) {
        unset($modifiersmath[$key]);
      }
    }
    // get vendors in case of remote products
    $vendors = $this->get_vendors($directory.'vendors/');
    foreach($products as $id => $product) {
      $products[$id]['price-notax'] = $products[$id]['price'];
    }
    foreach($products as $id => $product) {
      if($product['vendorid']) {
        if(isset($vendors[ $product['vendorid'] ])) {
          foreach($vendors[ $product['vendorid'] ]['apply_local_modifiers'] as $key) {
            if(isset($modifiersmath[$key])) {
              $products[$id]['price'] = $products[$id]['price'] + $this->modifier_eval( $modifiersmath[$key]['formula'] , array('result'=>$products[$id]['price'],'product'=>$product) );
            }
          }
        }
      } else {
        foreach($product['modifiers'] as $key) {
          if(isset($modifiersmath[$key])) {
            $products[$id]['price'] = $products[$id]['price'] + $this->modifier_eval( $modifiersmath[$key]['formula'] , array('result'=>$products[$id]['price'],'product'=>$product) );
          }
        }
      }
    }
    return $products;
  }

  // read product from disk
  function get_product($directory,$id) {
    $array = FALSE;
    if(file_exists($directory.$id.'/product.json')) {
      $array = $this->get_json($directory.$id.'/product.json');
    }
    // load stock amount
    if(file_exists($directory.$id.'/stock.asc')) {
      $array[$id]['stock'] = file_get_contents($directory.$id.'/stock.asc');
    } else {
      $array[$id]['stock'] = 0;
    }
    // load sales amount
    if(file_exists($directory.$id.'/sales.asc')) {
      $array[$id]['sales'] = file_get_contents($directory.$id.'/sales.asc');
    } else {
      $array[$id]['sales'] = 0;
    }
    return $array;
  }

  // write products to disk
  function put_products($directory,$array) {
    foreach($array as $id => $product) {
      if(!file_exists($directory.$id)) {
        mkdir($directory.$id);
        touch($directory.$id.'/product.json');
        touch($directory.$id.'/stock.asc');
        touch($directory.$id.'/sales.asc');
      }
      $stock = (isset($product['stock'])?$product['stock']:0);
      unset($product['stock']);
      $sales = (isset($product['sales'])?$product['sales']:0);
      unset($product['sales']);
      file_put_contents($directory.$id.'/product.json',json_encode( array($id => $product) ));
      file_put_contents($directory.$id.'/stock.asc',$stock);
      file_put_contents($directory.$id.'/sales.asc',$sales);
    }
  }

  function get_products($directory,$session = FALSE,$tags = FALSE) {
    global $SET;
    // get local products
    if($tags == FALSE) {
      if(isset($SET['defaulttags'])) {
        $tags = $SET['defaulttags'];
        $tags = explode(',',$tags);
      } else {
        $tags = -1;
      }
    } else {
      if($tags != -1) { $tags = explode(',',$tags); }
    }
    $products = array();
    $handle = opendir($directory);
    while ($file = readdir($handle)) if (!in_array($file, array('.', '..','README.md'))) {
      if(file_exists($directory.$file.'/product.json')) {
        $json = $this->get_json($directory.$file.'/product.json');
        foreach($json as $id => $product) {
          if(isset($product['tags'])) { $categories = explode(',',$product['tags']); } else { $categories=array(); }
          if($tags!=-1) {
            $incategory = FALSE;
            foreach($tags as $tag) { if(in_array($tag,$categories)) { $incategory = TRUE; } }
          } else {
            $incategory = TRUE;
          }
          if($incategory) {
            // basic key definitions for product views
            $product['name'] = (isset($product['name'])?$product['name']:'');
            $product['description'] = (isset($product['description'])?$product['description']:'');
            $product['time'] = (isset($product['time'])?$product['time']:'');
            // add main image to product
            if(isset($product['image']) && $product['image'] && file_exists($directory.$id.'/'.$product['image'])) {
              $product['img']=$directory.$id.'/'.$product['image'];
            } else {
              $product['img']=FALSE;
            }
            // add detailed images to product
            if(isset($product['detail_image_A']) && $product['detail_image_A'] && file_exists($directory.$id.'/'.$product['detail_image_A'])) { $product['imgA']=$directory.$id.'/'.$product['detail_image_A']; } else { $product['imgA']=FALSE; }
            if(isset($product['detail_image_B']) && $product['detail_image_B'] && file_exists($directory.$id.'/'.$product['detail_image_B'])) { $product['imgB']=$directory.$id.'/'.$product['detail_image_B']; } else { $product['imgB']=FALSE; }
            // load stock amount
            if(file_exists($directory.$id.'/stock.asc')) {
              $product['stock'] = file_get_contents($directory.$id.'/stock.asc');
            } else {
              $product['stock'] = 0;
            }
            // load sales amount
            if(file_exists($directory.$id.'/sales.asc')) {
              $product['sales'] = file_get_contents($directory.$id.'/sales.asc');
            } else {
              $product['sales'] = 0;
            }
            // add product to array
            $products[$id] = $product;
          }
        }
      }
    }
    closedir($handle);
    // sort products if session variables are passed (backend related!)
    if($session) {
      $sort_key = (isset($session['sort_key'])?$session['sort_key']:'name');
      $sort_asc = (isset($session['sort_asc'])?$session['sort_asc']:TRUE);
      $products = $this->deepsort($products,$sort_key,$sort_asc);
    }
    return $products;
  }

  /*function get_vendor($directory,$id) {
    // get vendor
    $vendors = array();
    $json = $this->get_json($directory.$id.'/vendor.json');
    foreach($json as $id => $vendor) $vendors[$id] = $vendor;
    $vendor = $vendors[$id];
    return $vendor;
  }*/

  function get_vendors($directory) {
    // get vendors
    $handle = opendir($directory);
    $vendors = array();
    while ($file = readdir($handle)) if (!in_array($file, array('.', '..','README.md'))) {
      $json = $this->get_json($directory.$file.'/vendor.json');
      foreach($json as $id => $vendor) $vendors[$id] = $vendor;
    }
    $vendors = $this->deepsort($vendors,'name',TRUE);
    closedir($handle);
    return $vendors;
  }

  function get_users($directory) {
    // get vendors
    $handle = opendir($directory);
    $users = array();
    while ($file = readdir($handle)) if (!in_array($file, array('.', '..','README.md'))) {
      $json = $this->get_json($directory.$file.'/user.json');
      foreach($json as $id => $user) $users[$id] = $user;
    }
    closedir($handle);
    return $users;
  }

  function get_gateways($directory) {
    // make a list of usable gateways
    $handle = opendir($directory);
    $gateways = array();
    while ($file = readdir($handle)) if (!in_array($file, array('.', '..','README.md'))) {
      if(file_exists($directory.$file.'/gateway.json')) {
        $json = $this->get_json($directory.$file.'/gateway.json');
        $gateways[$file]['gateway'] = $json['gateway'];
        $gateways[$file]['name'] = $json['gateway_name'];
        $gateways[$file]['description'] = $json['gateway_description'];
        $gateways[$file]['active'] = (isset($json['gateway_active']) && $json['gateway_active']?TRUE:FALSE);
      }
    }
    closedir($handle);
    ksort($gateways);
    return $gateways;
  }

  /* CURRENTLY NOT USED, INSTEAD BOOLEAN OPTION TO HAVE PACKAGE SENT OR RETRIEVAL
  function get_transports($directory) {
    // make a list of usable transport methods
    $transports = array();
    if(file_exists($directory.'transportmath.json')) {
      $json = $this->get_json($directory.'transportmath.json');
      foreach($json as $id => $transport) {
        $title = $transport[$id]['title'];
        $transports[$title]['id'] = $id;
        $transports[$title]['title'] = $title;
      }
    }
    ksort($transports);
    return $transports;
  }
  */

  function get_order($directory,$id) {
    $orders = array();
    if(file_exists($directory.$id.'.json')) {
      $suffix = '.json';
    } elseif(file_exists($directory.$id.'.pending')) {
      $suffix = '.pending';
    } else {
      $suffix = FALSE;
    }
    if($suffix) {
      $json = $this->get_json($directory.$id.$suffix);
      foreach($json as $id => $order) $orders[$id] = $order;
    }
    return $orders;
  }

  function get_orders($directory,$session = FALSE) {
    $orders = array();
    // make a list of pending and filled orders
    $handle = opendir($directory);
    while ($file = readdir($handle)) if (!in_array($file, array('.', '..','README.md'))) {
      if(file_exists($directory.$file)) {
        $json = $this->get_json($directory.$file);
        foreach($json as $id => $order) $orders[$id] = $order;
      }
    }
    closedir($handle);
    // sort orders if session variables are passed (backend related!)
    if($session) {
      $sort_key = (isset($session['sort_key'])?$session['sort_key']:'time');
      $sort_asc = (isset($session['sort_asc'])?$session['sort_asc']:TRUE);
      $orders = $this->deepsort($orders,$sort_key,$sort_asc);
    }
    return $orders;
  }

  function fill_item_from_array($item,$json,$id) {
    $cnt = 0;
    foreach($item as $key => $val) {
      if($cnt) {
        $item[$key] = $id;
      } else {
        $item[$key] = $json[$id];
      }
      $cnt++;
    }
    $item[1] = $json[$id];
    return $item;
  }

  // move an element up or down in a key-value array
  function move_element_in_array($json,$action,$id) {
    $cnt = 0;
    // ready array for moving elements
    foreach($json as $key => $val) {
      if($key == $id) {
        $keynr = $cnt;
      }
      $sortarray[] = array($key,$val);
      $cnt++;
    }
    // move elements in temporary array
    switch($action) {
      case 'up':
        if($keynr>0) {
          $tmp = $sortarray[ $keynr ];
          $sortarray[ $keynr ] = $sortarray[ $keynr - 1 ];
          $sortarray[ $keynr - 1 ] = $tmp;
        }
      break;
      case 'down':
        if($keynr<($cnt-1)) {
          $tmp = $sortarray[ $keynr ];
          $sortarray[ $keynr ] = $sortarray[ $keynr + 1 ];
          $sortarray[ $keynr + 1 ] = $tmp;
        }
      break;
    }
    // rebuild key-value array
    $json = array();
    foreach($sortarray as $val) {
      $json[$val[0]] = $val[1];
    }
    return $json;
  }

  // convert a (pseudo-) tuple to an array
  function tuple_to_array($tuple,$identifier) {
    $array=array();
    foreach($tuple as $key => $val) {
      $array[$key] = $val[$identifier];
    }
    return $array;
  }

  // pseudo-jailed eval for transport formula's
  function transport_eval($code,$var) {
    $price = (isset($var['product']['price'])?$var['product']['price']:0);;
    $quantity = (isset($var['products']['quantity'])?$var['products']['quantity']:0);;
    $weight = (isset($var['products']['weight'])?$var['products']['weight']:0);
    $size = (isset($var['products']['size'])?$var['products']['size']:0);
    $minsize = (isset($var['products']['minsize'])?$var['products']['minsize']:0);
    $maxsize = (isset($var['products']['maxsize'])?$var['products']['maxsize']:0);

    $company = trim(strtolower( (isset($var['array']['company'])?$var['array']['company']:0) ));
    $housenumber = trim(strtolower( (isset($var['array']['housenumber'])?$var['array']['housenumber']:0) ));
    $street = trim(strtolower( (isset($var['array']['street'])?$var['array']['street']:0) ));
    $city = trim(strtolower( (isset($var['array']['city'])?$var['array']['city']:0) ));
    $countrycode = trim(strtolower( (isset($var['array']['countrycode'])?$var['array']['countrycode']:0) ));
    $zipcode = trim(strtolower( (isset($var['array']['zipcode'])?$var['array']['zipcode']:0) ));
    $notransport = (isset($var['array']['notransport'])?$var['array']['notransport']:0);

    $result=$var['result'];
    try {
      {{{{{{{{{{
      {{{{{{{{{{
      {{{{{{{{{{
      {{{{{{{{{{
        eval($code.';;;;;;;;;;');
      }}}}}}}}}}
      }}}}}}}}}}
      }}}}}}}}}}
      }}}}}}}}}}
    }
    catch (customException $e) {
      $result=FALSE;
    }
    return $result;
  }

  // pseudo-jailed eval for modifier formula's
  function modifier_eval($code,$var) {
    $price = (isset($var['product']['price'])?$var['product']['price']:0);;
    $quantity = (isset($var['product']['quantity'])?$var['product']['quantity']:0);;
    $weight = (isset($var['product']['weight'])?$var['product']['weight']:0);
    $size = (isset($var['product']['size'])?$var['product']['size']:0);

    $company = trim(strtolower( (isset($var['array']['company'])?$var['array']['company']:0) ));
    $housenumber = trim(strtolower( (isset($var['array']['housenumber'])?$var['array']['housenumber']:0) ));
    $street = trim(strtolower( (isset($var['array']['street'])?$var['array']['street']:0) ));
    $city = trim(strtolower( (isset($var['array']['city'])?$var['array']['city']:0) ));
    $countrycode = trim(strtolower( (isset($var['array']['countrycode'])?$var['array']['countrycode']:0) ));
    $zipcode = trim(strtolower( (isset($var['array']['zipcode'])?$var['array']['zipcode']:0) ));
    $notransport = (isset($var['array']['notransport'])?$var['array']['notransport']:0);

    $result=$var['result'];
    try {
      {{{{{{{{{{
      {{{{{{{{{{
      {{{{{{{{{{
      {{{{{{{{{{
        eval($code.';;;;;;;;;;');
      }}}}}}}}}}
      }}}}}}}}}}
      }}}}}}}}}}
      }}}}}}}}}}
    }
    catch (customException $e) {
      $result=FALSE;
    }
    return $result;
  }

  // create jcart or slider forms (only if stock>0)
  function make_product_forms($products,$slider=FALSE) {
    global $SET,$DEF,$STR;
    $html = '';
    if($slider) {
      $element = 'ui/webshop-slider.htm';
    } else {
      $element = 'ui/webshop-item.htm';
    }
    foreach($products as $productid => $product) {
      if($product['stock']!=0) {
        ob_start();
        include($element);
        $html .= ob_get_clean();
      }
    }
    return $html;
  }

  function make_producttable($array,$products,$vendors,$modifiersmath,$transportmath) {
    global $SET,$STR;
    // reset summary variables
    $array['amount']=0;
    $array['maxsize']=0;
    $array['weight']=0;
    $array['transport']=0;
    $array['quantity']=0;
    $array['modifiers']=array();
    // construct producttable
    $producttable='<div class="table-responsive"><table id="checkout-table" class="table table-striped"><tbody></div>';
    foreach ($products as $id => $product) {
      // apply local and remote modifiers per product
      if(isset($product['modifiers'])) {
        if($product['vendorid']=='#') {
          foreach($product['modifiers'] as $modifier_key) {
            if(isset($modifiersmath[$modifier_key]) && $modifiersmath[$modifier_key]['enabled_locally']) {
              if(!isset($array['modifiers'][$modifier_key]['amount'])) { $array['modifiers'][$modifier_key]['amount']=0; }
              if(!isset($array['modifiers'][$modifier_key]['title'])) { $array['modifiers'][$modifier_key]['title'] = $modifiersmath[$modifier_key]['title']; }
              $array['modifiers'][$modifier_key]['amount'] = $array['modifiers'][$modifier_key]['amount'] + ( $array['quantities'][$id] * $this->modifier_eval( $modifiersmath[$modifier_key]['formula'] , array('result'=>$array['modifiers'][$modifier_key]['amount'],'product'=>$product) ) );
              $array['modifiers'][$modifier_key]['count'] = $array['modifiers'][$modifier_key]['count'] + $array['quantities'][$id];
              if($modifiersmath[$modifier_key]['tax_modifier']) { $array['modifiers'][$modifier_key]['tax'] = TRUE; } else { $array['modifiers'][$modifier_key]['tax'] = FALSE; }
            }
          }
        } else {
          // apply remote modifiers for remote vendor's products
          $hash = crc32($vendors[ $product['vendorid'] ]['secret']);
          $query = $this->tx( array($hash,'mod',array($id,$array['quantities'][$id],$array['modifiers'])) );
          $remote_mods = $this->rx(file_get_contents( $vendors[ $product['vendorid'] ]['host'].'?q='.$query ),$vendors[ $product['vendorid'] ]['secret']);
          if($remote_mods && is_array($remote_mods)) {
            // merge stuff
            foreach($remote_mods as $key => $val) {
              $array['modifiers'][$key] = $val;
            }
          }
          // apply local modifiers for remote vendor's products (for example: applicable to special tax situations)
          foreach($vendors[ $product['vendorid'] ]['apply_local_modifiers'] as $modifier_key) {
            if(isset($modifiersmath[$modifier_key]) && $modifiersmath[$modifier_key]['enabled_locally']) {
              if(!isset($array['modifiers'][$modifier_key]['amount'])) { $array['modifiers'][$modifier_key]['amount']=0; }
              if(!isset($array['modifiers'][$modifier_key]['title'])) { $array['modifiers'][$modifier_key]['title'] = $modifiersmath[$modifier_key]['title']; }
              $array['modifiers'][$modifier_key]['amount'] = $array['modifiers'][$modifier_key]['amount'] + ( $array['quantities'][$id] * $this->modifier_eval( $modifiersmath[$modifier_key]['formula'] , array('result'=>$array['modifiers'][$modifier_key]['amount'],'product'=>$product) ) );
              $array['modifiers'][$modifier_key]['count'] = $array['modifiers'][$modifier_key]['count'] + $array['quantities'][$id];
              if($modifiersmath[$modifier_key]['tax_modifier']) { $array['modifiers'][$modifier_key]['tax'] = TRUE; } else { $array['modifiers'][$modifier_key]['tax'] = FALSE; }
            }
          }
        }
      }
      // add data up to totals
      $array['weight']=$array['weight']+($product['weight'] * $array['quantities'][$id]);
      $array['quantity']=$array['quantity']+$array['quantities'][$id];
      if($array['maxsize']<$product['size']) { $array['maxsize'] = $product['size']; }
      $array['amount']=$array['amount']+($array['quantities'][$id]*$product['price']);
      $vendorname = $vendors[ $product['vendorid'] ]['name'];
      $producttable.=$this->make_orderline($product['name'],$array['quantities'][$id],$SET['shopcurrency'],$product['price'],$vendorname);
      // create array for sorting vendorid's
      $products_by_vendor[$product['vendorid']][] = $id;
    }
    // sort the products by vendor for transport calculation
    ksort($products_by_vendor);
    // combine modifiers that have identical titles (case-insensitive)
    $tmp = array();
    foreach($array['modifiers'] as $key => $val) {
      $simpletitle = strtolower( preg_replace('/[^a-zA-Z0-9]/','', $val['title']) );
      $tmp[ $simpletitle ]['amount'] = $tmp[ $simpletitle ]['amount'] + $val['amount'];
      $tmp[ $simpletitle ]['count'] = $tmp[ $simpletitle ]['count'] + $val['count'];
      if(!isset($tmp[ $simpletitle ]['title'])) { $tmp[ $simpletitle ]['title'] = $val['title']; }
      if(!isset($tmp[ $simpletitle ]['key']) && array_key_exists($key,$array['modifiers'])) { $tmp[ $simpletitle ]['key']=$key; }
    }
    // construct modifier lines
    $modifiers = 0;
    $modifiertable = '';
    $array['taxes'] = 0;
    foreach($tmp as $title => $val) {
      //$modifiertable.='<tr class="checkout-table-modif"><td><i>'.$array['modifiers'][ $val['key'] ]['title'].'</i></td><td NOWRAP align="right"><i>'.$val['count'].'<span style="color: #AAA;">x</span></i></td><td NOWRAP align="right"> </td><td NOWRAP align="right"><i>'.$SET['shopcurrency'].' '.$this->formatn( $val['amount'] ).'</i></td></tr>';
      $modifiertable.='<tr class="checkout-table-modif"><td><i>'.$array['modifiers'][ $val['key'] ]['title'].'</i></td><td NOWRAP align="right"> </td><td NOWRAP align="right"> </td><td NOWRAP align="right"><i>'.$SET['shopcurrency'].' '.$this->formatn( $val['amount'] ).'</i></td></tr>';
      if(!$array['modifiers'][ $val['key'] ]['tax']) {
        $modifiers=$modifiers+$val['amount'];
      } else {
        $array['taxes']=$array['taxes']+$val['amount'];
      }
    }
    // collapse modifiers into single amount
    $array['modifiers'] = $modifiers;
    $array['amount']=$array['amount']+$array['modifiers'];
    // calculate local and remote transport
    foreach ($products_by_vendor as $vendorid => $ids) {
      $vendorssubtotal[$vendorid] = 0;
      // calculate local transport
      if($vendorid=='#') {
        $productslist['quantity'] = 0;
        $productslist['weight'] = 0;
        $productslist['maxsize'] = 0;
        $productslist['minsize'] = 99999999;
        foreach($ids as $id) {
          // collect product metrics and totals
          $productslist['quantity'] = $productslist['quantity'] + $array['quantities'][$id];
          $productslist['weight'] = $productslist['weight'] + ($products[$id]['weight'] * $array['quantities'][$id]);
          $productslist['size'] = $productslist['size'] + ($products[$id]['size'] * $array['quantities'][$id]);
          if($productslist['maxsize']<$products[$id]['size']) { $productslist['maxsize']=$products[$id]['size']; };
          if($productslist['minsize']>$products[$id]['size']) { $productslist['minsize']=$products[$id]['size']; };
          //$vendorssubtotal[$vendorid] = $vendorssubtotal[$vendorid] + ($transport * $array['quantities'][$id]);
        }
        $transport = 0;
        foreach($transportmath as $transport_key => $transport_val) {
          $transport = $transport + $this->transport_eval( $transport_val['formula'] , array('result'=>$vendorssubtotal[$vendorid],'products'=>$productslist,'array'=>$array) );
        }
        $vendorssubtotal[$vendorid] = $vendorssubtotal[$vendorid] + $transport;
      // calculate remote transport
      } else {
        $hash = crc32($vendors[ $vendorid ]['secret']);
        $s_array = $array; unset($s_array['product-table']); // share array excluding entire product table and all product quantities
        $query = $this->tx( array($hash,'pst',array($ids,$array)) );
        $vendorssubtotal[$vendorid] = $this->rx(file_get_contents( $vendors[ $vendorid ]['host'].'?q='.$query ),$vendors[ $vendorid ]['secret']);
        if(is_array($vendorssubtotal[$vendorid])) { $vendorssubtotal[$vendorid]=0; }
      }
    }
    foreach($vendorssubtotal as $key => $val) {
      $array['transport'] = $array['transport'] + $val;
    }
    // construct subtotal (and store)
    $array['subtotal'] = $array['amount'];
    $subtotaltable = '<tr class="checkout-table-total">
      <td><i>'.$STR['Subtotal'].'</i></td>
      <td NOWRAP align="right"><i>'.$array['quantity'].'<span style="color: #AAA;">x</span></i></td>
      <td> </td>
      <td NOWRAP align="right"><i>'.$SET['shopcurrency'].' '.$this->formatn( $array['subtotal'] ).'</i></td>
      </tr>';
    // construct transport cost line
    if(!$array['notransport'] && $array['transport']>0) {
      $transporttable = '<tr class="checkout-table-modif"><td><i>'.$STR['Transport'].'</i></td><td NOWRAP align="right"><i>'.$array['weight'].'<span style="color: #AAA;">g</span></i></td><td NOWRAP align="right"> </td><td NOWRAP align="right"><i>'.$SET['shopcurrency'].' '.$this->formatn( $array['transport'] ).'</i></td></tr>';
      $array['amount']=($array['amount']+$array['transport']);
    } else { $transporttable = ''; }
    // now add the taxes
    $array['amount']=$array['amount']+$array['taxes'];
    // insert subtotal line
    $producttable .= $subtotaltable;
    // insert modifier lines
    $producttable .= $modifiertable;
    // insert transport line
    $producttable .= $transporttable;
    // construct totals
    $producttable.='<tr class="checkout-table-total">
      <td><b>'.$STR['TOTAL'].'</b></td>
      <td> </td>
      <td> </td>
      <td NOWRAP align="right"><b>'.$SET['shopcurrency'].' '.$this->formatn( $array['amount'] ).'</b></td>
      </tr>
    </tbody></table>';
    // return $array with the constructed producttable
    $array['product-table'] = $producttable; // table of ordered products as user literally saw them
    return $array;
  }

  function make_orderline($productname,$quantity,$currency,$price,$vendor) {
    global $STR;
    return '  <tr>
            <td>'.$productname.($vendor?' <small>('.$STR['origin'].' '.$vendor.')</small>':'').'</td>
            <td NOWRAP align="right">'.$quantity.'<span style="color: #AAA;">x</span></td>
            <td NOWRAP align="right">'.$currency.' '.$this->formatn( $price ).'</td>
            <td NOWRAP align="right">'.$currency.' '.$this->formatn( $quantity*$price ).'</td>
          </tr>';
  }

  function order_status($array,$short=0) {
    if(!isset($array['orderstatus'])) { $array['orderstatus']=0; }
    if(!isset($array['ordersent'])) { $array['ordersent']=0; }
    $completion = round(($array['sequence']/4)*100);
    // set order status completion to less than 100 if payment gateway step failed
    if($completion == 100) {
      $completion=$completion-2;
      $completion=$completion+($array['orderstatus']==100?1:0);
      $completion=$completion+($array['ordersent']?1:0);
    }
    if($completion==100) { $color="green"; }
    elseif($completion>=90) { $color="orange"; }
    else { $color="red"; }
    $percent = '<div style="display: inline-block; font-weight: bold; color: '.$color.'; width: 42px;">'.$completion.'%</div><i class="fa fa-money" style="color:'.($array['orderstatus']==100?'green':'grey').';"></i> <i class="fa fa-rocket" style="color:'.($array['ordersent']?'green':'grey').';"></i>';
    switch($short) {
      case 0: $result = $completion; break;
      case 1: $result=$percent; break;
      case 2: $result=$percent;
        $result='<span style="font-weight: bold; color: '.$color.';">'.$completion.'%</span> (checkout completed to step '.$array['sequence'].' of 4'.($array['orderstatus']==100?', payment done':($array['orderstatus']<99 && $array['orderstatus']!=0?', payment failed!':'')).($array['ordersent']?', order sent':'').')';
      break;
    }
    return $result;
  }

  //
  //  COMMON FUNCTIONS
  //

  function siteroot() {
    header('Location: '.dirname($_SERVER['SCRIPT_NAME']));
  }

  function formatn($number) {
    return number_format($number , 2, '.', '');
  }

  function reporting($directory,$array,$users,$type) {
    // get settings
    $SET = $this->update_array($this->get_json($directory.'settings.def'), $this->get_json($directory.'settings.json'));
    $reporting = $this->get_json($directory.'reporting.json');
    if($reporting) {
      $style = '<style>.table {width: 100%;} .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {padding: 8px;line-height: 1.42857;vertical-align: top;border-top: 1px solid #DDD;} .checkout-table-modif {color: #FFF;background-color: #6A6A6A !important;} .checkout-table-total {color: #FFF;background-color: #555 !important;} </style>';
      switch($type) {
        case 'order_complete':
          $username = (!empty($array['company'])?$array['company']:$array['firstname'].(!empty($array['preposition'])?' '.$array['preposition']:'').' '.$array['lastname']);
          $address = '<p>'.(!empty($array['company'])?$array['company']: (!empty($array['firstname'])?$array['firstname'].' ':'').(!empty($array['preposition'])?$array['preposition'].' ':'').(!empty($array['lastname'])?$array['lastname']:'') ).'<br>
                 '.$array['streetname'].' '.$array['housenumber'].'<br>
                 '.$array['zipcode'].'<br>
                 '.$array['city'].'<br>
                 '.$array['country'].'</p>';
          $message = '<html><body>'.$style;
          $message .= str_replace(array('{ordernumber}','{shopname}','{user}','{address}','{product-table}','{date}','{duedate}'), array($array['ordernumber'],$SET['shopname'],$username,$address,$array['product-table'],date('d-m-Y'),date('d-m-Y',strtotime('now +14 days'))), $reporting['order_complete_message']);
          $message .= '</body></html>';
          $subject = str_replace(array('{ordernumber}','{shopname}'),array($array['ordernumber'],$SET['shopname']),$reporting['order_complete_subject']);
        break;
        case 'order_sent':
          $username = (!empty($array['company'])?$array['company']:$array['firstname'].(!empty($array['preposition'])?' '.$array['preposition']:'').' '.$array['lastname']);
          $address = '<p>'.(!empty($array['company'])?$array['company']: (!empty($array['firstname'])?$array['firstname'].' ':'').(!empty($array['preposition'])?$array['preposition'].' ':'').(!empty($array['lastname'])?$array['lastname']:'') ).'<br>
                 '.$array['streetname'].' '.$array['housenumber'].'<br>
                 '.$array['zipcode'].'<br>
                 '.$array['city'].'<br>
                 '.$array['country'].'</p>';
          $message = '<html><body>'.$style;
          $message .= str_replace(array('{ordernumber}','{shopname}','{user}','{address}','{product-table}','{date}','{duedate}'), array($array['ordernumber'],$SET['shopname'],$username,$address,$array['product-table'],date('d-m-Y'),date('d-m-Y',strtotime('now +14 days'))), $reporting['order_sent_message']);
          $message .= '</body></html>';
          $subject = str_replace(array('{ordernumber}','{shopname}'),array($array['ordernumber'],$SET['shopname']),$reporting['order_sent_subject']);
        break;
      }
      $target = array();
      foreach($users as $key => $user) {
        if(isset($user['e-mail']) && $user['e-mail'] && isset($user['receive_notifications']) && $user['receive_notifications']) {
          $target[] = $user['e-mail'];
        }
      }
      // sent by php mail or smtp
      if($reporting['use_smtp']) {
        include('Mail.php');  // PEAR object
        $recipients = implode(',',$target);
        $headers['To']      = implode(',',$target);
        $headers['From']    = $SET['shopname'].' <'.$reporting['from_e-mail'].'>';
        $headers['Subject'] = $subject;
        $params['host'] = $reporting['smtp_host'];
        $params['port'] = ($reporting['smtp_port']?$reporting['smtp_port']:'25');
        $params['auth'] = ($reporting['smtp_user']?true:false);
        $params['username'] = $reporting['smtp_user'];
        $params['password'] = $reporting['smtp_pass'];
        // Create the mail object using the Mail::factory method
        $mail_object =& Mail::factory('smtp', $params);
        $mail_object->send($recipients, $headers, $message);
      } else {
        $headers = 'From:'.$SET['shopname'].' <'.$reporting['from_e-mail'].'>' . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n"; // To send HTML mail, the Content-type header must be set
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        mail(implode(',',$target),$subject,$message,$headers);
      }
    }
  }

  function encrypt($data, $key)
  {
      $l = strlen($key);
      if ($l < 16)
          $key = str_repeat($key, ceil(16/$l));
      if ($m = strlen($data)%8)
          $data .= str_repeat("\x00",  8 - $m);
      if (function_exists('mcrypt_encrypt'))
          $val = mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_ECB);
      else
          $val = openssl_encrypt($data, 'BF-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
      return $val;
  }

  function decrypt($data, $key)
  {
      $l = strlen($key);
      if ($l < 16)
          $key = str_repeat($key, ceil(16/$l));
      if (function_exists('mcrypt_encrypt'))
          $val = mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $data, MCRYPT_MODE_ECB);
      else
          $val = openssl_decrypt($data, 'BF-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
      return $val;
  }

  // RX TX functions (rudimentary security over unsafe connections)
  function net_encode($string,$key = '#p4dd%ng#r(jNda3l',$compression = 7) {
    $string = gzdeflate($string,$compression);
    $string = $this->encrypt($string, $key);
    $string = base64_encode($string);
    $string = str_replace(array('+','/','='),array('-','_','.'),$string); // make url safe
    return $string;
  }

  function net_decode($string,$key = '#p4dd%ng#r(jNda3l') {
    $string = str_replace(array('-','_','.'),array('+','/','='),$string); // restore from url safe
    $string = base64_decode($string);
    $string = $this->decrypt($string, $key);
    $string = gzinflate($string);
    return $string;
  }

  function tx($array,$key = '#') {
    if (isset($array['tmp'])) { $tmp=$array['tmp']; unset($array['tmp']); } // unset temporary data storage properties
    $txout = $this->net_encode(json_encode($array),$key.'p4dd%ng#r(jNda3l');
    return $txout;
    $array['tmp'] = $tmp; // restore temporary data storage properties
  }

  function rx($string,$key = '#') {
    return json_decode($this->net_decode($string,$key.'p4dd%ng#r(jNda3l'), true);
  }

}

?>

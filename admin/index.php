<?php

// resolve symlinked paths
$BASEPATH = (is_link(__FILE__) ? dirname(readlink(__FILE__)) : dirname(__FILE__));

// kickstart the framework
$f3=require($BASEPATH.'/lib/base.php');

// quick 'n dirty injection of shop/product functions class
require($BASEPATH.'/../lib/functions.php');
$FUNC = new functions;

$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
  trigger_error('PCRE version is out of date');

// load configuration
$f3->config('config.ini');
$f3->set('DATA', '../'.$f3->get('DATA') );
// get authentication levels
$AUTH = $f3->get('AUTH');
// get translation data
$LANG = $FUNC->update_array( $FUNC->get_json($f3->get('DATA').'translation.def'), $FUNC->get_json($f3->get('DATA').'translation.json') );
// load session data
$SESSION = $f3->get('SESSION');

// check if logged in and make sure autologout occurs after certain time
if(isset($SESSION['auth']) && isset($SESSION['authID']) && $SESSION['authID']==session_id().dirname($_SERVER['PHP_SELF']) && $SESSION['auth']>time()) {
  $SESSION['auth'] = time()+$f3->get('AUTOLOGOUT');

  /*
   *   PRODUCTS/MAIN CATEGORY
   */

  $f3->route('GET|POST /login',
    function($f3) {
      $f3->reroute('/');
    }
  );

  $f3->route('GET /',
    function($f3) {
      global $LANG,$AUTH,$SESSION,$FUNC;
      $f3->set('LANG',$LANG);
      $directories = array(
        $f3->get('DATA'),
        $f3->get('DATA').$f3->get('PRODUCTS'),
        $f3->get('DATA').$f3->get('VENDORS'),
        $f3->get('DATA').$f3->get('USERS'),
        $f3->get('DATA').$f3->get('ORDERS'),
        $f3->get('DATA').$f3->get('SETTLEMENTS')
      );
      $notify = $FUNC->notifywriteable($directories);
      $f3->set('sidebar',$f3->get('SIDEBAR'));
      $f3->set('sidebar_active',1);
      // set chart data: visitors
      $visitors = $FUNC->get_stat_visitors( $f3->get('DATA').$f3->get('STATISTICS') );
      $chart['visitors'] = $FUNC->chart_array($visitors);
      // set chart data: sales
      $sales = $FUNC->get_stat_sales( $f3->get('DATA').$f3->get('STATISTICS') );
      $chart['sales'] = $FUNC->chart_array($sales);
      // set chart data: popular
      $chart['mostsold'] = $FUNC->chart_stat_mostsold( $f3->get('DATA').$f3->get('PRODUCTS') );
      // set chart data: toptags
      $chart['toptags'] = $FUNC->chart_stat_toptags( $f3->get('DATA').$f3->get('STATISTICS') );
      // dump on screen :)
      $f3->set('chart',$chart);
      $f3->set('notify',$notify);
      $f3->set('content','dashboard.htm');
      $f3->set('AUTH',$AUTH);
      $f3->set('SESSION',$SESSION);
      echo View::instance()->render('backoffice.htm');
    }
  );

  $f3->route('GET /sort/@key/@page',
    function($f3,$params) {
      global $SESSION,$FUNC;
      $reroute = $params['page'];
      // DEBUG: echo $reroute;
      if($params['key']!='-') {
        $SESSION = $FUNC->setsorting($SESSION,$params['key']);
      } else {
        unset($SESSION['sort_key']);
        unset($SESSION['sort_asc']);
      }
      $f3->set('SESSION',$SESSION);
      $f3->reroute('/'.$reroute);
    }
  );

  if($AUTH['Orders']>=$SESSION['authrole']) {
    $f3->route('GET|POST /orders',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','orders');
        $notify = FALSE;
        $orders = $FUNC->get_orders( $f3->get('DATA').$f3->get('ORDERS'),$SESSION );
        $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        // dump on screen :)
        $f3->set('notify',$notify);
        $f3->set('orders',$orders);
        $f3->set('vendors',$vendors);
        $f3->set('shop',$FUNC);
        $f3->set('content','orders.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /order/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','orders');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json( $f3->get('DATA').'order.map' );
        // get inventory to show/edit the product
        $inventory = $FUNC->get_orders( $f3->get('DATA').$f3->get('ORDERS') );
        // get vendors to show from which vendor the order originated
        $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          // change time to epoch value
          $post['time']=strtotime((isset($post['time'])?str_replace('.','-',$post['time']):0));
          // catch the notransport checkbox
          $post['notransport']=(isset($post['notransport'])?1:0);
          switch($action) {
            case 'edit-order':
              $directory = $f3->get('DATA').$f3->get('ORDERS').$id;
              $inventory[$id] = $FUNC->update_array($inventory[$id],$post);
              $order[$id]=$inventory[$id];
              if(file_exists($directory.'.json')) {
                file_put_contents($directory.'.json',json_encode($order));
              } else {
                file_put_contents($directory.'.pending',json_encode($order));
              }
            break;
          }
        }
        // load our order
        $inventory[$id] = $FUNC->mapfill_array($map,$inventory[$id]);
        foreach($inventory[$id] as $key => $val) {
          $item[$key]=$val;
        }
        $f3->set('id',$id);
        $f3->set('json',$item);
        $f3->set('vendors',$vendors);
        $f3->set('shop',$FUNC);
        $f3->set('content','orderview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /order/ajax/@id/@set',
      function($f3,$params) {
        global $FUNC;
        $id = $params['id'];
        // get order and amend status entries
        $order = $FUNC->get_order( $f3->get('DATA').$f3->get('ORDERS'),$id );
        if($params['set']=='ordercomplete') {  // this is usually not fired off unless admin "presses" resend confirmation button
          $order[$id]['ordercomplete']=1;
          $users = array(); $users[] = array('e-mail'=>$order[$id]['email'],'receive_notifications'=>1);
          $array=$order[$id];
          $array['ordernumber']=$id;
          $FUNC->reporting($f3->get('DATA'),$array,$users,'order_complete');
        }
        if($params['set']=='orderpaid') {
          $order[$id]['orderstatus']=100; // set the status of the order as being complete (user ordered and paid for products)
          $order[$id]['orderpaid']=1;
          $users = array(); $users[] = array('e-mail'=>$order[$id]['email'],'receive_notifications'=>1);
          $array=$order[$id];
          $array['ordernumber']=$id;
          $FUNC->reporting($f3->get('DATA'),$array,$users,'order_paid');
        }
        if($params['set']=='ordersent') {
          $order[$id]['ordersent']=1;
          $users = array(); $users[] = array('e-mail'=>$order[$id]['email'],'receive_notifications'=>1);
          $array=$order[$id];
          $array['ordernumber']=$id;
          $FUNC->reporting($f3->get('DATA'),$array,$users,'order_sent');
        }
        $directory = $f3->get('DATA').$f3->get('ORDERS').$id;
        if(file_exists($directory.'.json')) {
          file_put_contents($directory.'.json',json_encode($order));
        } else {
          file_put_contents($directory.'.pending',json_encode($order));
        }
        // return status information
        echo $FUNC->order_status($order[$id],2);
      }
    );

    $f3->route('GET /order/edit/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','orders');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json( $f3->get('DATA').'order.map' );
        // get inventory to show/edit the product
        $inventory = $FUNC->get_orders( $f3->get('DATA').$f3->get('ORDERS') );
        $inventory[$id] = $FUNC->mapfill_array($map,$inventory[$id]);
        foreach($inventory[$id] as $key => $val) {
          $item[$key]=$val;
        }
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','order/'.$id);
        $f3->set('back','order/'.$id);
        $f3->set('action','edit-order');
        $f3->set('title','Edit order');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /order/delete/@id',
      function($f3,$params) {
        $id = $params['id'];
        $file = $f3->get('DATA').$f3->get('ORDERS').$id;
        if(file_exists($file.'.json')) {
          unlink($file.'.json');
        } else {
          if(file_exists($file.'.pending')) {
            unlink($file.'.pending');
          }
        }
        $f3->reroute('/orders');
      }
    );
  }

  if($AUTH['Products']>=$SESSION['authrole']) {
    $f3->route('GET|POST /products',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $notify['type'] = FALSE;
        $notify['message'] = '';
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','products');
        $inventory = $FUNC->get_products( $f3->get('DATA').$f3->get('PRODUCTS') ,$SESSION);
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          // empty modifier section is allowed
          if(!isset($post['modifiers'])) {
            $post['modifiers']=array();
          }
          // change time to epoch value
          $post['time']=strtotime((isset($post['time'])?str_replace('.','-',$post['time']):0));
          // split, sort tags and trim spaces
          $post['tags'] = $FUNC->trimsortstring($post['tags']);
          // scrounge numbers into metric grams
          $post['weight'] = preg_replace('/[^0-9]/','', $post['weight'])*(strpos(strtolower($post['weight']),'k')?1000:1)*(strpos(strtolower($post['weight']),'m')?0.001:1);
          switch($action) {
            case 'edit-product':
              if(!isset($inventory[$id])) { $inventory[$id]=array(); }
              $directory = $f3->get('DATA').$f3->get('PRODUCTS');
              // handle file uploads
              foreach($_FILES as $key => $val) {
                switch($key) {
                  case 'image': $val['savename']='image0'; break;
                  case 'detail_image_A': $val['savename']='image1'; break;
                  case 'detail_image_B': $val['savename']='image2'; break;
                }
                // check name and file size
                if ($val['name'] && $val['size'] > 1500000) {
                  $notify['type'] = 'warning';
                  $notify['message'] .= ' File '.basename($val['name']).' is too large.';
                  $uploadOk = FALSE;
                } else {
                  $uploadOk = TRUE;
                }
                if ($uploadOk) {
                  if(!file_exists($directory.$id)) {
                    mkdir($directory.$id);
                  }
                  if(file_exists($directory.$id.'/'.$val['savename'])) { rename($directory.$id.'/'.$val['savename'],$directory.$id.'/'.$val['savename'].'.bak'); }
                  if (move_uploaded_file($val['tmp_name'], $directory.$id.'/'.$val['savename'] )) {
                    shell_exec('rm '.$directory.$id.'/'.$val['savename'].'bak');
                    $inventory[$id][$key] = $val['savename'];
                    $notify['type'] = 'success';
                    $notify['message'] .= ' File '.basename($val['name']).' has been uploaded.';
                  } else {
                    if(file_exists($directory.$id.'/'.$val['savename'].'.bak')) { rename($directory.$id.'/'.$val['savename'].'.bak',$directory.$id.'/'.$val['savename']); }
                  }
                }
              }
              // write out the product data
              $inventory[$id] = $FUNC->update_array($inventory[$id],$post);
              $product[$id] = $inventory[$id]; // single out only one product to write back to disk
              $FUNC->put_products($directory,$product);
            break;
          }
        }
        // dump on screen :)
        $f3->set('notify',$notify);
        $f3->set('inventory',$inventory);
        $f3->set('content','products.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /product/new',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','products');
        $id = uniqid();
        // load product template and map...
        $def  = $FUNC->get_json( $f3->get('DATA').'product.def' );
        $map  = $FUNC->get_json( $f3->get('DATA').'product.map' );
        $json = $FUNC->get_json( $f3->get('DATA').'product.json' );
        $tuple = $FUNC->get_json( $f3->get('DATA').'modifiersmath.json' );
        $array['modifiers'] = $FUNC->tuple_to_array($tuple,'title');
        // pre-select modifiers that have 'default_on'
        foreach($tuple as $key => $val) {
          if(isset($val['default_active']) && $val['default_active']) {
            $json['modifiers'][] = $key;
          }
        }
        // get inventory to show/edit the product
        $item = $FUNC->mapfill_array($map,$json);
        $f3->set('id',$id);
        $f3->set('dir',$f3->get('DATA').$f3->get('PRODUCTS'));
        $f3->set('def',$def);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('array',$array);
        $f3->set('jump','products');
        $f3->set('back','products');
        $f3->set('action','edit-product');
        $f3->set('title','New product');
        $f3->set('paragraph','When adding a product, you can categorize it by adding tag words. Each category that has the same tags will list your product.');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /product/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','products');
        $id = $params['id'];
        // load product map...
        $def  = $FUNC->get_json( $f3->get('DATA').'product.def' );
        $map  = $FUNC->get_json( $f3->get('DATA').'product.map' );
        //$tuple = $FUNC->get_json( $f3->get('DATA').'transportmath.json' );
        //$array['transportmath'] = $FUNC->tuple_to_array($tuple,'title');
        $tuple = $FUNC->get_json( $f3->get('DATA').'modifiersmath.json' );
        $array['modifiers'] = $FUNC->tuple_to_array($tuple,'title');
        // get inventory to show/edit the product
        $inventory = $FUNC->get_products( $f3->get('DATA').$f3->get('PRODUCTS') );
        $tmp[$id] = $FUNC->mapfill_array($map,$inventory[$id]);
        $item = $tmp[$id];
        $f3->set('id',$id);
        $f3->set('dir',$f3->get('DATA').$f3->get('PRODUCTS'));
        $f3->set('def',$def);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('array',$array);
        $f3->set('jump','products');
        $f3->set('back','products');
        $f3->set('action','edit-product');
        $f3->set('title','Products');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /product/delete/@id',
      function($f3,$params) {
        $id = $params['id'];
        $directory = $f3->get('DATA').$f3->get('PRODUCTS').$id;
        shell_exec('rm -rf '.$directory);
        //unlink($directory.'/product.json');
        //rmdir($directory);
        $f3->reroute('/products');
      }
    );

    $f3->route('GET /product/stock/@id/@action',
      function($f3,$params) {
        global $FUNC;
        $id = $params['id'];
        $action = $params['action'];
        $directory = $f3->get('DATA').$f3->get('PRODUCTS');
        $array = $FUNC->get_stock($directory,$id);
        if($action=='add') {
          $array[$id]['stock']+=1;
        } else {
          if($array[$id]['stock']>-1) {
            $array[$id]['stock']-=1;
          } else {
            $array[$id]['stock']=0;
          }
        }
        $FUNC->put_stocks($directory,$array);
        echo ($array[$id]['stock']!=-1?$array[$id]['stock']:'∞');
      }
    );
  }

  if($AUTH['Categories']>=$SESSION['authrole']) {
    $f3->route('GET|POST /categories',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','categories');
        $inventory = $FUNC->get_json($f3->get('DATA').'categories.json');
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          if(isset($post['id'])) {
            $id = $post['id'];
            unset($post['id']);
          }
          // split, sort tags and trim spaces
          $post['tags'] = $FUNC->trimsortstring($post['tags']);
          // write out categories
          $inventory[$id] = $post;
          $FUNC->put_json($f3->get('DATA').'categories.json',$inventory);
        }
        // dump on screen :)
        $f3->set('inventory',$inventory);
        $f3->set('content','categories.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /category/new',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','categories');
        $id = uniqid();
        // load product template and map...
        $map = $FUNC->get_json($f3->get('DATA').'category.map');
        $json = $FUNC->get_json($f3->get('DATA').'category.json');
        $tmp[$id] = $FUNC->mapfill_array($map,$json);
        $item = $tmp[$id];
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','categories');
        $f3->set('action','edit-category');
        $f3->set('title','Create new category');
        $f3->set('content','editview.htm');
        $f3->set('paragraph','When adding a category, you can make it select products through tag words. Each product that has the same tags will be listed under this category.');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /category/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','categories');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json($f3->get('DATA').'category.map');
        // load product template and map...
        $item = $FUNC->get_json($f3->get('DATA').'category.json');
        $json = $FUNC->get_json($f3->get('DATA').'categories.json');
        $tmp[$id] = $FUNC->mapfill_array($map,$json[$id]);
        $item = $tmp[$id];
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','categories');
        $f3->set('action','edit-category');
        $f3->set('title','Edit category');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /category/delete/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $id = $params['id'];
        $json = $FUNC->get_json($f3->get('DATA').'categories.json');
        unset($json[$id]);
        $FUNC->put_json($f3->get('DATA').'categories.json',$json);
        $f3->reroute('/categories');
      }
    );

    $f3->route('GET /category/move/@id/@action',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $id = $params['id'];
        $action = $params['action'];
        $json = $FUNC->get_json($f3->get('DATA').'categories.json');
        $json = $FUNC->move_element_in_array($json,$action,$id);
        $FUNC->put_json($f3->get('DATA').'categories.json',$json);
        $f3->reroute('/categories');
      }
    );
  }

  if($AUTH['Vendors']>=$SESSION['authrole']) {
    $f3->route('GET|POST /vendors',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','vendors');
        $inventory = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        // get categories for dropdown list
        $categories = $FUNC->get_json( $f3->get('DATA').'categories.json' );
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          $directory = $f3->get('DATA').$f3->get('VENDORS').$id;
          // load template in case of new vendor
          if(!file_exists($directory)) {
            mkdir($directory);
            touch($directory.'/vendor.json');
            $inventory[$id] = $FUNC->get_json( $f3->get('DATA').'vendor.json' );
          }
          $inventory[$id] = $FUNC->update_array($inventory[$id],$post);
          $vendor[$id]=$inventory[$id];
          file_put_contents($directory.'/vendor.json',json_encode($vendor));
        }
        // dump on screen :)
        $f3->set('categories',$categories);
        $f3->set('inventory',$inventory);
        $f3->set('content','vendors.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /vendor/new',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','vendors');
        $id = uniqid();
        // get categories for dropdown list
        if(file_exists($f3->get('DATA').'categories.json')) {
           $tuple = $FUNC->get_json( $f3->get('DATA').'categories.json' );
           $array['categories'] = $FUNC->tuple_to_array($tuple,'name');
        } else {
          $array['categories'] = array();
        }
        // load product template and map...
        $map  = $FUNC->get_json( $f3->get('DATA').'vendor.map' );
        $json = $FUNC->get_json( $f3->get('DATA').'vendor.json' );
        $json = $FUNC->mapfill_array($map,$json);
        // get modifiers list
        $tuple = $FUNC->get_json( $f3->get('DATA').'modifiersmath.json' );
        $array['modifiers'] = $FUNC->tuple_to_array($tuple,'title');
        // remove only-local modifiers and pre-select modifiers that have 'default_on'
        foreach($tuple as $key => $val) {
          if(!(isset($val['enabled_locally']) && $val['enabled_locally'])) {
            unset($array['modifiers'][$key]);
          }
          if(isset($val['default_active']) && $val['default_active']) {
            $json['apply_local_modifiers'][] = $key;
          }
        }
        // prepare item for editview
        $item['id']=$id;
        foreach($json as $key => $val) {
          $item[$key] = $val;
        }
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('array',$array);
        $f3->set('jump','vendor/'.$id);
        $f3->set('back','vendors');
        $f3->set('action','edit-vendor');
        $f3->set('title','Add vendor');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /vendor/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','vendors');
        $id = $params['id'];
        // get modifiers list
        $tuple = $FUNC->get_json( $f3->get('DATA').'modifiersmath.json' );
        $array['modifiers'] = $FUNC->tuple_to_array($tuple,'title');
        // remove only-local modifiers
        foreach($tuple as $key => $val) {
          if(!(isset($val['enabled_locally']) && $val['enabled_locally'])) {
            unset($array['modifiers'][$key]);
          }
        }
        // get categories for dropdown list
        if(file_exists($f3->get('DATA').'categories.json')) {
           $tuple = $FUNC->get_json( $f3->get('DATA').'categories.json' );
           $array['categories'] = $FUNC->tuple_to_array($tuple,'name');
        } else {
          $array['categories'] = array();
        }
        // load product map...
        $map  = $FUNC->get_json( $f3->get('DATA').'vendor.map' );
        // get inventory to show/edit the product
        $inventory = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          $directory = $f3->get('DATA').$f3->get('VENDORS').$id;
          // load template in case of new vendor
          if(!file_exists($directory)) {
            mkdir($directory);
            touch($directory.'/vendor.json');
            $inventory[$id] = $FUNC->get_json( $f3->get('DATA').'vendor.json' );
          }
          $inventory[$id] = $FUNC->update_array($inventory[$id],$post);
          $vendor[$id]=$inventory[$id];
          file_put_contents($directory.'/vendor.json',json_encode($vendor));
        }
        foreach($inventory[$id] as $key => $val) {
          $item[$key]=$val;
        }
        $item = $FUNC->mapfill_array($map,$item);
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('array',$array);
        $f3->set('jump','vendor/'.$id);
        $f3->set('back','vendors');
        $f3->set('action','edit-vendor');
        $f3->set('title','Vendor');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /vendor/delete/@id',
      function($f3,$params) {
        $id = $params['id'];
        $directory = $f3->get('DATA').$f3->get('VENDORS').$id;
        unlink($directory.'/vendor.json');
        rmdir($directory);
        $f3->reroute('/vendors');
      }
    );

    $f3->route('GET /vendor/ajax/status/@vendorid',
      function($f3,$params) {
        global $LANG,$FUNC;
        $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        $vendorid = $params['vendorid'];
        if(isset($vendors[ $vendorid ])) {
          // send remote order
          $hash = crc32($vendors[ $vendorid ]['secret']);
          // request list of shared categories
          $query = $FUNC->tx( array($hash,'vnd') );
          // settlement and total amount is returned and added to settlements list
          if($vendors[ $vendorid ]['host'] && $tmp = file_get_contents( $vendors[ $vendorid ]['host'].'?q='.$query )) {
            $result = $FUNC->rx($tmp,$vendors[ $vendorid ]['secret']);
          } else { $result = FALSE; }
        } else { $result = FALSE; }
        if($result && $result[0]) {
          echo '<span style="color: green;">✓</span> '.$LANG['vendors:connected-message'].'<br>';
          if(!empty($result[1])) {
            foreach(explode(',',$result[1]) as $tag) {
              $tagshtml[]='<a target="_blank" href="'.$vendors[ $vendorid ]['host'].'/category-'.$tag.'">'.$tag.'</a>';
            }
            echo '<span style="color: gray;">'.$LANG['vendors:shared-categories-tags'].':</span> '.implode(', ',$tagshtml);
          } else {
            echo '<span style="color: gray;">'.$LANG['vendors:no-shares-message'].'</span>';
          }
        } else {
          echo '<span style="color: gray;">'.$LANG['vendors:not-connected-message'].'</span>';
        }

      }
    );
  }

  if($AUTH['Settlements']>=$SESSION['authrole']) {
    $f3->route('GET|POST /settlements',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','settlements');
        $SET = $FUNC->get_json( $f3->get('DATA').'settings.json' );
        $settlements = $FUNC->get_settlements( $f3->get('DATA').$f3->get('SETTLEMENTS') );
        $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          $vendorid = $post['vendor'];
          unset($post['vendor']);
          $post['orderid'] = $id;
          // change time to epoch value
          $post['time']=strtotime((isset($post['time'])?str_replace('.','-',$post['time']):0));
          if(!isset($post['send']) || !$post['send']) {
            $post['settlement']=0-$post['settlement'];
          }
          // write out settlement
          $directory = $f3->get('DATA').$f3->get('SETTLEMENTS').$vendorid;
          if(!file_exists($directory)) { mkdir($directory); }
          $settlements[$vendorid][$id]=$post;
          $settlement[$vendorid]=$post;
          $FUNC->put_json($directory.'/'.$id.'.json',$settlement);
          // send settlement data to remote vendor
          $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
          if(isset($vendors[ $vendorid ]['secret'])) {  // test if valid vendor is selected
              // send remote order
              $hash = crc32($vendors[ $vendorid ]['secret']);
              // settlement and total amount is sent and result is returned
              $query = $FUNC->tx( array($hash,'clr',$post) );
              $result = $FUNC->rx(file_get_contents( $vendors[ $vendorid ]['host'].'?q='.$query ),$vendors[ $vendorid ]['secret']);
          }
        }
        // calculate the totals of our settlements
        $inventory = array();
        $entries = 0;
        foreach($settlements as $vendorid => $settlement) {
          $inventory = $FUNC->calc_settlements_totals($vendorid,$settlement,$inventory,$SESSION);
          $entries++;
        }
        // dump on screen :)
        $f3->set('entries',$entries);
        $f3->set('vendors',$vendors);
        $f3->set('inventory',$inventory);
        $f3->set('content','settlements.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /settlements/@id',
      function($f3,$params) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','settlements');
        // handle actions (cloned)
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          $vendorid = $post['vendor'];
          unset($post['vendor']);
          $post['orderid'] = $id;
          // change time to epoch value
          $post['time']=strtotime((isset($post['time'])?str_replace('.','-',$post['time']):0));
          if(!isset($post['send']) || !$post['send']) {
            $post['settlement']=0-$post['settlement'];
          }
          // write out settlement
          $directory = $f3->get('DATA').$f3->get('SETTLEMENTS').$vendorid;
          if(!file_exists($directory)) { mkdir($directory); }
          $settlements[$vendorid][$id]=$post;
          $settlement[$vendorid]=$post;
          $FUNC->put_json($directory.'/'.$id.'.json',$settlement);
          // send settlement data to remote vendor
          $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
          if(isset($vendors[ $vendorid ]['secret'])) {  // test if valid vendor is selected
              // send remote order
              $hash = crc32($vendors[ $vendorid ]['secret']);
              // settlement and total amount is sent and result is returned
              $query = $FUNC->tx( array($hash,'clr',$post) );
              $result = $FUNC->rx(file_get_contents( $vendors[ $vendorid ]['host'].'?q='.$query ),$vendors[ $vendorid ]['secret']);
          }
        }
        // build settlements totals and get all settlements for this vendorid
        $id = $params['id'];
        $inventory = $FUNC->get_settlements( $f3->get('DATA').$f3->get('SETTLEMENTS'), $id);
        // sort orders if session variables are passed (backend related!)
        if($SESSION) {
          $sort_key = (isset($SESSION['sort_key'])?$SESSION['sort_key']:'time');
          $sort_asc = (isset($SESSION['sort_asc'])?$SESSION['sort_asc']:TRUE);
          $inventory[$id] = $FUNC->deepsort($inventory[$id],$sort_key,$sort_asc);
        }
        // calculate total balance
        $totals = $FUNC->calc_settlements_totals($id,$inventory[$id]);
        $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        $f3->set('id',$id);
        $f3->set('vendors',$vendors);
        $f3->set('totals',$totals);
        $f3->set('inventory',$inventory);
        $f3->set('content','settlements-details.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->redirect('GET|HEAD /settlement/new', '/settlement/new/0');
    $f3->route('GET /settlement/new/@id*',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SIDEBAR'));
        $f3->set('sidebar_active','settlements');
        $id = 'X'.uniqid();
        $vendorid = $params['id'];
        // load product template and map...
        $map  = $FUNC->get_json( $f3->get('DATA').'settlement.map' );
        $json = $FUNC->get_json( $f3->get('DATA').'settlement.json' );
        $json['vendorid'] = $vendorid; // pre-select vendor if editing from detailed view
        $vendors = $FUNC->get_vendors( $f3->get('DATA').$f3->get('VENDORS') );
        $array['vendors'] = $FUNC->tuple_to_array($vendors,'name');
        $item['id']=$id;
        foreach($json as $key => $val) {
          $item[$key] = $val;
        }
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('array',$array);
        $f3->set('jump','settlements'.($vendorid?'/'.$vendorid:''));
        $f3->set('back','settlements'.($vendorid?'/'.$vendorid:''));
        $f3->set('action','edit-settlement');
        $f3->set('title','Add clearing');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settlement/delete/@vendorid/@orderid',
      function($f3,$params) {
        $vendorid = $params['vendorid'];
        $orderid = $params['orderid'];
        $directory = $f3->get('DATA').$f3->get('SETTLEMENTS').$vendorid;
        unlink($directory.'/'.$orderid.'.json');
        $f3->reroute('/settlements'.($vendorid?'/'.$vendorid:''));
      }
    );
  }

  /*
   *   SETTINGS CATEGORY
   */

  if($AUTH['Settings']>=$SESSION['authrole']) {
    $f3->route('GET|POST /settings',
      function($f3) {
        global $LANG,$AUTH,$SESSION,$FUNC;        
        $f3->set('LANG',$LANG);
        if($AUTH['Settings']<=$SESSION['authrole']) { $f3->reroute('/settings/reporting'); }
        $notify['type'] = FALSE;
        $notify['message'] = '';
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings');
        // load settings...
        $def  = $FUNC->get_json( $f3->get('DATA').'settings.def' );
        $map  = $FUNC->get_json( $f3->get('DATA').'settings.map' );
        $json = $FUNC->get_json( $f3->get('DATA').'settings.json' );
        if(count($json)) {
          $json = $FUNC->mapfill_array($map,$json);
        } else {
          $json = $FUNC->mapfill_array($map,$def);
          $json['shopmainlogo'] = '';
          $json['shopbackdrop'] = '';
        }
        // get themes for dropdown list
        $handle = opendir($f3->get('DATA').$f3->get('THEMES'));
        $array['themes'] = array();
        while ($file = readdir($handle)) if (!in_array($file, array('.', '..','README.md'))) {
          $array['themes'][$file] = ucwords($file);
        }
        // get categories for dropdown list
        if(file_exists($f3->get('DATA').'categories.json')) {
           $tuple = $FUNC->get_json( $f3->get('DATA').'categories.json' );
           $array['categories'] = $FUNC->tuple_to_array($tuple,'name');
        } else {
          $array['categories'] = array();
        }
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          switch($action) {
            case 'edit-settings':
              $directory = $f3->get('DATA');
              // handle file uploads
              foreach($_FILES as $key => $val) {
                $uploadOk = TRUE;
                 // Check name and file size
                if (!$val['name'] || $val['size'] > 500000) {
                  $notify['type'] = 'warning';
                  $notify['message'] .= ' File '.basename($val['name']).' is too large.';
                  $uploadOk = FALSE;
                }
                if ($uploadOk) {
                  shell_exec('rm '.$directory.$json[$key]);
                  if (move_uploaded_file($val['tmp_name'], $directory.basename($val['name']) )) {
                    $json[$key] = $val['name'];
                    $notify['type'] = 'success';
                    $notify['message'] .= ' File '.basename($val['name']).' has been uploaded.';
                  } else {
                    $json[$key] = '';
                    $notify['type'] = 'warning';
                    $notify['message'] .= ' File '.basename($val['name']).' was not uploaded!';
                  }
                }
              }
              if(!isset($post['shopslider'])) { $post['shopslider']=FALSE; }
              $json = $FUNC->update_array($json,$post);
              file_put_contents($directory.'settings.json',json_encode($json));
            break;
          }
        }
        $f3->set('id',FALSE);
        $f3->set('dir',$f3->get('DATA'));
        $f3->set('def',$def);
        $f3->set('map',$map);
        $f3->set('json',$json);
        $f3->set('array',$array);
        $f3->set('jump','');
        $f3->set('action','edit-settings');
        $f3->set('title','General');
        $f3->set('paragraph','These global settings change properties, style and content to customize your Peer2Product webshop.');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /settings/reporting',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/reporting');
        // load settings...
        $map  = $FUNC->get_json( $f3->get('DATA').'reporting.map' );
        $json = $FUNC->get_json( $f3->get('DATA').'reporting.json' );
        $json = $FUNC->mapfill_array($map,$json);
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          if($post['smtp_pass']=='#####') {
            $post['smtp_pass'] = $json['smtp_pass'];
          }
          $directory = $f3->get('DATA');
          $FUNC->put_json($directory.'reporting.json',$post);
          // TODO: send a test-email when requested in the UI: $FUNC->reporting($f3->get('DATA'),$array,$users,'test_mail');
        }
        $f3->set('id',FALSE);
        $f3->set('dir',$f3->get('DATA'));
        $f3->set('map',$map);
        $f3->set('json',$json);
        $f3->set('jump','');
        $f3->set('action','edit-reporting');
        $f3->set('title','Reporting');
        $f3->set('paragraph','Below is the configuration for e-mail and reports being sent from this webshop. You can use the replacement variables <i>{ordernumber}, {shopname}, {user}, {address}, {product-table}, {date}, {duedate}</i> in messages for clients.');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /settings/modifiers',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/modifiers');
        $inventory = $FUNC->get_json($f3->get('DATA').'modifiersmath.json');
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          $item[$id]=$post;
          $inventory[$id] = $item[$id];
          $FUNC->put_json($f3->get('DATA').'modifiersmath.json',$inventory);
        }
        // dump on screen :)
        $f3->set('inventory',$inventory);
        $f3->set('content','settings-modifiers.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/modifier/new',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/modifiers');
        $id = uniqid();
        // load product template and map...
        $map = $FUNC->get_json($f3->get('DATA').'modifiersrule.map');
        $json = $FUNC->get_json($f3->get('DATA').'modifiersrule.json');
        $item['id']=$id;
        foreach($json['id'] as $key => $val) {
          $item[$key] = $val;
        }
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/modifiers');
        $f3->set('action','edit-modifier');
        $f3->set('paragraph','The variable <i>$result</i> holds the final amount to add to, or subtract from, the price. The result can also be used to transfer data between rules. Variables like <i>$price</i>, <i>$weight</i>, <i>$size</i> and <i>$quantity</i> may be used to influence this result. Feel free to use PHP code to make complex formula\'s if you need them. Setting default active will enable this rule for every new product you add.<br><br>Example formula for adding a 21% tax: <i>$result = $price*0.21;</i>');
        $f3->set('title','Create new modifier rule');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/modifier/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/modifiers');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json($f3->get('DATA').'modifiersrule.map');
        // get inventory to show/edit the modifiers
        $json = $FUNC->get_json($f3->get('DATA').'modifiersmath.json');
        $json[$id] = $FUNC->mapfill_array($map,$json[$id]);
        foreach($json[$id] as $key => $val) {
          $item[$key] = $val;
        }
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/modifiers');
        $f3->set('action','edit-modifier');
        $f3->set('paragraph','The variable <i>$result</i> holds the final amount to add to, or subtract from, the price. The result can also be used to transfer data between rules. Variables like <i>$price</i>, <i>$weight</i>, <i>$size</i> and <i>$quantity</i> may be used to influence this result. Feel free to use PHP code to make complex formula\'s if you need them. Setting default active will enable this rule for every new product you add.<br><br>Example formula for adding a 21% tax: <i>$result = $price*0.21;</i>');
        $f3->set('title','Edit modifier rule');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/modifier/delete/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $id = $params['id'];
        $json = $FUNC->get_json($f3->get('DATA').'modifiersmath.json');
        unset($json[$id]);
        $FUNC->put_json($f3->get('DATA').'modifiersmath.json',$json);
        $f3->reroute('/settings/modifiers');
      }
    );

    $f3->route('GET /settings/modifier/move/@id/@action',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $id = $params['id'];
        $action = $params['action'];
        $json = $FUNC->get_json($f3->get('DATA').'modifiersmath.json');
        $json = $FUNC->move_element_in_array($json,$action,$id);
        $FUNC->put_json($f3->get('DATA').'modifiersmath.json',$json);
        $f3->reroute('/settings/modifiers');
      }
    );

    $f3->route('GET|POST /settings/transport',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/transport');
        $inventory = $FUNC->get_json($f3->get('DATA').'transportmath.json');
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          $item[$id]=$post;
          $inventory[$id] = $item[$id];
          $FUNC->put_json($f3->get('DATA').'transportmath.json',$inventory);
        }
        // dump on screen :)
        $f3->set('inventory',$inventory);
        $f3->set('content','settings-transport.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/transport/new',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/transport');
        $id = uniqid();
        // load product template and map...
        $map = $FUNC->get_json($f3->get('DATA').'transportrule.map');
        $json = $FUNC->get_json($f3->get('DATA').'transportrule.json');
        $item['id']=$id;
        foreach($json['id'] as $key => $val) {
          $item[$key] = $val;
        }
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/transport');
        $f3->set('action','edit-transport');
        $f3->set('paragraph','The variable <i>$result</i> holds the final price, but can also be used to transfer data between rules. Variables like <i>$weight</i>, <i>$size</i>, <i>$minsize</i>, <i>$maxsize</i> and <i>$quantity</i> may be used to influence this result. Feel free to use PHP code to make complex formula\'s if you need them. Other variables that are available are <i>$company, $housenumber, $street, $city, $countrycode</i>, and <i>$zipcode</i>. <br><br>Example formula: <i>if ($weight>3) { $result=$weight*0.25; } else { $result=$weight*0.33; }</i>');
        $f3->set('title','Create new transport rule');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/transport/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/transport');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json($f3->get('DATA').'transportrule.map');
        // get inventory to show/edit the users
        $json = $FUNC->get_json($f3->get('DATA').'transportmath.json');
        foreach($json[$id] as $key => $val) {
          $item[$key] = $val;
        }
        //$item[$id] = $FUNC->fill_item_from_array($temp['id'],$json[$id],$id);
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/transport');
        $f3->set('action','edit-transport');
        $f3->set('paragraph','The variable <i>$result</i> holds the final price, but can also be used to transfer data between rules. Variables like <i>$weight</i>, <i>$size</i>, <i>$minsize</i>, <i>$maxsize</i> and <i>$quantity</i> may be used to influence this result. Feel free to use PHP code to make complex formula\'s if you need them. Other variables that are available are <i>$company, $housenumber, $street, $city, $countrycode</i>, and <i>$zipcode</i>. <br><br>Example formula: <i>if ($weight>3) { $result=$weight*0.25; } else { $result=$weight*0.33; }</i>');
        $f3->set('title','Edit transport rule');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/transport/delete/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $id = $params['id'];
        $json = $FUNC->get_json($f3->get('DATA').'transportmath.json');
        unset($json[$id]);
        $FUNC->put_json($f3->get('DATA').'transportmath.json',$json);
        $f3->reroute('/settings/transport');
      }
    );

    $f3->route('GET /settings/transport/move/@id/@action',
      function($f3,$params) {
        global $FUNC;
        $id = $params['id'];
        $action = $params['action'];
        $json = $FUNC->get_json($f3->get('DATA').'transportmath.json');
        $json = $FUNC->move_element_in_array($json,$action,$id);
        $FUNC->put_json($f3->get('DATA').'transportmath.json',$json);
        $f3->reroute('/settings/transport');
      }
    );

    $f3->route('GET|POST /settings/countries',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/countries');
        $inventory = $FUNC->get_json($f3->get('DATA').'countries.json');
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          if(isset($post['id'])) {
            unset($inventory[$post['id']]);
            unset($post['id']);
          }
          $item=array();
          foreach($post as $key => $val) {
            $item[]=$val;
          }
          $inventory[strtolower($item[1])] = ucfirst($item[0]);
          $FUNC->put_json($f3->get('DATA').'countries.json',$inventory);
        }
        // dump on screen :)
        $f3->set('inventory',$inventory);
        $f3->set('content','settings-countries.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/country/new',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/countries');
        // load product template and map...
        $map = $FUNC->get_json($f3->get('DATA').'country.map');
        $json = $FUNC->get_json($f3->get('DATA').'country.json');
        $f3->set('map',$map);
        $f3->set('json',$json);
        $f3->set('jump','settings/countries');
        $f3->set('action','edit-country');
        $f3->set('title','Create new country entry');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/country/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/countries');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json($f3->get('DATA').'country.map');
        // get inventory to show/edit the users
        $item = $FUNC->get_json($f3->get('DATA').'country.json');
        $json = $FUNC->get_json($f3->get('DATA').'countries.json');
        $item = $FUNC->fill_item_from_array($item,$json,$id);
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/countries');
        $f3->set('back','settings/countries');
        $f3->set('action','edit-country');
        $f3->set('title','Edit country');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/country/delete/@id',
      function($f3,$params) {
        global $FUNC;
        $id = $params['id'];
        $json = $FUNC->get_json($f3->get('DATA').'countries.json');
        unset($json[$id]);
        $FUNC->put_json($f3->get('DATA').'countries.json',$json);
        $f3->reroute('/settings/countries');
      }
    );

    $f3->route('GET /settings/country/move/@id/@action',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $id = $params['id'];
        $action = $params['action'];
        $json = $FUNC->get_json($f3->get('DATA').'countries.json');
        $json = $FUNC->move_element_in_array($json,$action,$id);
        $FUNC->put_json($f3->get('DATA').'countries.json',$json);
        $f3->reroute('/settings/countries');
      }
    );

    $f3->route('GET|POST /settings/gateways',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/gateways');
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          if(!isset($post['gateway_active'])) { $post['gateway_active']=0; }
          $FUNC->put_json($f3->get('DATA').$f3->get('GATEWAYS').$id.'/gateway.json',$post);
        }
        // dump on screen :)
        $inventory = $FUNC->get_gateways( $f3->get('DATA').$f3->get('GATEWAYS') );
        $f3->set('inventory',$inventory);
        $f3->set('content','settings-gateways.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/gateway/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/gateways');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json($f3->get('DATA').$f3->get('GATEWAYS').$id.'/gateway.map');
        // get data to show/edit the gateway configuration
        $item = $FUNC->get_json($f3->get('DATA').$f3->get('GATEWAYS').$id.'/gateway.json');
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/gateways');
        $f3->set('back','settings/gateways');
        $f3->set('action','edit-gateway');
        $f3->set('title','Configure gateway');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET|POST /settings/users',
      function($f3) {
        global $LANG,$SESSION,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/users');
        $inventory = $FUNC->get_users( $f3->get('DATA').$f3->get('USERS') );
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          if($id=='0admin') { $post['role']='administrator'; }
          unset($post['id']);
          switch($action) {
            case 'edit-user':
              // hash the password
              if($post['password']!='#####') {
                $post['password']=hash('sha256',$post['password'].$f3->get('SALT'));
              } else {
                unset($post['password']);
              }
              $directory = $f3->get('DATA').$f3->get('USERS').$id;
              // load template in case of new vendor
              if(!file_exists($directory)) {
                mkdir($directory);
                touch($directory.'/user.json');
                $inventory[$id] = $FUNC->get_json( $f3->get('DATA').'user.json' );
              }
              $inventory[$id] = $FUNC->update_array($inventory[$id],$post);
              $user[$id]=$inventory[$id];
              file_put_contents($directory.'/user.json',json_encode($user));
            break;
          }
        }
        // dump on screen :)
        $f3->set('inventory',$inventory);
        $f3->set('content','settings-users.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/user/new',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/users');
        $id = uniqid();
        // load product template and map...
        $map  = $FUNC->get_json( $f3->get('DATA').'user.map' );
        $json = $FUNC->get_json( $f3->get('DATA').'user.json' );
        $item['id']=$id;
        foreach($json as $key => $val) {
          $item[$key] = $val;
        }
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/users');
        $f3->set('action','edit-user');
        $f3->set('title','Create new user');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/user/@id',
      function($f3,$params) {
        global $LANG,$FUNC;
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/users');
        $id = $params['id'];
        // load product map...
        $map  = $FUNC->get_json( $f3->get('DATA').'user.map' );
        $json  = $FUNC->get_json( $f3->get('DATA').'user.json' );
        // get inventory to show/edit the users
        $inventory = $FUNC->get_users( $f3->get('DATA').$f3->get('USERS') );
        foreach($inventory[$id] as $key => $val) {
          $item[$key]=$val;
        }
        $item = $FUNC->update_array($json,$item);
        $f3->set('id',$id);
        $f3->set('map',$map);
        $f3->set('json',$item);
        $f3->set('jump','settings/users');
        $f3->set('action','edit-user');
        $f3->set('title','Edit user');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

    $f3->route('GET /settings/user/delete/@id',
      function($f3,$params) {
        $id = $params['id'];
        $directory = $f3->get('DATA').$f3->get('USERS').$id;
        unlink($directory.'/user.json');
        rmdir($directory);
        $f3->reroute('/settings/users');
      }
    );

    $f3->route('GET|POST /settings/translate',
      function($f3) {
        global $LANG,$AUTH,$SESSION,$FUNC;        
        $f3->set('LANG',$LANG);
        $f3->set('sidebar',$f3->get('SETTINGSBAR'));
        $f3->set('sidebar_active','settings/translate');
        // load frontend translation... set all mappings as string
        $json = $FUNC->update_array( $FUNC->get_json($f3->get('DATA').'translation.def'), $FUNC->get_json($f3->get('DATA').'translation.json') );

        $map = array();
        foreach($json as $key => $val) {
          $map[$key] = 'string';
        }
        // handle actions
        $post = $f3->get('POST');
        if(isset($post['action'])) {
          $action = $post['action'];
          unset($post['action']);
          $id = $post['id'];
          unset($post['id']);
          $directory = $f3->get('DATA');
          $jsonWrite = $FUNC->diff_array( $FUNC->get_json($f3->get('DATA').'translation.def') , $post);
          file_put_contents($directory.'translation.json',json_encode($jsonWrite));
          // again update our json object to reflect changes made in front-end
          $json = $FUNC->update_array($json,$post);
        }
        $f3->set('id',FALSE);
        $f3->set('dir',$f3->get('DATA'));
        $f3->set('map',$json);
        $f3->set('json',$json);
        $f3->set('jump','');
        $f3->set('action','edit-translate');
        $f3->set('title','Translate');
        $f3->set('paragraph','The front-end of your webshop can be customized or translated into another language. Each string below represents part of the frontend of the store.');
        $f3->set('content','editview.htm');
        echo View::instance()->render('backoffice.htm');
      }
    );

  }

  $f3->route('GET /logout',
    function($f3,$params) {
      $SESSION = $f3->get('SESSION');
      unset($SESSION['auth']);
      unset($SESSION['authID']);
      $f3->set('SESSION',$SESSION);
      $f3->reroute('/login');
    }
  );

} else {

  $f3->redirect('GET|HEAD /', '/login');

  $f3->route('GET|POST /@url*',
    function($f3,$params) {
      global $LANG,$FUNC;
      $f3->set('LANG',$LANG);
      switch($params['url']) {
        case 'login':
          $auth = $f3->get('POST');
          if( isset($auth['username']) && isset($auth['password']) ) {
            // get inventory to show/edit the users
            $inventory = $FUNC->get_users( $f3->get('DATA').$f3->get('USERS') );
            $users = array();
            foreach($inventory as $id => $val) {
              if (!is_numeric($val['role'])) $val['role']=0; // default to admin role if input is non-numeric
              $users[$val['username']] = $val['password'];
              $roles[$val['username']] = $val['role'];
            }
            if( array_key_exists($auth['username'],$users) && hash('sha256',$auth['password'].$f3->get('SALT')) == $users[ $auth['username'] ]) {
              $SESSION = $f3->get('SESSION');
              $SESSION['auth'] = time()+$f3->get('AUTOLOGOUT');
              $SESSION['authID'] = session_id().dirname($_SERVER['PHP_SELF']);
              $SESSION['authrole'] = $roles[ $auth['username'] ];
              $f3->set('SESSION',$SESSION);
              $f3->reroute('/');
            } else {
              echo '<div style="text-align: center; color: red; margin: 48px;">'.$LANG['login:bad-login-message'].'</div>';
              // DEBUG: echo hash('sha256',$auth['password'].$f3->get('SALT')) .'=='. $users[ $auth['username'] ];
            }
          }
          $f3->set('content','login.htm');
          echo View::instance()->render('layout.htm');
        break;
        case 'logout':
          $SESSION = $f3->get('SESSION');
          unset($SESSION['auth']);
          $f3->set('SESSION',$SESSION);
        default:
          // redirect any junk...
          $f3->reroute('/login');
      }
    }
  );

}

$f3->run();

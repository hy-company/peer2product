<?php
// some hardcoded settings...
$TESTMODE = FALSE;

$DEBUG = FALSE;
error_reporting($DEBUG || $TESTMODE ? E_ERROR | E_WARNING | E_PARSE : 0);

// track order through $_POST['x'] array
try {
  // normal step sequence
  if (isset($_POST['x'])) {
    $array=$shop->rx($_POST['x']);
  } else {
    // on return from payment gateway, restore order from file storage
    if(file_exists($SET['data/'].$SET['ordr/'].$_GET['x'].'.pending')) {
      $input=$shop->get_json( $SET['data/'].$SET['ordr/'].$_GET['x'].'.pending' );
      $array=$input[$_GET['x']];
      $array['ordernumber'] = $_GET['x'];
    }
  }
} catch(Exception $e) {
  // No cart data, return to siteroot.
  siteroot();
}

// HACK: gateway forwards user to payment gateway if $array['gateway'] and if $_POST['submit_payment_method'] is true
if(isset($_POST['gateway'])) {
  $array['gateway']=$_POST['gateway'];
}
if(isset($array['gateway'])) {
  if (isset($_SERVER['HTTPS']) &&
  ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
  isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
  $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $protocol = 'https://';
  }
  else {
    $protocol = 'http://';
  }
  $GATEWAY['host']=$protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
  $GATEWAY['directory']=$SET['data/'].$SET['gate/'].$array['gateway'].'/';
  include($GATEWAY['directory'].'gateway.php');
}

// give a shopper back his old data at checkout when roaming website
if (!isset($array['ordernumber'])) {
  if(isset($_SESSION['ordernumber'])) {
    // retrieve old ordernumber from active session
    // load order data into array
    if(file_exists($SET['data/'].$SET['ordr/'].$_SESSION['ordernumber'].'.pending')) {
      $input=$shop->get_json( $SET['data/'].$SET['ordr/'].$_SESSION['ordernumber'].'.pending' );
      $array=$input[$_SESSION['ordernumber']];
      unset($array['sequence']);
    } else if (file_exists($SET['data/'].$SET['ordr/'].$_SESSION['ordernumber'].'.json')) {
      $input=$shop->get_json( $SET['data/'].$SET['ordr/'].$_SESSION['ordernumber'].'.json' );
      $array=$input[$_SESSION['ordernumber']];
      unset($array['sequence']);
    }
    $array['ordernumber'] = $_SESSION['ordernumber'];
  } else {
    // create new ordernumber
    $array['ordernumber'] = uniqid();
    $_SESSION['ordernumber'] = $array['ordernumber'];
  }
}

if (!isset($array['sequence'])) {
  $array['sequence']=0;
} else {
  if(!empty($_REQUEST['back'])) {
    $array['sequence']=$array['sequence']-1;
  } else {
    $array['sequence']=$array['sequence']+1;
  }
}

// have we returned from an order? route to evaluation of the orderstatus
if (isset($_GET['s'])) {
  $array['sequence'] = 4;

  $array['orderstatus'] = $array['orderstatus']?$array['orderstatus']:0;

  if($_GET['s']>$array['orderstatus']) {
    $array['orderstatus'] = $_GET['s'];
  }
}

function paymentnav($tx,$next = FALSE,$back = FALSE,$enabled = TRUE) {
  global $STR;
  if(!$next) { $next = $STR['Next']; }
  if(!$back) { $back = $STR['Back']; }
  return '    <div id="paymentnav">
            <input type="hidden" name="x" value="'.$tx.'"/>
            <input class="back btn btn-'.($enabled?'danger" type="submit':'disabled" style="color: white;" type="button').'" name="back" value="&lt;&nbsp; '.$back.'"/>
            <input class="submit btn btn-success" type="submit" name="next" value="'.$next.' &nbsp;&gt;" style="float: right;" />
          </div>';
}

switch($array['sequence']) {
  case -1:
    // store any user data before going back
    if(!empty($_REQUEST['back'])) {
        $array['firstname']=$_POST['firstname'];
        $array['preposition']=$_POST['preposition'];
        $array['lastname']=$_POST['lastname'];
        $array['company']=$_POST['company'];
        $array['taxnumber']=$_POST['taxnumber'];
        $array['streetname']=$_POST['streetname'];
        $array['housenumber']=$_POST['housenumber'];
        $array['zipcode']=$_POST['zipcode'];
        $array['city']=$_POST['city'];
        $array['country']=$_POST['country'];
        $array['telephone']=$_POST['telephone'];
        $array['email']=$_POST['email'];
        $array['remarks']=$_POST['remarks'];
        $array['notransport']=(isset($_POST['notransport'])?1:0);
    }
    // set sequence of order to zero
    $array['sequence']=0;
    // store order in file
    $array['time'] = time(); // add time of order creation
    $orderid = $array['ordernumber'];
    $output[$orderid] = $array;
    unset($output[$orderid]['ordernumber']);
    file_put_contents($SET['data/'].$SET['ordr/'].$orderid.'.pending',json_encode($output));
    // redirect to siteroot
    $shop->siteroot();
  break;
  // STEP 1: ENTER ADDRESS
  case 0:
    $countries=$shop->get_json($SET['data/'].'countries.json');
    include('ui/checkout-delivery.htm');
  break;
  // STEP 2: ORDER VALIDATION
  case 1:
      $countries=$shop->get_json($SET['data/'].'countries.json');
      // get post data
      if(empty($_REQUEST['back'])) {
        $array['firstname']=$_POST['firstname'];
        $array['preposition']=$_POST['preposition'];
        $array['lastname']=$_POST['lastname'];
        $array['company']=$_POST['company'];
        $array['taxnumber']=$_POST['taxnumber'];
        $array['streetname']=$_POST['streetname'];
        $array['housenumber']=$_POST['housenumber'];
        $array['zipcode']=$_POST['zipcode'];
        $array['city']=$_POST['city'];
        $array['countrycode']=$_POST['country'];
        $array['country']=$countries[$_POST['country']];
        $array['telephone']=$_POST['telephone'];
        $array['email']=$_POST['email'];
        $array['remarks']=$_POST['remarks'];
        $array['notransport']=(isset($_POST['notransport'])?1:0);
      }
      // display list of products
    ?><div class="clear"></div>
       <div id="checkout-container">
      <form method="post" action="" name="orderForm" id="orderForm">
      <?php echo paymentnav($shop->tx($array)); ?>
        <div id="checkout-form">
          <div class="navsteps">
            <span class="badge">1. <?=$STR['Delivery'];?></span>
            <span class="badge badge-active">2. <?=$STR['Validation'];?></span>
            <span class="badge">3. <?=$STR['Payment'];?></span>
          </div>
          <div style="text-align: center; margin-top: 24px; margin-bottom: 12px;"><?=$STR['Ensure_order_correct'];?></div>
          <h2><?=$STR['Product_list'];?></h2>
      <?php
      // prepare quantities list
      $quantities = $jcart->qtys;     // get jcart quantities on id => val
      // load list of vendors
      $vendors = $shop->get_vendors($SET['data/'].$SET['vend/']);
      // load products and split their id to recover productid and vendorid (hacky due to the crudeness of jcart)
      foreach ($jcart->items as $item) {
        $tmp = explode('|',$item);
        $productid = $tmp[0];
        $vendorid = $tmp[1];
        $array['quantities'][$productid] = $quantities[$item];  // rebase quantities from messy jcart array
        if(!$vendorid) {
          // get local product information
          $product = $shop->get_product($SET['data/'].$SET['prod/'],$productid);
          // make sure we have enough in stock, else reduce amount on shopping list
          if($product[$productid]['stock']>0 || $product[$productid]['stock']==-1) {
            if($product[$productid]['stock']<$array['quantities'][$productid] && $product[$productid]['stock']!=-1) {
              $array['quantities'][$productid] = $product[$productid]['stock'];
            }
            $products[$productid] = $product[$productid];
            $products[$productid]['vendorid'] = '#';
          }
        } else {
          // get remote product information
          $hash = crc32($vendors[ $vendorid ]['secret']);
          $query = $shop->tx( array($hash,'get',$productid) );
          $product = $shop->rx(file_get_contents( $vendors[ $vendorid ]['host'].'?q='.$query ),$vendors[ $vendorid ]['secret']);
          if($product) {
            // make sure we have enough in stock, else reduce amount on shopping list
            if($product[$productid]['stock']>0 || $product[$productid]['stock']==-1) {
              if($product[$productid]['stock']<$array['quantities'][$productid] && $product[$productid]['stock']!=-1) {
                $array['quantities'][$productid] = $product[$productid]['stock'];
              }
              $products[$productid] = $product[$productid];
              $products[$productid]['vendorid'] = $vendorid;
            }
          }
        }
      }
      // get modifiers rules
      $modifiersmath = $shop->get_json($SET['data/'].'modifiersmath.json');
      // use only enabled_locally rules
      foreach($modifiersmath as $key => $val) {
        if(!$val['enabled_locally']) {
          unset($modifiersmath[$key]);
        }
      }
      // get transport rules
      $transportmath = $shop->get_json($SET['data/'].'transportmath.json');
      // construct producttable
      $array = $shop->make_producttable($array,$products,$vendors,$modifiersmath,$transportmath);
      // display producttable
      echo $array['product-table'];
      // warn user if no transport...
      if($array['notransport']) { echo '<br /><center><b><u>You are coming by to retrieve the product!</u></b></center>'; }
      // store order in file
      $array['time'] = time(); // add time of order creation
      $orderid = $array['ordernumber'];
      $output[$orderid] = $array;
      unset($output[$orderid]['ordernumber']);
      file_put_contents($SET['data/'].$SET['ordr/'].$orderid.'.pending',json_encode($output));
      // display destination address and gateway choice
        ?><br />
          <table border=0 style="width: 100%">
            <tr><td style="vertical-align: top;">
              <h2><?=$STR['Destination_address'];?></h2>
              <br /><div style="display: inline-block; margin-left: 20px; text-align: right; vertical-align: top;">To:&nbsp;&nbsp;&nbsp;</div>
              <div id="address-container">
                <div style="display: inline-block;">
                  <?php echo (!empty($array['company'])?$array['company']:$array['firstname'].(!empty($array['preposition'])?' '.$array['preposition']:'').' '.$array['lastname']); ?>
                </div><br />
                <div style="display: inline-block;">
                  <?php echo $array['streetname'].' '.$array['housenumber']; ?>
                </div><br />
                <div style="display: inline-block;">
                  <?php echo $array['zipcode']; ?>
                </div><br />
                <div style="display: inline-block;">
                  <?php echo $array['city']; ?>
                </div><br />
                <div style="display: inline-block;">
                  <?php echo $array['country']; ?>
                </div>
              </div>
              </td><td style="vertical-align: top; width: 30%;">
                <h2><?=$STR['Payment_gateway'];?></h2>
                <select name="gateway" class="form-control">
                  <?php
                    $gateways = $shop->get_gateways($SET['data/'].$SET['gate/']);
                    foreach($gateways as $key => $val) {
                      if($val['active']) {
                        echo '<option value="'.$key.'"'.(isset($array['gateway']) && $array['gateway']==$key?' selected':'').'>'.$val['description'].' | '.$val['name'].'</option>';
                      }
                    }
                  ?>
                </select>
                <br />
                <h2><?=$STR['Ordernumber'];?></h2>
                <div style="display: inline-block; width: 32px;"></div><?php echo $array['ordernumber']; ?>
            </td><td style="vertical-align: top; width: 15%;">
            </td></tr>
          </table>
          <br /><br />
        </div>
        <div class="clear"></div>
        <?php echo paymentnav($shop->tx($array)); ?>
      </form></div><?php
  break;
  // STEP 3: SHOW THE PAYMENT FORM
  case 2:
      // store order in file
      $array['time'] = time(); // add time of order creation
      $orderid = $array['ordernumber'];
      $output[$orderid] = $array;
      unset($output[$orderid]['ordernumber']);
      file_put_contents($SET['data/'].$SET['ordr/'].$orderid.'.json',json_encode($output));
      // collect and show payment gateways...
      ?><div id="checkout-container">
        <form method="post" action="" name="orderForm" id="orderForm">
          <?php echo paymentnav($shop->tx($array),$STR['Finish']); ?>
          <div id="checkout-form">
            <div class="navsteps">
              <span class="badge">1. <?=$STR['Delivery'];?></span>
              <span class="badge">2. <?=$STR['Validation'];?></span>
              <span class="badge badge-active">3. <?=$STR['Payment'];?></span>
            </div>
            <div style="text-align: center; margin-top: 24px; margin-bottom: 12px;"><?=$STR['Almost_ready_to_pay'];?></div>
            <div style="height:32px; margin-top: 40px; margin-bottom: -80px; width: 100%; text-align: center;"><img class="loading" style="display: none;" src="ui/images/loading.gif" /></div>
            <h2><?=$STR['Payment'];?></h2>
            <?php
              paymentform($array,$shop);
            ?>
          </div>
          <?php echo paymentnav($shop->tx($array),$STR['Finish']); ?>
        </form>
        </div>
        <script language="javascript" type="text/javascript">
          items = document.getElementsByClassName("submit");
          backs = document.getElementsByClassName("back");
          for (var i = 0; i < items.length; i++) {
            items[i].setAttribute("type", "button");
            items[i].setAttribute("onclick", "submitCheck();");
          }
          function submitCheck(){
            $(".loading").show(1000);
            for (var i = 0; i < items.length; i++) {
              items[i].setAttribute("onclick", "");
              items[i].setAttribute("class", "submit btn btn-disabled");
              backs[i].setAttribute("class", "back btn btn-disabled");
            }
            document.orderForm.submit();
            setTimeout(function(){
              $(".loading").hide(1000);
              for (var i = 0; i < items.length; i++) {
                for (var i = 0; i < items.length; i++) {
                  items[i].setAttribute("onclick", "submitCheck();");
                  items[i].setAttribute("class", "submit btn btn-success");
                  backs[i].setAttribute("class", "back btn btn-danger");
                }
              }
            }, 60000);
          }
        </script>
      <?php
  break;
  // STEP 4: FORWARD TO PAYMENT GATEWAY
  case 3:
      // forward to payment gateway
      if(!$TESTMODE) {
        $array = paymentgate($array,$shop);
      } else { $array['forwardurl'] = 'TEST'; }
      $array['time'] = time(); // add time of order creation
      if(isset($array['forwardurl'])) {
        $forwardurl = $array['forwardurl'];
        unset($array['forwardurl']);
        // store order in file
        $orderid = $array['ordernumber'];
        $output[$orderid] = $array;
        unset($output[$orderid]['ordernumber']);
        file_put_contents($SET['data/'].$SET['ordr/'].$orderid.'.pending',json_encode($output));
        sleep(2);  // give external processes some time...
        // forward user to payment processor...
        if($TESTMODE) {
          // DEBUG: TEMPORARY FAKE TO SIMULATE SUCCESS OF payment processor Pay.nl...
          header('Location: '.$protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/'.$SET['data/'].$SET['gate/'].$array['gateway'].'/return.php?orderId='.$array['ordernumber'].'&orderStatusId=100');
        } else {
          header('Location: '. $forwardurl);
        }
      }
  break;
  // STEP 5: RETURN FROM PAYMENT GATEWAY
  case 4:
    // return values when good payment -> http://example.com/checkout.php?orderId=333316177X1bcc7a&orderStatusId=100&paymentSessionId=333316177
    if (isset($array['orderstatus']) && $array['orderstatus']>=99) {
      // send e-mail to client and shopadministrators
      if($array['orderstatus']==100) {
        sleep(2);  // wait to avoid collisions writing file
        $users = array(); $users[] = array('e-mail'=>$array['email'],'receive_notifications'=>1);
        $shop->reporting($SET['data/'],$array,$users,'order_complete');
      }
      $users = $shop->get_users($SET['data/'].$SET['user/']);
      $shop->reporting($SET['data/'],$array,$users,'order_complete');
      // adjust stock amounts according to order
      $remoteorders = array();
      foreach($jcart->items as $cnt => $item) {
        $tmp = explode('|',$item);
        $id = $tmp[0];
        $vendorid = (isset($tmp[1])?$tmp[1]:FALSE);
        if(!$vendorid) {
          // subtract from stock, add to sales
          $product = $shop->get_product($SET['data/'].$SET['prod/'],$id);
          if(isset($product[$id])) {
            if($product[$id]['stock']!=-1) {
              $product[$id]['stock'] = $product[$id]['stock']-$array['quantities'][$id];
            }
            $product[$id]['sales'] = $product[$id]['sales']+$array['quantities'][$id];
            $shop->put_stocks($SET['data/'].$SET['prod/'],$product);
          }
        } else {
          // add id's to remote order array, thus sorting by vendor
          $remoteorders[ $vendorid ][] = $id;
        }
      }
      // log add total number of sales
      if(!file_exists($SET['data/'].$SET['stat/'].'sales')) {
        mkdir($SET['data/'].$SET['stat/'].'sales',0777,TRUE);
      } else {
        $filename = $SET['data/'].$SET['stat/'].'sales/'.date('Y-m-d').'.asc';
        if(file_exists($filename)) {
          $sales = file_get_contents($filename);
        } else {
          touch($filename);
          $sales=0;
        }
        $sales++;
        file_put_contents($filename,$sales);
      }
      // load list of vendors
      $vendors = $shop->get_vendors($SET['data/'].$SET['vend/']);
      // push productid's to create remote orders with vendors, log into settlements
      $settlements = array();
      foreach($remoteorders as $vendorid => $ids) {
          // send remote order
          $hash = crc32($vendors[ $vendorid ]['secret']);
          // share array excluding original product table, amount, weight
          $s_array = $array; unset($s_array['product-table']); unset($s_array['subtotal']); unset($s_array['amount']); unset($s_array['weight']); unset($s_array['size']); unset($s_array['transport']);
          // add settlement margin percentage value for easy remote calculation
          $s_array['settlementmargin'] = $vendors[ $vendorid ]['reseller_margin'];
          $query = $shop->tx( array($hash,'ord',array($ids,$s_array)) );
          // settlement and total amount is returned and added to settlements list
          $settlements[$vendorid] = $shop->rx(file_get_contents( $vendors[ $vendorid ]['host'].'?q='.$query ),$vendors[ $vendorid ]['secret']);
      }
      // write out settlements
      foreach($settlements as $vendorid => $data) {
        // write the settlement with the orderid!
        $shop->put_settlement($SET['data/'].$SET['sett/'],'d',$vendorid,$array['ordernumber'],$data['subtotal'],$data['settlement'],$data['transport'],$data['modifiers'],$data['margin']);
      }
      // display HAPPY face ;)
      ?>
      <div class="clear"></div>
      <div id="checkout-container">
        <form method="post" action="checkout" name="orderForm" id="orderForm">
        <?php echo paymentnav($shop->tx($array),$STR['Return'],$STR['Back'],FALSE); ?>
        <div id="checkout-form">
          <div class="navsteps">
            <span class="badge">1. <?=$STR['Delivery'];?></span>
            <span class="badge">2. <?=$STR['Validation'];?></span>
            <span class="badge badge-active">3. <?=$STR['Payment'];?></span>
          </div>
          <h2><?=$STR['Order_succesful'];?></h2>
          <center>
            <?=$STR['Processing_order'];?><br><br>
            <img src="ui/images/happyface.png"/>
            <br><br>
          </center>
        </div>
        <?php echo paymentnav($shop->tx($array),$STR['Return'],$STR['Back'],FALSE); ?>
        </form>
      </div>
      <?php
      // clean up
      if(!$TESTMODE) {
        // empty the cart
        $jcart->empty_cart();
        // destroy ordernumber in session
        unset($_SESSION['ordernumber']);
      }
    } else {
      // no correct data, show checkout error page
      $array['sequence']=$array['sequence']-3;  // Go back to pre-payment status.
      // display SAD face!
        ?>
        <div class="clear"></div>
        <div id="checkout-container">
          <form method="post" action="checkout" name="orderForm" id="orderForm">
          <div id="checkout-container">
            <?php echo paymentnav($shop->tx($array),$STR['Retry'],$STR['Back']); ?>
            <div id="checkout-form">
              <center>
                <div class="navsteps">
              <span class="badge">1. <?=$STR['Delivery'];?></span>
              <span class="badge">2. <?=$STR['Validation'];?></span>
              <span class="badge badge-active">3. <?=$STR['Payment'];?></span>
                </div>
                <h2><?=$STR['Order_failed'];?></h2>
                <?=$STR['Please_try_again'];?><br>
                <?=$STR['Or_contact_us'];?><br><br>
                <img src="ui/images/sadface.png"/>
                <br><br>
              </center>
            </div>
          </div>
          <?php echo paymentnav($shop->tx($array),$STR['Retry'],$STR['Back']); ?>
        </form>
      </div> <?php
      // reset status to unsuccessful
      $array['sequence']=$array['sequence']+3;  // reset status flag for writing order
    }
    if(!$TESTMODE) {
      // store order in file
      $orderid = $array['ordernumber'];
      unset($output[$orderid]['ordernumber']);
      $filenameNoExt = $SET['data/'].$SET['ordr/'].$orderid;
      if(file_exists($filenameNoExt.'.json')||file_exists($filenameNoExt.'.pending')) {
        $array['time'] = time(); // add time of order creation
        $output[$orderid] = $array;
        file_put_contents($filenameNoExt.'.json',json_encode($output));
        unlink($SET['data/'].$SET['ordr/'].$orderid.'.pending');
      }
    }
  break;
  case 5:
    // back to home
    $shop->siteroot();
  break;
}


/* DEBUG
echo '<pre>';
var_dump($_GET);
echo '<br>';
var_dump($_POST);
echo '</pre>';
*/

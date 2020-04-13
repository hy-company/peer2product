<?php

$DEBUG = TRUE;
error_reporting($DEBUG ? E_ALL : 0);

include_once('lib/jcart/jcart.php');

if(isset($_GET['checkout'])) {
	include('lib/checkout.php');
} elseif(isset($_GET['about'])) {
	include('ui/about.htm');
} elseif(isset($_GET['terms'])) {
	include('ui/terms.htm');
} elseif(isset($_GET['contact'])) {
	include('ui/contact.htm');	
} else {

	// load list of vendors
	$vendors = $shop->get_vendors($SET['data/'].$SET['vend/']);

	// cull the inventory by category
	$category = (isset($_GET['category'])?$_GET['category']:FALSE);
	$categories = $shop->get_json($SET['data/'].'categories.json');
	// also load default tags
	if(isset($categories[ $SET['mainpage_category'] ]['tags'])) { $SET['defaulttags'] = $categories[ $SET['mainpage_category'] ]['tags']; } else { $SET['defaulttags']=FALSE; }

	// peer2peer communication with other shops
	if(function_exists('openssl_encrypt')) {
		// handle encrypted queries
		if (isset($_GET['q'])) {
			$query = $shop->rx($_GET['q']);
			$hash = $query[0];
			foreach($vendors as $vendorid => $vendor) {
				if($hash == crc32($vendor['secret'])) {
					switch( $query[1] ) {
						case 'get':	// return single product
							$result = $shop->get_product($SET['data/'].$SET['prod/'],$query[2]); // query[2] => productid
						break;
						case 'vnd':	// return tags of shared categories
							$tags = '';
							foreach($vendor['shared_categories'] as $val) {
								$tags .= $categories[$val]['tags'].',';
							}
							$tags = $shop->trimsortstring($tags);
							$result = array(TRUE,$tags);
						break;
						case 'inv':	// return inventory of products
							// check if one of our shared categories is being requested
							if($query[2]) {
								$requestedtags = explode(',',$query[2]);
								$sharedtags = '';
								foreach($vendor['shared_categories'] as $val) {
									$sharedtags .= $categories[$val]['tags'].',';
								}
								$sharedtags = explode(',',$shop->trimsortstring($sharedtags));
								$returnedtags = '';
								foreach($requestedtags as $tag) {
									if(in_array($tag,$sharedtags)) {
										$returnedtags[] = $tag;
									}
								}
								$returnedtags = implode(',',$returnedtags);
								if($returnedtags) {
									$result = $shop->get_products($SET['data/'].$SET['prod/'],FALSE,$returnedtags);
								} else { $result = array(); }
							}
						break;
						case 'ord':	// handle order and subtract from stock
							$ids = $query[2][0];
							$array = $query[2][1];
							// get ordered products data locally and subtract from stock
							$products = array();
							foreach($ids as $id) {
								$product = $shop->get_product($SET['data/'].$SET['prod/'],$id);
								$products[$id] = $product[$id];
								$products[$id]['stock'] = $products[$id]['stock']-$array['quantities'][$id]; // subtract from stock
								$products[$id]['sales']++; // add to sales
								$products[$id]['vendorid'] = '#';	// disable vendorid, since we are now processing locally!
							}
							// write back subtracted stocks
							$shop->put_stocks($SET['data/'].$SET['prod/'],$products);
							// get modifier rules
							$modifiersmath = $shop->get_json($SET['data/'].'modifiersmath.json');
							// use only enabled_remotely rules
							foreach($modifiersmath as $key => $val) {
								if(!$val['enabled_remotely']) {
									unset($modifiersmath[$key]);
								}
							}
							// get transport rules
							$transportmath = $shop->get_json($SET['data/'].'transportmath.json');
							// construct producttable
							$array = $shop->make_producttable($array,$products,$vendors,$modifiersmath,$transportmath);
							// prepare array for writing to file
							$array['time'] = time(); // add time of order creation
							$array['vendor'] = $vendorid;
							$orderid = $array['ordernumber'];
							$output[$orderid] = $array;
							unset($output[$orderid]['ordernumber']);
							// write to file
							file_put_contents($SET['data/'].$SET['ordr/'].$orderid.'.json',json_encode($output));
							// calculate and return settlement amount (margin is not calculated over transport!)
							$settlement = ($array['subtotal']-$array['modifiers'])-(($array['subtotal']-$array['modifiers'])*($array['settlementmargin']*0.01)) +$array['transport'];
							// write out the settlement value as receivable in our own shop
							$shop->put_settlement($SET['data/'].$SET['sett/'],'c',$vendorid,$orderid,$array['subtotal'],$settlement,$array['transport'],$array['modifiers'],$array['settlementmargin']);
							// e-mail the shop administrators
							$users = $shop->get_users($SET['data/'].$SET['user/']);
							// DEBUG: $users = array(); $users[] = array('e-mail'=>'contact@metasync.info','receive_notifications'=>1);
							$shop->reporting($SET['data/'],$users,'order_complete');
							// NOTE: settlement amount is negative towards the reseller
							$result['settlement'] = 0-$settlement;
							$result['subtotal'] = (isset($array['subtotal'])?0-$array['subtotal']:0);
							$result['modifiers'] = (isset($array['modifiers'])?0-$array['modifiers']:0);
							$result['transport'] = (isset($array['transport'])?0-$array['transport']:0);
							$result['margin'] = (isset($array['settlementmargin'])?$array['settlementmargin']:0);
						break;
						case 'clr':	// handle remote clearance
							$array = $query[2];
							// reverse settlement
  							$array['vendor']=$vendorid;
  							$array['send']=($array['send']?0:1);
							$array['settlement']=0-$array['settlement'];
							// write out the settlement value as receivable in our own shop
							$directory = $SET['data/'].$SET['sett/'].$vendorid;
							if(!file_exists($directory)) { mkdir($directory); }
							$settlement = array( $vendorid => $array );
							$shop->put_json($directory.'/'.$array['orderid'].'.json',$settlement);
							$result = true;
						break;
						case 'mod':	// calculate modifiers for external request
							$result = FALSE;
							$id = $query[2][0];
							$quantity = $query[2][1];
							$array = array();
							$array['modifiers'] = $query[2][3];
							// get product details
							$products = $shop->get_product($SET['data/'].$SET['prod/'],$id);
							$product = $products[$id];
							// get modifier rules
							$modifiersmath = $shop->get_json($SET['data/'].'modifiersmath.json');
							foreach($product['modifiers'] as $modifier_key) {
								if($modifiersmath[$modifier_key]['enabled_remotely']) {
									if(!isset($array['modifiers']['R'.$modifier_key]['amount'])) { $array['modifiers']['R'.$modifier_key]['amount']=0; }
									$array['modifiers']['R'.$modifier_key]['amount'] = $array['modifiers']['R'.$modifier_key]['amount'] + ( $quantity * $shop->modifier_eval( $modifiersmath[$modifier_key]['formula'] , array('result'=>$array['modifiers']['R'.$modifier_key]['amount'],'product'=>$product) ) );
									$array['modifiers']['R'.$modifier_key]['title'] = $modifiersmath[$modifier_key]['title'];
									$array['modifiers']['R'.$modifier_key]['count'] = $array['modifiers']['R'.$modifier_key]['count'] + $quantity;
								}
							}
							$result = $array['modifiers'];
						break;
						case 'pst':	// calculate transport for external request
							$result = 0;
							$ids = $query[2][0];
							$array = $query[2][1];
							$product[$id]['quantity'] = $array['quantities'][$id];
							// get transport rules
							$transportmath = $shop->get_json($SET['data/'].'transportmath.json');
							$productslist['quantity'] = 0;
							$productslist['weight'] = 0;
							$productslist['maxsize'] = 0;
							$productslist['minsize'] = 99999999;
							foreach($ids as $id) {
								$products = $shop->get_product($SET['data/'].$SET['prod/'],$id);
								$productslist['quantity'] = $productslist['quantity'] + $array['quantities'][$id];
								$productslist['weight'] = $productslist['weight'] + ($products[$id]['weight'] * $array['quantities'][$id]);
								$productslist['size'] = $productslist['size'] + ($products[$id]['size'] * $array['quantities'][$id]);
								if($productslist['maxsize']<$products[$id]['size']) { $productslist['maxsize']=$products[$id]['size']; }
								if($productslist['minsize']>$products[$id]['size']) { $productslist['minsize']=$products[$id]['size']; }
							}
							$transport = 0;
							foreach($transportmath as $transport_key => $transport_val) {
								$transport = $transport + $shop->transport_eval( $transport_val['formula'] , array('result'=>$transport,'products'=>$productslist,'array'=>$array) );
							}
							$result = $transport;
						break;
					}
					echo $shop->tx($result,$vendor['secret']);
				}	
			}
			exit(0);
		}

		// load products,vendors into memory
		$products = $shop->get_products($SET['data/'].$SET['prod/'],FALSE,$category);
		// merge remote products (FIX BY ADDING IMAGE CACHING?!)
		if(!file_exists($SET['data/'].'cache')) {
			mkdir($SET['data/'].'cache');
		}
		foreach($vendors as $id => $vendor) {
			// get data from cache if possible
			$catcmp = ($category?'-'.str_replace(',','',$category):'');
			if(file_exists($SET['data/'].'cache/'.$id.$catcmp)) {
				$tmp = json_decode(file_get_contents( $SET['data/'].'cache/'.$id.$catcmp ),true);
			} else {
				$tmp = FALSE;
			}
			if($tmp && ((!$tmp['data'] && $tmp['time']>time()-3600) || ($tmp['data'] && $tmp['time']>time()-300))) {
				$tmp = $tmp['data'];
			} else {
				// get data from remote vendor
				$hash = crc32($vendor['secret']);
				$query = $shop->tx( array($hash,'inv', ($category?$category:$SET['defaulttags']) ) );
				$tmp = $shop->rx(file_get_contents( $vendor['host'].'?q='.$query ),$vendor['secret']);
				file_put_contents( $SET['data/'].'cache/'.$id.$catcmp, json_encode(array('time'=>time(),'data'=>$tmp)) );
			}
			// merge arrays into products
			if($tmp) {
				foreach($tmp as $id_remote => $product) {
					// product is remote
					$product['vendorid'] = $id;
					$product['vendorname'] = $vendor['name'];
					if($product['img']) { $product['img']=$vendor['host'].'/'.$product['img']; }
					if($product['imgA']) { $product['imgA']=$vendor['host'].'/'.$product['imgA']; }
					if($product['imgB']) { $product['imgB']=$vendor['host'].'/'.$product['imgB']; }
					// add product to array
					$products[$id_remote] = $product;
				}
			}
		}
	} else { die('<h4 style="color:red;">ERROR: Peer2Product uses cryptography for peer-to-peer connections. You do not have the required library on this server. Make sure to install openssl, and enable it by doing #TODO. Then restart your webserver.</h4>');}
	
	// create category buttons
	$content['categories'] = '';
	foreach($categories as $key => $val) {
		if($val['visible']) {
			$content['categories'] .= '<a href="category-'.str_replace('%2C',',',urlencode($val['tags'])).'"><button class="btn btn-'.($val['tags']==$category?'info':'default').'">'.$val['name'].'</button></a>';
			// set the current description
			if($val['tags']==$category) {
				$content['description'] = $val['description'];
			}
		}
	}
	$content['categories'] .= '&nbsp;'.($category?'<a href="'.dirname($_SERVER['PHP_SELF']).'"><button class="btn btn-xs btn-danger">X</button></a>':'');  // <button class="btn btn-xs btn-default"><span style="color: grey;">X</span></button>
	
	// sort products by {name, price, stock, newest}
	if(isset($_POST['sort'])) {
		$sort = $_POST['sort'];
		$_SESSION = $shop->setsorting($_SESSION,$sort);
	} elseif (isset($_POST['sortasc'])) {
		$sort = $_SESSION['sort_key'];
		$_SESSION = $shop->setsorting($_SESSION,$sort);
	}
	
	// apply tax modifiers
	$products = $shop->calc_taxes($SET['data/'],$products);

	// display landing page or category page
	if($SET['shopslider'] && !$category) {
		// LANDING PAGE
		// if user is not so smart and has no featured products... feature them all!
		if(!count($products)) {
			$products = $shop->get_products($SET['data/'].$SET['prod/'],FALSE,-1);
			// apply tax modifiers
			$products = $shop->calc_taxes($SET['data/'],$products);
		}
		// sort the products
		$sortasc = (isset($_SESSION['sort_asc'])?$_SESSION['sort_asc']:TRUE);
		$sort    = (isset($_SESSION['sort_key'])?$_SESSION['sort_key']:'time');
		$products = $shop->deepsort($products,$sort,$sortasc);
		// show the welcome message
		$content['description'] = $SET['mainpage_message'];
		// show the slider
		$content['forms'] = '<div id="slideshow"><div id="slidesContainer">';
		$content['forms'] .= $shop->make_product_forms($products,TRUE);
		$content['forms'] .= '</div></div><script type="text/javascript" src="ui/js/slider.js"></script>';	
	} else {	
		// CATEGORY PAGE
		$sort    = (isset($_SESSION['sort_key'])?$_SESSION['sort_key']:'name');
		$sortasc = (isset($_SESSION['sort_asc'])?$_SESSION['sort_asc']:TRUE);
		$products = $shop->deepsort($products,$sort,$sortasc);
		// create product forms
		$content['forms'] = $shop->make_product_forms($products);
	}
	include('ui/webshop.htm');
}

// log unique visitors
if(!file_exists($SET['data/'].$SET['stat/'].'visitors')) {
	mkdir($SET['data/'].$SET['stat/'].'visitors',0777,TRUE);
} else {
	$filename = $SET['data/'].$SET['stat/'].'visitors/'.date('Y-m-d').'.asc';
	if(file_exists($filename)) {
		$visitors = file($filename,FILE_SKIP_EMPTY_LINES);
	} else {
		touch($filename);
		$visitors=array();
	}
	if(!in_array($_SERVER['REMOTE_ADDR']."\n",$visitors)) {
		file_put_contents($filename,$_SERVER['REMOTE_ADDR']."\n",FILE_APPEND+LOCK_EX);
	}
}

// log unique visitors to specific tags
if(!file_exists($SET['data/'].$SET['stat/'].'tags')) {
	mkdir($SET['data/'].$SET['stat/'].'tags',0777,TRUE);
} else {
	$filename = $SET['data/'].$SET['stat/'].'tags/'.date('Y-m-d').'.asc';
	if(file_exists($filename)) {
		$visitors = file($filename,FILE_SKIP_EMPTY_LINES);
	} else {
		touch($filename);
		$visitors=array();
	}
	foreach(explode(',',$category) as $tag) {
		if($tag && !in_array($tag.':'.$_SERVER['REMOTE_ADDR']."\n",$visitors)) {
			file_put_contents($filename,$tag.':'.$_SERVER['REMOTE_ADDR']."\n",FILE_APPEND+LOCK_EX);
		}
	}
}

?>

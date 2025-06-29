<?php

$DEBUG = FALSE;
error_reporting($DEBUG ? E_ERROR | E_WARNING | E_PARSE : 0);

// jCart v1.3
// http://conceptlogic.com/jcart/
// NOTE: This Jcart code has been modified from the original for Peer2Product!
//error_reporting(E_ALL);

// Cart logic based on Webforce Cart: http://www.webforcecart.com/
class Jcart {

	public $subtotal  = 0;
	public $itemCount = 0;
	public $config    = array();
	public $qtys      = array();
	public $items     = array();
	public $names     = array();
	public $prices    = array();
	private $urls     = array();

	function __construct() {
		// Get $config array
		include_once('config.php');
		$this->config = $config;
	}

	/**
	* Get cart contents
	*
	* @return array
	*/
	public function get_contents() {
		$items = array();
		foreach($this->items as $tmpItem) {
			$item = null;
			$item['id']       = $tmpItem;
			$item['name']     = $this->names[$tmpItem];
			$item['price']    = $this->prices[$tmpItem];
			$item['qty']      = $this->qtys[$tmpItem];
			$item['url']      = $this->urls[$tmpItem];
			$item['subtotal'] = $item['price'] * $item['qty'];
			$items[]          = $item;
		}
		return $items;
	}

	/**
	* Add an item to the cart
	*
	* @param string $id
	* @param string $name
	* @param float $price
	* @param mixed $qty
	* @param string $url
	*
	* @return mixed
	*/
	private function add_item($id, $name, $price, $qty, $url) {

		$validPrice = false;
		$validQty = false;

		// Verify the price is numeric
		if (is_numeric($price)) {
			$validPrice = true;
		}

		// If decimal quantities are enabled, verify the quantity is a positive float
		if ($this->config['decimalQtys'] === true && filter_var($qty, FILTER_VALIDATE_FLOAT) && $qty > 0) {
			$validQty = true;
		}
		// By default, verify the quantity is a positive integer
		elseif (filter_var($qty, FILTER_VALIDATE_INT) && $qty > 0) {
			$validQty = true;
		}

		// Add the item
		if ($validPrice !== false && $validQty !== false) {

			// If the item is already in the cart, increase its quantity
			if($this->qtys[$id] > 0) {
				$this->qtys[$id] += $qty;
				$this->update_subtotal();
			}
			// This is a new item
			else {
				$this->items[]     = $id;
				$this->names[$id]  = $name;
				$this->prices[$id] = $price;
				$this->qtys[$id]   = $qty;
				$this->urls[$id]   = $url;
			}
			$this->update_subtotal();
			return true;
		}
		elseif ($validPrice !== true) {
			$errorType = 'price';
			return $errorType;
		}
		elseif ($validQty !== true) {
			$errorType = 'qty';
			return $errorType;
		}
	}

	/**
	* Update an item in the cart
	*
	* @param string $id
	* @param mixed $qty
	*
	* @return boolean
	*/
	private function update_item($id, $qty) {

		// If the quantity is zero, no futher validation is required
		if ((int) $qty === 0) {
			$validQty = true;
		}
		// If decimal quantities are enabled, verify it's a float
		elseif ($this->config['decimalQtys'] === true && filter_var($qty, FILTER_VALIDATE_FLOAT)) {
			$validQty = true;
		}
		// By default, verify the quantity is an integer
		elseif (filter_var($qty, FILTER_VALIDATE_INT))	{
			$validQty = true;
		}

		// If it's a valid quantity, remove or update as necessary
		if ($validQty === true) {
			if($qty < 1) {
				$this->remove_item($id);
			}
			else {
				$this->qtys[$id] = $qty;
			}
			$this->update_subtotal();
			return true;
		}
	}


	/* Using post vars to remove items doesn't work because we have to pass the
	id of the item to be removed as the value of the button. If using an input
	with type submit, all browsers display the item id, instead of allowing for
	user-friendly text. If using an input with type image, IE does not submit
	the	value, only x and y coordinates where button was clicked. Can't use a
	hidden input either since the cart form has to encompass all items to
	recalculate	subtotal when a quantity is changed, which means there are
	multiple remove	buttons and no way to associate them with the correct
	hidden input. */

	/**
	* Reamove an item from the cart
	*
	* @param string $id	*
	*/
	private function remove_item($id) {
		$tmpItems = array();

		unset($this->names[$id]);
		unset($this->prices[$id]);
		unset($this->qtys[$id]);
		unset($this->urls[$id]);

		// Rebuild the items array, excluding the id we just removed
		foreach($this->items as $item) {
			if($item != $id) {
				$tmpItems[] = $item;
			}
		}
		$this->items = $tmpItems;
		$this->update_subtotal();
	}

	/**
	* Empty the cart
	*/
	public function empty_cart() {
		$this->items     = array();
		$this->names     = array();
		$this->prices    = array();
		$this->qtys      = array();
		$this->urls      = array();
		$this->subtotal  = 0;
		$this->itemCount = 0;
	}

	/**
	* Update the entire cart
	*/
	public function update_cart() {

		// Post value is an array of all item quantities in the cart
		// Treat array as a string for validation
		if (is_array($_POST['jcartItemQty'])) {
			$qtys = implode($_POST['jcartItemQty']);
		}

		// If no item ids, the cart is empty
		if ($_POST['jcartItemId']) {

			$validQtys = false;

			// If decimal quantities are enabled, verify the combined string only contain digits and decimal points
			if ($this->config['decimalQtys'] === true && preg_match("/^[0-9.]+$/i", $qtys)) {
				$validQtys = true;
			}
			// By default, verify the string only contains integers
			elseif (filter_var($qtys, FILTER_VALIDATE_INT) || $qtys == '') {
				$validQtys = true;
			}

			if ($validQtys === true) {

				// The item index
				$count = 0;

				// For each item in the cart, remove or update as necessary
				foreach ($_POST['jcartItemId'] as $id) {

					$qty = $_POST['jcartItemQty'][$count];

					if($qty < 1) {
						$this->remove_item($id);
					}
					else {
						$this->update_item($id, $qty);
					}

					// Increment index for the next item
					$count++;
				}
				return true;
			}
		}
		// If no items in the cart, return true to prevent unnecssary error message
		elseif (!$_POST['jcartItemId']) {
			return true;
		}
	}

	/**
	* Recalculate subtotal
	*/
	private function update_subtotal() {
		$this->itemCount = 0;
		$this->subtotal  = 0;

		if(sizeof($this->items) > 0) {
			foreach($this->items as $item) {
				$this->subtotal += ($this->qtys[$item] * $this->prices[$item]);

				// Total number of items
				$this->itemCount += $this->qtys[$item];
			}
		}
	}

	/**
	* Process and display cart
	*/
	public function display_cart($ajax=FALSE) {

		// hotfix for relative paths config bug
		if(!$ajax) {
			include('config.php');
			$this->config = $config;
		} else {
			$config = $this->config; 
		}

		$errorMessage = null;

		// Simplify some config variables
		$checkout = $config['checkoutPath'];
		$priceFormat = $config['priceFormat'];

		$id    = $config['item']['id'];
		$name  = $config['item']['name'];
		$price = $config['item']['price'];
		$qty   = $config['item']['qty'];
		$vendorid = (isset($config['item']['vendorid'])?$config['item']['vendorid']:null);
		$add   = $config['item']['add'];

		// Use config values as literal indices for incoming POST values
		// Values are the HTML name attributes set in config.json
		$id    = (isset($_POST[$id])?$_POST[$id]:null);
		$name  = (isset($_POST[$name])?$_POST[$name]:null);
		$price = (isset($_POST[$price])?$_POST[$price]:null);
		$qty   = (isset($_POST[$qty])?$_POST[$qty]:null);
		$vendorid   = (isset($_POST[$vendorid])?$_POST[$vendorid]:null);

		// Optional CSRF protection, see: http://conceptlogic.com/jcart/security.php
		$jcartToken = (isset($_POST['jcartToken'])?$_POST['jcartToken']:null);

		// Only generate unique token once per session
		if(!$_SESSION['jcartToken']){
			$_SESSION['jcartToken'] = md5(session_id() . time() . $_SERVER['HTTP_USER_AGENT']);
		}
		// If enabled, check submitted token against session token for POST requests
		if ($config['csrfToken'] === 'true' && $_POST && $jcartToken != $_SESSION['jcartToken']) {
			$errorMessage = 'Invalid token!' . $jcartToken . ' / ' . $_SESSION['jcartToken'];
		}

		// Sanitize values for output in the browser
		$id    = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
		$name  = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
		//$url   = filter_var($url, FILTER_SANITIZE_URL);

		// Round the quantity if necessary
		if($config['decimalPlaces'] === true) {
			$qty = round($qty, $config['decimalPlaces']);
		}

		// Add an item
		if (isset($_POST[$add]) && $_POST[$add]) {
			$itemAdded = $this->add_item($id, $name, $price, $qty, $url);
			// If not true the add item function returns the error type
			if ($itemAdded !== true) {
				$errorType = $itemAdded;
				switch($errorType) {
					case 'qty':
						$errorMessage = $config['text']['quantityError'];
						break;
					case 'price':
						$errorMessage = $config['text']['priceError'];
						break;
				}
			}
		}

		// Update a single item
		if (isset($_POST['jcartUpdate']) && $_POST['jcartUpdate']) {
			$itemUpdated = $this->update_item($_POST['itemId'], $_POST['itemQty']);
			if ($itemUpdated !== true)	{
				$errorMessage = $config['text']['quantityError'];
			}
		}

		// Update all items in the cart
		if((isset($_POST['jcartUpdateCart']) && $_POST['jcartUpdateCart']) || (isset($_POST['jcartCheckout']) && $_POST['jcartCheckout']))	{
			$cartUpdated = $this->update_cart();
			if ($cartUpdated !== true)	{
				$errorMessage = $config['text']['quantityError'];
			}
		}

		// Remove an item
		/* After an item is removed, its id stays set in the query string,
		preventing the same item from being added back to the cart in
		subsequent POST requests.  As result, it's not enough to check for
		GET before deleting the item, must also check that this isn't a POST
		request. */
		if($_GET['jcartRemove'] && !$_POST) {
			$this->remove_item($_GET['jcartRemove']);
		}

		// Empty the cart
		if(isset($_POST['jcartEmpty']) && $_POST['jcartEmpty']) {
			$this->empty_cart();
		}

		// Determine which text to use for the number of items in the cart
		$itemsText = $config['text']['multipleItems'];
		if ($this->itemCount == 1) {
			$itemsText = $config['text']['singleItem'];
		}

		// Determine if this is the checkout page
		/* First we check the request uri against the config checkout (set when
		the visitor first clicks checkout), then check for the hidden input
		sent with Ajax request (set when visitor has javascript enabled and
		updates an item quantity). */
		$isCheckout = strpos(request_uri(), $checkout);
		if ($isCheckout !== false || (isset($_REQUEST['jcartIsCheckout']) && $_REQUEST['jcartIsCheckout'] == 'true')) {
			$isCheckout = true;
		}
		else {
			$isCheckout = false;
		}

		// Overwrite the form action to post to gateway.php instead of posting back to checkout page
		if ($isCheckout === true) {

			// Sanititze config path
			$path = filter_var($config['jcartPath'], FILTER_SANITIZE_URL);

			// Trim trailing slash if necessary
			$path = rtrim($path, '/');

			$checkout = $path . '/gateway.php';
		}

		// Default input type
		// Overridden if using button images in config.php
		$inputType = 'submit';

		// If this error is true the visitor updated the cart from the checkout page using an invalid price format
		// Passed as a session var since the checkout page uses a header redirect
		// If passed via GET the query string stays set even after subsequent POST requests
		if (isset($_SESSION['quantityError']) && $_SESSION['quantityError'] === true) {
			$errorMessage = $config['text']['quantityError'];
			unset($_SESSION['quantityError']);
		}

		// Set currency symbol based on config currency code
		$currencyCode = trim(strtoupper($config['currencyCode']));
		$currencySymbol = $currencyCode.' ';

		////////////////////////////////////////////////////////////////////////
		// Output the cart

    // Return specified number of tabs to improve readability of HTML output
    function insertTab($n) {
      $tabs = null;
      while ($n > 0) {
        $tabs .= "\t";
        --$n;
      }
      return $tabs;
    }

		// If there's an error message wrap it in some HTML
		if ($errorMessage)	{
			$errorMessage = "<p id='jcart-error'>$errorMessage</p>";
		}

		// Display the cart header
		echo insertTab(1) . "$errorMessage\n";
		echo insertTab(1) . "<form method='post' action='$checkout'>\n";
		echo insertTab(2) . "<fieldset>\n";
		echo insertTab(3) . "<input type='hidden' name='jcartToken' value='{$_SESSION['jcartToken']}' />\n";
		echo insertTab(3) . "<table border='1'>\n";
		echo insertTab(4) . "<thead>\n";
		echo insertTab(5) . "<tr>\n";
		echo insertTab(6) . "<th colspan='3'>\n";
		echo insertTab(7) . "<strong id='jcart-title'>{$config['text']['cartTitle']}</strong> ($this->itemCount $itemsText)\n";
		echo insertTab(6) . "</th>\n";
		echo insertTab(5) . "</tr>". "\n";
		echo insertTab(4) . "</thead>\n";
		// Display the cart footer
		echo insertTab(4) . "<tfoot>\n";
		echo insertTab(5) . "<tr>\n";
		echo insertTab(6) . "<th colspan='3'>\n";

		// If this is the checkout hide the cart checkout button
    // Agent725 hack: always hide button
    /*
		if ($isCheckout !== true) {
			if ($config['button']['checkout']) {
				$inputType = "image";
				$src = " src='{$config['button']['checkout']}' alt='{$config['text']['checkout']}' title='' ";
			}
			echo insertTab(7) . "<input type='$inputType' ".(isset($src)?$src:'')." id='jcart-checkout' name='jcartCheckout' class='btn btn-primary jcart-button' value='{$config['text']['checkout']}' />\n";
		}
    */

		echo insertTab(7) . "<span id='jcart-subtotal'>".$config['text']['subtotal'].": <strong>$currencySymbol" . number_format($this->subtotal, $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</strong></span>\n";
		echo insertTab(6) . "</th>\n";
		echo insertTab(5) . "</tr>\n";
		echo insertTab(4) . "</tfoot>\n";
		echo insertTab(4) . "<tbody>\n";

		// If any items in the cart
		if($this->itemCount > 0) {

			// Display line items
			foreach($this->get_contents() as $item)	{
				echo insertTab(5) . "<tr>\n";
				echo insertTab(6) . "<td class='jcart-item-qty'>\n";
				echo insertTab(7) . "<input name='jcartItemId[]' type='hidden' value='{$item['id']}' />\n";
				echo insertTab(7) . "<input id='jcartItemQty-{$item['id']}' name='jcartItemQty[]' size='2' type='text' value='{$item['qty']}' />\n";
				echo insertTab(6) . "</td>\n";
				echo insertTab(6) . "<td class='jcart-item-name'>\n";

				if ($item['url']) {
					echo insertTab(7) . "<a href='{$item['url']}'>{$item['name']}</a>\n";
				}
				else {
					echo insertTab(7) . $item['name'] . "\n";
				}
				echo insertTab(7) . "<input name='jcartItemName[]' type='hidden' value='{$item['name']}' />\n";
				echo insertTab(6) . "</td>\n";
				echo insertTab(6) . "<td class='jcart-item-price'>\n";
				echo insertTab(7) . "<span>$currencySymbol" . number_format($item['subtotal'], $priceFormat['decimals'], $priceFormat['dec_point'], $priceFormat['thousands_sep']) . "</span><input name='jcartItemPrice[]' type='hidden' value='{$item['price']}' />\n";
				echo insertTab(7) . "<a class='jcart-remove' href='?jcartRemove={$item['id']}'>{$config['text']['removeLink']}</a>\n";
				echo insertTab(6) . "</td>\n";
				echo insertTab(5) . "</tr>\n";
			}
		}

		// The cart is empty
		else {
			echo insertTab(5) . "<tr><td id='jcart-empty' colspan='3'>{$config['text']['emptyMessage']}</td></tr>\n";
		}
		echo insertTab(4) . "</tbody>\n";
		echo insertTab(3) . "</table>\n\n";

    // Agent725 hack: hide buttons
    /*
		echo insertTab(3) . "<div id='jcart-buttons'>\n";

		if ($config['button']['update']) {
			$inputType = "image";
			$src = " src='{$config['button']['update']}' alt='{$config['text']['update']}' title='' ";
		}

		echo insertTab(4) . "<input type='$inputType' $src name='jcartUpdateCart' value='{$config['text']['update']}' class='btn btn-default jcart-button' />\n";

		if ($config['button']['empty']) {
			$inputType = "image";
			$src = " src='{$config['button']['empty']}' alt='{$config['text']['emptyButton']}' title='' ";
		}
		echo insertTab(4) . "<input type='$inputType' $src name='jcartEmpty' value='{$config['text']['emptyButton']}' class='btn btn-default jcart-button' />\n";
		echo insertTab(3) . "</div>\n";
    */
    
		echo insertTab(2) . "</fieldset>\n";
		echo insertTab(1) . "</form>\n\n";
		echo insertTab(1) . "<div id='jcart-tooltip'></div>\n";
	}
}

// Start a new session in case it hasn't already been started on the including page
@session_start();

// Initialize jcart after session start
$jcart = $_SESSION['jcart'];
if(!is_object($jcart)) {
	$jcart = $_SESSION['jcart'] = new Jcart();
}

// Enable request_uri for non-Apache environments
// See: http://api.drupal.org/api/function/request_uri/7
if (!function_exists('request_uri')) {
	function request_uri() {
		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
		}
		else {
			if (isset($_SERVER['argv'])) {
				$uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
			}
			elseif (isset($_SERVER['QUERY_STRING'])) {
				$uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
			}
			else {
				$uri = $_SERVER['SCRIPT_NAME'];
			}
		}
		$uri = '/' . ltrim($uri, '/');
		return $uri;
	}
}
?>

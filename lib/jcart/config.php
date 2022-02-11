<?php

// jCart v1.3
// http://conceptlogic.com/jcart/

// Do NOT store any sensitive info in this file!!!
// It's loaded into the browser as plain text via Ajax


////////////////////////////////////////////////////////////////////////////////
// REQUIRED SETTINGS
error_reporting(E_ALL & ~E_NOTICE);

// Path to your jcart files
$config['jcartPath']              = dirname($_SERVER['PHP_SELF']);  // DEPRECATED: $config['jcartPath']                = realpath(dirname(__FILE__));

global $SET,$DEF,$STR;

// Path to your checkout page
$config['checkoutPath']           = 'checkout';

// The HTML name attributes used in your item forms
$config['item']['id']             = 'my-item-id';    // Item id
$config['item']['name']           = 'my-item-name';    // Item name
$config['item']['price']          = 'my-item-price';    // Item price
$config['item']['qty']            = 'my-item-qty';    // Item quantity
$config['item']['url']            = 'my-item-url';    // Item URL (optional)
$config['item']['add']            = 'my-add-button';    // Add to cart button

////////////////////////////////////////////////////////////////////////////////
// OPTIONAL SETTINGS

// Three-letter currency code, defaults to USD if empty
// See available options here: http://j.mp/agNsTx
$config['currencyCode']           = ($SET['shopcurrency']?$SET['shopcurrency']:$DEF['shopcurrency']);

// Add a unique token to form posts to prevent CSRF exploits
// Learn more: http://conceptlogic.com/jcart/security.php
$config['csrfToken']              = false;

// Override default cart text
$config['text']['cartTitle']      = $STR['Shopping_cart'];    // Shopping Cart
$config['text']['singleItem']     = $STR['item'];    // Item
$config['text']['multipleItems']  = $STR['items'];    // Items
$config['text']['subtotal']       = $STR['Subtotal'];    // Subtotal
$config['text']['update']         = $STR['Update'];    // update
$config['text']['checkout']       = $STR['Checkout'];    // checkout
$config['text']['removeLink']     = $STR['remove_item'];    // remove
$config['text']['emptyButton']    = $STR['Empty_cart'];    // empty
$config['text']['emptyMessage']   = $STR['Your_cart_is_empty'];    // Your cart is empty!
$config['text']['itemAdded']      = FALSE;    // DISABLED TEXT DUE TO CRAPPY BUG: Item added!
$config['text']['priceError']     = $STR['Invalid_price_format'];    // Invalid price format!
$config['text']['quantityError']  = $STR['Quantities_whole_numbers'];    // Item quantities must be whole numbers!

// Override the default buttons by entering paths to your button images
$config['button']['checkout']     = '';
$config['button']['update']       = '';
$config['button']['empty']        = '';


////////////////////////////////////////////////////////////////////////////////
// ADVANCED SETTINGS

// Display tooltip after the visitor adds an item to their cart?
$config['tooltip']                = true;

// Allow decimals in item quantities?
$config['decimalQtys']            = true;

// How many decimal places are allowed?
$config['decimalPlaces']          = 8;

// Number format for prices, see: http://php.net/manual/en/function.number-format.php
$config['priceFormat']            = array('decimals' => 2, 'dec_point' => '.', 'thousands_sep' => ',');

// Use default values for any settings that have been left empty
/*
if (!isset($config['currencyCode']) || !$config['currencyCode']) $config['currencyCode']                     = 'EUR';
if (!isset($config['text']['cartTitle']) || !$config['text']['cartTitle']) $config['text']['cartTitle']           = 'Shopping cart';
if (!isset($config['text']['singleItem']) || !$config['text']['singleItem']) $config['text']['singleItem']         = 'item';
if (!isset($config['text']['multipleItems']) || !$config['text']['multipleItems']) $config['text']['multipleItems']   = 'items';
if (!isset($config['text']['subtotal']) || !$config['text']['subtotal']) $config['text']['subtotal']             = 'Subtotal';
if (!isset($config['text']['update']) || !$config['text']['update']) $config['text']['update']                 = 'update';
if (!isset($config['text']['checkout']) || !$config['text']['checkout']) $config['text']['checkout']             = 'checkout';
if (!isset($config['text']['removeLink']) || !$config['text']['removeLink']) $config['text']['removeLink']         = 'remove';
if (!isset($config['text']['emptyButton']) || !$config['text']['emptyButton']) $config['text']['emptyButton']       = 'empty';
if (!isset($config['text']['emptyMessage']) || !$config['text']['emptyMessage']) $config['text']['emptyMessage']     = 'Your cart is empty!';
if (!isset($config['text']['itemAdded']) || !$config['text']['itemAdded']) $config['text']['itemAdded']           = 'Item added!';
if (!isset($config['text']['priceError']) || !$config['text']['priceError']) $config['text']['priceError']         = 'Invalid price format!';
if (!isset($config['text']['quantityError']) || !$config['text']['quantityError']) $config['text']['quantityError']   = 'Item quantities must be whole numbers!';
*/

if ($_GET['ajax'] == 'true') {
  header('Content-type: application/json; charset=utf-8');
  echo json_encode($config);
}

?>

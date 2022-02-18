<?php

/*
 * 
 *  GATEWAY FOR PAYING BY BANKWIRES
 * 
 */

$array['payment_method']='bankwire';
$array['forwardurl'] = $SITE.'?checkout&x='.$array['ordernumber'].'&s=99';

function paymentgate($array,$shop) {
		return $array;
}


function paymentform($array,$shop) {
		global $GATEWAY,$SET,$STR;
		// get gateway variables
		$json = json_decode(file_get_contents($GATEWAY['directory'].'gateway.json'));
		foreach($json as $key => $val) {
			$GATEWAY[$key] = $val;
		}

		if (!isset($array['amount']) || !isset($array['ordernumber'])) {
			echo 'Missing amount or ordernumber!';
			die();
		}
		
		echo '<div class="row">
			  <div class="col-xs-12">
			    <h4>'.$STR['Amount_to_pay'].': <span style="font-weight: bold;">'.$SET['shopcurrency'].' '.$shop->formatn($array['amount']).'</span></h4>
			    <br>'.
			   '<span>'.$GATEWAY['Payment_instructions'].'</span><br><br>'.
		     '</div>
			  </div>';
		
			 
		echo '<div class="row" id="user-paymentdata" style="background: #FFF; border: 3px dashed #777;">'. 
			'<div class="col-xs-12"><br></div>' .
			 '<div class="col-xs-12 col-sm-4">' .
			   	$STR['Bank'] . ':' .
		     '</div>';
		echo '<div class="col-xs-12 col-sm-8">' .
				$GATEWAY['Bank_name'] . 
			 	'<br><br>'.
			 '</div>';
		echo '<div class="col-xs-12 col-sm-4">' .
			        $STR['Account'] . ':' .
			 '</div>' ;
		echo '<div class="col-xs-12 col-sm-8">' .
				   $GATEWAY['Bank_account'] .
				   '<br><br>'.
			 '</div>';
		echo '<div class="col-xs-12 col-sm-4">' .
				$STR['Beneficiary'] . ':' .
			 '</div>';
		echo '<div class="col-xs-12 col-sm-8">' .
				$GATEWAY['Bank_beneficiary'] .
				'<br><br>'. 
			 '</div>';
		echo '<div class="col-xs-12 col-sm-4">' . 
				$STR['Ordernumber'] . ':' .
		 	 '</div>';
		echo '<div class="col-xs-12 col-sm-8">' . 
				$array['ordernumber'] . 
				'<br><br>' . 
			 '</div>';
		echo '</div>';
		// simply forward all other data
		echo '<input type="hidden" name="amount" value="' . ($array['amount']*100) . '" />';
		echo '<input type="hidden" name="x" value="'.$shop->tx($array).'" />';
}

?>

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

		echo '<div style="width: 100%; margin-top: 48px; text-align: center;"><h4>'.$STR['Amount_to_pay'].': <span style="font-weight: bold;">'.$SET['shopcurrency'].' '.$shop->formatn($array['amount']).'</span></h4><br>'.
			 '<span>'.$GATEWAY['Payment_instructions'].'</span><br><br>'.
			 '<div style="display: inline-block; text-align: left; background: #FFF none repeat scroll 0% 0%; font-weight: bold; margin: 12px 0; padding: 12px; min-width: 320px; border: 3px dashed #777;"><table>'.
			 '<tr><td style="width: 200px;">'.$STR['Bank'].': </td><td>'.$GATEWAY['Bank_name'].'</td></tr>'.
			 '<tr><td>'.$STR['Account'].': </td><td>'.$GATEWAY['Bank_account'].'</td></tr>'.
			 '<tr><td>'.$STR['Beneficiary'].': </td><td>'.$GATEWAY['Bank_beneficiary'].'</td></tr>'.
			 '</table></div></div>';
		// simply forward all other data
		echo '<input type="hidden" name="amount" value="' . ($array['amount']*100) . '" />';
		echo '<input type="hidden" name="x" value="'.$shop->tx($array).'" />';
}

?>

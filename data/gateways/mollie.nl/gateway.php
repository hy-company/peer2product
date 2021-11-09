<?php

/*
 * 
 *  WARNING: REALLY HACKY CODE!
 *  WHY? -> THIS HAS BEEN WRITTEN BY THE BOFFINS AT PAY.NL!
 * 
 */

//        if(isset($_POST['next']) && $_POST['next']=='Pay') {
function paymentgate($array,$shop) {
		global $GATEWAY;
		
		// include the settings file
		require($GATEWAY['directory'].'settings.php');

		try
		{
			// an amount has been entered and an payment method has been chosen
			// now we can create a Pay.nl transaction
			$objPay = new Transaction($iProgramId, $iWebsiteId, $iWebsiteLocationId, $arrEmailAddress, $iAccountId, $strToken, $boolDebugMode);

			// set vars from settings.php
			if(isset($_POST['external_order']) && strlen($_POST['external_order']) > 0)
			{
			  $objPay->setExternalOrder($_POST['external_order']);
			}
			$objPay->setExchangeUrl($strExchangeUrl);
			$objPay->setReturnUrl($strReturnUrl);
			$objPay->setTestMode($boolTestMode);

			// check if a bank has been chosen
			if(intval($_POST['payment_profile']) == 10)
			{                    
				$objPay->setBankId($_POST['bank_id']);
			}

			// create transaction
			$transaction = $objPay->createTransaction(intval($_POST['amount']), intval($_POST['payment_profile']), array('extra1' => isset($_POST['external_order']) ? $_POST['external_order'] : ''));
		}
		catch(Exception $exc)
		{
			// set error in debug info
			if(is_object($objPay) && $objPay->getDebugMode()) $objPay->doDebug('An error occurred: ' . $exc->getMessage(). ' - Script execution stopped');
			
			// put error on screen
			//else echo 'An error occurred: '.$exc->getMessage(). ' - Script execution stopped';
			
			//  translate to: &x='.$_GET['orderId'].'&s='.$_GET['orderStatusId'])
			
			$array['forwardurl'] = $strReturnUrl.'?orderId='.$array['ordernumber'].'&orderStatusId=0&error='.$exc->getMessage();
			return $array;		
			}

		// first we prepare our pending order array to later write to a file...
		$array['payment_method']=$_POST['payment_profile'].(isset($_POST['bank_id'])?','.$_POST['bank_id']:'');

		$array['forwardurl'] = $transaction['issuerUrl'];
		return $array;
}


function paymentform($array,$shop) {		
		global $GATEWAY;
		
		// include the settings file
		require($GATEWAY['directory'].'settings.php');

		if (!isset($array['amount']) || !isset($array['ordernumber'])) {
			echo 'Missing amount or ordernumber!';
			die();
		}

		echo '</body>
			<head>
				<!-- css -->
				<link rel="stylesheet" type="text/css" href="'.$GATEWAY['directory'].'/includes/css/ppt.css" />
				<script src="'.$GATEWAY['directory'].'/includes/script/ppt.js" type="text/javascript"></script>        
			</head>
			<body>';

		try
		{
			// an amount has been entered and the submit button has been clicked
			// now we have to set the entered amount and display the available payment profiles
			// create object
			$objPay = new Transaction($iProgramId, $iWebsiteId, $iWebsiteLocationId, $arrEmailAddress, $iAccountId, $strToken, $boolDebugMode);

			// get the availabel payment profiles
			$arrPaymentProfiles = $objPay->getActivePaymentProfiles();

			// generate select box with availabel payment profiles
			$selectPaymentProfiles = '<option>Select</option>';
			if(is_array($arrPaymentProfiles))
			{
				foreach($arrPaymentProfiles as $arrPaymentProfile)
				{
					$selectPaymentProfiles .= '<option value="' . $arrPaymentProfile['id'] . '">' . $arrPaymentProfile['name'] . '</option>';
				}
			}

			// if payment method 10 is available we need to show a list of banks
			if($objPay->isBankList($arrPaymentProfiles))
			{
				$arrBankList = $objPay->getIdealBanks();

				if(is_array($arrBankList))
				{
					$selectBank = '';

					foreach($arrBankList as $arrBank)
					{
						// generate list of banks
						$selectBank .= "<div><input type='radio' name='bank_id' value='" . $arrBank['id'] . "' /><img src='" . $arrBank['icon'] . "' alt='" . $arrBank['name'] . "'></div>";
					}
				}
			}
		}
		catch(Exception $exc)
		{
			// set error in debug info
			if(is_object($objPay) && $objPay->getDebugMode()) $objPay->doDebug('An error occured: ' . $exc->getMessage());
							
			// put error on screen
			else echo 'An error occurred: '.$exc->getMessage();
			
			exit;
		}

		echo "<div class='ppt'>";

		// amount in cents
		echo "<div>";
		echo "<label>";
		echo "Amount to pay:";
		echo "</label>";
		echo "<input type='hidden' name='amount' value='" . ($array['amount']*100) . "' />";
		echo '&euro; '.number_format($array['amount'], 2, ',', '');
		echo "</div>";

		// optional: an external order
		echo "<div>";
		echo "<label>";
		echo "Ordernumber:";
		echo "</label>";
		echo "<input type='hidden' name='external_order' value='" . $array['ordernumber'] . "' />";
		echo $array['ordernumber'];
		echo "</div>";

		// available payment methods
		echo "<div>";
		echo "<label>";
		echo "Payment method:";
		echo "</label>";
		echo "<select name='payment_profile'>";
		echo $selectPaymentProfiles;
		echo "</select>";
		echo "</div>";

		// list of banks in case iDEAL has been selected
		echo "<div id='banks'>";
		echo "<label>";
		echo "Select bank:";
		echo "</label>";
		echo "<div class='bankList'>";
		echo $selectBank;
		echo "</div>";

		// x array value
		echo "<input type='hidden' name='x' value='".$shop->tx($array)."' />";

		// submit button
		echo "</div>";
		echo '<br/></div>';
}

?>

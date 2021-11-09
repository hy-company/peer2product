<?php
/*
 * Pay.nl Exchange Class 
 * 
 * @author      Kelvin Huizing
 * @copyright   2013 - Pay.nl
 * @version     1.0
 * @link        http://www.pay.nl/
 */
final class Exchange extends Transaction
{
    /**
     * @var bool determines if a transaction is a refund 
     */
    private $boolRefund = false;

    /**
     * @var array with the status of the payment
     */
    private $arrPaymentStatus = array();

    /**
     * @string containing the message of the email to be send
     */
    private $strMessage = '';

    /**
     *
     * @string containg the subject of the mail to be send 
     */
    private $strSubject = '';

    /**
     * constructor
     * 
     * @param array $arrGet
     * @param int $iAccountId
     * @param string $strToken
     * @param array $arrEmailAddress
     * @param bool $boolReveiveExchangeMail 
     */
    public function __construct(array $arrGet, $iAccountId, $strToken, $arrEmailAddress, $boolReveiveExchangeMail = true)
    {
        // call the parent's constructor
        parent::__construct($arrGet['program_id'], $arrGet['website_id'], $arrGet['website_location_id'], $arrEmailAddress, $iAccountId, $strToken);

        // set payment session id
        $this->setPaymentSessionId($arrGet['payment_session_id']);

        // set external order id
        $this->setExternalOrder($arrGet['object']);

        // check for a refund
        $this->setRefund($this->isRefund($arrGet['action']));

        // check for the payment state
        $this->arrPaymentStatus = $this->getPaymentStatus($arrGet['payment_session_id']);


        // check if payment is a refund
        if($this->getRefund())
        {
            // notify client by mail about the transaction
            if($boolReveiveExchangeMail)
            {
                $this->doSendEmail();
            }

            echo "TRUE|refund OK";
            return true;
        }
        else
        {
            // check if the payment has been processed
            if($this->arrPaymentStatus['status'] == 'PAID' || $this->arrPaymentStatus['status'] == 'PAID_CHECKAMOUNT')
            {

                // notify client by mail about the transaction
                if($boolReveiveExchangeMail)
                {
                    $this->doSendEmail();
                }

                echo "TRUE|transaction OK";
                return true;
            }

            echo "TRUE|Nothing done";
            return true;
        }
    }

    /**
     * get the bool if an transaction is a refund
     * 
     * @return bool 
     */
    private function getRefund()
    {
        return $this->boolRefund;
    }

    /**
     * set the bool if an transaction is a refund
     *  
     * @param bool $boolRefund
     * @return bool 
     */
    private function setRefund($boolRefund)
    {
        if($this->validateRefund($boolRefund))
        {
            return true;
        }

        return false;
    }

    /**
     * validate the bool if an transaction is a refund
     * 
     * @param bool $boolRefund
     * @return bool 
     */
    private function validateRefund($boolRefund)
    {
        if(!isset($boolRefund) && !is_bool($boolRefund))
        {

            // write errorlog
            $this->doError('Invalid refund value');

            // we don't throw an exception here
            // cause we don't want the script to stop executing

            return false;
        }

        return true;
    }

    /**
     * determines if exchange is a refund
     *
     * @param  $paymentStatus
     * @return unknown
     */
    private function isRefund($strAction)
    {
        $arrActionDel = array();
        $arrActionDel[] = "delete";
        $arrActionDel[] = "incassodelete";
        $arrActionDel[] = "incassopredeclined";
        $arrActionDel[] = "incassostorno";

        if(in_array(strtolower($strAction), $arrActionDel))
        {
            return true;
        }

        return false;
    }

    private function doSendEmail($strMessage = '', $strSubject = '')
    {

        // generates the subject to be send
        // check if a custom subject has been set
        if(strlen($strSubject) > 0)
        {
            $this->strSubject = $strSubject;
        }
        else
        {
            // set the standard subject
            $this->strSubject = "New Pay.nl action";
        }

        // generates the message to be send
        // check if a custom message has been set
        if(strlen($strMessage) > 0)
        {
            $this->strMessage = $strMessage;
        }
        else
        {
            $strMessageKindOfPayment = 'payment';
            $strMessageExternalOrder = 'unknown / net set';

            // set message vars
            if($this->getRefund()) $strMessageKindOfPayment = 'refund';
            if(strlen($this->getExternalOrder()) > 0) $strMessageExternalOrder = $this->getExternalOrder();

            // we need to convert the payment from cents to euro's
            $iMessageAmount = number_format(($this->arrPaymentStatus['amount'] / 100), 2, ',', '.');

            // set the standard message
            $this->strMessage = "Dear sir/madam,\nA " . $strMessageKindOfPayment . " has been completed for " . $this->getPaymentSessionId() . ".\nThe order number for this was: " . $strMessageExternalOrder . ".\nAmount paid: " . $iMessageAmount . " EUR.\n\nKind regards,\nExchange script";
        }

        // send the email to the all addresses set in the settings.php
        foreach($this->getEmailAddress() as $strEmail)
        {
            mail($strEmail, $this->strSubject, $this->strMessage);
        }
    }
}

/** eof **/
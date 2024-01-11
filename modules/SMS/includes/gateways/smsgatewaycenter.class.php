<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class smsgatewaycenter extends SMS
{
    private $wsdl_link = "https://www.smsgateway.center/SMSApi/rest";
    public $tariff = "https://www.smsgateway.center/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "91xxxxxxxxxx";
    }

    public function SendSMS()
    {
        // Check gateway credit
        if (!$this->GetCredit()) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Your account does not have credit to send SMS.'));
        }

        /**
         * Modify sender number
         *
         * @param string $this ->from sender number.
         * @since 3.4
         *
         */


        /**
         * Modify Receiver number
         *
         * @param array $this ->to receiver number
         * @since 3.4
         *
         */


        /**
         * Modify text message
         *
         * @param string $this ->msg text message.
         * @since 3.4
         *
         */


        $msg = urlencode($this->msg);

        $result = file_get_contents($this->wsdl_link . "send_sms_2.php?UserName=" . $this->username . "&Password=" . $this->password . "&Type=Bulk&To=" . implode(',', $this->to) . "&Mask=" . $this->from . "&Message=" . $msg);

        $jsonDecode = json_decode($result);

        if ($jsonDecode->status == 'error') {
            return false;
        }

        if ($jsonDecode->status == 'success') {

            return lcfirst($jsonDecode->status) . ' | ' . $jsonDecode->transactionId;
        }

        return new SMS_Error('send-sms', lcfirst($jsonDecode->status) . ' | ' . $jsonDecode->transactionId);

    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $result = file_get_contents($this->wsdl_link . "balanceValidityCheck?userId=" . $this->username . "&password=" . $this->password . "&format=json");

        $jsonDecode = json_decode($result);

        if ($jsonDecode->status !== 'success') {
            return new SMS_Error('account-credit', "$jsonDecode->status | $jsonDecode->errorCode | $jsonDecode->reason");
        }

        return $jsonDecode->smsBalance;
    }
}

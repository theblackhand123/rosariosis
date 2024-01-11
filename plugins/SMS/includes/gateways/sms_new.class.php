<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class sms_new extends SMS
{
    private $wsdl_link = "http://n.sms.ir/SendMessage.ashx";
    public $tariff = "http://sms.ir/";
    public $unitrial = false;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "09xxxxxxxx";

        ini_set("soap.wsdl_cache_enabled", "0");
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

        foreach ($this->to as $number) {
            $result = file_get_contents("{$this->wsdl_link}?user={$this->username}&pass={$this->password}&lineNo={$this->from}&to={$number}&text={$msg}");
        }

        if ($result == 'ok') {
            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */


            return true;
        }

        return new SMS_Error('send-sms', $result);
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        return 1;
    }
}

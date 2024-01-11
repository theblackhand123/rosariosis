<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\gateways\includes\websmscy\WebsmsClient;
use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class websmscy extends SMS
{
    private $wsdl_link = "https://www.websms.com.cy/webservices/websms.wsdl";
    public $tariff = "https://www.websms.com.cy/";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "Phone numbers must be in the 9XXXXXXX format beginning with 99, 96 or 97";
        @ini_set("soap.wsdl_cache_enabled", "0");
        include_once('includes/websmscy/soapClient.class.php');
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


        $cfg = array(
            'wsdl_file' => $this->wsdl_link,
            'username' => $this->username,
            'password' => $this->password,
        );

        $ws = new WebsmsClient($cfg);

        try {
            $result = $ws->submitSM($this->from, $this->to, $this->msg, "GSM");

            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */


            return $result;
        } catch (Exception $e) {
            return new SMS_Error('send-sms', $e->getMessage());
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        if (!class_exists('SoapClient')) {
            return new SMS_Error('required-class', dgettext('SMS', 'Class SoapClient not found. please enable php_soap in your php.'));
        }

        $cfg = array(
            'wsdl_file' => $this->wsdl_link,
            'username' => $this->username,
            'password' => $this->password,
        );

        $ws = new WebsmsClient($cfg);

        try {
            $credits = $ws->getCredits();

            return $credits;
        } catch (Exception $e) {
            return new SMS_Error('account-credit', $e->getMessage());
        }
    }
}

<?php

namespace plugins\SMS\includes\gateways;

use AfricasTalkingGatewayException;
use plugins\SMS\includes\gateways\includes\africastalking\AfricasTalkingGateway;
use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class africastalking extends SMS
{
    private $wsdl_link = '';
    private $client = null;
    private $http;
    public $tariff = "http://africastalking.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        include_once('includes/africastalking/AfricasTalkingGateway.php');

        $this->validateNumber = "+254711XXXYYY";
        $this->help = "API key generated from your account settings";
        $this->has_key = true;
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


        $gateway = new AfricasTalkingGateway($this->username, $this->has_key);

        try {
            $results = $gateway->sendMessage(implode(',', $this->to), $this->msg, $this->from);

            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */


            return $result;
        } catch (AfricasTalkingGatewayException $e) {
            return new SMS_Error('send-sms', $e->getMessage());
        }
    }

    public function GetCredit()
    {
        // Check username and key
        if (!$this->username or !$this->has_key) {
            return new SMS_Error('account-credit', dgettext('SMS', 'API key not set for this gateway'));
        }

        if (!function_exists('curl_version')) {
            return new SMS_Error('required-function', dgettext('SMS', 'CURL extension not found in your server. please enable curl extension.'));
        }

        $gateway = new AfricasTalkingGateway($this->username, $this->has_key);

        try {
            $data = $gateway->getUserData();
            preg_match('!\d+!', $data->balance, $matches);

            return $matches[0];
        } catch (AfricasTalkingGatewayException $e) {
            return new SMS_Error('account-credit', $e->getMessage());
        }
    }
}

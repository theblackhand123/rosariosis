<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class smsapi extends SMS
{
    private $wsdl_link = "https://api.smsapi.pl/";
    public $tariff = "https://smsapi.pl/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "48500500500 or with country code";
        $this->help = "Please enter your username to username and api pass MD5 to password field.";
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


        $response = wp_remote_post($this->wsdl_link . 'sms.do?username=' . urlencode($this->username) . '&password=' . $this->password . '&message=' . urlencode($this->msg) . '&to=' . implode(",", $this->to) . '&from=' . urlencode($this->from));

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('send-sms', dgettext('SMS', 'No response'));
        }

        // Ger response code
        $response_code = wp_remote_retrieve_response_code($response);

        // Check response code
        if ($response_code == '200' and strpos($response['body'], 'OK') !== false) {
            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $response result output.
             * @since 2.4
             *
             */


            return $response['body'];
        } else {
            return new SMS_Error('send-sms', $response['body']);
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $result = @file_get_contents($this->wsdl_link . 'user.do?username=' . urlencode($this->username) . '&credits=1&details=1&password=' . $this->password);

        return $result;
    }
}

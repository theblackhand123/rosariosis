<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class smsgatewayhub extends SMS
{
    private $wsdl_link = "http://login.smsgatewayhub.com/api/mt/";
    public $tariff = "https://www.smsgatewayhub.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "91989xxxxxxx,91999xxxxxxx";

        // Enable api key
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


        // Implode numbers
        $to = implode(',', $this->to);

        // Unicode message
        $msg = urlencode($this->msg);

        $response = wp_remote_get($this->wsdl_link . 'SendSMS?APIKey=' . $this->has_key . '&senderid=' . urlencode($this->from) . '&channel=2&DCS=0&flashsms=0&number=' . $to . '&text=' . $msg . '&route=clickhere');

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('account-credit', dgettext('SMS', 'No response'));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == '200') {
            // Decode json
            $result = json_decode($response['body']);

            // Check response
            if ($result->ErrorMessage != 'Success') {
                return new SMS_Error('send-sms', $result->ErrorMessage);
            }

            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */

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

        $response = wp_remote_get($this->wsdl_link . 'GetBalance?APIKey=' . $this->has_key);

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('account-credit', dgettext('SMS', 'No response'));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == '200') {
            $result = json_decode($response['body']);

            // Check response
            if ($result->ErrorMessage != 'Success') {
                return new SMS_Error('account-credit', $result->ErrorMessage);
            }

            return $result->Balance;
        } else {
            return new SMS_Error('account-credit', $response['body']);
        }
    }
}

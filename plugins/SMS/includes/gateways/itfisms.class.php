<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class itfisms extends SMS
{
    private $wsdl_link = "http://websms.itfisms.com/vendorsms/";
    public $tariff = "http://www.itfisms.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "e.g. 9029963999";
        $this->help = 'Please enter Route ID in API Key field';
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


        $response = wp_remote_get($this->wsdl_link . "pushsms.aspx?user=" . urlencode($this->username) . "&password=" . urlencode($this->password) . "&msisdn=" . implode(',', $this->to) . "&sid=" . urlencode($this->from) . "&msg=" . urlencode($this->msg) . "&fl=0&gwid=2");

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('send-sms', dgettext('SMS', 'No response'));
        }

        // Ger response code
        $response_code = wp_remote_retrieve_response_code($response);

        // Check response code
        if ($response_code == '200') {
            $response = json_decode($response['body']);

            if ($response->ErrorMessage == 'Success') {
                $this->InsertToDB($this->from, $this->msg, $this->to);

                /**
                 * Run hook after send sms.
                 *
                 * @param string $response result output.
                 * @since 2.4
                 *
                 */


                return $response;
            } else {
                return new SMS_Error('send-sms', $response->ErrorMessage);
            }

        } else {
            return new SMS_Error('send-sms', $response['body']);
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username or !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $response = wp_remote_get($this->wsdl_link . "CheckBalance.aspx?user=" . urlencode($this->username) . "&password=" . urlencode($this->password));

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('account-credit', dgettext('SMS', 'No response'));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == '200') {
            if (strstr($response['body'], 'Success')) {
                return $response['body'];
            } else {
                return new SMS_Error('account-credit', $response['body']);
            }
        } else {
            return new SMS_Error('account-credit', $response['body']);
        }

        return true;
    }
}

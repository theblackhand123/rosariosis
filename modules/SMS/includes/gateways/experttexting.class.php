<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class experttexting extends SMS
{
    private $wsdl_link = "https://www.experttexting.com/ExptRestApi/sms/";
    public $tariff = "http://experttexting.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "The number you want to send message to. Number should be in international format. Ex: to=17327572923";
        $this->has_key = true;
        $this->help = "You can find the API Key under \"Account Settings\" in <a href='https://www.experttexting.com/appv2/Dashboard/Profile'>ExpertTexting Profile</a>.";
        $this->from = 'DEFAULT';
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

        // FJ Fix #1 set type to Unicode.
        $response = wp_remote_get($this->wsdl_link . "json/Message/Send?username=" . urlencode($this->username) . "&password=" . urlencode($this->password) . "&api_key=" . $this->has_key . "&from=" . urlencode($this->from) . "&to=" . implode(',', $this->to) . "&text=" . urlencode($this->msg) . "&type=unicode", array('timeout' => 30));

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('send-sms', dgettext('SMS', 'No response'));
        }

        // Ger response code
        $response_code = wp_remote_retrieve_response_code($response);

        // Check response code
        if ($response_code == '200') {
            $json = json_decode($response['body']);

            if ($json->Status == 0) {
                $this->InsertToDB($this->from, $this->msg, $this->to);

                /**
                 * Run hook after send sms.
                 *
                 * @param string $response result output.
                 * @since 2.4
                 *
                 */


                return $json;
            } else {
                return new SMS_Error('send-sms', $json->ErrorMessage);
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

        $response = wp_remote_get($this->wsdl_link . "json/Account/Balance?username=" . urlencode($this->username) . "&password=" . urlencode($this->password) . "&api_key=" . $this->has_key, array('timeout' => 30));

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('account-credit', dgettext('SMS', 'No response'));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == '200') {
            $json = json_decode($response['body']);

            if ($json->Status == 0) {
                return $json->Response->Balance;
            } else {
                return new SMS_Error('account-credit', $json->ErrorMessage);
            }

        } else {
            return new SMS_Error('account-credit', $response['body']);
        }

        return true;
    }
}

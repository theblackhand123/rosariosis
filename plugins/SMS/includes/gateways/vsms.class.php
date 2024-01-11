<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class vsms extends SMS
{
    private $wsdl_link = "http://vsms.club/api/";
    public $tariff = "http://vsms.club/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "92xxxxxxxxxx";
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


        $url = $this->wsdl_link . "Relay/SendMultiSms";

        $fields = array(
            'ApiKey' => $this->has_key,
            'PhoneNumber' => implode(",", $this->to),
            'Message' => $this->msg,
            'SenderId' => $this->from
        );

        $fields_string = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($fields_string)
            )
        );

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result == '"true"') {
            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */


            return $result;
        } else {
            return new SMS_Error('send-sms', $result);
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username or !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        return true;
    }
}

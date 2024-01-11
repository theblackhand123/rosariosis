<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class instantalerts extends SMS
{
    private $wsdl_link = "http://instantalerts.co/api/";
    public $tariff = "http://springedge.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "90xxxxxxxxxx";
        $this->has_key = true;
    }

    public function SendSMS()
    {
        // Check gateway credit
        if (!$this->GetCredit()) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Your account does not have credit to send SMS.'));
        }

        // Encode message
        $msg = urlencode($this->msg);

        foreach ($this->to as $to) {
            $result = file_get_contents($this->wsdl_link . 'web/send/?apikey=' . $this->has_key . '&sender=' . $this->from . '&to=' . $to . '&message=' . $msg . '&format=json');
        }

        if (isset($result['MessageIDs'])) {
            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */


            return $result;
        }

        return new SMS_Error('send-sms', $result);
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        // Get data
        $get_data = file_get_contents($this->wsdl_link . 'status/credit?apikey=' . $this->has_key);

        // Check enable simplexml function in the php
        if (!function_exists('simplexml_load_string')) {
            return new SMS_Error('account-credit', $result);
        }

        // Load xml
        $xml = simplexml_load_string($get_data);

        return (int)$xml->credits;
    }
}

<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class sonoratecnologia extends SMS
{
    private $wsdl_link = "http://sonoratecnologia.ddns.net:1002/";
    public $tariff = "http://www.sonoratecnologia.com.br/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "Entre with country code like (27xxxxxxxxxx)";
        $this->help = "For configuration gateway, please enter your username and password and enter the port gateway in `API/Key` field.";
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
        $to = implode(",", $this->to);

        // Encode message
        $msg = urlencode($this->msg);

        // Set gateway port
        if ($this->has_key) {
            $port = "&port=" . $this->has_key;
        } else {
            $port = '';
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "1002",
            CURLOPT_URL => $this->wsdl_link . "sendsms?username=" . $this->username . "&password=" . $this->password . "&phonenumber=" . $to . "&message=" . $msg . $port . "&report=1&timeout=0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 4f6990c5-c293-1dba-1ef5-c77cef7fee3d"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return false;
        }

        if (strstr($response, 'success')) {

            // Insert to DB
            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */


            return true;

        } else {
            return new SMS_Error('send-sms', $result);
        }


    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        return true;
    }
}

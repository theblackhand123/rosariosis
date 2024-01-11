<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class asanak extends SMS
{
    private $wsdl_link = "http://panel.asanak.ir/webservice/v1rest/sendsms";
    public $tariff = "http://asanak.ir/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    function __construct()
    {
        parent::__construct();
        $this->validateNumber = "09xxxxxxxx";
    }

    function SendSMS()
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


        $to = implode('-', $this->to);
        $msg = urlencode(trim($this->msg));
        $url = $this->wsdl_link . '?username=' . $this->username . '&password=' . urlencode($this->password) . '&source=' . $this->from . '&destination=' . $to . '&message=' . $msg;

        $headers[] = 'Accept: text/html';
        $headers[] = 'Connection: Keep-Alive';
        $headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';

        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);

        if (curl_exec($process)) {
            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $process result output.
             * @since 2.4
             *
             */


            return $process;
        } else {
            return new SMS_Error('send-sms', $process);
        }
    }

    function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        return true;
    }
}

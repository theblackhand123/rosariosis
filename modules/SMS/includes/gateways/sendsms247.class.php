<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class sendsms247 extends SMS
{
    private $wsdl_link = "http://www.sendsms247.com/components/com_smsreseller/smsapi.php";
    public $tariff = "http://www.sendsms247.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "2348033333333";
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


        $to = implode(',', $this->to);
        $msg = urlencode($this->msg);

        $result = file_get_contents("{$this->wsdl_link}?username={$this->username}&password={$this->password}&sender={$this->from}&recipient={$to}&message={$msg}");

        if ($result == 'OK') {
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

        $result = file_get_contents("{$this->wsdl_link}?username={$this->username}&password={$this->password}&balance=true");

        if ($result == '2905') {
            return new SMS_Error('account-credit', $result);
        } else {
            return $result;
        }
    }
}

<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class isms extends SMS
{
    private $wsdl_link = "https://www.isms.com.my/";
    public $tariff = "https://www.isms.com.my/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
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


        $msg = urlencode($this->msg);

        foreach ($this->to as $number) {
            $result = file_get_contents("{$this->wsdl_link}isms_send.php?un={$this->username}&pwd={$this->password}&dstno={$number}&msg={$msg}&type=1&sendid={$this->from}");
        }

        if ($result) {
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

        $result = file_get_contents("{$this->wsdl_link}isms_balance.php?un={$this->username}&pwd={$this->password}");
        if (preg_replace('/[^0-9]/', '', $result) == 1008) {
            return new SMS_Error('account-credit', $result);
        } else {
            return $result;
        }
    }
}

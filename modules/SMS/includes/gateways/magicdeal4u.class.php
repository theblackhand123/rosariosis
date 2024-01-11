<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class magicdeal4u extends SMS
{
    private $wsdl_link = "http://sms.magicdeal4u.com/smsapi/";
    public $tariff = "http://www.magicdeal4u.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "";
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


        $to = implode(",", $this->to);
        $msg = urlencode($this->msg);
        $result = file_get_contents($this->wsdl_link . "pushsms.aspx?user=" . $this->username . "&pwd=" . $this->password . "&to=" . $to . "&sid=" . $this->from . "&msg=" . $msg . "&fl=0&gwid=2");

        if ($result) {
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
        if (!$this->username or !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $result = file_get_contents($this->wsdl_link . "CheckBalance.aspx?user=" . $this->username . "&password=" . $this->password . "&gwid=1");

        return preg_replace('/[^0-9]+/', '', $result);
    }
}

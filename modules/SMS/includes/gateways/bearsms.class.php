<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class bearsms extends SMS
{
    private $wsdl_link = "http://app.bearsms.com/index.php?app=ws";
    public $tariff = "http://www.bearsms.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "97xxxxxxxxxxx";
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

        $result = file_get_contents($this->wsdl_link . '&u=' . $this->username . '&h=' . $this->password . '&op=pv&to=' . $to . '&msg=' . $msg);
        $result_arr = json_decode($result);

        if ($result_arr->data[0]->status == 'ERR') {
            return new SMS_Error('send-sms', $result);
        }

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

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $result = file_get_contents($this->wsdl_link . '&u=' . $this->username . '&h=' . $this->password . '&op=cr');
        $result_arr = json_decode($result);

        if ($result_arr->status == 'ERR') {
            return new SMS_Error('account-credit', $result);
        }

        return $result_arr->credit;
    }
}

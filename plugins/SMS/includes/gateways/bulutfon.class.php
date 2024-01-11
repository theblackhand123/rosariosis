<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class bulutfon extends SMS
{
    private $wsdl_link = "https://api.bulutfon.com/messages";
    public $tariff = "http://bulutfon.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "90xxxxxxxxxx";
    }

    public function SendSMS()
    {
        // Check gateway credit
        if (!$this->GetCredit()) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Your account does not have credit to send SMS.'));
        }

        $msg = urlencode($this->msg);

        $data = array(
            'title' => $this->from,
            'email' => $this->username,
            'password' => $this->password,
            'receivers' => implode(',', $this->to),
            'content' => $this->msg,
        );

        $data = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->wsdl_link);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);
        $json = json_decode($result, true);

        if ($result) {
            $this->InsertToDB($this->from, $this->msg, $this->to);

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             * @since 2.4
             *
             */


            return $json;
        }

        return new SMS_Error('send-sms', $result);
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $result = file_get_contents('https://api.bulutfon.com/me' . '?email=' . $this->username . '&password=' . $this->password);
        $result_arr = json_decode($result);

        return $result_arr->credit->sms_credit;
    }
}

<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class labsmobile extends SMS
{
    private $wsdl_link = "http://api.labsmobile.com/ws/services/LabsMobileWsdl.php?wsdl";
    public $tariff = "http://www.labsmobile.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "34XXXXXXXXX";
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


        $client = new SoapClient($this->wsdl_link);
        $str_to = "";

        if (is_array($this->to)) {
            foreach ($this->to as $item_to) {
                $str_to .= "<msisdn>$item_to</msisdn>";
            }
        } else {
            $str_to = $this->to;
        }

        $to_message = urlencode(htmlspecialchars($this->msg, ENT_QUOTES));
        $xmldata = "
            <sms>
                <recipient>
                    $str_to
                </recipient>
                <message>$to_message</message>
                <tpoa>$this->from</tpoa>
            </sms>";

        $result = $client->__soapCall("SendSMS", array(
            "client" => $this->has_key,
            "username" => $this->username,
            "password" => $this->password,
            "xmldata" => $xmldata
        ));

        if ($this->_xml_extract("code", $result) == "0") {
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
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        if (!class_exists('SoapClient')) {
            return new SMS_Error('required-class', dgettext('SMS', 'Class SoapClient not found. please enable php_soap in your php.'));
        }

        try {
            $client = new SoapClient($this->wsdl_link);
        } catch (Exception $e) {
            return new SMS_Error('account-credit', $e->getMessage());
        }

        $result = $client->GetCredit($this->username, $this->password);

        return $this->_xml_extract("messages", $result);
    }

    private function _xml_extract($attr, $xml)
    {
        $init = stripos($xml, "<" . $attr . ">");
        $end_pos = stripos($xml, "</" . $attr . ">");
        $init_pos = $init + strlen($attr) + 2;

        return substr($xml, $init_pos, $end_pos - $init_pos);
    }
}

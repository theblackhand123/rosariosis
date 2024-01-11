<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class smsozone extends SMS
{
    private $wsdl_link = "http://smsozone.com/api/mt/";
    public $tariff = "http://ozonesms.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "e.g. 91989xxxxxxx";
        $this->has_key = true;
        $this->help = "Enter the route id in this API key field. Click Here (https://smsozone.com/Web/MT/MyRoutes.aspx) for more information regarding your routeid.";
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


        $response = wp_remote_get($this->wsdl_link . "SendSMS?user=" . urlencode($this->username) . "&password=" . urlencode($this->password) . "&senderid=" . urlencode($this->from) . "&channel=Trans&DCS=0&flashsms=0&number=" . implode(',', $this->to) . "&text=" . urlencode($this->msg) . "&route=" . $this->has_key);

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('send-sms', dgettext('SMS', 'No response'));
        }

        // Ger response code
        $response_code = wp_remote_retrieve_response_code($response);
        $json = json_decode($response['body']);
        // Check response code
        if ($response_code == '200') {
            if ($json->ErrorCode == 0) {
                $this->InsertToDB($this->from, $this->msg, $this->to);

                /**
                 * Run hook after send sms.
                 *
                 * @param string $response result output.
                 * @since 2.4
                 *
                 */


                return $json;
            } else {
                return new SMS_Error('send-sms', $json->ErrorMessage);
            }

        } else {
            return new SMS_Error('send-sms', $json->ExceptionMessage);
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username or !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $response = wp_remote_get($this->wsdl_link . "GetBalance?User=" . urlencode($this->username) . "&Password=" . urlencode($this->password));

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('account-credit', dgettext('SMS', 'No response'));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == '200') {
            $json = json_decode($response['body']);

            if ($json->ErrorCode == 0) {
                return $json->Balance;
            } else {
                return new SMS_Error('account-credit', $json->ErrorMessage);
            }

        } else {
            return new SMS_Error('account-credit', $response['body']);
        }

        return true;
    }
}

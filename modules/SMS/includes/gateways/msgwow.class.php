<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class msgwow extends SMS
{
    private $wsdl_link = "http://my.msgwow.com/api/";
    public $tariff = "http://msgwow.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "919999999999";
        $this->help = "Login authentication key (this key is unique for every user).<br>The default route number is 4 and you can set your route number in sender number. e.g. 100000:4 or 100000:2";
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
        $to = implode(',', $this->to);

        $from = explode(':', $this->from);
        if (is_array($from)) {
            $route = $from[1];
        } else {
            $route = 4;
        }

        // Unicode message
        $msg = urlencode($this->msg);

        $response = wp_remote_get($this->wsdl_link . "v2/sendsms?authkey=" . $this->has_key . "&mobiles=" . $to . "&message=" . $msg . "&sender=" . urlencode($this->from) . "&route=" . $route . "&country=0", array('timeout' => 30));

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('account-credit', dgettext('SMS', 'No response'));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $result = json_decode($response['body']);

        if ($response_code == '200') {
            if ($result->type == 'success') {
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
                return $result->message;
            }

        } else {
            return new SMS_Error('send-sms', $result->message);
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->has_key) {
            return new SMS_Error('account-credit', dgettext('SMS', 'API key not set for this gateway'));
        }

        $response = wp_remote_get($this->wsdl_link . "balance.php?authkey=" . $this->has_key . "&type=1", array('timeout' => 30));

        // Check gateway credit
        if (empty($response['body'])) {
            return new SMS_Error('account-credit', dgettext('SMS', 'No response'));
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == '200') {
            if (!$response['body']) {
                return new SMS_Error('account-credit', dgettext('SMS', 'Server API Unavailable'));
            }

            $result = json_decode($response['body']);

            if (isset($result->msgType) and $result->msgType == 'error') {
                return new SMS_Error('account-credit', $result->msg . ' (See error codes: http://my.msgwow.com/apidoc/basic/error-code-basic.php)');
            } else {
                return $result;
            }
        } else {
            return new SMS_Error('account-credit', $response['body']);
        }
    }
}

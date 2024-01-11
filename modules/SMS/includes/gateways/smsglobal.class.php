<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class smsglobal extends SMS
{
    private $wsdl_link = "https://api.smsglobal.com/v2/";
    public $tariff = "https://smsglobal.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "The number starting with country code.";
        $this->help = "Fill REST API key and use API password as API Secret and leave empty the API username.";
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


        $time = time();
        $nonce = mt_rand();

        $mac = array(
            $time,
            $nonce,
            'POST',
            '/v2/sms',
            'api.smsglobal.com',
            '443',
            '',
        );

        $mac = sprintf("%s\n", implode("\n", $mac));
        $hash = hash_hmac('sha256', $mac, $this->password, true);
        $mac = base64_encode($hash);

        $headers = array(
            'Authorization' => 'MAC id="' . $this->has_key . '", ts="' . $time . '", nonce="' . $nonce . '", mac="' . $mac . '"',
            'Content-Type' => 'application/json'
        );

        $body = array(
            'destinations' => explode(',', implode(',', $this->to)),
            'message' => $this->msg,
            'origin' => $this->from,
        );

        $response = wp_remote_post($this->wsdl_link . 'sms', [
            'headers' => $headers,
            'body' => json_encode($body)
        ]);

        $result = json_decode($response['body']);
        $response_code = wp_remote_retrieve_response_code($response);

        if (is_object($result)) {
            if ($response_code == '200') {
                return $result;
            } else {
                return new SMS_Error('send-sms', print_r($result->errors, 1));
            }
        } else {
            return new SMS_Error('send-sms', $response_code . ' ' . print_r($response['body'], 1));
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        $time = time();
        $nonce = mt_rand();

        $mac = array(
            $time,
            $nonce,
            'GET',
            '/v2/user/credit-balance',
            'api.smsglobal.com',
            '443',
            '',
        );

        $mac = sprintf("%s\n", implode("\n", $mac));
        $hash = hash_hmac('sha256', $mac, $this->password, true);
        $mac = base64_encode($hash);

        $headers = array(
            'Authorization' => 'MAC id="' . $this->has_key . '", ts="' . $time . '", nonce="' . $nonce . '", mac="' . $mac . '"',
            'Content-Type' => 'application/json'
        );

        $response = wp_remote_get($this->wsdl_link . 'user/credit-balance', [
            'headers' => $headers
        ]);

        $result = json_decode($response['body']);

        $response_code = wp_remote_retrieve_response_code($response);
        if (is_object($result)) {
            if ($response_code == '200') {
                return $result->balance;
            } else {
                return new SMS_Error('credit', $result->error->message);
            }
        } else {
            return new SMS_Error('credit', $response_code . ' ' . $response['body']);
        }
    }
}

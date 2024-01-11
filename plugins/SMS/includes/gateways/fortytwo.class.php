<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class fortytwo extends SMS
{
    //private $wsdl_link = "http://imghttp.fortytwotele.com/api/current";
    private $wsdl_link = "https://rest.fortytwo.com/1/";
    public $tariff = "http://fortytwo.com/";
    public $unitrial = false;
    public $unit;
    public $flash = "enable";
    public $isflash = false;
    public $has_key = true;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "46731111111";
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

        // Reformat number
        $to = array();

        foreach ($this->to as $number) {
            $to[] = array('number' => $number);
        }

        $args = array(
            'headers' => array(
                'Authorization' => 'Token ' . $this->has_key,
                'Content-Type' => 'application/json; charset=utf-8',
            ),
            'body' => json_encode(array(
                'destinations' => $to,
                'sms_content' => array(
                    'sender_id' => $this->from,
                    'message' => $this->msg,
                )
            ))
        );

        $response = wp_remote_post($this->wsdl_link . "sms", $args);

        // Ger response code
        $response_code = wp_remote_retrieve_response_code($response);

        // Decode response
        $response = json_decode($response['body']);

        // Check response code
        if ($response_code == '200') {

            return $response;
        } else {
            return new SMS_Error('account-credit', $response_code . ' ' . $response->result_info->description);
        }
    }

    public function GetCredit()
    {
        // Check API key.
        if (!$this->has_key) {
            return new SMS_Error('account-credit', dgettext('SMS', 'API key not set for this gateway'));
        }

        return true;
    }
}

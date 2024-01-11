<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class eurosms extends SMS
{
    private $wsdl_link = "http://as.eurosms.com/api/v3/";
    public $tariff = "https://www.eurosms.com";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "Číslo pre SMSku na Slovensko má tvar: 09xxYYYYYY (napr. 0988123456)." . PHP_EOL . "Tvar čísla do sveta: +KrajinaOperátorČíslo. Napr. +421988987654" . PHP_EOL . "oddeliť každé číslo čiarkou , . Dĺžka celého čísla (vrátane znaku +) je 14.";
        $this->help = 'Fill the API Username as your Integration ID and the API Password with Integration KEY.';
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
         *
         * @since 3.4
         *
         */

        /**
         * Modify Receiver number
         *
         * @param array $this ->to receiver number
         *
         * @since 3.4
         *
         */

        /**
         * Modify text message
         *
         * @param string $this ->msg text message.
         *
         * @since 3.4
         *
         */


        try {
            $numbers = array();

            foreach ($this->to as $number) {
                $numbers[] = $this->clean_number($number);
            }

            // Set message flgs
            $flgs = 3;
            if (isset($this->options['send_unicode']) and $this->options['send_unicode']) {
                $flgs = 6;
            }

            // Set sms signature
            $sgn = array($this->from, implode('', $numbers), $this->msg);
            $args = array(
                'headers' => array(
                    'Content-Type' => 'application/json; charset=UTF-8'
                ),
                'body' => json_encode(
                    array(
                        'iid' => $this->username,
                        'sgn' => $this->calcSignature($sgn),
                        'rcpts' => $numbers,
                        'flgs' => $flgs,
                        'sndr' => $this->from,
                        'txt' => $this->msg,
                    ), JSON_NUMERIC_CHECK)
            );


            // Authentication
            $response = wp_remote_post($this->wsdl_link . "send/o2m", $args);

            // check response have error or not
            if (empty($response['body'])) {
                return false;
            }

            // Decode response
            $result = json_decode($response['body']);

            // Check response code
            if (!isset($result->err_code)) {

                return $result;
            } else {

                return new SMS_Error('send-sms', 'Error Code: \'' . $result->err_list[0]->err_code . '\'. Description: \'' . $result->err_list[0]->err_desc . '\'');
            }
        } catch (\Exception $e) {

            return new SMS_Error('send-sms', $e->getMessage());
        }
    }

    public function GetCredit()
    {
        // Check username and password
        if (!$this->username or !$this->password) {
            return new SMS_Error('account-credit', dgettext('SMS', 'Username/Password not set for this gateway'));
        }

        return 'active';
    }

    /**
     * Clean number
     *
     * @param $number
     *
     * @return bool|string
     */
    private function clean_number($number)
    {
        $number = str_replace('+', '', $number);
        $number = trim($number);

        return $number;
    }

    /**
     * Calculate message signature message hash code
     *
     * @param $sgn
     *
     * @return false|string
     */
    private function calcSignature($sgn)
    {
        $sgn_str = '';
        foreach ($sgn as $entry) {
            $sgn_str .= $entry;
        }

        $hash = hash_hmac('sha1', $sgn_str, $this->password);

        return $hash;
    }

}

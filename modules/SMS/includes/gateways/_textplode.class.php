<?php

namespace plugins\SMS\includes\gateways;

use plugins\SMS\includes\gateways\includes\textplode\Textplode;
use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class _textplode extends SMS
{
    private $wsdl_link = "";
    public $tariff = "https://www.textplode.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "440000000000,440000000001";

        // Enable api key
        $this->has_key = true;

        // includes library
        include('includes/textplode/textplode.class.php');
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


        // Init class
        $textplode = new Textplode($this->has_key);

        // Add recipient
        foreach ($this->to as $to) {
            $textplode->messages->add_recipient($to, array());
        }

        // Set From Name
        $textplode->messages->set_from($this->from);

        // Set Message
        $textplode->messages->set_message($this->msg);

        // Send sms
        $result = $textplode->messages->send();

        // Check result
        if (!$result) {
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

        // Init class
        $textplode = new Textplode($this->has_key);

        // Get credit
        $credits = $textplode->account->get_credits();

        return $credits;
    }
}

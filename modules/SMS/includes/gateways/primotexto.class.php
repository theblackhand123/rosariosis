<?php

namespace plugins\SMS\includes\gateways;

use messagesManager;
use plugins\SMS\includes\gateways\includes\primotexto\accountManager;
use plugins\SMS\includes\gateways\includes\primotexto\authenticationManager;
use plugins\SMS\includes\gateways\includes\primotexto\Sms1;
use plugins\SMS\includes\SMS;
use plugins\SMS\includes\SMS_Error;

class primotexto extends SMS
{
    private $wsdl_link = "https://api.primotexto.com/v2/";
    public $tariff = "http://www.primotexto.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "disable";
    public $isflash = false;

    public function __construct()
    {
        parent::__construct();
        $this->validateNumber = "Format: 0600000000, +33600000000";
        $this->help = 'Vous devez génerer une clé depuis votre <a href="https://www.primotexto.com/webapp/#/developer/keys">interface Primotexto</a> pour pouvoir utiliser l\'API.';
        $this->has_key = true;
        $this->bulk_send = false;
        require_once 'includes/primotexto/baseManager.class.php';
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


        // Authentication
        authenticationManager::setApiKey($this->has_key);

        // New notification SMS
        $sms = new Sms1;
        $sms->type = 'notification';
        $sms->number = $this->to[0];
        $sms->message = $this->msg;
        $sms->sender = $this->from;

        $result = messagesManager::messagesSend($sms);
        $json = json_decode($result);

        if (isset($json->snapshotId)) {
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

        return new SMS_Error('credit', $json->code);
    }

    public function GetCredit()
    {
        // Authentication
        authenticationManager::setApiKey($this->has_key);

        // Account Stats
        $result = accountManager::accountStats();
        $json = json_decode($result);

        if (isset($json->error)) {
            return new SMS_Error('credit', $json->error);
        }

        return $json->credits;
    }
}

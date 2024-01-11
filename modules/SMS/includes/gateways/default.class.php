<?php

class Default_Gateway extends SMS {
	private $wsdl_link = '';
	public $tariff = '';
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;
	public $bulk_send = false;

	public function __construct() {
		$this->validateNumber = "1xxxxxxxxxx";
	}

	public function SendSMS() {
		// Check gateway credit
		if ( ! $this->GetCredit() ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Your account does not have credit to send SMS.' ) );
		}

		return new SMS_Error( 'send-sms', dgettext( 'SMS', 'Does not set any gateway' ) );
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		return new SMS_Error( 'account-credit', 0 );
	}
}

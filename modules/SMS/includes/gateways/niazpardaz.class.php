<?php

class niazpardaz extends SMS {
	private $wsdl_link = "http://api.payamak-panel.com/post/send.asmx?wsdl";
	public $tariff = "http://www.niazpardaz.com/sms/SmsPrice.aspx";
	public $unitrial = true;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "09xxxxxxxx";

		ini_set( "soap.wsdl_cache_enabled", "0" );
	}

	public function SendSMS() {
		// Check gateway credit
		if ( ! $this->GetCredit() ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Your account does not have credit to send SMS.' ) );
		}

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 *
		 * @param string $this ->from sender number.
		 */


		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 *
		 * @param array $this ->to receiver number
		 */


		/**
		 * Modify text message
		 *
		 * @since 3.4
		 *
		 * @param string $this ->msg text message.
		 */


		try {
			$client = new SoapClient( $this->wsdl_link );
			$parameters['username'] = $this->username;
			$parameters['password'] = $this->password;
			$parameters['from'] = $this->from;
			$parameters['to'] = $this->to;
			$parameters['text'] = $this->msg;
			$parameters['isflash'] = $this->isflash;
			$parameters['udh'] = "";
			$parameters['recId'] = array( 0 );
			$parameters['status'] = 0x0;

			$result = $client->SendSms( $parameters )->SendSmsResult;

			$this->InsertToDB( $this->from, $this->msg, $this->to );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */


			return $result;
		} catch ( SoapFault $ex ) {
			return new SMS_Error( 'send-sms', $ex->faultstring );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		if ( ! class_exists( 'SoapClient' ) ) {
			return new SMS_Error( 'required-class', dgettext( 'SMS', 'Class SoapClient not found. please enable php_soap in your php.' ) );
		}

		try {
			$client = new SoapClient( $this->wsdl_link );

			return $client->GetCredit( array(
				"username" => $this->username,
				"password" => $this->password
			) )->GetCreditResult;
		} catch ( SoapFault $ex ) {
			return new SMS_Error( 'account-credit', $ex->faultstring );
		}
	}
}

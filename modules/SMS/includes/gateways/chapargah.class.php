<?php

class chapargah extends SMS {
	private $wsdl_link = "http://panel.chapargah.ir/API/Send.asmx?WSDL";
	public $tariff = "http://chapargah.ir/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
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


		$client = new SoapClient( $this->wsdl_link );

		$result = $client->SendSms(
			array(
				'username' => $this->username,
				'password' => $this->password,
				'from'     => $this->from,
				'to'       => $this->to,
				'text'     => $this->msg,
				'flash'    => false,
				'udh'      => ''
			)
		);

		if ( $result ) {
			$this->InsertToDB( $this->from, $this->msg, $this->to );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */


			return $result;
		}

		return new SMS_Error( 'send-sms', $result );
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
		} catch ( Exception $e ) {
			return new SMS_Error( 'account-credit', $e->getMessage() );
		}

		$result = $client->Credit( array( 'username' => $this->username, 'password' => $this->password ) );

		return $result->CreditResult;
	}
}

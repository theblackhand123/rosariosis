<?php

class payamresan extends SMS {
	private $wsdl_link = "http://www.payam-resan.com/";
	public $tariff = "http://www.payam-resan.com/";
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


		$to = implode( ',', $this->to );

		$message = urlencode( $this->msg );

		$client = file_get_contents( "{$this->wsdl_link}APISend.aspx?Username={$this->username}&Password={$this->password}&From={$this->from}&To={$to}&Text={$message}" );

		if ( $client ) {
			$this->InsertToDB( $this->from, $this->msg, $this->to );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */

		}

		return new SMS_Error( 'send-sms', $result );
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$client = file_get_contents( "{$this->wsdl_link}Credit.aspx?Username={$this->username}&Password={$this->password}" );

		if ( $client == 'ERR' ) {
			return new SMS_Error( 'account-credit', $client );
		}

		return $client;
	}
}

<?php

class smstrade extends SMS {
	private $wsdl_link = "http://gateway.smstrade.de/";
	public $tariff = "http://www.smstrade.de/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
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


		$to        = implode( ';', $this->to );
		$timestamp = time();
		$msg       = str_replace( " ", "+", $this->msg );

		$result = file_get_contents( $this->wsdl_link . "/bulk/?key={$this->password}&to={$to}&route=gold&from={$this->from}&message={$msg}&senddate={$timestamp}" );

		if ( $result == 'OK' ) {
			$this->InsertToDB( $this->from, $this->msg, $this->to );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */


			return $result;
		} else {
			return new SMS_Error( 'send-sms', $result );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$result = file_get_contents( $this->wsdl_link . "/credits/?key=" . $this->password );

		if ( $result == 'ERROR' ) {
			return new SMS_Error( 'account-credit', $result );
		}

		return $result;
	}
}

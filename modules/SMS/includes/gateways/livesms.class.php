<?php

class livesms extends SMS {
	private $wsdl_link = "http://panel.livesms.eu/sms.do";
	public $tariff = "http://www.livesms.eu/en";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "";
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


		$to     = implode( ",", $this->to );
		$msg    = urlencode( $this->msg );
		$result = file_get_contents( $this->wsdl_link . "?username=" . $this->username . "&password=" . md5( $this->password ) . "&from=" . $this->from . "&to=" . $to . "&message=" . $msg );

		if ( $result ) {
			$this->InsertToDB( $this->from, $this->msg, $this->to );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */


			return true;
		} else {
			return new SMS_Error( 'send-sms', $result );
		}

	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$result = file_get_contents( $this->wsdl_link . "?username=" . $this->username . "&password=" . md5( $this->password ) . "&credits=1" );

		if ( strchr( $result, 'ERROR' ) ) {
			return new SMS_Error( 'account-credit', $result );
		}

		$result = explode( " ", $result );

		return $result[1];
	}
}

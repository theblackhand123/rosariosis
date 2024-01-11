<?php

class sendsms247 extends SMS {
	private $wsdl_link = "http://www.sendsms247.com/components/com_smsreseller/smsapi.php";
	public $tariff = "http://www.sendsms247.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "2348033333333";
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


		$to  = implode( ',', $this->to );
		$msg = urlencode( $this->msg );

		$result = file_get_contents( "{$this->wsdl_link}?username={$this->username}&password={$this->password}&sender={$this->from}&recipient={$to}&message={$msg}" );

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

		$result = file_get_contents( "{$this->wsdl_link}?username={$this->username}&password={$this->password}&balance=true" );

		if ( $result == '2905' ) {
			return new SMS_Error( 'account-credit', $result );
		} else {
			return $result;
		}
	}
}

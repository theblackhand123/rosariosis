<?php

class spirius extends SMS {
	private $wsdl_link = "https://get.spiricom.spirius.com:55001/cgi-bin/";
	public $tariff = "http://www.spirius.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "+46701234567";
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


		// Implode numbers
		$to = implode( ',', $this->to );

		// Unicode message
		$msg = urlencode( $this->msg );

		$response = wp_remote_get( $this->wsdl_link . "sendsms?User=" . urlencode( $this->username ) . "&Pass=" . urlencode( $this->password ) . "&To=" . $to . "&From=" . urlencode( $this->from ) . "&FromType=A&Msg=" . $msg, array( 'timeout' => 30 ) );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '202' ) {
				$this->InsertToDB( $this->from, $this->msg, $this->to );

				/**
				 * Run hook after send sms.
				 *
				 * @since 2.4
				 *
				 * @param string $result result output.
				 */


				return $response['body'];
		} else {
			return new SMS_Error( 'send-sms', $response['body'] );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		return 1;
	}
}

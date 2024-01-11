<?php

class oursms extends SMS {
	private $wsdl_link = "https://www.oursms.net/api/";
	public $tariff = "https://www.oursms.net/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "Separate numbers between them with comma ( , ) Numbers must be entered in international format 966500000000 and international messages without 00 or +";
	}

	public function SendSMS() {
		// Check gateway credit
		if ( ! $this->GetCredit() ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Your account does not have credit to send SMS.' ) );
		}

		/**
		 * Modify sender number
		 *
		 * @param string $this ->from sender number.
		 *
		 * @since 3.4
		 *
		 */

		/**
		 * Modify Receiver number
		 *
		 * @param array $this ->to receiver number
		 *
		 * @since 3.4
		 *
		 */

		/**
		 * Modify text message
		 *
		 * @param string $this ->msg text message.
		 *
		 * @since 3.4
		 *
		 */

		$to = implode( ',', $this->to );

		$msg = urlencode( $this->msg );

		// Get response
		$response = wp_remote_get( $this->wsdl_link . 'sendsms.php?username=' . urlencode( $this->username ) . '&password=' . urlencode( $this->password ) . '&message=' . $msg . '&numbers=' . $to . '&sender=' . urlencode( $this->from ) . '&unicode=e&Rmduplicated=1&return=json' );

		// Decode response
		$response = json_decode( $response['body'] );

		if ( $response->Code == 100 ) {

			return true;
		} else {

			return new SMS_Error( 'send-sms', $response->MessageIs );
		}

	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		// Get response
		$response = wp_remote_get( $this->wsdl_link . 'getbalance.php?username=' . urlencode( $this->username ) . '&password=' . urlencode( $this->password ) . '&return=json' );

		// Decode response
		$response = json_decode( $response['body'] );

		if ( $response->Code == 117 ) {
			// Return blance
			return $response->currentuserpoints;
		} else {
			return new SMS_Error( 'account-credit', $response->MessageIs );
		}
	}
}

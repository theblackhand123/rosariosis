<?php

class aruba extends SMS {
	private $wsdl_link = "http://adminsms.aruba.it/";
	public $tariff = "http://adminsms.aruba.it/";
	public $unitrial = true;
	public $unit;
	public $flash = "disable";
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

		$to  = implode( ",", $this->to );
		$msg = urlencode( $this->msg );

		$response = wp_remote_post( $this->wsdl_link . "Aruba/SENDSMS?login=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&message=" . $msg . "&message_type=N&order_id=999FFF111&sender=" . $this->from . "&recipient=" . $to );

		// Ger response code
		$response_code = wp_remote_retrieve_response_code( $response );

		// Check response code
		if ( $response_code == '200' and substr( $response['body'], 0, 2 ) == 'OK' ) {

			return $response;
		} else {

			return new SMS_Error( 'account-credit', $response_code . ' ' . $response['body'] );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . "Aruba/CREDITS?login=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) );

		// Ger response code
		$response_code = wp_remote_retrieve_response_code( $response );

		// Check response code
		if ( $response_code == '200' and substr( $response['body'], 0, 2 ) == 'OK' ) {
			return $response['body'];
		} else {
			return new SMS_Error( 'account-credit', $response_code . ' ' . $response['body'] );
		}
	}
}

<?php

class resalaty extends SMS {
	private $wsdl_link = "http://www.resalaty.com/api/";
	public $tariff = "https://resalaty.com/";
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


		$to = implode( ',', $this->to );

		$msg = urlencode( $this->msg );

		// Get response
		$response = wp_remote_get( $this->wsdl_link . 'sendsms.php?username=' . urlencode( $this->username ) . '&password=' . urlencode( $this->password ) . '&message=' . $msg . '&numbers=' . $to . '&sender=' . urlencode( $this->from ) . '&unicode=e&Rmduplicated=1&return=json' );

		// Check response
		if ( $response['response']['message'] != 'OK' ) {
			return;
		}

		// Decode response
		$response = json_decode( $response['body'] );

		if ( $response->Code == 100 ) {
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

		// Check response
		if ( $response['response']['message'] != 'OK' ) {
			return new SMS_Error( 'account-credit', $response );
		}

		// Decode response
		$response = json_decode( $response['body'] );

		// Return blance
		return $response->currentuserpoints;
	}
}

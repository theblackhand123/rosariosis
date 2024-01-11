<?php

class pridesms extends SMS {
	private $wsdl_link = "http://pridesms.in/api/v1/";
	public $tariff = "http://pridesms.in/";
	public $unitrial = true;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "e.g. 9029963999";
		$this->help           = 'Please enter Route ID in API Key field';
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


		$response = wp_remote_get( $this->wsdl_link . "sendSMS.php?user=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&senderid=" . urlencode( $this->from ) . "&number=" . implode( ',', $this->to ) . "&text=" . urlencode( $this->msg ) );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'send-sms', dgettext( 'SMS', 'No response' ) );
		}

		// Ger response code
		$response_code = wp_remote_retrieve_response_code( $response );

		// Check response code
		if ( $response_code == '200' ) {
			$response = json_decode( $response['body'] );

			if ( $response->ErrorCode == '000' ) {
				$this->InsertToDB( $this->from, $this->msg, $this->to );

				/**
				 * Run hook after send sms.
				 *
				 * @since 2.4
				 *
				 * @param string $response result output.
				 */


				return $response;
			} else {
				return new SMS_Error( 'send-sms', $response->ErrorMessage );
			}

		} else {
			return new SMS_Error( 'send-sms', $response['body'] );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username or ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . "getBalance.php?user=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			$response = json_decode( $response['body'] );

			if ( $response->ErrorCode == '000' ) {
				return $response->Balance;
			} else {
				return new SMS_Error( 'account-credit', $response->ErrorMessage );
			}

		} else {
			return new SMS_Error( 'account-credit', $response['body'] );
		}

		return true;
	}
}

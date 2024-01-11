<?php

class afilnet extends SMS {
	private $wsdl_link = "https://www.afilnet.com/api/http/";
	public $tariff = "http://www.afilnet.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "34600000000";
		$this->bulk_send      = false;
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

		$response = wp_remote_get( $this->wsdl_link . "?class=sms&method=sendsms&user=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&from=" . urlencode( $this->from ) . "&to=" . $this->to[0] . "&sms=" . $msg . "&scheduledatetime=&output=", array( 'timeout' => 30 ) );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			$result = json_decode( $response['body'] );

			if ( $result->status == 'SUCCESS' ) {
				$this->InsertToDB( $this->from, $this->msg, $this->to[0] );

				/**
				 * Run hook after send sms.
				 *
				 * @since 2.4
				 *
				 * @param string $result result output.
				 */


				return $result->result;
			} else {
				return new SMS_Error( 'send-sms', $result->error );
			}
		} else {
			return new SMS_Error( 'send-sms', $response['body'] );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . "?class=user&method=getbalance&user=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ), array( 'timeout' => 30 ) );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			if ( ! $response['body'] ) {
				return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Server API Unavailable' ) );
			}

			$result = json_decode( $response['body'] );

			if ( $result->status == 'SUCCESS' ) {
				return $result->result;
			} else {
				return new SMS_Error( 'account-credit', $result->error );
			}
		} else {
			return new SMS_Error( 'account-credit', $response['body'] );
		}
	}
}

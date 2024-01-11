<?php

class gateway extends SMS {
	private $wsdl_link = "https://apps.gateway.sa/vendorsms/";
	public $tariff = "http://sms.gateway.sa/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "+966556xxxxxx";
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


		$to  = implode( ",", $this->to );
		$msg = urlencode( $this->msg );

		if ( $this->isflash ) {
			$flash = 1;
		} else {
			$flash = 0;
		}

		if ( isset( $this->options['send_unicode'] ) and $this->options['send_unicode'] ) {
			$response = wp_remote_get( $this->wsdl_link . "pushsms.aspx?user=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&msisdn=" . $to . "&sid=" . urlencode( $this->from ) . "&msg=" . $msg . "&fl=" . $flash . "&dc=8" );
		} else {
			$response = wp_remote_get( $this->wsdl_link . "pushsms.aspx?user=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&msisdn=" . $to . "&sid=" . urlencode( $this->from ) . "&msg=" . $msg . "&fl=" . $flash );
		}

		// Check response error
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'send-sms', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		$result = json_decode( $response['body'] );

		if ( $result->ErrorCode == '000' ) {

			return $result;
		} else {
			return new SMS_Error( 'send-sms', $result->ErrorMessage );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . "CheckBalance.aspx?user=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) );

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			return $response['body'];
		} else {
			return new SMS_Error( 'account-credit', $response['body'] );
		}
	}
}

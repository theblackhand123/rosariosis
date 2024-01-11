<?php

class easysendsms extends SMS {
	private $wsdl_link = "https://api.easysendsms.app/bulksms";
	public $tariff = "https://easysendsms.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "Eg: 61409317436, 61409317435, 61409317434 (Do not use + before the country code)";
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

		$type = 1; // 1: Unicode (For any other language)

		$numbers = array();

		foreach ( $this->to as $number ) {
			$numbers[] = $this->clean_number( $number );
		}

		$to  = implode( ',', $numbers );
		$msg = urlencode( $this->msg );

		$response = wp_remote_get( $this->wsdl_link . "?username=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&from=" . urlencode( $this->from ) . "&to=" . $to . "&text=" . $msg . "&type=" . $type );

		// Check response error
		if ( empty( $response['body'] ) ) {

			return new SMS_Error( 'send-sms', dgettext( 'SMS', 'No response' ) );
		}

		$error = $this->send_error_check( $response['body'] );

		if ( ! $error ) {

			return true;
		} else {

			return new SMS_Error( 'send-sms', $error );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		return 1;

	}

	/**
	 * Clean number
	 *
	 * @param $number
	 *
	 * @return bool|string
	 */
	private function clean_number( $number ) {
		$number = str_replace( '+', '', $number );
		$number = trim( $number );

		return $number;
	}

	/**
	 * @param $result
	 *
	 * @return string|\WP_Error
	 */
	private function send_error_check( $result ) {

		switch ( $result ) {
			case strpos( $result, 'OK' ) !== false:
				$error = '';
				break;
			case '1001':
				$error = 'Invalid URL. This means that one of the parameters was not provided or left blank.';
				break;
			case '1002':
				$error = 'Invalid username or password parameter.';
				break;
			case '1003':
				$error = 'Invalid type parameter.';
				break;
			case '1004':
				$error = 'Invalid message.';
				break;
			case '1005':
				$error = 'Invalid mobile number.';
				break;
			case '1006':
				$error = 'Invalid sender name.';
				break;
			case '1007':
				$error = 'Insufficient credit.';
				break;
			case '1008':
				$error = 'Internal error (do NOT re-submit the same message again).';
				break;
			case '1009':
				$error = 'Service not available (do NOT re-submit the same message again).';
				break;
			default:
				$error = sprintf( 'Unknow error: %s', $result );
				break;
		}

		if ( $error ) {
			return $error;
		}

		return false;
	}

}

<?php

class suresms extends SMS {
	private $wsdl_link = "https://api.suresms.com/";
	public $tariff = "https://www.suresms.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "disabled";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "+4520202020, The recipient of the message. (remember countrycode)	";
		$this->has_key        = true;
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

		$msg      = urlencode( $this->msg );
		$response = array();

		foreach ( $this->to as $to ) {
			$response = wp_remote_get( $this->wsdl_link . "script/SendSMS.aspx?login=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&to=" . $to . "&text=" . $msg );
		}

		// Check response error
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'send-sms', dgettext( 'SMS', 'No response' ) );
		}

		if ( strpos( $response['body'], 'sent' ) !== false ) {

			return $response['body'];
		} else {
			return new SMS_Error( 'send-sms', $response['body'] );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->has_key ) {
			return new \WP_Error( 'account-credit', _( 'SMS', 'Username/API-Key does not set for this gateway' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . "script/GetUserBalance.aspx?login=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) );

		$xml = new \SimpleXMLElement( $response['body'] );

		if ( ! is_object( $xml ) ) {
			return new \WP_Error( 'account-credit', 'The XML is not valid, Please contact with gateways administrator.' );
		}

		// Convert to array
		$arr = json_decode( json_encode( $xml ), 1 );

		return $arr['Balance'];
	}
}

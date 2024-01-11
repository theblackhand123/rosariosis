<?php

class unisender extends SMS {
	private $wsdl_link = "https://api.unisender.com/en/api/";
	public $tariff = "http://www.unisender.com/en/prices/";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->has_key        = true;
		$this->validateNumber = "The recipient's phone in international format with the country code (you can omit the leading \"+\").Example: Phone = 79092020303. You can specify multiple  ecipient numbers separated by commas. Example: Phone = 79092020303,79002239878";
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


		$to   = implode( ",", $this->to );
		//$text = iconv( 'cp1251', 'utf-8', $this->msg );

		$response = wp_remote_get( $this->wsdl_link . "sendSms?format=json&api_key=" . $this->has_key . "&sender=" . urlencode( $this->from ) . "&text=" . urlencode( $text ) . "&phone=" . $to );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'send-sms', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			$result = json_decode( $response['body'] );

			if ( isset( $result->result->error ) ) {
				return new SMS_Error( 'send-sms', $result->result->error );
			}

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
			return new SMS_Error( 'send-sms', $response['body'] );
		}
	}

	public function GetCredit() {
		// Check api key
		if ( ! $this->has_key ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'API key not set for this gateway' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . "getUserInfo?format=json&api_key={$this->has_key}" );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			$result = json_decode( $response['body'], true );
			if ( isset( $result['error'] ) ) {
				return new SMS_Error( 'account-credit', $result['error'] );
			} else {
				return $result['result']['balance'];
			}
		} else {
			return new SMS_Error( 'account-credit', $response['body'] );
		}
	}
}

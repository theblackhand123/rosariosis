<?php

class Mobtexting extends SMS {
	private $wsdl_link = "http://portal.mobtexting.com/api/v2";
	public $tariff = "https://www.mobtexting.com/pricing.php";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;
	public $service = 'T'; // T for Transactional, G for Global.

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "91[9,8,7,6]XXXXXXXXX";
		$this->help           = "Login authentication key (this key is unique for every user).<br>For BRAND Sender id Please Make it Approve Before Sending SMS";
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


		// comma seperated receivers
		$to            = implode( ',', $this->to );
		$msg           = $this->msg;
		$api_end_point = $this->wsdl_link . "/sms/send";
		$api_args      = Array(
			'access_token' => $this->has_key,
			'sender'       => $this->from,
			'message'      => urlencode( $msg ),
			'to'           => $to,
			'service'      => $this->service,
		);

		// $response = wp_remote_post( $api_end_point, Array( 'body' => $api_args, 'timeout' => 30 ) );

		// Fix send URL, use GET.
		$send_url = $api_end_point;

		$first_api_arg = true;

		foreach ( $api_args as $api_key => $api_arg )
		{
			$sep = '&';

			if ( $first_api_arg )
			{
				$sep = '?';

				$first_api_arg = false;
			}

			$send_url .= $sep . $api_key . '=' . $api_arg;
		}

		$response = wp_remote_get( $send_url );

		// Check response error
		if ( empty( $response['body'] ) ) {

			return new SMS_Error( 'send-sms', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$result        = json_decode( $response['body'] );

		if ( $response_code < 300 ) {
			if ( $result->status == 200 ) {

				return true;
			} else {

				return new SMS_Error( 'send-sms', $response_code . ' - ' . $result->message );
			}

		} else {
			return new SMS_Error( 'send-sms', $response_code . ' - ' . $result->message );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->has_key ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'API key not set for this gateway' ) );
		}

		$api_end_point = $this->wsdl_link . "/account/balance";
		$api_args      = Array(
			'timeout' => 18000
		);
		$response      = wp_remote_get( $api_end_point . '?access_token=' . $this->has_key, $api_args );

		// Check gateway credit
		// Check response error
		if ( empty( $response['body'] ) ) {

			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'No response' ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {

			$result = json_decode( $response['body'] );
			$result = (array) ( $result );
			foreach ( $result['data'] as $key => $value ) {
				$value = (array) ( $value );

				$credits = $value['credits'];

				$this->service = $value['service']; // T for Transactional, G for Global.

				if ( $value['service'] == "T" ) {
					break;
				}
			}

			if ( isset( $result->status ) and $result->status != 'success' ) {
				return new SMS_Error( 'account-credit', $result->msg . $result->description );
			} else {
				return $credits;
			}
		} else {
			return new SMS_Error( 'account-credit', $response['body'] );
		}
	}
}

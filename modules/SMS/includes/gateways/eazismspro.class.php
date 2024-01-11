<?php

class eazismspro extends SMS {
	private $wsdl_link = "https://apps.eazismspro.com/smsapi/";
	public $tariff = "https://eazismspro.com/";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;
	public $bulk_send = true;
	public $help = '';

	private $_responses = array(
		1000 => '1000 - Message submitted successfully',
		1002 => '1002 - SMS sending failed',
		1003 => '1003 - Insufficient balance',
		1004 => '1004 - Invalid API key',
		1005 => '1005 - Invalid Phone Number',
		1006 => '1006 - Invalid Sender ID. Sender ID must not be more than 11 Characters. Characters include white space.',
		1007 => '1007 - Message scheduled for later delivery',
		1008 => '1008 - Empty Message',
	);

	public function __construct() {
		parent::__construct();
		$this->has_key        = true;
		$this->validateNumber = "The recipient's phone in international format with the country code (you can omit the leading \"+\"). Example: Phone = 233240123456. You can specify multiple recipient numbers separated by commas. Example: Phone = 233240123456, 233240123457";
		$this->help = "Visit <a href='http://apps.eazismspro.com/sms/api'>http://apps.eazismspro.com/sms/api</a> and click on 'Create API Key' to create your API Key. This gateway does not use a username or password. <br>";
		$this->help .= "We also deliver messages worldwide. All you need to do is to prefix the right country code. <br>";
		$this->help .= "Visit <a href='https://eazismspro.com/blog/faqs-on-eazi-sms-pro-gateway-on-wp-sms-wordpress-plugin/'>Our FAQ</a>  for assistance";
	}


	public function SendSMS() {
		// Check api key
		if ( ! is_string( $this->has_key ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'API key not set for this gateway' ) );
		}

		// Check gateway credit
		if ( ! $this->GetCredit() ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Your account does not have credit to send SMS.' ) );
		}

		if(count($this->to) == 1) {
			$to = $this->to[0];
		} else {
			$to   = implode(",", $this->to );
		}
		$text = iconv( 'cp1251', 'utf-8', $this->msg );

		$result = wp_remote_get( $this->wsdl_link . "?key=" . urlencode( $this->has_key ) . "&sender_id=" . urlencode( $this->from ) . "&msg=" . urlencode( $text ) . "&to=" . $to );

		if ( $result ) {
			$response_body = $result['body'];

			$result = $this->decode_response( $response_body );

			// in EaziSMSPro, if response is not 1000, the message was not sent

			if(count($this->to) == 1) {
				if ( isset( $result['code'] )
					&& $result['code'] != '1000'
					&& isset( $this->_responses[$result['code']] ) ) {
					return new SMS_Error('send-sms', $result['message']);
				} else {
					// Log the result
				}
			}

			// check the result for bulk messages. Format: 233246227810:1000|233206527740:1000
			if(count($this->to) > 1) {
				$response_body = '';
				$response_array = explode("|", $result['body']);
				$all_submit = true;
				foreach($response_array as $response) {
					$array = explode(":", $response);
					$response_body .= $array[0] . ' (' . $this->_responses[$array[1]] . ') | ';
					if($array[1] != '1000') {
						$all_submit = false;
					}
				}
				$send_status = 'success';
				if($all_submit == false) {
					$send_status = 'success + error';
				}
			}

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */

			return isset( $result['message'] ) ? $result['message'] : $result;
		}
		// Log the result
		$response_body = $result['body'];

		return new SMS_Error( 'send-sms', $response_body );
	}

	public function GetCredit() {
		// Check api key
		if ( ! is_string( $this->has_key ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'API key not set for this gateway' ) );
		}

		$response = wp_remote_get( "https://apps.eazismspro.com/api/balance/sms?key=" . urlencode( $this->has_key ) );

		// Check gateway credit
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code == '200' ) {
			$result = $this->decode_response( $response['body'] );

			if ( isset( $result['code'] )
				&& $result['code'] != '1000'
				&& isset( $this->_responses[$result['code']] ) ) {
				return new SMS_Error( 'account-credit', $result['message'] );
			} else {
				return $result;
			}
		} else {
			return new SMS_Error( 'account-credit', $response['body'] );
		}
	}

	public function decode_response( $response ) {
		$response_json = json_decode( $response, true );

		if ( json_last_error() !== JSON_ERROR_NONE )
		{
			return $response;
		}

		return $response_json;
	}
}

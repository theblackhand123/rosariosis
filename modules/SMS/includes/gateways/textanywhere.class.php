<?php

class textanywhere extends SMS {
	private $wsdl_link = "https://www.textapp.net/webservice/httpservice.aspx";
	public $tariff = "http://www.textanywhere.net/";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->has_key        = true;
		$this->help           = 'You should use the Client_Id and Client_Pass instead API username and API password. The Client_Id and Client_Pass value can be found by logging in to your online account, and clicking on the ADMIN PANEL button.';
		$this->validateNumber = "For example, mobile number (07836) 123-456 would be formatted as +447836123456.";
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


		$to      = implode( ",", $this->to );
		$message = urlencode( $this->msg );

		$response = wp_remote_get( $this->wsdl_link . "?method=sendsms&externallogin=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) . "&clientbillingreference=myclientbillingreference&clientmessagereference=myclientmessagereference&originator=" . urlencode( $this->from ) . "&destinations=" . $to . "&body=" . $message . "&validity=72&charactersetid=2&replymethodid=1" );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'send-sms', dgettext( 'SMS', 'No response' ) );
		}

		$result = $this->XML2Array( $response['body'] );

		if ( isset( $result['Transaction']['Code'] ) and $result['Transaction']['Code'] == '1' ) {

			if ( isset( $result['Destinations']['Destination']['Code'] ) and $result['Destinations']['Destination']['Code'] == '1' ) {
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
				return new SMS_Error( 'send-sms', $this->get_error_message( $result['Destinations']['Destination']['Code'] ) );
			}
		} else {
			return new SMS_Error( 'send-sms', $result['Transaction']['Description'] );
		}
	}

	public function GetCredit() {
		// Check api key and password
		if ( ! $this->has_key && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'API key/Password not set for this gateway' ) );
		}

		$response = wp_remote_get( $this->wsdl_link . "?method=GetCreditsLeft&externallogin=" . urlencode( $this->username ) . "&password=" . urlencode( $this->password ) );

		// Check gateway credit
		if ( empty( $response['body'] ) ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'No response' ) );
		}

		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new SMS_Error( 'account-credit', sprintf( dgettext( 'SMS', 'The <code>%s</code> function is not active in your server.' ), 'simplexml_load_string' ) );
		}

		$result = $this->XML2Array( $response['body'] );

		if ( isset( $result['Transaction']['Code'] ) and $result['Transaction']['Code'] == '1' ) {
			return $result['CreditLeft'];
		} else {
			return new SMS_Error( 'account-credit', $result['Transaction']['Description'] );
		}
	}

	/**
	 * @param $xml
	 * @param bool $recursive
	 *
	 * @return array
	 */
	private function XML2Array(
		$xml, $recursive = false
	) {
		if ( ! $recursive ) {
			$array = simplexml_load_string( $xml );
		} else {
			$array = $xml;
		}

		$newArray = array();
		$array    = ( array ) $array;
		foreach ( $array as $key => $value ) {
			$value = ( array ) $value;
			if ( isset ( $value [0] ) ) {
				$newArray [ $key ] = trim( $value [0] );
			} else {
				$newArray [ $key ] = $this->XML2Array( $value, true );
			}
		}

		return $newArray;
	}

	/**
	 * @param $error_code
	 *
	 * @return string
	 */
	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			case '361':
				return 'Destination in wrong format';
				break;

			case '901':
				return 'Account suspended';
				break;

			default:
				return sprintf( 'Error code: %s, See message codes: http://developer.textapp.net/HTTPService/TransactionCodes.aspx', $error_code );
				break;
		}
	}
}

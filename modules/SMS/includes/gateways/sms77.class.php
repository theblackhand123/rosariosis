<?php

class sms77 extends SMS {
	private $wsdl_link = "https://gateway.sms77.io/api/";
	public $tariff = "http://www.sms77.de";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;
	public $has_key = true;

	public function __construct() {
		parent::__construct();
		$this->help           = 'For API Key find it in your login under Settings > HTTP Api';
		$this->validateNumber = "0049171999999999 or 0171999999999 or 49171999999999";
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


		$result = @file_get_contents( $this->wsdl_link . 'sms?p=' . urlencode( $this->has_key ) . '&text=' . urlencode( $this->msg ) . '&to=' . implode( ",", $this->to ) . '&type=direct&from=' . urlencode( $this->from ) );

		if ( $result == '100' ) {
			$this->InsertToDB( $this->from, $this->msg, $this->to );

			/**
			 * Run hook after send sms.
			 *
			 * @since 2.4
			 *
			 * @param string $result result output.
			 */


			return $result;
		}

		return new SMS_Error( 'send-sms', get_error_message( $result ) );
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$result = @file_get_contents( $this->wsdl_link . 'balance?p=' . urlencode( $this->has_key ) );

		return $result;
	}

	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			case '101':
				return 'Transmission to at least one recipient failed';
				break;
			case '201':
				return 'Sender invalid. A maximum of 11 alphanumeric or 16 numeric characters are allowed.';
				break;
			case '202':
				return 'Recipient number invalid';
				break;
			case '300':
				return 'Variable p is not specified';
				break;
			case '301':
				return 'Variable to not set';
				break;
			case '304':
				return 'Variable type not set';
				break;
			case '305':
				return 'Variable text not set';
				break;
			case '400':
				return 'type invalid. See allowed values ​​above.';
				break;
			case '401':
				return 'Variable text is too long';
				break;
			case '402':
				return 'Reload Lock – this SMS has already been sent within the last 180 seconds';
				break;
			case '403':
				return 'Max. limit per day reached for this number';
				break;
			case '500':
				return 'Too little credit available';
				break;
			case '600':
				return 'Carrier delivery failed';
				break;
			case '700':
				return 'Unknown error';
				break;
			case '900':
				return 'Authentication failed. Please check user and api key';
				break;
			case '902':
				return 'http API disabled for this account';
				break;
			case '903':
				return 'Server IP is wrong';
				break;
			case '11':
				return 'SMS carrier temporarily not available';
			break;
		}

		return 'Unknown error';
	}
}

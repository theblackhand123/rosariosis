<?php

class smshosting extends SMS {
	private $wsdl_link = "https://api.smshosting.it/rest/api";
	public $tariff = "https://www.smshosting.it/en/pricing";
	public $unitrial = false;
	public $unit;
	public $flash = "disable";
	public $isflash = false;
	private $smsh_response_status = 0;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "";
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


		$to = implode( ",", $this->to );

		$sms_text = iconv( 'cp1251', 'utf-8', $this->msg );

		$POST = array(
			'to'   => $to,
			'from' => $this->from,
			'text' => $sms_text
		);

		$to_smsh = curl_init( "{$this->wsdl_link}/sms/send" );
		curl_setopt( $to_smsh, CURLOPT_POST, true );
		curl_setopt( $to_smsh, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $to_smsh, CURLOPT_USERPWD, $this->username . ":" . $this->password );
		curl_setopt( $to_smsh, CURLOPT_POSTFIELDS, http_build_query( $POST ) );
		curl_setopt( $to_smsh, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $to_smsh, CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded' ) );

		$result = curl_exec( $to_smsh );

		$this->smsh_response_status = curl_getinfo( $to_smsh, CURLINFO_HTTP_CODE );

		if ( $result ) {
			$jsonObj = json_decode( $result );

			if ( null === $jsonObj ) {
				return false;
			} elseif ( $this->smsh_response_status != 200 ) {
				return false;
			} else {
				$result = $jsonObj->transactionId;

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
		} else {
			return new SMS_Error( 'send-sms', $result );
		}
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$to_smsh = curl_init( "{$this->wsdl_link}/user" );

		curl_setopt( $to_smsh, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $to_smsh, CURLOPT_USERPWD, $this->username . ":" . $this->password );
		curl_setopt( $to_smsh, CURLOPT_TIMEOUT, 10 );

		$result = curl_exec( $to_smsh );

		$this->smsh_response_status = curl_getinfo( $to_smsh, CURLINFO_HTTP_CODE );

		if ( $result ) {
			$jsonObj = json_decode( $result );

			if ( null === $jsonObj ) {
				return new SMS_Error( 'account-credit', $result );
			} elseif ( $this->smsh_response_status != 200 ) {
				return new SMS_Error( 'account-credit', $result );
			} else {
				return $jsonObj->italysms;
			}
		} else {
			return new SMS_Error( 'account-credit', $result );
		}
	}
}

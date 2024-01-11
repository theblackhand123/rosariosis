<?php

class ismsie extends SMS {
	private $wsdl_link = "http://ws3584.isms.ir/sendWS";
	public $tariff = "http://isms.ir/";
	public $unitrial = true;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "09xxxxxxxx";
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


		$data = array(
			'username' => $this->username,
			'password' => $this->password,
			'mobiles'  => $this->to,
			'body'     => $this->msg,
		);

		$data = http_build_query( $data );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->wsdl_link );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

		$result = curl_exec( $ch );
		$json   = json_decode( $result, true );

		if ( $result ) {
			$this->InsertToDB( $this->from, $this->msg, $this->to );
			$this->Hook( 'wp_sms_send', $json );

			return $json;
		}

		return new SMS_Error( 'send-sms', $result );
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		return true;
	}
}

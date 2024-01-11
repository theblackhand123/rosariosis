<?php

class shreesms extends SMS {
	private $wsdl_link = "http://ip.shreesms.net/";
	public $tariff = "http://www.shreesms.net";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
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


		$msg = urlencode( $this->msg );

		foreach ( $this->to as $number ) {
			$result = file_get_contents( "{$this->wsdl_link}smsserver/SMS10N.aspx?Userid={$this->username}&UserPassword={$this->password}&PhoneNumber={$number}&Text={$msg}&GSM={$this->from}" );
		}

		if ( $result = 'Ok' ) {
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

		return new SMS_Error( 'send-sms', $result );
	}

	public function GetCredit() {
		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		$result = file_get_contents( "{$this->wsdl_link}SMSServer/SMSCnt.asp?ID={$this->username}&pw={$this->password}" );

		if ( preg_replace( '/[^0-9]/', '', $result ) ) {
			return $result;
		} else {
			return new SMS_Error( 'account-credit', $result );
		}
	}
}

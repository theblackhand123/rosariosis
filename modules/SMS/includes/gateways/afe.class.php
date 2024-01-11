<?php

class afe extends SMS {
	private $wsdl_link = "http://www.afe.ir/WebService/webservice.asmx?WSDL";
	public $tariff = "http://afe.ir";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "09xxxxxxxx";
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


		$client = new SoapClient( 'http://www.afe.ir/WebService/V4/BoxService.asmx?WSDL' );

		if ( $this->isflash ) {
			$type = 0;
		} else {
			$type = 1;
		}

		$param = array(
			'Username' => $this->username,
			'Password' => $this->password,
			'Number'   => $this->from,
			'Mobile'   => $this->to,
			'Message'  => $this->msg,
			'Type'     => $type
		);

		$result = $client->SendMessage( $param );
		$result = $result->SendMessageResult->string;

		if ( $result ) {
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

		if ( ! class_exists( 'SoapClient' ) ) {
			return new SMS_Error( 'required-class', dgettext( 'SMS', 'Class SoapClient not found. please enable php_soap in your php.' ) );
		}

		try {
			$client = new SoapClient( $this->wsdl_link );
		} catch ( Exception $e ) {
			return new SMS_Error( 'account-credit', $e->getMessage() );
		}

		$result = $client->GetRemainingCredit( array( 'Username' => $this->username, 'Password' => $this->password ) );

		return $result->GetRemainingCreditResult;
	}
}

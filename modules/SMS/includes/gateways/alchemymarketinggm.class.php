<?php

class alchemymarketinggm extends SMS {
	private $wsdl_link = "http://alchemymarketinggm.com:port/api";
	public $tariff = "http://www.alchemymarketinggm.com";
	public $unitrial = false;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "90xxxxxxxxxx";
		$this->help           = "Use API key as Alchemy server port, like: 9443, you must ask them for it.";
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
		 * @param string $this ->from sender number.
		 *
		 * @since 3.4
		 *
		 */

		/**
		 * Modify Receiver number
		 *
		 * @param array $this ->to receiver number
		 *
		 * @since 3.4
		 *
		 */

		/**
		 * Modify text message
		 *
		 * @param string $this ->msg text message.
		 *
		 * @since 3.4
		 *
		 */

		// Encode message

		foreach ( $this->to as $k => $number ) {
			$this->to[ $k ] = trim( $number );
		}

		$this->wsdl_link = str_replace( 'port', $this->has_key, $this->wsdl_link );
		$to              = implode( ',', $this->to );
		$to              = urlencode( $to );
		$msg             = urlencode( $this->msg );

		$result = file_get_contents( $this->wsdl_link . '?username=' . $this->username . '&password=' . $this->password . '&action=sendmessage&messagetype=SMS:TEXT&recipient=' . $to . '&messagedata=' . $msg );

		$result = (array) simplexml_load_string( $result );

		if ( isset( $result['action'] ) AND $result['action'] == 'sendmessage' AND isset( $result['data']->acceptreport ) ) {

			return $result['data'];
		}

		return new SMS_Error( 'send-sms', $result );
	}

	public function GetCredit() {

		// Check username and password
		if ( ! $this->username && ! $this->password ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Username/Password not set for this gateway' ) );
		}

		// Check api key
		if ( ! $this->has_key ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'API key not set for this gateway' ) );
		}

		$this->wsdl_link = str_replace( 'port', $this->has_key, $this->wsdl_link );

		// Get data
		$response = wp_remote_get( $this->wsdl_link . '?action=getcredits&username=' . urlencode( $this->username ) . '&password=' . urlencode( $this->password ) );

		// Check enable simplexml function in the php
		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new SMS_Error( 'account-credit', 'simplexml_load_string PHP Function disabled!' );
		}

		if ( empty( $response['body'] ) ) {
			return false;
		}

		// Load xml
		$xml = (array) simplexml_load_string( $response['body'] );

		if ( isset( $xml['action'] ) AND $xml['action'] == 'getcredits' ) {
			return (int) $xml['data']->account->balance;
		} else {
			$error = (array) $xml['data']->errormessage;

			return new SMS_Error( 'account-credit', $error[0] );
		}
	}
}

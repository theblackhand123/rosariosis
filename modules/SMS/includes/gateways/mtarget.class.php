<?php

class mtarget extends SMS {
	private $wsdl_link = "https://api-public.mtarget.fr/api-sms.json";
	public $tariff = "http://mtarget.fr/";
	public $unitrial = true;
	public $unit;
	public $flash = "enable";
	public $isflash = false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber = "33xxxxxxxxx";
	}

	public function SendSMS() {
		// Check gateway credit
		if ( ! $this->GetCredit() ) {
			return new SMS_Error( 'account-credit', dgettext( 'SMS', 'Your account does not have credit to send SMS.' ) );
		}

		$msg = urlencode( $this->msg );

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


		if ( isset( $this->options['send_unicode'] ) and $this->options['send_unicode'] ) {
			$allowunicode = 'true';
		} else {
			$allowunicode = 'false';
		}

		$success = true;

		// We want to send as few requests as we can
		$msisdns_sublists = array_chunk($this->to, 500);
		foreach ( $msisdns_sublists as $sublist) {
			$to_list = '';
			foreach ( $sublist as $to ) {
				$to_list .= $to . ',';
			}

			$resultJSON = file_get_contents( $this->wsdl_link . '?username=' . urlencode($this->username) . '&password=' . urlencode($this->password) . '&sender=' . urlencode($this->from) . '&msisdn=' . urlencode($to_list) . '&msg=' . urlencode($this->msg) . '&allowunicode=' . $allowunicode );

			try {
				$result = json_decode($resultJSON);
				foreach ($result->results as $message) {
					if ($message->reason !== 'ACCEPTED') {
						$success = false;
					}
				}
			} catch (Exception $e) {
				$success = false;
			}
		}

		if ( $success ) {

			return true;
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

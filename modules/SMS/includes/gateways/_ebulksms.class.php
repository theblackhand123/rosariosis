<?php

class _ebulksms extends SMS {

    public $wsdl_link = "http://api.ebulksms.com";
    public $tariff = "http://ebulksms.com/";
    public $unitrial = true;
    public $unit;
    public $flash = "enable";
    public $isflash = false;

    public function __construct() {
        parent::__construct();
        $this->validateNumber = "23470XXXXXXXX,23480XXXXXXXX,23490XXXXXXXX,23481XXXXXXXX";

        // Enable api key
        $this->has_key = true;

        // includes library
        include_once 'includes/ebulksms/ebulksms.class.php';
    }

    public function SendSMS() {
        // Get the credit.
        $credit = $this->GetCredit();

        // Check gateway credit
        if ( ! $credit ) {
            return $credit;
        }

        $response = wp_remote_get( $this->wsdl_link . "/sendsms?username=" . urlencode( $this->username ) . "&apikey=" . $this->has_key . "&sender=" . urlencode( $this->from ) . "&messagetext=" . urlencode( $this->msg ) . "&flash=0&recipients=" . implode( ',', $this->to ) );

        // Ger response code
        $response_code = wp_remote_retrieve_response_code( $response );

        // Check response code
        if ( $response_code == '200' ) {
            if ( strpos( $response['body'], 'SUCCESS' ) !== false ) {

                return $response;
            } else {

                return new SMS_Error( 'send-sms', $response['body'] );
            }

        } else {

            return new SMS_Error( 'send-sms', $response_code . ' ' . $response['body'] );
        }
   }

    public function GetCredit() {
        // Check username and password
        if ( ! $this->username && ! $this->has_key ) {
            return new SMS_Error( 'account-credit', _( 'SMS', 'Username/API key not set for this gateway' ) );
        }

        // Get response
        $response = wp_remote_get( $this->wsdl_link . '/balance/' . urlencode( $this->username ) . '/' . $this->has_key );

        $response_code = wp_remote_retrieve_response_code( $response );

        if ( $response_code == '200' ) {
            return $response['body'];

        } else {
            return new SMS_Error( 'account-credit', $response_code . ' ' . $response['body'] );
        }
    }

}

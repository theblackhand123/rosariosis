<?php

if ( ! defined( 'SMS_MOBILE_REGEX' ) )
{
	define( 'SMS_MOBILE_REGEX', '/^[\+|\(|\)|\d|\- ]*$/' );
}

if ( ! function_exists( 'initial_gateway' ) ) {
	/**
	 * Initial gateway
	 * @return mixed
	 */
	function initial_gateway() {
		global $sms_option;

		if ( empty( $sms_option ) )
		{
			$sms_option = [
				'gateway_name' => Config( 'SMS_GATEWAY' ),
				'gateway_key' => Config( 'SMS_KEY' ),
				'gateway_username' => Config( 'SMS_USERNAME' ),
				'gateway_password' => Config( 'SMS_PASSWORD' ),
				'gateway_sender_id' => Config( 'SMS_SENDER_ID' ),
			];
		}

		// Include default gateway
		include_once dirname( __FILE__ ) . '/class-sms.php';
		include_once dirname( __FILE__ ) . '/gateways/default.class.php';

		// Using default gateway if does not set gateway in the setting
		if ( empty( $sms_option['gateway_name'] ) ) {
			return new Default_Gateway;
		}

		if ( ! include_gateway_class( $sms_option['gateway_name'] ) )
		{
			return new Default_Gateway;
		}

		// Create object from the gateway class
		if ( $sms_option['gateway_name'] == 'default' ) {
			$sms = new Default_Gateway();
		} else {
			$sms = new $sms_option['gateway_name'];
		}

		// Set username and password
		$sms->username = $sms_option['gateway_username'];
		$sms->password = $sms_option['gateway_password'];

		// Set api key
		if ( $sms->has_key && $sms_option['gateway_key'] ) {
			$sms->has_key = $sms_option['gateway_key'];
		}

		// Show gateway help configuration in gateway page
		if ( $sms->help ) {
			// echo '<p class="description">' . $sms->help . '</p>';
		}

		// Check unit credit gateway
		if ( $sms->unitrial == true ) {
			$sms->unit = dgettext( 'SMS', 'Credit');
		} else {
			$sms->unit = dgettext( 'SMS', 'SMS');
		}

		// Set from sender id
		if ( ! $sms->from ) {
			$sms->from = $sms_option['gateway_sender_id'];
		}

		// Return gateway object
		return $sms;
	}
}

if ( ! function_exists( 'include_gateway_class' ) )
{
	/**
	 * Include Gateway class file
	 *
	 * @param  string $gateway_name Gateway name.
	 * @return bool   False if file does not exist.
	 */
	function include_gateway_class( $gateway_name )
	{
		global $RosarioModules;

		if ( is_file( 'modules/SMS/includes/gateways/' . $gateway_name . '.class.php' ) )
		{
			include_once 'modules/SMS/includes/gateways/' . $gateway_name . '.class.php';

			return true;
		}

		if ( ! empty( $RosarioModules['SMS_Premium'] )
			&& is_file( 'modules/SMS_Premium/includes/gateways/' . $gateway_name . '.class.php' ) )
		{
			include_once 'modules/SMS_Premium/includes/gateways/' . $gateway_name . '.class.php';

			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'wp_remote_request' ) )
{
	function wp_remote_request( $url, $args )
	{
		if ( $args['method'] === 'POST' )
		{
			return wp_remote_post( $url, $args );
		}

		return wp_remote_get( $url, $args );
	}
}

// WP remote functions found at
// @link https://github.com/webVenus928/wordpress-checksum/blob/master/tests/httpFunctions.php
if ( ! function_exists( 'wp_remote_post' ) )
{
	function wp_remote_post( $url, $args, $method = 'POST' )
	{
		$content = null;

		if ( isset( $args['body'] ) )
		{
			json_decode( $args['body'] );

			if ( json_last_error() == JSON_ERROR_NONE )
			{
				$content = $args['body'];
			}
			else
			{
				$content = http_build_query( $args['body'] );
			}
		}

		$opts = [ 'http' =>
			[
				'method'  => $method,
				'header'  => 'Content-type: application/x-www-form-urlencoded' . "\r\n",
				'content' => $content,
			]
		];

		if ( isset( $args['headers'] ) )
		{
			// Add custom headers, like Authorization.
			$headers = [];

			foreach ( (array) $args['headers'] as $header_type => $header_content )
			{
				if ( mb_strtolower( $header_type ) === 'content-type' )
				{
					$opts['http']['header'] = $header_type . ': ' . $header_content . "\r\n";

					continue;
				}

				$headers[] = $header_type . ': ' . $header_content;
			}

			$opts['http']['header'] .= implode( "\r\n", $headers );
		}

		if ( ROSARIO_DEBUG
			&& function_exists( 'd' ) )
		{
			d( $opts, $url );
		}

		$context  = stream_context_create($opts);
		$content = @file_get_contents( $url, false, $context );
		$codeParts = explode( ' ', $http_response_header[0] );

		return [
			'response' => [
				'code' => (int) $codeParts[1],
			],
			'body' => $content,
		];
	}
}

if ( ! function_exists( 'wp_remote_get' ) )
{
	function wp_remote_get( $url, $args = null )
	{
		if ( isset( $args['body'] ) || isset( $args['headers'] ) )
		{
			// Fix 403 error in smsglobal.class.php when calling REST API with OAuth2 header.
			return wp_remote_post( $url, $args, 'GET' );
		}

		$content = @file_get_contents( $url );
		$codeParts = explode( ' ', $http_response_header[0] );

		if ( ROSARIO_DEBUG
			&& function_exists( 'd' ) )
		{
			d( $url );
		}

		return [
			'response' => [
				'code' => (int) $codeParts[1],
			],
			'body' => $content,
		];
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) )
{
	function wp_remote_retrieve_response_code($response)
	{
		return isset( $response['response']['code'] ) ? $response['response']['code'] : false;
	}
}

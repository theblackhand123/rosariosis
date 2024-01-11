<?php
/**
 * Configuration program
 *
 * @package SMS module
 */

require_once 'ProgramFunctions/TipMessage.fnc.php';
require_once 'modules/SMS/includes/SMS.fnc.php';

require_once 'modules/SMS/includes/class-sms-error.php';
require_once 'modules/SMS/includes/class-sms-gateway.php';
require_once 'modules/SMS/includes/class-sms.php';
require_once 'modules/SMS/includes/functions.php';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['values'] ) )
	{
		foreach ( $_REQUEST['values'] as $item => $value )
		{
			Config( $item, $value );
		}
	}

	RedirectURL( [ 'modfunc', 'values' ] );
}

if ( $_REQUEST['modfunc'] === 'gateways-info' )
{
	DrawHeader(
		'<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] ) ) . '">« ' . _( 'Back' ) . '</a>'
	);

	$gateways = SMS_Gateway::gateway();

	$gateways_RET = [ 0 => '' ];

	// Format country separators: capitalize.
	foreach ( $gateways as $country => $country_gateways )
	{
		foreach ( $country_gateways as $class => $website )
		{
			if ( ! include_gateway_class( $class ) )
			{
				continue;
			}

			$gateway_object = new $class;

			$title = ucfirst( trim( $class, '_' ) );

			$validate_number = $gateway_object->validateNumber;

			if ( empty( $_REQUEST['LO_save'] )
				&& $validate_number )
			{
				$validate_number = MakeTipMessage(
					$gateway_object->validateNumber,
					dgettext( 'SMS', 'Mobile Number' ),
					button( 'help' )
				);
			}

			$title = ! empty( $_REQUEST['LO_save'] ) ?
				$title . ' ' . $gateway_object->tariff :
				'<a href="' . ( function_exists( 'URLEscape' ) ?
					URLEscape( $gateway_object->tariff ) :
					_myURLEncode( $gateway_object->tariff ) ) . '" target="_blank" title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $website ) : htmlspecialchars( $website, ENT_QUOTES ) ) . '">' .
					$title . '</a>';

			$gateways_RET[] = [
				'TITLE' => $title,
				'COUNTRY' => ucfirst( $country ),
				'VALIDATE_NUMBER' => $validate_number,
				'HELP' => ( $gateway_object->help ?
					'<div id="' . GetInputID( $class ) . '" class="rt2colorBox">' . $gateway_object->help . '</div>' :
					'' ),
			];
		}
	}

	unset( $gateways_RET[0] );

	$columns = [
		'TITLE' => _( 'Website' ),
		'COUNTRY' => dgettext( 'SMS', 'Country' ),
		'VALIDATE_NUMBER' => dgettext( 'SMS', 'Mobile Number' ),
		'HELP' => _( 'Help' ),
	];

	ListOutput(
		$gateways_RET,
		$columns
	);
}

$sms = initial_gateway();

if ( ! $_REQUEST['modfunc'] )
{
	if ( Config( 'SMS_GATEWAY' ) )
	{
		if ( $sms->tariff )
		{
			$note[] = '<a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( $sms->tariff ) :
				_myURLEncode( $sms->tariff ) ) . '" target="_blank">' . $sms->tariff . '</a>';
		}

		if ( $sms->validateNumber )
		{
			$note[] = dgettext( 'SMS', 'Mobile Number' ) . ' &mdash; ' . $sms->validateNumber;
		}

		$credit = $sms->GetCredit();

		if ( ( $credit || $credit === '0' )
			&& ! $credit instanceof SMS_Error )
		{
			$note[] = dgettext( 'SMS', 'Credit' ) . ': ' . $credit;
		}
	}

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error );

	echo '<form action="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) :
		_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) ) . '" method="POST">';

	DrawHeader(
		'<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=gateways-info' ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=gateways-info' ) ) . '">' .
			dgettext( 'SMS', 'Gateways Info' ) . '</a>',
		SubmitButton()
	);

	echo '<br />';

	PopTable( 'header', dgettext( 'SMS', 'SMS' ), 'style="max-width: 600px;"' );

	echo '<table class="width-100p cellpadding-5">';

	$student_mobile_field_RET = DBGet( "SELECT ID,TITLE
		FROM custom_fields
		WHERE TYPE='text'" );

	$student_mobile_field_options = [];

	foreach ( (array) $student_mobile_field_RET as $field )
	{
		$student_mobile_field_options['CUSTOM_' . $field['ID'] ] = ParseMLField( $field['TITLE'] );
	}

	echo '<tr><td>' . SelectInput(
		Config( 'SMS_STUDENT_MOBILE_FIELD' ),
		'values[SMS_STUDENT_MOBILE_FIELD]',
		dgettext( 'SMS', 'Student mobile number field' ),
		$student_mobile_field_options,
		'N/A',
		'required'
	) . '</td></tr>';

	$user_mobile_field_RET = DBGet( "SELECT ID,TITLE
		FROM staff_fields
		WHERE TYPE='text'" );

	if ( version_compare( ROSARIO_VERSION, '5.9-beta', '<' ) )
	{
		$user_mobile_field_options = [ 'PHONE' => _( 'Phone Number' ) ];
	}
	else
	{
		// @since 5.9 Move Email & Phone Staff Fields to custom fields.
		$user_mobile_field_options = [];
	}

	foreach ( (array) $user_mobile_field_RET as $field )
	{
		$field_key = 'CUSTOM_' . $field['ID'];

		if ( $field['ID'] === '200000000' )
		{
			// Fix User Email field.
			$field_key = 'EMAIL';
		}

		$user_mobile_field_options[ $field_key ] = ParseMLField( $field['TITLE'] );
	}

	echo '<tr><td>' . SelectInput(
		Config( 'SMS_USER_MOBILE_FIELD' ),
		'values[SMS_USER_MOBILE_FIELD]',
		dgettext( 'SMS', 'User mobile number field' ),
		$user_mobile_field_options,
		'N/A',
		'required'
	) . '</td></tr>';

	$gateways = SMS_Gateway::gateway();

	$gateway_options = [];

	// Format country separators: capitalize.
	foreach ( $gateways as $country => $country_gateways )
	{
		$gateway_options[ ucfirst( $country ) ] = $country_gateways;
	}

	// @deprecated since 5.6 can use SelectInput with group.
	echo '<tr><td>' . SMSSelectInput(
		Config( 'SMS_GATEWAY' ),
		'values[SMS_GATEWAY]',
		dgettext( 'SMS', 'Gateway' ),
		$gateway_options,
		'N/A',
		'group'
	) . '</td></tr>';

	if ( Config( 'SMS_GATEWAY' ) )
	{
		echo '<tr><td><fieldset><legend>' . dgettext( 'SMS', 'Gateway API' ) . '</legend><table>';

		echo '<tr><td><p>' . SMS_Gateway::help() . '</p></td></tr>';

		echo '<tr><td>' . TextInput(
			Config( 'SMS_KEY' ),
			'values[SMS_KEY]',
			dgettext( 'SMS', 'API key' ),
			'size=20'
		) . '</td></tr>';

		echo '<tr><td>' . TextInput(
			Config( 'SMS_USERNAME' ),
			'values[SMS_USERNAME]',
			dgettext( 'SMS', 'API username' ),
			'size=20'
		) . '</td></tr>';

		echo '<tr><td>' . PasswordInput(
			[ Config( 'SMS_PASSWORD' ), str_repeat( '*', 8 ) ],
			'values[SMS_PASSWORD]',
			dgettext( 'SMS', 'API password' )
		) . '</td></tr>';

		echo '<tr><td>' . TextInput(
			Config( 'SMS_SENDER_ID' ),
			'values[SMS_SENDER_ID]',
			dgettext( 'SMS', 'Sender name' ),
			'size=10 maxlength="11"'
		) . '</td></tr>';

		echo '</table></fieldset></td></tr>';

		// Gateway status
		/*dgettext( 'SMS', 'Gateway status' ),


		'account_credit'            => array(
			'id'      => 'account_credit',
			'name'    => dgettext( 'SMS', 'Status' ),
			'type'    => 'html',
			'options' => SMS_Gateway::status(),
		),
		'account_response'          => array(
			'id'      => 'account_response',
			'name'    => dgettext( 'SMS', 'Result request' ),
			'type'    => 'html',
			'options' => SMS_Gateway::response(),
		),
		'bulk_send'                 => array(
			'id'      => 'bulk_send',
			'name'    => dgettext( 'SMS', 'Bulk send' ),
			'type'    => 'html',
			'options' => SMS_Gateway::bulk_status(),
		),

		// Account credit
		dgettext( 'SMS', 'Account balance' ),

		'account_credit_in_menu'    => array(
			'id'      => 'account_credit_in_menu',
			'name'    => dgettext( 'SMS', 'Show in admin menu' ),
			'type'    => 'checkbox',
			'options' => $options,
			'desc'    => dgettext( 'SMS', 'Show your account credit in admin menu.' )
		),
		'account_credit_in_sendsms' => array(
			'id'      => 'account_credit_in_sendsms',
			'name'    => dgettext( 'SMS', 'Show in send SMS' ),
			'type'    => 'checkbox',
			'options' => $options,
			'desc'    => dgettext( 'SMS', 'Show your account credit in send SMS page.' )
		),*/

	}

	echo '</table>';

	PopTable( 'footer' );

	echo '</br /><div class="center">' . SubmitButton() . '</div></form>';
}

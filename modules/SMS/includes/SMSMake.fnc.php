<?php
/**
 * SMS Make functions
 *
 * @package SMS module
 */

/**
 * Make Sender
 *
 * @param string $value  Staff ID.
 * @param string $column 'STAFF_ID'.
 *
 * @return string Sender full name along with link to User Info if is admin.
 */
function SMSMakeSender( $value, $column = 'STAFF_ID' )
{
	if ( $value <= 0 )
	{
		return '';
	}

	$staff_id = (int) $value;

	$staff_full_name = DBGetOne( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME
		FROM staff s
		WHERE s.STAFF_ID='" . (int) $staff_id . "'" );

	if ( ! empty( $_REQUEST['LO_save'] )
		|| isset( $_REQUEST['_ROSARIO_PDF'] )
		|| User( 'PROFILE' ) !== 'admin' )
	{
		return $staff_full_name;
	}

	$staff_link = '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Users/User.php&staff_id=' . $staff_id ) :
		_myURLEncode( 'Modules.php?modname=Users/User.php&staff_id=' . $staff_id ) ) . '">' .
		$staff_full_name . '</a>';

	return $staff_link;
}

/**
 * Make Recipients
 *
 * @since 10.1 Add User ID after recipient name
 *
 * @param string $value  Recipients, JSON string.
 * @param string $column 'RECIPIENTS'.
 *
 * @return Formatted recipients list. If > 1, display profile + count & move list to TipMessage.
 */
function SMSMakeRecipients( $value, $column = 'RECIPIENTS' )
{
	require_once 'ProgramFunctions/TipMessage.fnc.php';

	if ( empty( $value ) )
	{
		return '';
	}

	$recipients = json_decode( $value, true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		return $value;
	}

	$count = count( $recipients );

	$is_student = $recipients[0]['profile'] === 'student';

	$recipient_ids = [];

	$recipient_names = [];

	foreach ( $recipients as $recipient )
	{
		$recipient_ids[] = $recipient['user_id'];

		$recipient_names[] = $recipient['name'] . ' (' . $recipient['user_id'] . ')';
	}

	if ( $count < 2 )
	{
		// $return = $is_student ? _( 'Student' ) : _( 'User' );

		// 1 recipient, list.
		$return = implode( ', ', $recipient_names );

		return $return;
	}

	$return = $is_student ? _( 'Students' ) : _( 'Users' );

	// More than 2 recipients. Count.
	$return .= ' (' . $count . ')';

	// Limit to 15 recipients.
	if ( count( $recipient_names ) > 16 )
	{
		$recipient_names = array_splice( $recipient_names, 0, 15 );

		$recipient_names[] = '...';
	}

	// Add recipient names inside Tip Message.
	$return = MakeTipMessage(
		'<span class="size-1">' . implode( '<br />', $recipient_names ) . '</span>',
		dgettext( 'SMS', 'Recipients' ),
		$return
	);

	return $return;
}

/**
 * Make Data
 *
 * @uses ColorBox jQuery plugin to display various lines texts in ListOutput on mobiles called using the .rt2colorBox CSS class
 * @param string $value  Data, JSON string.
 * @param string $column 'DATA'.
 *
 * @return string Formatted SMS text. Inside Colorbox if on mobile.
 */
function SMSMakeData( $value, $column = 'DATA' )
{
	static $i = 1;

	if ( empty( $value ) )
	{
		return '';
	}

	$data = json_decode( $value, true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		return $value;
	}

	$text = nl2br( $data['text'] );

	$return = mb_strpos( $text, '<br' ) !== false ?
		'<div id="' . GetInputID( $column . $i++ ) . '" class="rt2colorBox">' .
			$text . '</div>' :
		$text;

	return $return;
}

/**
 * Make Send Again link
 *
 * @param string $value  SMS ID.
 * @param string $column 'SEND_AGAIN'
 *
 * @return string Send Again link.
 */
function SMSMakeSendAgain( $value, $column = 'SEND_AGAIN' )
{
	global $THIS_RET,
		$RosarioModules;

	if ( $value <= 0 )
	{
		return '';
	}

	$program = 'SMS/Send.php';

	if ( ! empty( $RosarioModules['SMS_Premium'] ) )
	{
		$program = 'SMS_Premium/Send.php';
	}

	if ( ! empty( $_REQUEST['LO_save'] )
		|| isset( $_REQUEST['_ROSARIO_PDF'] )
		|| ! AllowEdit( $program ) )
	{
		return '';
	}

	$sms_id = (int) $value;

	$recipients = json_decode( $THIS_RET['RECIPIENTS'], true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		return '';
	}

	$is_student = $recipients[0]['profile'] === 'student';

	$recipients_to = $is_student ? 'student' : 'user';

	$link = dgettext( 'SMS', 'Send again' );

	$link = '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=' . $program . '&sms_id=' . $sms_id .
			'&recipients_to=' . $recipients_to . '&search_modfunc=list' ) :
		_myURLEncode( 'Modules.php?modname=' . $program . '&sms_id=' . $sms_id .
			'&recipients_to=' . $recipients_to . '&search_modfunc=list' ) ) . '">' . $link . '</a>';

	return $link;
}

/**
 * Make Choose Checkbox
 *
 * @param string $value  Student or Staff ID.
 * @param string $column 'CHECKBOX'.
 *
 * @return string Empty if no Mobile number, else Choose Checkbox.
 */
function SMSMakeChooseCheckbox( $value, $column = 'CHECKBOX' )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['MOBILE_NUMBER'] )
		|| ! preg_match( SMS_MOBILE_REGEX, $THIS_RET['MOBILE_NUMBER'] ) )
	{
		return '';
	}

	return MakeChooseCheckbox( $value, $column );
}

/**
 * Make Mobile Number
 *
 * @param string $value  Mobile Number.
 * @param string $column 'MOBILE_NUMBER'.
 *
 * @return string Formatted Mobile Number, or link to User / Student Info if empty.
 */
function SMSMakeMobileNumber( $value, $column = 'MOBILE_NUMBER' )
{
	global $THIS_RET;

	if ( empty( $value ) )
	{
		if ( ! empty( $THIS_RET['STAFF_ID'] )
			&& User( 'PROFILE' ) === 'admin'
			&& AllowEdit( 'Users/User.php&category_id=1' ) )
		{
			return button( 'x' ) . ' <a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=Users/User.php&category_id=1&staff_id=' . $THIS_RET['STAFF_ID'] ) :
				_myURLEncode( 'Modules.php?modname=Users/User.php&category_id=1&staff_id=' . $THIS_RET['STAFF_ID'] ) ) . '">' . _( 'User Info' ) . '</a>';
		}

		if ( ! empty( $THIS_RET['STUDENT_ID'] )
			&& User( 'PROFILE' ) === 'admin'
			&& AllowEdit( 'Students/Student.php&category_id=1' )
			&& Config( 'SMS_STUDENT_MOBILE_FIELD' ) )
		{
			return button( 'x' ) . ' <a href="' . ( function_exists( 'URLEscape' ) ?
				URLEscape( 'Modules.php?modname=Students/Student.php&category_id=1&student_id=' . $THIS_RET['STUDENT_ID'] ) :
				_myURLEncode( 'Modules.php?modname=Students/Student.php&category_id=1&student_id=' . $THIS_RET['STUDENT_ID'] ) ) . '">' . _( 'Student Info' ) . '</a>';
		}

		return $value;
	}

	// Check mobile against regex.
	if ( ! preg_match( SMS_MOBILE_REGEX, $value ) )
	{
		return button( 'x' ) . ' ' . $value;
	}

	return makePhone( $value, $column );
}

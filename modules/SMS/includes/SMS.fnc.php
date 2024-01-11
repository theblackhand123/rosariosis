<?php
/**
 * SMS functions
 *
 * @package SMS module
 */

/**
 * Send SMS
 *
 * @param string $text          Formatted text message.
 * @param string $mobile_number Mobile Number.
 *
 * @return boolean False if not sent, else true.
 */
function SMSSend( $text, $mobile_number )
{
	global $sms;

	if ( empty( $text )
		|| empty( $mobile_number ) )
	{
		return false;
	}

	$sms->to = [ $mobile_number ];

	$sms->msg = $text;

	$return = $sms->SendSMS();

	if ( ROSARIO_DEBUG )
	{
		var_dump( $sms, $return );
	}

	return ! ( $return instanceof SMS_Error );
}

/**
 * Save SMS
 *
 * @param string  $text            Raw text message.
 * @param array   $recipients      Recipients array.
 * @param integer $sender_staff_id Sender User ID (optional), defaults to logged in User, use '' (translated to NULL) for none.
 *
 * @return boolean True if SMS saved in DB, else false.
 */
function SMSSave( $text, $recipients, $sender_staff_id = -1 )
{
	$recipients_json = json_encode( $recipients );

	$data = [ 'text' => $text ];

	$data_json = json_encode( $data );


	if ( $sender_staff_id < 0 )
	{
		$sender_staff_id = User( 'STAFF_ID' );
	}

	return (bool) DBQuery( "INSERT INTO sms
		(SYEAR,SCHOOL_ID,STAFF_ID,RECIPIENTS,DATA)
		VALUES('" . UserSyear() . "','" . UserSchool() . "','" . $sender_staff_id. "','" .
		DBEscapeString( $recipients_json ) . "','" . DBEscapeString( $data_json ) . "');" );
}

/**
 * Get Recipient IDs
 *
 * @param int $sms_id SMS ID
 *
 * @return array Recipient IDs.
 */
function SMSGetRecipientIDs( $sms_id )
{
	$sms = SMSGet( $sms_id );

	if ( empty( $sms['RECIPIENTS'] ) )
	{
		return [];
	}

	$user_ids = [];

	$recipients = json_decode( $sms['RECIPIENTS'], true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		return [];
	}

	foreach ( $recipients as $user )
	{
		$user_ids[] = $user['user_id'];
	}

	return $user_ids;
}

/**
 * Get Text message
 *
 * @param int $sms_id SMS ID.
 *
 * @return string Text message.
 */
function SMSGetText( $sms_id )
{
	$sms = SMSGet( $sms_id );

	if ( empty( $sms['DATA'] ) )
	{
		return '';
	}

	$data = json_decode( $sms['DATA'], true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		return $sms['DATA'];
	}

	return $data['text'];
}

/**
 * Get SMS from DB
 *
 * @param int $sms_id SMS ID.
 *
 * @return array SMS from DB.
 */
function SMSGet( $sms_id )
{
	$sms_sql = "SELECT ID,SYEAR,SCHOOL_ID,STAFF_ID,RECIPIENTS,DATA
		FROM sms
		WHERE ID='" . (int) $sms_id . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'";

	if ( User( 'PROFILE' ) !== 'admin' )
	{
		$sms_sql .= " AND STAFF_ID='" . User( 'STAFF_ID' ) . "'";
	}

	$sms_RET = DBGet( $sms_sql );

	return empty( $sms_RET[1] ) ? [] : $sms_RET[1];
}

/**
 * Recipients To Header HTML
 *
 * @param string $recipients_to student or user.
 *
 * @return string Recipients To Header HTML.
 */
function SMSRecipientsToHeader( $recipients_to )
{
	$search_user_url = PreparePHP_SELF(
		$_REQUEST,
		[ 'search_modfunc', 'sms_id' ],
		[ 'recipients_to' => 'user' ]
	);

	$search_student_url = PreparePHP_SELF(
		$_REQUEST,
		[ 'search_modfunc', 'sms_id' ],
		[ 'recipients_to' => 'student' ]
	);

	$header_html = '<a href="' . $search_student_url . '">' .
		( $recipients_to === 'student' ? '<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) .
		'</a> | <a href="' . $search_user_url . '">' .
		( $recipients_to === 'user' ? '<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) .
		'</a>';

	return $header_html;
}


/**
 * Select Input
 *
 * @since 5.6 Support option groups (`<optgroup>`) by adding 'group' to $extra.
 *
 * @example SelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $options, 'N/A', $extra )
 *
 * @uses GetInputID() to generate ID from name
 * @uses FormatInputTitle() to format title
 * @uses InputDivOnclick()
 *       if ( AllowEdit() && !isset( $_REQUEST['_ROSARIO_PDF'] ) && $value != '' && $div )
 *
 * @param  string         $value    Input value.
 * @param  string         $name     Input name.
 * @param  string         $title    Input title (optional). Defaults to ''.
 * @param  array          $options  Input options: array( option_value => option_text ) or with groups: array( group_name => array( option_value => option_text ) ).
 * @param  string|boolean $allow_na Allow N/A (empty value); set to false to disallow (optional). Defaults to N/A.
 * @param  string         $extra    Extra HTML attributes added to the input. Add 'group' to enable options grouping.
 * @param  boolean        $div      Is input wrapped into <div onclick>? (optional). Defaults to true.
 *
 * @return string         Input HTML
 */
function SMSSelectInput( $value, $name, $title = '', $options = [], $allow_na = 'N/A', $extra = '', $div = true )
{
	$id = GetInputID( $name );

	// Mab - support array style $option values.
	$value = is_array( $value ) ? $value[0] : $value;

	$required = $value == '' && mb_strpos( $extra, 'required' ) !== false;

	$is_group = is_array( reset( $options ) ) && mb_strpos( $extra, 'group' ) !== false;

	$display_val = isset( $options[ $value ] ) ?
		( is_array( $options[ $value ] ) ? $options[ $value ][1] : $options[ $value ] ) :
		'';

	if ( $is_group )
	{
		foreach ( $options as $group_options )
		{
			if ( isset( $group_options[ $value ] ) )
			{
				$display_val = is_array( $group_options[ $value ] ) ? $group_options[ $value ][1] : $group_options[ $value ];

				break;
			}
		}
	}

	if ( AllowEdit()
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$select = '<select name="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $name ) : htmlspecialchars( $name, ENT_QUOTES ) ) . '" id="' . $id . '" ' . $extra . '>';

		if ( $allow_na !== false )
		{
			$select .= '<option value="">' . ( $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na ) . '</option>';
		}

		$make_option = function( $value, $key, $val )
		{
			$selected = '';

			$key .= '';

			if ( $value == $key
				&& ( !( $value == false && $value !== $key )
					|| ( $value === '0' && $key === 0 ) ) )
			{
				$selected = ' selected';
			}

			return '<option value="' . htmlspecialchars( $key, ENT_QUOTES ) . '"' .
				$selected . '>' . ( is_array( $val ) ? $val[0] : $val ) . '</option>';
		};

		if ( $is_group )
		{
			foreach ( (array) $options as $group => $group_options )
			{
				$select .= '<optgroup label="' . htmlspecialchars( $group, ENT_QUOTES ) . '">';

				foreach ( (array) $group_options as $key => $val )
				{
					$select .= $make_option( $value, $key, $val );
				}

				$select .= '</optgroup>';
			}
		}
		else
		{
			// Mab - append current val to select list if not in list.
			if ( $value != ''
				&& ( ! is_array( $options )
					|| ! array_key_exists( $value, $options ) ) )
			{
				$options[ $value ] = [ $value, '<span style="color:red">' . $value . '</span>' ];

				$display_val = '<span style="color:red">' . $value . '</span>';
			}

			foreach ( (array) $options as $key => $val )
			{
				$select .= $make_option( $value, $key, $val );
			}
		}

		$select .= '</select>' . FormatInputTitle( $title, $id, $required );

		if ( $value != ''
			&& $div )
		{
			$return = InputDivOnclick(
				$id,
				$select,
				$display_val,
				FormatInputTitle( $title )
			);
		}
		else
			$return = $select;
	}
	else
	{
		if ( $display_val == '' )
		{
			if ( $allow_na !== false )
			{
				$display_val = $allow_na === 'N/A' ? _( 'N/A' ) : $allow_na;
			}
			else
				$display_val = '-';
		}

		$return = $display_val . FormatInputTitle( $title );
	}

	return $return;
}

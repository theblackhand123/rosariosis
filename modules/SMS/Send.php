<?php
/**
 * Send program
 *
 * @package SMS module
 */

require_once 'modules/SMS/includes/SMS.fnc.php';
require_once 'modules/SMS/includes/SMSMake.fnc.php';

require_once 'modules/SMS/includes/class-sms-gateway.php';
require_once 'modules/SMS/includes/class-sms-error.php';
require_once 'modules/SMS/includes/functions.php';

$sms = initial_gateway();

if ( User( 'PROFILE' ) === 'teacher' )
{
	// Allow Edit if non admin.
	$_ROSARIO['allow_edit'] = true;
}

if ( ! empty( $_REQUEST['period'] )
	&& function_exists( 'SetUserCoursePeriod' ) )
{
	// @since RosarioSIS 10.9 Set current User Course Period.
	SetUserCoursePeriod( $_REQUEST['period'] );
}

$recipients_to = issetVal( $_REQUEST['recipients_to'], 'student' );

if ( $_REQUEST['modfunc'] === 'send'
	&& AllowEdit() )
{
	$RET = [];

	if ( empty( $_REQUEST['st_arr'] ) )
	{
		$error[] = $recipients_to === 'student' ?
			_( 'You must choose at least one student.' ) :
			_( 'You must choose at least one user' );
	}
	else
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

		if ( $recipients_to === 'student' )
		{
			$student_phone_field = Config( 'SMS_STUDENT_MOBILE_FIELD' ) ?
				"s." . Config( 'SMS_STUDENT_MOBILE_FIELD' ) :
				"''";

			// Phone number.
			$extra['SELECT'] .= "," . $student_phone_field . " AS PHONE_NUMBER";

			$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

			$RET = GetStuList( $extra );
		}
		else
		{
			$user_phone_field = Config( 'SMS_USER_MOBILE_FIELD' ) ?
				"s." . Config( 'SMS_USER_MOBILE_FIELD' ) :
				"''";

			// Phone number.
			$extra['SELECT'] .= "," . $user_phone_field . " AS PHONE_NUMBER";

			$extra['WHERE'] = " AND s.STAFF_ID IN (" . $st_list . ")";

			$RET = GetStaffList( $extra );
		}
	}

	if ( empty( $RET ) )
	{
		$error[] = $recipients_to === 'student' ?
			_( 'No Students were found.' ) :
			_( 'No Users were found.' );
	}
	else
	{
		// Increase PHP script time limit. Send up to 1000 SMS within 1000 seconds.
		@set_time_limit( 1000 );
	}

	// Security: use $_POST here to avoid DBEscapeString() on $_REQUEST, still use strip_tags() though.
	// DBEscapeString() is used in SMSSave().
	$text = strip_tags( $_POST['text'] );

	$sms_recipients = [];

	foreach ( (array) $RET as $user )
	{
		$phone_number = $user['PHONE_NUMBER'];

		if ( ! $phone_number )
		{
			continue;
		}

		$user_id = $recipients_to === 'student' ? $user['STUDENT_ID'] : $user['STAFF_ID'];

		$sms_sent = SMSSend( $text, $phone_number );

		if ( $sms_sent )
		{
			$sms_recipients[] = [
				'profile' => $recipients_to,
				'user_id' => $user_id,
				'name' => $user['FULL_NAME'],
				'phone_number' => $phone_number,
			];
		}
	}

	if ( $sms_recipients )
	{
		SMSSave( $text, $sms_recipients );

		$note[] = button( 'check' ) . '&nbsp;' .
		sprintf(
			( $recipients_to === 'student' ?
				dgettext( 'SMS', 'SMS sent to %d students.' ) :
				dgettext( 'SMS', 'SMS sent to %d users.' )
			),
			count( $sms_recipients )
		);
	}
	elseif ( $RET )
	{
		$error[] = dgettext( 'SMS', 'SMS not sent.' );
	}

	// Remove modfunc & st_arr from URL & redirect.
	RedirectURL( [ 'modfunc', 'st_arr' ] );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	$error_type = 'error';

	if ( $recipients_to === 'student'
		&& ! Config( 'SMS_STUDENT_MOBILE_FIELD' ) )
	{
		$error_type = 'fatal';

		if ( AllowEdit( 'SMS/Configuration.php' ) )
		{
			$error[] = sprintf(
				dgettext( 'SMS', 'Please set the Student mobile number field: %s' ),
				'<a href="Modules.php?modname=SMS/Configuration.php">' . _( 'Configuration' ) . '</a>'
			);
		}
		else
		{
			$error[] = dgettext( 'SMS', 'Student mobile number field not set.' );
		}
	}

	if ( $recipients_to === 'user'
		&& ! Config( 'SMS_USER_MOBILE_FIELD' ) )
	{
		$error_type = 'fatal';

		if ( User( 'PROFILE' ) === 'admin'
			&& AllowEdit( 'SMS/Configuration.php' ) )
		{
			$error[] = sprintf(
				dgettext( 'SMS', 'Please set the User mobile number field: %s' ),
				'<a href="Modules.php?modname=SMS/Configuration.php">' . _( 'Configuration' ) . '</a>'
			);
		}
		else
		{
			$error[] = dgettext( 'SMS', 'User mobile number field not set.' );
		}
	}

	if ( ! Config( 'SMS_GATEWAY' ) )
	{
		$error_type = 'fatal';

		if ( User( 'PROFILE' ) === 'admin'
			&& AllowEdit( 'SMS/Configuration.php' ) )
		{
			$error[] = sprintf(
				dgettext( 'SMS', 'Please select a Gateway: %s' ),
				'<a href="Modules.php?modname=SMS/Configuration.php">' . _( 'Configuration' ) . '</a>'
			);
		}
		else
		{
			$error[] = dgettext( 'SMS', 'Gateway not set.' );
		}
	}

	echo ErrorMessage( $error, $error_type );

	echo ErrorMessage( $note, 'note' );

	$recipients_to_header = SMSRecipientsToHeader( $recipients_to );

	DrawHeader( $recipients_to_header );

	if ( ! empty( $_REQUEST['sms_id'] ) )
	{
		$recipient_ids = SMSGetRecipientIDs( $_REQUEST['sms_id'] );

		if ( $recipient_ids )
		{
			$st_list = "'" . implode( "','", $recipient_ids ) . "'";

			if ( $recipients_to === 'student' )
			{
				$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";
			}
			else
			{
				$extra['WHERE'] = " AND s.STAFF_ID IN (" . $st_list . ")";
			}
		}
	}

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		$form_url = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=send&include_inactive=' .
			issetVal( $_REQUEST['include_inactive'], '' ) .
			'&_search_all_schools=' . issetVal( $_REQUEST['_search_all_schools'], '' ) .
			'&recipients_to=' . $recipients_to;

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			/**
			 * Adding `'&period=' . UserCoursePeriod()` to the Teacher form URL will prevent the following issue:
			 * If form is displayed for CP A, then Teacher opens a new browser tab and switches to CP B
			 * Then teacher submits the form, data would be saved for CP B...
			 *
			 * Must be used in combination with
			 * `if ( ! empty( $_REQUEST['period'] ) ) SetUserCoursePeriod( $_REQUEST['period'] );`
			 */
			$form_url .= '&period=' . UserCoursePeriod();
		}

		echo '<form action="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $form_url ) :
			_myURLEncode( $form_url ) ) . '" method="POST">';

		if ( $recipients_to === 'student' )
		{
			$submit_button_text = dgettext( 'SMS', 'Send SMS to Selected Students' );
		}
		else
		{
			$submit_button_text = dgettext( 'SMS', 'Send SMS to Selected Users' );
		}

		$extra['header_right'] = SubmitButton( $submit_button_text );

		$text = '';

		if ( ! empty( $_REQUEST['sms_id'] ) )
		{
			$text = SMSGetText( $_REQUEST['sms_id'] );
		}

		$extra['extra_header_left'] = '<table class="width-100p"><tr><td>' .
		TextAreaInput(
			$text,
			'text',
			_( 'Text' ),
			'required rows="5" style="width:280px;"',
			false,
			'text'
		) . '&nbsp;&nbsp;<span id="text-characters-count">0</span> / 160</td></tr>';

		// Count characters: SMS limit is 160 characters.
		$extra['extra_header_left'] .= '<script>
			$( "#text" ).keyup(function() {
  				$( "#text-characters-count" ).text( $(this).val().length );
			});
			$( "#text-characters-count" ).text( $( "#text" ).val().length );
		</script>';

		$extra['extra_header_left'] .= '</table>';
	}

	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['functions'] = [
		'CHECKBOX' => 'SMSMakeChooseCheckbox',
		'MOBILE_NUMBER' => 'SMSMakeMobileNumber',
	];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox(
		// @since RosarioSIS 11.5 Prevent submitting form if no checkboxes are checked
		( version_compare( ROSARIO_VERSION, '11.5', '<' ) ? 'Y' : 'Y_required' ),
		'',
		'st_arr'
	) ];
	$extra['columns_after'] = [ 'MOBILE_NUMBER' => dgettext( 'SMS', 'Mobile Number' ) ];
	$extra['new'] = true;
	// Pass recipients_to to next screen.
	$extra['action'] = '&recipients_to=' . $recipients_to;

	if ( $recipients_to === 'student' )
	{
		$student_mobile_field = Config( 'SMS_STUDENT_MOBILE_FIELD' ) ?
			"s." . Config( 'SMS_STUDENT_MOBILE_FIELD' ) :
			"''";

		// Mobile number.
		$extra['SELECT'] .= "," . $student_mobile_field . " AS MOBILE_NUMBER";

		$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX";

		Search( 'student_id', $extra );
	}
	else
	{
		$user_mobile_field = Config( 'SMS_USER_MOBILE_FIELD' ) ?
			"s." . Config( 'SMS_USER_MOBILE_FIELD' ) :
			"''";

		// Mobile number.
		$extra['SELECT'] .= "," . $user_mobile_field . " AS MOBILE_NUMBER";

		$extra['SELECT'] .= ",s.STAFF_ID AS CHECKBOX";

		Search( 'staff_id', $extra );
	}

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' .
			SubmitButton( $submit_button_text ) . '</div></form>';
	}
}

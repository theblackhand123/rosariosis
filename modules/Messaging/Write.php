<?php
/**
 * Write (new Message or a Reply)
 *
 * @package Messaging module
 */

// Include Common functions.
require_once 'modules/Messaging/includes/Common.fnc.php';

// Include Write functions.
require_once 'modules/Messaging/includes/Write.fnc.php';

$reply = '';

if ( $_REQUEST['modfunc'] === 'send' )
{
	$recipient_ids = isset( $_REQUEST['recipients_ids'] ) ? $_REQUEST['recipients_ids'] : '';

	if ( isset( $_REQUEST['admin_recipients_ids'] )
		|| isset( $_REQUEST['teacher_recipients_ids'] ) )
	{
		$recipient_ids = array_merge(
			( empty( $_REQUEST['admin_recipients_ids'] ) ? [] : $_REQUEST['admin_recipients_ids'] ),
			( empty( $_REQUEST['teacher_recipients_ids'] ) ? [] : $_REQUEST['teacher_recipients_ids'] )
		);

		foreach ( $recipient_ids as $i => $recipient_id )
		{
			if ( ! $recipient_id )
			{
				// Fix regression since RosarioSIS 10.8.4, remove hidden empty input.
				unset( $recipient_ids[ $i ] );
			}
		}
	}

	// Send message.
	$sent = SendMessage( [
		'reply_to_id' => isset( $_REQUEST['reply_to_id'] ) ? $_REQUEST['reply_to_id'] : '',
		'recipients_key' => isset( $_REQUEST['recipients_key'] ) ? $_REQUEST['recipients_key'] : '',
		'recipients_ids' => $recipient_ids,
		'message' => isset( $_POST['message'] ) ? $_POST['message'] : '', // Bypass strip_tags.
		'subject' => isset( $_REQUEST['subject'] ) ? $_REQUEST['subject'] : '',
	] );

	if ( $sent )
	{
		$note[] = button( 'check', '', '', 'bigger' ) . '&nbsp;' . dgettext( 'Messaging', 'Message sent.' );
	}
	elseif ( ! $error )
	{
		$error[] = dgettext( 'Messaging', 'The message could not be sent.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}
elseif ( isset( $_REQUEST['reply_to_id'] )
		&& $_REQUEST['reply_to_id'] )
{
	$reply = GetReplySubjectMessage( $_REQUEST['reply_to_id'] );
}


// Allow Edit if non admin.
if ( User( 'PROFILE' ) !== 'admin' )
{
	$_ROSARIO['allow_edit'] = true;
}


$title = ProgramTitle();

if ( SchoolInfo( 'SCHOOLS_NB' ) > 1
	&& User( 'PROFILE' ) !== 'student' )
{
	// If more than 1 school, mention current school.
	$title .= ' <small>' . SchoolInfo( 'TITLE' ) . '</small>';
}

DrawHeader( $title );

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

$recipients_key = '';

if ( ! $reply )
{
	// Get allowed recipients keys.
	$recipients_keys = GetAllowedRecipientsKeys( User( 'PROFILE' ) );

	if ( ! $recipients_keys )
	{
		// If no allowed recipients keys, display fatal error.
		$error[] = dgettext( 'Messaging', 'You are not allowed to send messages.' );

		echo ErrorMessage( $error, 'fatal' );
	}
	elseif ( count( $recipients_keys ) > 1
		&& ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) ) // Limit to Teachers & Admins.
	{
		// Search screen.
		// Set Recipients key.
		if ( isset( $_REQUEST['recipients_key'] )
			&& in_array( $_REQUEST['recipients_key'], $recipients_keys ) )
		{
			$recipients_key = $_REQUEST['recipients_key'];
		}
		else
		{
			// Defaults to student_id for Teachers, to staff_id for Admins.
			$recipients_key = User( 'PROFILE' ) === 'teacher' ? 'student_id' : 'staff_id';
		}

		$recipients_header = GetRecipientsHeader( $recipients_key );

		DrawHeader( $recipients_header );

		if ( empty( $_REQUEST['search_modfunc'] )
			&& empty( $_REQUEST['teacher_staff'] ) )
		{
			$extra['new'] = true;

			// Pass recipients_key to next screen.
			$extra['action'] = '&recipients_key=' . $recipients_key;

			if ( User( 'PROFILE' ) === 'teacher'
				&& $recipients_key === 'staff_id' )
			{
				// Find a Parent.
				$extra['search_title'] = dgettext( 'Messaging', 'Find a Parent' );

				$extra['profile'] = 'parent';
			}

			// Only for admins and teachers.
			Search( $recipients_key, $extra );

			// Unset Recipients key so Write form is not displayed.
			$recipients_key = '';
		}
	}
	else
	{
		if ( count( $recipients_keys ) === 1 )
		{
			$recipients_key = $recipients_keys[0];
		}
		elseif ( isset( $_REQUEST['recipients_key'] )
			&& in_array( $_REQUEST['recipients_key'], $recipients_keys ) )
		{
			$recipients_key = $_REQUEST['recipients_key'];
		}
	}
}

// Is reply or Recipients key set.
if ( $reply
	|| $recipients_key )
{
	// Write form.
	echo '<form method="POST" action="' . PreparePHP_SELF( [], [], [ 'modfunc' => 'send' ] ) . '">';

	// TODO: test when changing SYEAR / SCHOOL
	// Recipients key hidden field.
	echo '<input type="hidden" name="recipients_key" value="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $recipients_key ) : htmlspecialchars( $recipients_key, ENT_QUOTES ) ) . '" />';

	$subject = $original_message = $reply_to = '';

	// If is reply, get Subject as "Re: Original subject".
	if ( $reply )
	{
		echo '<input type="hidden" name="reply_to_id" value="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $_REQUEST['reply_to_id'] ) : htmlspecialchars( $_REQUEST['reply_to_id'], ENT_QUOTES ) ) . '" />';

		$subject = $reply['subject'];

		$original_message = $reply['message'];

		$reply_to = dgettext( 'Messaging', 'To' ) . ': ' . $reply['to'];
	}

	// Send button.
	DrawHeader( $reply_to, SubmitButton( dgettext( 'Messaging', 'Send' ) ) );

	// Subject field.
	DrawHeader( TextInput(
		$subject,
		'subject',
		dgettext( 'Messaging', 'Subject' ),
		'required maxlength="100" size="50"',
		false
	) );

	// Original message if Reply.
	if ( $original_message )
	{
		DrawHeader( '<div class="markdown-to-html" style="padding: 10px;">' . $original_message . '</div>' );
	}

	// Message field.
	DrawHeader( TinyMCEInput(
		'',
		'message',
		_( 'Message' ),
		'required'
	) );


	// Search results.
	if ( ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		&& isset( $_REQUEST['search_modfunc'] )
		&& ! $reply
		&& empty( $_REQUEST['teacher_staff'] ) )
	{
		// Choose recipients checkboxes.
		$extra['SELECT'] = ",'' AS CHECKBOX";
		$extra['functions'] = [ 'CHECKBOX' => '_makeWriteChooseCheckbox' ];
		$extra['columns_before'] = [
			'CHECKBOX' => MakeChooseCheckbox( 'Y', 'CHECKBOX', 'recipients_ids' )
		];

		// Force search.
		$extra['new'] = true;

		// No ListOutput search form.
		$extra['options']['search'] = false;

		// No link for name.
		$extra['link']['FULL_NAME'] = false;

		if ( $recipients_key === 'staff_id' )
		{
			// Do not send message to self.
			$extra['WHERE'] = " AND s.STAFF_ID<>'" . User( 'STAFF_ID' ) . "' ";
		}

		// Deactivate Search All Schools.
		$_REQUEST['_search_all_schools'] = false;

		// Only for admins and teachers.
		// TODO: try to allow Admin search for Teachers.
		Search( $recipients_key, $extra );
	}
	elseif ( ! $reply )
	{
		$value = $allow_na = $div = false;

		// Multiple select input.
		$extra = 'multiple';

		$add_label = '';

		// TODO add current school / student for Teachers / Parents.
		/*if ( User( 'PROFILE' ) === 'teacher'
			&& SchoolInfo( 'SCHOOLS_NB' ) > 1 )
		{
			// If teaches in more than one school.
			$add_label = ' (' . SchoolInfo( 'TITLE' ) . ')';
		}
		elseif ( User( 'PROFILE' ) === 'parent' )
		{
			$student_name_RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS NAME
					FROM students s,student_enrollment se
					WHERE se.STUDENT_ID='" . UserStudentID() . "'
					AND s.STUDENT_ID=se.STUDENT_ID
					AND se.SYEAR='" . UserSyear() . "'" );

			// If more than one student.
			$add_label = ' (' . ')';
		}*/

		// Display Teachers select.
		$teachers_options = GetRecipientsInfo( User( 'PROFILE' ), 'teacher' );


		$teachers_label = _( 'Teachers' ) . $add_label;


		// Display Admins select.
		$admins_options = GetRecipientsInfo( User( 'PROFILE' ), 'admin' );

		$admins_label = _( 'Administrators' ) . $add_label;

		// @since RosarioSIS 10.7 Use Select2 input instead of Chosen, fix overflow issue.
		$select_input_function = function_exists( 'Select2Input' ) ? 'Select2Input' : 'ChosenSelectInput';

		$teachers_select = $select_input_function(
			$value,
			'teacher_recipients_ids[]',
			$teachers_label,
			$teachers_options,
			$allow_na,
			$extra,
			$div
		);

		$admins_select = $select_input_function(
			$value,
			'admin_recipients_ids[]',
			$admins_label,
			$admins_options,
			$allow_na,
			$extra,
			$div
		);

		DrawHeader(	$teachers_select, $admins_select );
	}

	// Send button.
	echo '<br /><div class="center">' .
		SubmitButton( dgettext( 'Messaging', 'Send' ) ) .
		'</div></form>';

	// HTML add space for ChosenSelect to be entirely visible.
	echo '<br /><br /><br /><br /><br /><br /><br />';
}

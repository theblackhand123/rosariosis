<?php
/**
 * Write functions
 * Send Message, Get Recipients...
 *
 * @package Messaging module
 */

/**
 * Send Message
 *
 * @example $sent = SendMessage( array( 'recipients_key' => $_REQUEST['recipients_key'], 'recipients_ids' => $_REQUEST['recipients_ids'], 'message' => $_REQUEST['message'], 'subject' => $_REQUEST['subject'] ) );
 *
 * @see Write program
 *
 * @param array $msg Associative array (keys = reply_to_id|recipients_key|recipients_ids|message|subject).
 */
function SendMessage( $msg )
{
	global $error;

	// Check required parameters.
	if ( ( ( ! isset( $msg['reply_to_id'] )
				|| (string) $msg['reply_to_id'] === '' )
			&& ( ! isset( $msg['recipients_key'] )
				|| (string) $msg['recipients_key'] === ''
				|| ! isset( $msg['recipients_ids'] )
				|| ! $msg['recipients_ids'] ) )
		|| ! isset( $msg['message'] )
		|| ! isset( $msg['subject'] ) )
	{
		$error[] = dgettext( 'Messaging', 'The message could not be sent. Form elements are missing.' );

		return false;
	}

	// Check required fields.
	if ( (string) $msg['message'] === ''
		|| (string) $msg['subject'] === '' )
	{
		$error[] = _( 'Please fill in the required fields' );

		return false;
	}

	if ( (string) (int) $msg['reply_to_id'] === $msg['reply_to_id'] )
	{
		$recipients = _getMessageRecipients( 'reply', (string) $msg['reply_to_id'] );
	}
	else
	{
		$recipients = _getMessageRecipients( (string) $msg['recipients_key'], (array) $msg['recipients_ids'] );
	}

	// Check & Get Recipients.
	if ( ! $recipients )
	{
		$error[] = dgettext( 'Messaging', 'You are not allowed to send a message to those recipients.' );

		return false;
	}

	// Serialize From.
	$from = DBEscapeString( serialize( GetCurrentMessagingUser() ) );

	// Sanitize Message.
	// Is MarkDown.
	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	$sanitized_msg = SanitizeHTML( $msg['message'] );

	// Serialize Data.
	// TODO move from serialize() to json_encode()
	$data = serialize( [ 'message' => $sanitized_msg ] );

	$subject = $msg['subject'];

	// Limit Subject to 100 chars.
	if ( mb_strlen( $subject ) > 100 )
	{
		$subject = mb_substr( $subject, 0, 100 );
	}

	// Save Message.
	DBQuery( "INSERT INTO messages (syear,school_id," . DBEscapeIdentifier( 'from' ) . ",recipients,subject," . DBEscapeIdentifier( 'datetime' ) . ",data)
	VALUES(
		'" . UserSyear() . "',
		'" . UserSchool() . "',
		'" . $from . "',
		'" . DBEscapeString( $recipients ) . "',
		'" . $subject . "',
		CURRENT_TIMESTAMP,
		'" . DBEscapeString( $data ) . "'
	)" );

	if ( function_exists( 'DBLastInsertID' ) )
	{
		$msg_id = DBLastInsertID();
	}
	else
	{
		// @deprecated since RosarioSIS 9.2.1.
		$msg_id = DBGetOne( "SELECT LASTVAL();" );
	}

	if ( (string) (int) $msg['reply_to_id'] === $msg['reply_to_id'] )
	{
		$recipient = _getMessageFrom( $msg['reply_to_id'] );

		if ( ! $recipient )
		{
			// Original sender not found!
			// Unprobable here: already checked in _getMessageRecipients().
			return false;
		}

		return _saveMessageSenderRecipients( $msg_id, $recipient['key'], $recipient['user_id'] );
	}

	// Save Recipients in cross tables.
	if ( $msg['recipients_key'] === 'student_id'
		|| $msg['recipients_key'] === 'staff_id' )
	{
		return _saveMessageSenderRecipients( $msg_id, $msg['recipients_key'], $msg['recipients_ids'] );
	}
	else
	{
		// Wrong recipients key!
		// Unprobable here: already checked in _getMessageRecipients().
		return false;
	}
}


function _saveMessageSenderRecipients( $msg_id, $key, $recipients_ids )
{
	if ( ! in_array( $key, [ 'student_id', 'staff_id' ] )
		|| ! $recipients_ids
		|| ! $msg_id )
	{
		return false;
	}

	foreach ( (array) $recipients_ids as $recipient_id )
	{
		DBQuery( "INSERT INTO messagexuser VALUES(
			'" . $recipient_id . "',
			'" . $key . "',
			'" . $msg_id . "',
			'unread'
		)" );
	}

	$sender = GetCurrentMessagingUser();

	// Save Sender.
	DBQuery( "INSERT INTO messagexuser VALUES(
		'" . $sender['user_id'] . "',
		'" . $sender['key'] . "',
		'" . $msg_id . "',
		'sent'
	)" );

	return true;
}


function _getMessageRecipients( $recipients_key, $recipients_ids )
{
	if ( $recipients_key === 'reply' )
	{
		$reply_to_id = $recipients_ids;

		if ( ! $reply_to_id )
		{
			return '';
		}

		// Get User.
		$user = GetCurrentMessagingUser();

		// Reply: just check the reply to ID is allowed (the message has been sent to him first).
		$allowed_reply = DBGetOne( "SELECT 1
			FROM messagexuser
			WHERE MESSAGE_ID='" . (int) $reply_to_id . "'
			AND USER_ID='" . (int) $user['user_id'] . "'
			AND " . DBEscapeIdentifier( 'KEY' ) . "='" . $user['key'] . "'
			AND STATUS<>'sent'" );

		if ( ! $allowed_reply )
		{
			return '';
		}

		// Get Recipient == Original message From.
		$recipient = _getMessageFrom( $reply_to_id );

		return $recipient['name'];
	}

	$recipients_keys = GetAllowedRecipientsKeys( User( 'PROFILE' ) );

	// Check parameters.
	if ( ! isset( $recipients_key )
		|| ! in_array( $recipients_key, $recipients_keys )
		|| ! $recipients_ids )
	{
		return '';
	}

	$allowed_recipient = false;

	// Check Recipients.
	if ( $recipients_ids === '0' )
	{
		$recipients_all_labels = [
			'student_id' => _( 'Students' ),
			'staff_id' => _( 'Staff' ),
			// 'course_period_id' => '',
			// 'grade_id' => '',
			// 'profile_id' => '',
		];

		// All.
		return sprintf( 'All %s', $recipients_all_labels[ $recipients_key ] );
	}

	// Recipients.
	foreach ( (array) $recipients_ids as $recipient_id )
	{
		$allowed_recipient = _checkMessageRecipient( $recipients_key, $recipient_id );
	}

	if ( ! $allowed_recipient )
	{
		return '';
	}

	if ( $recipients_key === 'student_id' )
	{
		$names_RET = DBGet( "SELECT " . _SQLCommaSeparatedResult( DisplayNameSQL(), ', ' ) . " AS NAMES
			FROM students
			WHERE STUDENT_ID IN('" . implode( "','", $recipients_ids ) . "')" );
	}
	elseif ( $recipients_key === 'staff_id' )
	{
		$names_RET = DBGet( "SELECT " . _SQLCommaSeparatedResult( DisplayNameSQL(), ', ' ) . " AS NAMES
			FROM staff
			WHERE STAFF_ID IN('" . implode( "','", $recipients_ids ) . "')" );
	}

	if ( isset( $names_RET[1]['NAMES'] ) )
	{
		// Return Student Student, Andrea Mazariegos.
		return $names_RET[1]['NAMES'];
	}

	return '';
}


function _checkMessageRecipient( $recipient_key, $recipient_id )
{
	$recipients_keys = GetAllowedRecipientsKeys( User( 'PROFILE' ) );

	// Check parameters.
	if ( ! isset( $recipient_key )
		|| ! in_array( $recipient_key, $recipients_keys )
		|| ! isset( $recipient_id )
		|| (string) (int) $recipient_id !== $recipient_id )
	{
		return false;
	}

	// Check Recipient ID is allowed.
	// Check not self.
	if ( $recipient_key === 'staff_id'
		&& $recipient_id === User( 'STAFF_ID' ) )
	{
		return false;
	}

	switch ( $recipient_key )
	{
		case 'student_id':

			// May exit on HackingAttempt() if you do not behave!
			SetUserStudentID( $recipient_id );

		break;

		case 'staff_id':

			if ( User( 'PROFILE' ) === 'admin' )
			{
				// May exit on HackingAttempt() if you do not behave!
				SetUserStaffID( $recipient_id );
			}
			else
			{
				$allowed = [];

				if ( User( 'PROFILE' ) === 'student' )
				{
					// Student: allow its Teachers + Admin staff.
					$allowed = _getStudentAllowedTeachersRecipients( UserStudentID() );
					$allowed = array_merge( $allowed, _getStudentAllowedAdminsRecipients() );
				}
				elseif ( User( 'PROFILE' ) === 'parent' )
				{
					// Parent: allow students' Teachers + Admin staff.
					$allowed = _getParentAllowedTeachersRecipients();
					$allowed = array_merge( $allowed, _getParentAllowedAdminsRecipients() );
				}
				elseif ( User( 'PROFILE' ) === 'teacher' )
				{
					// Teachers: Parents of related students + Admin staff + other Teachers.
					$allowed = _getTeacherAllowedParentsRecipients(); // see SetUserStaffID()!
					$allowed = array_merge( $allowed, _getTeacherAllowedAdminsRecipients() );
					$allowed = array_merge( $allowed, _getTeacherAllowedTeachersRecipients() );
				}

				if ( ! in_array( $recipient_id, $allowed ) )
				{
					return false;
				}
			}

		break;

		case 'course_period_id':

		break;

		case 'profile_id':

		break;

		case 'grade_id':

		break;
	}

	return true;
}


function GetAllowedRecipientsKeys( $profile )
{
	if ( $profile === 'student' )
	{
		return [ 'staff_id' ];
	}
	elseif ( $profile === 'parent' )
	{
		return [ 'staff_id' ];
	}
	elseif ( $profile === 'teacher' )
	{
		return [ 'student_id', 'staff_id' ];
	}
	elseif ( $profile === 'admin' )
	{
		return [ 'student_id', 'staff_id' ];
	}

	//return array( 'student_id', 'staff_id', 'course_period_id', 'grade_id', 'profile_id' );

	return [];
}


function _getAllowedAdminsRecipients()
{
	static $allowed_ids = [];

	if ( ! $allowed_ids )
	{
		$allowed_ids_RET = DBGet( "SELECT " . _SQLCommaSeparatedResult( 'STAFF_ID' ) . " as ALLOWED_IDS
			FROM staff
			WHERE PROFILE='admin'
			AND SYEAR='" . UserSyear() . "'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" );

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
		{
			// For example: 70,10,1.
			$allowed_ids = explode( ',', $allowed_ids_RET[1]['ALLOWED_IDS'] );
		}
	}

	return (array) $allowed_ids;
}


function _getAllowedTeachersRecipients()
{
	static $allowed_ids = [];

	if ( ! $allowed_ids )
	{
		$sql_exclude_self = '';

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			// Exclude self.
			$sql_exclude_self = " AND STAFF_ID<>'" . User( 'STAFF_ID' ) . "'";
		}

		$allowed_ids_RET = DBGet( "SELECT " . _SQLCommaSeparatedResult( 'STAFF_ID' ) . " as ALLOWED_IDS
			FROM staff
			WHERE PROFILE='teacher'
			AND SYEAR='" . UserSyear() . "'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" .
			$sql_exclude_self );

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
		{
			// For example: 70,10,1.
			$allowed_ids = explode( ',', $allowed_ids_RET[1]['ALLOWED_IDS'] );
		}
	}

	return (array) $allowed_ids;
}


function _getStudentAllowedAdminsRecipients()
{
	return _getAllowedAdminsRecipients();
}


function _getParentAllowedAdminsRecipients()
{
	return _getAllowedAdminsRecipients();
}


function _getTeacherAllowedAdminsRecipients()
{
	return _getAllowedAdminsRecipients();
}


function _getTeacherAllowedTeachersRecipients()
{
	return _getAllowedTeachersRecipients();
}


function _getStudentAllowedTeachersRecipients( $student_id )
{
	static $allowed_ids = [];

	if ( ! $student_id )
	{
		return [];
	}

	if ( ! isset( $allowed_ids[ $student_id ] ) )
	{
		$allowed_ids_RET = DBGet( "SELECT " . _SQLCommaSeparatedResult( 'DISTINCT(cp.TEACHER_ID)' ) . " as ALLOWED_IDS
			FROM schedule sch, course_periods cp
			WHERE sch.SYEAR='" . UserSyear() . "'
			AND sch.SCHOOL_ID='" . UserSchool() . "'
			AND sch.STUDENT_ID='" . (int) $student_id . "'
			AND sch.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
			AND cp.SYEAR=sch.SYEAR
			AND cp.SCHOOL_ID=sch.SCHOOL_ID" );

		$allowed_ids[ $student_id ] = [];

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
		{
			// For example: 70,10,1.
			$allowed_ids[ $student_id ] = explode( ',', $allowed_ids_RET[1]['ALLOWED_IDS'] );
		}
	}

	return (array) $allowed_ids[ $student_id ];
}


function _getParentAllowedTeachersRecipients()
{
	static $allowed_ids = [];

	if ( ! User( 'STAFF_ID' ) )
	{
		return $allowed_ids;
	}

	if ( ! $allowed_ids )
	{
		// Get Parent Students for current school.
		$students_RET = DBGet( "SELECT sju.STUDENT_ID
			FROM students s,students_join_users sju,student_enrollment se
			WHERE s.STUDENT_ID=sju.STUDENT_ID
			AND sju.STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND se.SYEAR='" . UserSyear() . "'
			AND se.SCHOOL_ID ='" . UserSchool() . "'
			AND se.STUDENT_ID=sju.STUDENT_ID
			AND ('" . DBDate() . "'>=se.START_DATE
				AND ('" . DBDate() . "'<=se.END_DATE
					OR se.END_DATE IS NULL ) )" );

		// Get each student's Teachers.
		foreach ( (array) $students_RET as $student )
		{
			$allowed_ids = array_merge( $allowed_ids, _getStudentAllowedTeachersRecipients( $student['STUDENT_ID'] ) );
		}
	}

	return (array) $allowed_ids;
}


function _getTeacherAllowedParentsRecipients()
{
	static $allowed_ids = [];

	if ( ! User( 'STAFF_ID' ) )
	{
		return $allowed_ids;
	}

	if ( ! $allowed_ids )
	{
		$allowed_ids_RET = DBGet( "SELECT " . _SQLCommaSeparatedResult( 'sju.STAFF_ID' ) . " as ALLOWED_IDS
			FROM students_join_users sju,student_enrollment sem,schedule sch
			WHERE sem.STUDENT_ID=sju.STUDENT_ID
			AND sem.SYEAR='" . UserSyear() . "'
			AND sem.SCHOOL_ID='" . UserSchool() . "'
			AND sch.STUDENT_ID=sem.STUDENT_ID
			AND sch.SYEAR=sem.SYEAR
			AND sch.SCHOOL_ID=sem.SCHOOL_ID
			AND sch.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

		if ( isset( $allowed_ids_RET[1]['ALLOWED_IDS'] ) )
		{
			// For example: 70,10,1.
			$allowed_ids = explode( ',', $allowed_ids_RET[1]['ALLOWED_IDS'] );
		}
	}

	return (array) $allowed_ids;
}


function _getMessageFrom( $msg_id )
{
	static $from = [];

	if ( ! $msg_id )
	{
		return [];
	}

	if ( ! isset( $from[ $msg_id ] ) )
	{
		$from_RET = DBGet( "SELECT m.FROM
			FROM messages m
			WHERE m.MESSAGE_ID='" . (int) $msg_id . "'" );

		if ( isset( $from_RET[1]['FROM'] ) )
		{
			$from[ $msg_id ] = unserialize( $from_RET[1]['FROM'] );
		}
		else
		{
			$from[ $msg_id ] = [];
		}
	}

	return $from[ $msg_id ];
}


function GetReplySubjectMessage( $msg_id )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return [];
	}

	// Get User.
	$user = GetCurrentMessagingUser();

	// Get message Subject.
	$subject_message_sql = "SELECT m.SUBJECT,m.DATA,m.FROM
		FROM messages m, messagexuser mxu
		WHERE m.MESSAGE_ID='" . (int) $msg_id . "'
		AND m.SYEAR='" . UserSyear() . "'
		AND m.SCHOOL_ID='" . UserSchool() . "'
		AND mxu.MESSAGE_ID=m.MESSAGE_ID
		AND mxu.KEY='" . $user['key'] . "'
		AND mxu.USER_ID='" . (int) $user['user_id'] . "'
		AND mxu.STATUS<>'sent'";

	$subject_message_RET = DBGet( $subject_message_sql );

	if ( ! isset( $subject_message_RET[1]['SUBJECT'] ) )
	{
		return [];
	}

	$subject = $subject_message_RET[1]['SUBJECT'];

	// Add "Re:" once!
	if ( mb_strpos( $subject, sprintf( dgettext( 'Messaging', 'Re: %s' ), '' ) ) !== 0 )
	{
		$subject = sprintf( dgettext( 'Messaging', 'Re: %s' ), $subject );
	}

	$data = unserialize( $subject_message_RET[1]['DATA'] );

	$message = $data['message'];

	$from = unserialize( $subject_message_RET[1]['FROM'] );

	$to = $from['name'];

	return [ 'subject' => $subject, 'message' => $message, 'to' => $to ];
}


function GetRecipientsInfo( $user_profile, $recipients_profile = 'teacher' )
{
	if ( ! $user_profile
		|| ! $recipients_profile )
	{
		return null;
	}

	if ( $user_profile === 'teacher' )
	{
		if ( $recipients_profile === 'teacher' )
		{
			$allowed_ids = _getTeacherAllowedTeachersRecipients();
		}
		else
		{
			$allowed_ids = _getTeacherAllowedAdminsRecipients();
		}
	}
	elseif ( $user_profile === 'student' )
	{
		if ( $recipients_profile === 'teacher' )
		{
			$user = GetCurrentMessagingUser();

			$allowed_ids = _getStudentAllowedTeachersRecipients( $user['user_id'] );
		}
		else
		{
			$allowed_ids = _getStudentAllowedAdminsRecipients();
		}
	}
	elseif ( $user_profile === 'parent' )
	{
		if ( $recipients_profile === 'teacher' )
		{
			$allowed_ids = _getParentAllowedTeachersRecipients();
		}
		else
		{
			$allowed_ids = _getParentAllowedAdminsRecipients();
		}
	}

	if ( ! $allowed_ids )
	{
		return null;
	}

	// Get user name.
	// TODO get Teacher course for Parents & Students => "Name (Course)".
	$users_info_sql = "SELECT STAFF_ID," . DisplayNameSQL() . " AS NAME
		FROM staff s
		WHERE s.STAFF_ID IN ('" . implode( "','", $allowed_ids ) . "')
		ORDER BY NAME";

	$users_info_RET = DBGet( $users_info_sql );

	$users_options = [];

	foreach ( (array) $users_info_RET as $users_info )
	{
		// Add profile to name if profile != default teacher (2) or admin (1).
		$option = $users_info['NAME']; // . ( $users_info['PROFILE'] ? ' (' . $users_info['PROFILE'] . ')' : '' );

		$users_options[ $users_info['STAFF_ID'] ] = $option;
	}

	return $users_options;
}


function _makeWriteChooseCheckbox( $value, $title )
{
	global $THIS_RET;

	$user_id = isset( $THIS_RET['STAFF_ID'] ) ? $THIS_RET['STAFF_ID'] : $THIS_RET['STUDENT_ID'];

	return '<input type="checkbox" name="recipients_ids[]" value="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $user_id ) : htmlspecialchars( $user_id, ENT_QUOTES ) ) . '" checked />';
}


/**
 * Get Recipients header (tabs):
 * Depends on User profile (only Teachers and Admins)
 * Depends on current Recipient key (bold)
 *
 * @since 1.1
 *
 * @param string $recipients_key Current recipient key.
 *
 * @return string Header to be displayed using DrawHeader().
 */
function GetRecipientsHeader( $recipients_key )
{
	$search_staff_url = PreparePHP_SELF(
		$_REQUEST,
		[ 'search_modfunc', 'reply_to_id', 'teacher_staff' ],
		[ 'recipients_key' => 'staff_id' ]
	);

	$search_teacher_staff_url = PreparePHP_SELF(
		$_REQUEST,
		[ 'search_modfunc', 'reply_to_id' ],
		[ 'recipients_key' => 'staff_id', 'teacher_staff' => 'Y' ]
	);

	$search_student_url = PreparePHP_SELF(
		$_REQUEST,
		[ 'search_modfunc', 'reply_to_id', 'teacher_staff' ],
		[ 'recipients_key' => 'student_id' ]
	);

	// If more than one allowed recipients key, display Users | Students.
	// For Teachers, it will be Parents | Students | Staff.
	$header = User( 'PROFILE' ) === 'admin' ? _( 'Users' ) : _( 'Parents' );

	if ( $recipients_key === 'staff_id'
		&& ! isset( $_REQUEST['teacher_staff'] ) )
	{
		$header = '<b>' . $header . '</b>';
	}

	$header = '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( $search_staff_url ) :
		_myURLEncode( $search_staff_url ) ) . '">' . $header . '</a>';

	$header_students = _( 'Students' );

	if ( $recipients_key === 'student_id' )
	{
		$header_students = '<b>' . $header_students . '</b>';
	}

	$header .= ' | <a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( $search_student_url ) :
		_myURLEncode( $search_student_url ) ) . '">' . $header_students . '</a>';

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$header_teacher_staff = _( 'Staff' );

		if ( $recipients_key === 'staff_id'
			&& isset( $_REQUEST['teacher_staff'] ) )
		{
			$header_teacher_staff = '<b>' . $header_teacher_staff . '</b>';
		}

		$header .= ' | <a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $search_teacher_staff_url ) :
			_myURLEncode( $search_teacher_staff_url ) ) . '">' .
			$header_teacher_staff . '</a>';
	}

	return $header;
}

/**
 * SQL result as comma separated list
 *
 * @since 9.3 Add MySQL support
 * @link https://dev.mysql.com/doc/refman/5.7/en/aggregate-functions.html#function_group-concat
 *
 * @param string $column    SQL column.
 * @param string $separator List separator, default to comma.
 *
 * @return string MySQL or PostgreSQL function
 */
function _SQLCommaSeparatedResult( $column, $separator = ',' )
{
	global $DatabaseType;

	if ( $DatabaseType === 'mysql' )
	{
		return "GROUP_CONCAT(" . $column . " SEPARATOR '" . DBEscapeString( $separator ) . "')";
	}

	return "ARRAY_TO_STRING(ARRAY_AGG(" . $column . "), '" . DBEscapeString( $separator ) . "')";
}

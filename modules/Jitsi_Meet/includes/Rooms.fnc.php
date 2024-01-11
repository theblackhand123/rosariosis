<?php
/**
 * Rooms functions
 *
 * @package Jitsi Meet module
 */

/**
 * Add (Students, Users) Search() $extra
 *
 * @param array  $existing_user_ids Existing User/Student IDs.
 * @param string $type              User or student.
 *
 * @return array $extra for Search().
 */
function JitsiMeetAddSearchExtra( $existing_user_ids, $type = 'user' )
{
	$extra = [];
	$extra['new'] = true;
	$extra['Redirect'] = false;
	$extra['link'] = [ 'FULL_NAME' => false ];

	$extra['SELECT'] = $type === 'user' ? ",s.STAFF_ID AS CHECKBOX" : ",s.STUDENT_ID AS CHECKBOX";

	$extra['WHERE'] = '';

	if ( $type === 'user' )
	{
		$extra['WHERE'] .= " AND s.STAFF_ID<>'" . User( 'STAFF_ID' ) . "'";
	}

	if ( $existing_user_ids )
	{
		$extra['WHERE'] .= $type === 'user' ?
			" AND s.STAFF_ID NOT IN (" . implode( ',', $existing_user_ids ) . ")" :
			" AND s.STUDENT_ID NOT IN (" . implode( ',', $existing_user_ids ) . ")";
	}

	$extra['functions'] = [
		'FULL_NAME' => 'makePhotoTipMessage',
		'CHECKBOX' => 'MakeChooseCheckbox',
	];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', '', 'st_arr' ) ];

	$extra['action'] = '&modfunc=add&id=' . $_REQUEST['id'] . '&type=' . $type;

	return $extra;
}

/**
 * User (all admins and teachers) List Output for Teachers
 * Teachers cannot access admins and teachers through the Find a User screen, hence this function.
 *
 * @uses ListOutput()
 *
 * @param array $existing_user_ids Existing User IDs.
 */
function JitsiMeetUserListOutputForTeachers( $existing_user_ids )
{
	$sql_where = '';

	if ( $existing_user_ids )
	{
		$sql_where = " AND STAFF_ID NOT IN (" . implode( ',', $existing_user_ids ) . ")";
	}

	$functions = [
		'FULL_NAME' => 'makePhotoTipMessage',
		'CHECKBOX' => 'MakeChooseCheckbox',
		'PROFILE' => 'makeProfile',
	];

	$LO_columns = [
		'CHECKBOX' => MakeChooseCheckbox( '', '', 'st_arr' ),
		'FULL_NAME' => _( 'User' ),
		'PROFILE' => _( 'Profile' ),
		'STAFF_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
	];

	$users_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME,STAFF_ID AS CHECKBOX,
		PROFILE,PROFILE_ID
		FROM staff
		WHERE STAFF_ID<>'" . User( 'STAFF_ID' ) . "'
		AND SYEAR='" . UserSyear() . "'
		AND (PROFILE='teacher' OR PROFILE='admin')
		AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" .
		$sql_where .
		" ORDER BY FULL_NAME",
		$functions );

	ListOutput(
		$users_RET,
		$LO_columns,
		'User',
		'Users'
	);
}

/**
 * Room Form HTML
 *
 * @param array $RET Room.
 *
 * @return string Room Form HTML
 */
function JitsiMeetRoomsForm( $RET )
{
	$id = issetVal( $RET['ID'] );

	if ( ! $id )
	{
		return '';
	}

	$form = '<form action="';

	$form .= PreparePHP_SELF(
		[],
		[ 'id', 'table' ]
	);

	if ( $id
		&& $id !== 'new' )
	{
		$form .= '&id=' . $id;
	}

	$form .= '&table=jitsi_meet_rooms" method="POST">';

	$delete_button = '';

	if ( AllowEdit()
		&& $id
		&& $id !== 'new' )
	{
		$delete_url = PreparePHP_SELF(
			[],
			[ 'table' ],
			[
				'modfunc' => 'delete',
				'id' => $id,
			]
		);

		$onclick_link = 'ajaxLink(' . json_encode( $delete_url ) . ');';

		$delete_button = '<input type="button" value="' .
		( function_exists( 'AttrEscape' ) ? AttrEscape( _( 'Delete' ) ) : htmlspecialchars( _( 'Delete' ), ENT_QUOTES ) ) .
		'" onclick="' .
		( function_exists( 'AttrEscape' ) ? AttrEscape( $onclick_link ) : htmlspecialchars( $onclick_link, ENT_QUOTES ) ) .
		'" /> ';
	}

	ob_start();

	$title = empty( $RET['TITLE'] ) ? dgettext( 'Jitsi_Meet', 'New Room' ) : $RET['TITLE'];

	DrawHeader( $title, $delete_button . SubmitButton() );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col">';

	if ( $id )
	{
		// Title field.
		$header .= '<tr class="st"><td>' . TextInput(
			issetVal( $RET['TITLE'], uniqid() ),
			'tables[' . $id . '][TITLE]',
			_( 'Title' ),
			'required maxlength="50" pattern="[a-zA-Z0-9-]+"',
			( $id !== 'new' )
		) . '</td>';

		// Subject field.
		$header .= '<td>' . TextInput(
			issetVal( $RET['SUBJECT'] ),
			'tables[' . $id . '][SUBJECT]',
			_( 'Description' ),
			'size="30" maxlength="200"'
		) . '</td></tr>';

		$header .= '<tr class="st">';

		// Password field.
		$header .= '<td>' . PasswordInput(
			issetVal( $RET['PASSWORD'] ),
			'tables[' . $id . '][PASSWORD]',
			_( 'Password' )
		) . '</td>';

		$tooltip = ' <div class="tooltip"><i>' .
			dgettext( 'Jitsi_Meet', 'Every participant enters the room having enabled only their microphone. Camera is off.' ) . '</i></div>';

		// Start Audio Only field.
		$header .= '<td>' . CheckboxInput(
			issetVal( $RET['START_AUDIO_ONLY'] ),
			'tables[' . $id . '][START_AUDIO_ONLY]',
			dgettext( 'Jitsi_Meet', 'Start Audio Only' ) . $tooltip,
			'',
			$id === 'new'
		) . '</td></tr>';
	}

	$header .= '</table>';

	ob_start();

	DrawHeader( $header );

	$form .= ob_get_clean();

	$form .= '</form>';

	return $form;
}

/**
 * Output List of Users (Students) added to Room
 *
 * @uses ListOutput()
 *
 * @param array  $RET  Room.
 * @param string $type user or student, or user_teacher_admin.
 */
function JitsiMeetRoomsListUsers( $RET, $type = 'user' )
{
	$LO_columns = [
		'FULL_NAME' => _( 'Name' ),
	];

	$LO_link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=add&id=' . $_REQUEST['id'] . '&type=' . $type;

	$LO_link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=remove&&id=' . $_REQUEST['id'] . '&type=' . $type;

	$extra = [
		'functions' => [
			'FULL_NAME' => 'makePhotoTipMessage',
			'INVITE_LINK' => '_jitsiMeetMakeInviteLink',
			'PROFILE' => 'makeProfile',
		],
	];

	$LO_link['add']['title'] = dgettext( 'Jitsi_Meet', 'Add Users' );

	if ( $type === 'user'
		&& User( 'PROFILE' ) === 'teacher' )
	{
		$LO_link['add']['title'] = dgettext( 'Jitsi_Meet', 'Add Parents' );
	}

	$LO_link['remove']['variables'] = [ 'user_id' => 'STAFF_ID' ];

	$extra['WHERE'] = " AND STAFF_ID IS NULL";

	$staff_ids = trim( (string) $RET['USERS'], ',' );

	if ( $staff_ids )
	{
		$extra['WHERE'] = " AND STAFF_ID IN(" . $staff_ids . ")";
	}

	if ( $type === 'user' )
	{
		$extra['SELECT'] = ",s.EMAIL AS INVITE_LINK";

		$LO_columns['PROFILE'] = _( 'Profile' );

		$users_RET = GetStaffList( $extra );
	}
	elseif ( $type === 'user_teacher_admin' )
	{
		$LO_columns['PROFILE'] = _( 'Profile' );

		$users_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME,EMAIL AS INVITE_LINK,
			PROFILE,PROFILE_ID
			FROM staff
			WHERE STAFF_ID<>'" . User( 'STAFF_ID' ) . "'
			AND SYEAR='" . UserSyear() . "'
			AND (PROFILE='teacher' OR PROFILE='admin')
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)" .
			$extra['WHERE'] .
			" ORDER BY FULL_NAME",
			$extra['functions'] );
	}
	else
	{
		$LO_columns['GRADE_ID'] = _( 'Grade Level' );

		$LO_link['add']['title'] = dgettext( 'Jitsi_Meet', 'Add Students' );

		$LO_link['remove']['variables'] = [ 'user_id' => 'STUDENT_ID' ];

		$student_email_field = "''";

		if ( Config( 'STUDENTS_EMAIL_FIELD' ) )
		{
			$student_email_field = Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
				's.USERNAME' :
				's.CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );
		}

		$extra['SELECT'] = "," . $student_email_field . " AS INVITE_LINK";

		$extra['WHERE'] = " AND s.STUDENT_ID IS NULL";

		$student_ids = trim( (string) $RET['STUDENTS'], ',' );

		if ( $student_ids )
		{
			$extra['WHERE'] = " AND s.STUDENT_ID IN(" . $student_ids . ")";
		}

		$users_RET = GetStuList( $extra );
	}

	$LO_columns['INVITE_LINK'] = _( 'Email' );

	$LO_options = [ 'save' => false, 'search' => false ];

	if ( $type === 'user'
		&& User( 'PROFILE' ) === 'teacher' )
	{
		ListOutput(
			$users_RET,
			$LO_columns,
			'Parent',
			'Parents',
			$LO_link,
			[],
			$LO_options
		);
	}
	elseif ( $type === 'user'
		|| $type === 'user_teacher_admin' )
	{
		ListOutput(
			$users_RET,
			$LO_columns,
			'User',
			'Users',
			$LO_link,
			[],
			$LO_options
		);
	}
	else
	{
		ListOutput(
			$users_RET,
			$LO_columns,
			'Student',
			'Students',
			$LO_link,
			[],
			$LO_options
		);
	}
}

/**
 * Make Send Invitation link (AJAX)
 *
 * @param  string $value  User / student email address if any.
 * @param  string $column 'INVITE_LINK'.
 *
 * @return string         x button if no email or Send Invitation AJAX link.
 */
function _jitsiMeetMakeInviteLink( $value, $column = '' )
{
	global $THIS_RET;

	static $i = 0;

	if ( ! $value
		|| ! filter_var( $value, FILTER_VALIDATE_EMAIL ) )
	{
		return button( 'x' );
	}

	$ajax_link = 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&modfunc=send_invite&id=' . $_REQUEST['id'] . '&email=' . $value;

	$id = 'send-invite-' . ++$i;

	// AJAX link: target is parent <div>.
	return '<div id="' . $id . '"><a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $ajax_link ) :
			_myURLEncode( $ajax_link ) ) . '" target="' . $id . '">' .
		dgettext( 'Jitsi_Meet', 'Send Invitation' ) . '</a></div>';
}

/**
 * Send Invitation by email to join meeeting in Room.
 *
 * @uses SendEmail()
 *
 * @param string $to          To email address.
 * @param string $room        Room Title.
 * @param string $description Room description.
 * @param string $password    Room password.
* @param string $url_link     URL link to Meeting.
 *
 * @return bool Email sent or not.
 */
function JitsiMeetSendInvitation( $to, $room, $description, $password, $url_link )
{
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	$subject = sprintf(
		dgettext( 'Jitsi_Meet', 'New meeting request in room: %s' ),
		$room
	);

	$message = sprintf(
		dgettext( 'Jitsi_Meet', "%s has invited you to join a meeting in room &quot;%s&quot;:\n%s\n<a href=\"%s\">Enter the meeting</a>." ),
		User( 'NAME' ),
		$room,
		$description,
		( function_exists( 'URLEscape' ) ? URLEscape( $url_link ) : _myURLEncode( $url_link ) )
	);

	if ( $password )
	{
		$message .= "\n" . _( 'Password' ) . ': ' . $password;
	}

	$reply_to = null;

	if ( filter_var( User( 'EMAIL' ), FILTER_VALIDATE_EMAIL ) )
	{
		$reply_to = User( 'EMAIL' );
	}

	return SendEmail( $to, $subject, $message, $reply_to );
}

/**
 * Rooms Menu Output.
 *
 * @uses ListOutput()
 *
 * @param array $RET Rooms.
 * @param int   $id  Room ID.
 */
function JitsiMeetRoomsMenuOutput( $RET, $id )
{
	if ( $RET
		&& $id
		&& $id !== 'new' )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['ID'] == $id )
			{
				$RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	$LO_columns = [
		'TITLE' => dgettext( 'Jitsi_Meet', 'Room' ),
	];

	$LO_link = [];

	$LO_link['TITLE']['link'] = PreparePHP_SELF(
		[],
		[ 'id', 'table' ]
	);

	$LO_link['TITLE']['variables'] = [ 'id' => 'ID' ];

	$LO_link['add']['link'] = PreparePHP_SELF(
		[],
		[ 'id', 'table' ]
	) . '&id=new';

	ListOutput(
		$RET,
		$LO_columns,
		dgettext( 'Jitsi_Meet', 'Room' ),
		dgettext( 'Jitsi_Meet', 'Rooms' ),
		$LO_link,
		[],
		$LO_options
	);
}

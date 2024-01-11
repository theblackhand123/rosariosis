<?php
/**
 * Functions
 *
 * @package Calndar Schedule View plugin
 */

// Translate plugin name.
dgettext( 'iCalendar', 'iCalendar' );

if ( ! empty( $_REQUEST['icalendar'] ) )
{
	// Use composer autoloader.
	require_once 'plugins/iCalendar/vendor/autoload.php';
	require_once 'plugins/iCalendar/includes/iCalendar.fnc.php';

	if ( iCalendarDo() )
	{
		exit;
	}
}

// Register plugin functions to be hooked.
add_action( 'School_Setup/Calendar.php|header', 'iCalendarHeader' );

function iCalendarHeader()
{
	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// No header on PDF to gain space.
		return '';
	}

	$icalendar_key = Config( 'ICALENDAR_KEY' );

	if ( $icalendar_key )
	{
		$icalendar_key = mb_substr( sha1( rand( 999999999, 9999999999 ) ), 0, 16 );

		// Set iCalendar key secret.
		Config( 'ICALENDAR_KEY', $icalendar_key );
	}

	$user_id = UserStudentID() && ( User( 'PROFILE' ) === 'student' || User( 'PROFILE' ) === 'parent' ) ?
		'-' . UserStudentID() :
		( User( 'STAFF_ID' ) > 0 ? User( 'STAFF_ID' ) : 0 );

	$plain_hash = UserSchool() . SchoolInfo( 'TITLE' ) . $user_id . Config( 'ICALENDAR_KEY' );

	$link = PreparePHP_SELF(
		[],
		[
			'month',
			'year',
			'calendar_id',
			'modname',
			'calendar_schedule',
		],
		[
			'icalendar' => SchoolInfo( 'TITLE' ),
			'school_id' => UserSchool(),
			'schedule' => (int) ! empty( $_REQUEST['calendar_schedule'] ),
			'h' => encrypt_password( $plain_hash ),
		]
	);

	if ( $user_id )
	{
		$link .= '&user_id=' . $user_id;
	}

	$link = str_replace( 'Modules.php?modname=&', 'index.php?', $link );

	$link_text = dgettext( 'iCalendar', 'iCalendar' );

	$tooltip_html = '<div class="tooltip"><i>' .
		dgettext( 'iCalendar', 'Add school events to your Outlook, Google, Thunderbird, Android calendar (.ics).' ) .
	'</i></div>';

	if ( ! empty( $_REQUEST['calendar_schedule'] ) )
	{
		$tooltip_html = '<div class="tooltip"><i>' .
			dgettext( 'iCalendar', 'Add classes to your Outlook, Google, Thunderbird, Android calendar (.ics).' ) .
		'</i></div>';
	}

	DrawHeader( '<a href="' . $link . '" target="_blank">' . button( 'calendar' ) . ' ' . $link_text . '</a> ' . $tooltip_html );
}

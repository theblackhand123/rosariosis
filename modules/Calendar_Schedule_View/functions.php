<?php
/**
 * Functions
 *
 * @package Calndar Schedule View plugin
 */

// Translate plugin name.
dgettext( 'Calendar_Schedule_View', 'Calendar Schedule View' );

if ( ! empty( $_REQUEST['calendar_schedule'] )
	|| ( isset( $_REQUEST['bottomfunc'] )
		&& $_REQUEST['bottomfunc'] === 'print'
		&& ! empty( $_SESSION['_REQUEST_vars']['calendar_schedule'] ) ) )
{
	// Load our functions in place of the default ones if Schedule view is activated.
	require_once 'plugins/Calendar_Schedule_View/includes/CalendarScheduleView.fnc.php';
	require_once 'plugins/Calendar_Schedule_View/includes/CalendarDay.inc.php';
	require_once 'ProgramFunctions/TipMessage.fnc.php';
}

// Register plugin functions to be hooked.
add_action( 'School_Setup/Calendar.php|header', 'CalendarScheduleViewHeader' );

function CalendarScheduleViewHeader()
{
	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// No header on PDF to gain space.
		return '';
	}

	if ( empty( $_REQUEST['calendar_schedule'] ) )
	{
		$link = PreparePHP_SELF(
			[],
			[],
			[
				'calendar_schedule' => '1',
			]
		);

		$link_text = dgettext( 'Calendar_Schedule_View', 'Schedule View' );
	}
	else
	{
		$link = PreparePHP_SELF(
			[],
			[
				'calendar_schedule',
			]
		);

		$link_text = dgettext( 'Calendar_Schedule_View', 'Events and Assignments View' );
	}

	DrawHeader( '<a href="' . $link . '">' . $link_text . '</a>' );
}

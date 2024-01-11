<?php
/**
 * Plugin configuration interface
 *
 * @package Automatic Attendance
 */

// Check the script is called by the right program & plugin is activated.
if ( $_REQUEST['modname'] !== 'School_Setup/Configuration.php'
	|| ! $RosarioPlugins['Automatic_Attendance']
	|| $_REQUEST['modfunc'] !== 'config' )
{
	$error[] = _( 'You\'re not allowed to use this program!' );

	echo ErrorMessage( $error, 'fatal' );
}

// Note: no need to call ProgramTitle() here!

if ( isset( $_REQUEST['save'] )
	&& $_REQUEST['save'] === 'true' )
{
	if ( $_REQUEST['values']['config']
		&& $_POST['values']
		&& AllowEdit() )
	{
		if ( isset( $_REQUEST['values']['config'] )
			&& is_array( $_REQUEST['values']['config'] ) )
		{
			$hour = $_REQUEST['values']['config']['AUTOMATIC_ATTENDANCE_CRON_HOUR_H'];

			unset( $_REQUEST['values']['config']['AUTOMATIC_ATTENDANCE_CRON_HOUR_H'] );

			$minutes = $_REQUEST['values']['config']['AUTOMATIC_ATTENDANCE_CRON_HOUR_I'];

			unset( $_REQUEST['values']['config']['AUTOMATIC_ATTENDANCE_CRON_HOUR_I'] );

			$_REQUEST['values']['config']['AUTOMATIC_ATTENDANCE_CRON_HOUR'] = $hour . $minutes;

			foreach ( (array) $_REQUEST['values']['config'] as $column => $value )
			{
				Config( $column, $value );
			}

			$note[] = button( 'check' ) . '&nbsp;' . _( 'The plugin configuration has been modified.' );
		}
	}

	// Unset save & values & redirect URL.
	RedirectURL( [ 'save', 'values' ] );
}

if ( isset( $_REQUEST['missing_attendance_save'] )
	&& $_REQUEST['missing_attendance_save'] === 'true' )
{
	// Set start date.
	$start_date = RequestedDate( 'start', false );

	// Set end date.
	$end_date = RequestedDate( 'end', false );

	if ( $start_date
		&& $end_date
		&& AllowEdit() )
	{
		require_once 'plugins/Automatic_Attendance/includes/common.fnc.php';

		$return = AutomaticAttendanceDo( $start_date, $end_date );

		if ( $return )
		{
			$note[] = button( 'check' ) . '&nbsp;' . dgettext( 'Automatic_Attendance', 'Attendance has been taken for this timeframe.' );
		}
	}

	// Unset save & values & redirect URL.
	RedirectURL( 'missing_attendance_save', 'values' );
}

$default_attendance_code = DBGetOne( "SELECT TITLE
	FROM attendance_codes
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	AND TYPE='teacher'
	AND TABLE_NAME='0'
	AND DEFAULT_CODE='Y'" );


$school_title = '';

// If more than 1 school, add its title to table title.
if ( SchoolInfo( 'SCHOOLS_NB' ) > 1 )
{
	$school_title = SchoolInfo( 'SHORT_NAME' );

	if ( ! $school_title )
	{
		// No short name, get full title.
		$school_title = SchoolInfo( 'TITLE' );
	}

	$school_title = ' &mdash; ' . $school_title;
}

if ( ! empty( $_REQUEST['missing_attendance'] ) )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&tab=plugins&modfunc=config&plugin=Automatic_Attendance&missing_attendance_save=true' ) . '" method="POST">';

	$back_link = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&tab=plugins&modfunc=config&plugin=Automatic_Attendance' ) . '">« ' . _( 'Back' ) . '</a>';

	DrawHeader( $back_link, SubmitButton( _( 'Save' ) ) );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Automatic_Attendance', 'Automatic Attendance' ) . $school_title
	);

	echo '<p>' . dgettext( 'Automatic_Attendance', 'Take missing attendance for this timeframe.' ) . '</p>';

	// Set start date.
	$start_date = date( 'Y-m' ) . '-01';

	// Set end date.
	$end_date = date( 'Y-m-d', time() - 60 * 60 * 24 );

	// Hour.
	echo '<table><tr><td>' . _( 'Timeframe' ) . ': ' . PrepareDate( $start_date, '_start', false ) . ' ' .
		_( 'to' ) . ' ' . PrepareDate( $end_date, '_end', false ) . '</td></tr></table>';

	echo NoInput(
		$default_attendance_code,
		dgettext( 'Automatic_Attendance', 'Default Attendance Code' )
	);

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}

if ( empty( $_REQUEST['save'] )
	&& empty( $_REQUEST['missing_attendance'] ) )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
		'&tab=plugins&modfunc=config&plugin=Automatic_Attendance&save=true' ) . '" method="POST">';

	$save_missing_attendance_link = '';

	if ( AllowEdit() )
	{
		$save_missing_attendance_link = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=config&plugin=Automatic_Attendance&missing_attendance=true' ) . '">' .
			dgettext( 'Automatic_Attendance', 'Take missing attendance' ) . '</a>';
	}

	DrawHeader( $save_missing_attendance_link, SubmitButton( _( 'Save' ) ) );

	echo ErrorMessage( $note, 'note' );

	echo ErrorMessage( $error, 'error' );

	echo '<br />';

	PopTable(
		'header',
		dgettext( 'Automatic_Attendance', 'Automatic Attendance' ) . $school_title
	);

	$cron_hour = Config( 'AUTOMATIC_ATTENDANCE_CRON_HOUR' );

	$hour = mb_substr( $cron_hour, 0, 2 );

	for ( $i = 0; $i <= 23; $i++ )
	{
		$option = str_pad( $i, 2, '0', STR_PAD_LEFT );

		$hour_options[ $option ] = $option;
	}

	echo '<p>' . dgettext( 'Automatic_Attendance', 'Automatically take missing attendance for the day after this hour.' ) . '</p>';

	// Hour.
	echo '<table><tr><td>' . SelectInput(
		$hour,
		'values[config][AUTOMATIC_ATTENDANCE_CRON_HOUR_H]',
		dgettext( 'Automatic_Attendance', 'Hour' ),
		$hour_options,
		false,
		'',
		false
	) . '</td><td>: ';

	$minutes = mb_substr( $cron_hour, 2, 2 );

	for ( $i = 0; $i <= 59; $i++ )
	{
		$option = str_pad( $i, 2, '0', STR_PAD_LEFT );

		$minute_options[ $option ] = $option;
	}

	// Minutes.
	echo SelectInput(
		$minutes,
		'values[config][AUTOMATIC_ATTENDANCE_CRON_HOUR_I]',
		dgettext( 'Automatic_Attendance', 'Minutes' ),
		$minute_options,
		false,
		'',
		false
	) . '</td></tr>';

	echo '</table>';

	echo NoInput(
		$default_attendance_code,
		dgettext( 'Automatic_Attendance', 'Default Attendance Code' )
	);

	PopTable( 'footer' );

	echo '<br /><div class="center">' . SubmitButton( _( 'Save' ) ) . '</div></form>';
}

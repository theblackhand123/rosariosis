<?php
/**
 * Functions
 *
 * @package Automatic Attendance
 */

/**
 * Run AJAX requests in the footer to Update Daily Attendance
 * so Attendance/includes/UpdateAttendanceDaily.fnc.php|total_minutes_present Action hook can run properly
 *
 * @see Absent for the Day on First Absence plugin, function.php
 *
 * @uses Warehouse.php|footer action hook
 *
 * @since 11.0
 */
function AutomaticAttendanceUpdateDailyAJAXFooterAction()
{
	if ( isset( $_REQUEST['modfunc'] )
		&& $_REQUEST['modfunc'] === 'automatic_attendance_update_daily_ajax' )
	{
		require_once 'plugins/Automatic_Attendance/includes/common.fnc.php';

		$course_periods = empty( $_REQUEST['cp_id'] ) ? [] : $_REQUEST['cp_id'];

		$date = empty( $_REQUEST['date'] ) ? '' : $_REQUEST['date'];

		// Reset modfunc & cp_id & date in case Modules is dynamically reloaded based on $_SESSION request.
		RedirectURL( [ 'modfunc', 'cp_id', 'date' ] );

		AutomaticAttendanceUpdateDailyAJAX( $course_periods, $date );
	}
}

// It would be better to run in header, add Warehouse.php|header_ajax?
add_action( 'Warehouse.php|footer', 'AutomaticAttendanceUpdateDailyAJAXFooterAction' );


// Add our AutomaticAttendanceCronDo() function to the Warehouse.php|header action.
add_action( 'Warehouse.php|header', 'AutomaticAttendanceCronDo' );

/**
 * Run daily CRON on page load.
 * Do my CRON logic.
 *
 * @uses Warehouse.php|header action hook
 */
function AutomaticAttendanceCronDo()
{
	$cron_day = Config( 'AUTOMATIC_ATTENDANCE_CRON_DAY' );

	if ( DBDate() <= $cron_day
		|| ! UserSchool()
		|| basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
	{
		// CRON already ran today or not logged in.
		return false;
	}

	$cron_hour = Config( 'AUTOMATIC_ATTENDANCE_CRON_HOUR' );

	$yesterday = date( 'Y-m-d', time() - 60 * 60 * 24 );

	$run_time = date( 'Hi' );

	if ( $cron_day === $yesterday
		&& $run_time < $cron_hour )
	{
		// CRON already ran yesterday. Current time is before CRON hour.
		return false;
	}

	$cron_day_save = DBDate();

	if ( $run_time < $cron_hour )
	{
		// Save yesterday as we still need to run for today.
		$cron_day_save = $yesterday;
	}

	// Save CRON day.
	Config( 'AUTOMATIC_ATTENDANCE_CRON_DAY', $cron_day_save );

	require_once 'plugins/Automatic_Attendance/includes/common.fnc.php';

	$cron_day_after = date( 'Y-m-d', strtotime( $cron_day ) + 60 * 60 * 24 );

	$return = AutomaticAttendanceDo( $cron_day_after );

	return $return;
}

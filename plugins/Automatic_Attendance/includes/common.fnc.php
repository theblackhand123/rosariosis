<?php
/**
 * Common functions
 *
 * @package Automatic_Attendance
 */

function AutomaticAttendanceDo( $date_start, $date_end = '' )
{
	global $AUTOMATIC_ATTENDANCE;

	$cron_hour = Config( 'AUTOMATIC_ATTENDANCE_CRON_HOUR' );

	if ( ! $date_end )
	{
		$date_end = DBDate();

		if ( date( 'Hi' ) < $cron_hour )
		{
			// Yesterday.
			$date_end = date( 'Y-m-d', time() - 60 * 60 * 24 );
		}
	}

	if ( $date_start > $date_end )
	{
		return false;
	}

	$missing_attendance = AutomaticAttendanceMissing( $date_start, $date_end );

	if ( ! $missing_attendance )
	{
		return false;
	}

	$insert_sql = $AUTOMATIC_ATTENDANCE['course_periods'] = $AUTOMATIC_ATTENDANCE['dates'] = [];

	foreach ( $missing_attendance as $missing_attendance_day_period )
	{
		$course_period_id = $missing_attendance_day_period['COURSE_PERIOD_ID'];

		$date = $missing_attendance_day_period['SCHOOL_DATE'];

		$period_id = $missing_attendance_day_period['PERIOD_ID'];

		$insert_attendance_sql = AutomaticAttendanceDayPeriodSQL( $course_period_id, $date, $period_id );

		if ( ! $insert_attendance_sql )
		{
			continue;
		}

		$insert_sql[] = $insert_attendance_sql;

		$teacher_id = $missing_attendance_day_period['TEACHER_ID'];

		$insert_sql[] = AutomaticAttendanceCompletedSQL( $teacher_id, $date, $period_id );

		// Use global var for AutomaticAttendanceUpdateDailyFooterAction().
		$AUTOMATIC_ATTENDANCE['course_periods'][ $course_period_id ] = $course_period_id;

		$AUTOMATIC_ATTENDANCE['dates'][ $date ] = $date;
	}

	if ( ! $insert_sql )
	{
		return false;
	}

	// @since 11.1 Fix SQL error duplicate key value violates unique constraint "attendance_period_pkey": use transaction
	db_trans_start();

	$insert_done = db_trans_query( implode( '', $insert_sql ), false );

	db_trans_commit();

	if ( $insert_done === false ) // false == rollback, so no Daily Attendance to update...
	{
		return false;
	}

	// Run AJAX requests in the background to Update Daily Attendance.
	add_action( 'Warehouse.php|footer', 'AutomaticAttendanceUpdateDailyFooterAction' );

	return true;
}

function AutomaticAttendanceMissing( $date_start, $date_end )
{
	global $DatabaseType;

	// Only for "Attendance" category.
	$category_id = '0';

	$missing_attendance_sql = "SELECT cp.COURSE_PERIOD_ID,cp.TEACHER_ID,acc.SCHOOL_DATE,sp.PERIOD_ID
		FROM attendance_calendar acc,course_periods cp,school_periods sp,
		course_period_school_periods cpsp
		WHERE EXISTS(SELECT 1
			FROM schedule se
			WHERE cp.COURSE_PERIOD_ID=se.COURSE_PERIOD_ID
			AND se.SYEAR='" . UserSyear() . "'
			AND acc.SCHOOL_DATE>=se.START_DATE
			AND (se.END_DATE IS NULL OR acc.SCHOOL_DATE<=se.END_DATE))
		AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND acc.MINUTES>0
		AND acc.SCHOOL_ID='" . UserSchool() . "'
		AND cp.SCHOOL_ID=acc.SCHOOL_ID
		AND cp.SYEAR='" . UserSyear() . "'
		AND cp.CALENDAR_ID=acc.CALENDAR_ID
		AND acc.SCHOOL_DATE<='" . $date_end . "'
		AND acc.SCHOOL_DATE>='" . $date_start . "'
		AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP<>'PRO') AND SCHOOL_ID=acc.SCHOOL_ID AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
		AND sp.PERIOD_ID=cpsp.PERIOD_ID
		AND acc.SCHOOL_DATE NOT IN(SELECT ac.SCHOOL_DATE FROM attendance_completed ac WHERE ac.STAFF_ID=cp.TEACHER_ID AND ac.PERIOD_ID=cpsp.PERIOD_ID AND TABLE_NAME='" . $category_id . "')
		AND position('," . $category_id . ",' IN cp.DOES_ATTENDANCE)>0";

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		// FJ days numbered.
		// FJ multiple school periods for a course period.
		$missing_attendance_sql .= " AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
			(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE SCHOOL_DATE<=acc.SCHOOL_DATE
			AND SCHOOL_DATE>=(SELECT START_DATE
				FROM school_marking_periods
				WHERE START_DATE<=acc.SCHOOL_DATE
				AND END_DATE>=acc.SCHOOL_DATE
				AND MP='QTR'
				AND SCHOOL_ID=acc.SCHOOL_ID
				AND SYEAR=acc.SYEAR)
			AND CALENDAR_ID=cp.CALENDAR_ID)
			" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
			" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";
	}
	else
	{
		// @since RosarioSIS 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
		$missing_attendance_sql .= " AND (sp.BLOCK IS NULL AND position(substring('UMTWHFS' FROM " .
			( $DatabaseType === 'mysql' ?
				"DAYOFWEEK(acc.SCHOOL_DATE)" :
				"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
			" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";
	}

	$missing_attendance_RET = DBGet( $missing_attendance_sql );

	return $missing_attendance_RET;
}

function AutomaticAttendanceDayPeriodSQL( $course_period_id, $date, $period_id )
{
	static $default_attendance_code = null;

	// Only for "Attendance" category.
	$table = '0';

	if ( is_null( $default_attendance_code ) )
	{
		$default_attendance_code = DBGetOne( "SELECT ID
			FROM attendance_codes
			WHERE SCHOOL_ID=(SELECT SCHOOL_ID
				FROM course_periods
				WHERE COURSE_PERIOD_ID='" . (int) $course_period_id . "')
			AND SYEAR='" . UserSyear() . "'
			AND TYPE='teacher'
			AND TABLE_NAME='" . $table . "'
			AND DEFAULT_CODE='Y'" );
	}

	$qtr_id =  GetCurrentMP( 'QTR', $date, false );

	if ( ! $qtr_id )
	{
		return '';
	}

	$sql = "INSERT INTO attendance_period (STUDENT_ID,SCHOOL_DATE,MARKING_PERIOD_ID,PERIOD_ID,
		COURSE_PERIOD_ID,ATTENDANCE_CODE,ATTENDANCE_TEACHER_CODE)
		SELECT sd.STUDENT_ID,'" . $date . "','" . $qtr_id . "','" . $period_id .
		"','" . $course_period_id . "','" . $default_attendance_code . "','" .
		$default_attendance_code . "'
		FROM (SELECT DISTINCT s.STUDENT_ID
			FROM students s
			JOIN schedule ss ON (ss.STUDENT_ID=s.STUDENT_ID
				AND ss.COURSE_PERIOD_ID='" . (int) $course_period_id . "'
				AND ss.SYEAR='" . UserSyear() . "'
				AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', $qtr_id ) . ")
				AND ('" . $date . "'>=ss.START_DATE AND ('" . $date . "'<=ss.END_DATE OR ss.END_DATE IS NULL)))
			JOIN student_enrollment ssm ON (ssm.STUDENT_ID=s.STUDENT_ID
				AND ssm.SYEAR=ss.SYEAR
				AND ('" . $date . "'>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR '" . $date . "'<=ssm.END_DATE)))
			WHERE s.STUDENT_ID NOT IN(SELECT STUDENT_ID
				FROM attendance_period
				WHERE SCHOOL_DATE='" . $date . "'
				AND PERIOD_ID='" . (int) $period_id . "')) sd;";

	return $sql;
}

function AutomaticAttendanceCompletedSQL( $teacher_id, $date, $period_id )
{
	static $check_duplicate = [];

	// Only for "Attendance" category.
	$table = '0';

	$key = $teacher_id . '|' . $date . '|' . $period_id . '|' . $table;

	if ( in_array( $key, $check_duplicate ) )
	{
		return '';
	}

	$sql = "INSERT INTO attendance_completed (STAFF_ID,SCHOOL_DATE,PERIOD_ID,TABLE_NAME)
		values('" . $teacher_id . "','" . $date . "','" . $period_id . "','" . $table . "');";

	$check_duplicate[] = $key;

	return $sql;
}

function AutomaticAttendanceUpdateDailyFooterAction()
{
	global $AUTOMATIC_ATTENDANCE;

	if ( empty( $_REQUEST['modname'] ) )
	{
		return;
	}

	$course_periods = $AUTOMATIC_ATTENDANCE['course_periods'];

	$dates = $AUTOMATIC_ATTENDANCE['dates'];

	$cp_id_array = implode( '&cp_id[]=', $course_periods );

	foreach ( (array) $dates as $date )
	{
		// Call AutomaticAttendanceUpdateDailyAJAX() using 'automatic_attendance_update_daily_ajax' modfunc.
		?>
		<script>
			$.ajax( 'Modules.php?modname=' + <?php echo json_encode( $_REQUEST['modname'] ); ?> +
				'&modfunc=automatic_attendance_update_daily_ajax&cp_id[]=' + <?php echo json_encode( $cp_id_array ); ?> +
				'&date=' + <?php echo json_encode( $date ); ?> );
		</script>
		<?php
	}
}

function AutomaticAttendanceUpdateDailyAJAX( $course_periods, $date )
{
	require_once 'modules/Attendance/includes/UpdateAttendanceDaily.fnc.php';

	if ( ! $course_periods
		|| ! $date )
	{
		die( 0 );
	}

	$students_RET = DBGet( "SELECT DISTINCT STUDENT_ID
		FROM schedule
		WHERE COURSE_PERIOD_ID IN('" . implode( "','", $course_periods ) . "')
		AND START_DATE<='" . $date . "'
		AND (END_DATE>='" . $date . "' OR END_DATE IS NULL)
		AND SYEAR='" . UserSyear() . "';" );

	foreach ( $students_RET as $student )
	{
		UpdateAttendanceDaily( $student['STUDENT_ID'], $date );
	}

	die( 'OK' );
}

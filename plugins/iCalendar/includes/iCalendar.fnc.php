<?php
/**
 * iCal functions
 *
 * @package iCal
 */

/**
 * Do iCalendar: match hash, create calendar, get events, add events and output calendar.
 *
 * @since 2.0
 *
 * @uses iCalendarCreate()
 * @uses iCalendarScheduleEvents()
 * @uses iCalendarAssignmentEvents()
 * @uses iCalendarEvents()
 * @uses iCalendarOutput()
 *
 * @return bool False if hash does not match or Calendar not output.
 */
function iCalendarDo()
{
	$user_id = issetVal( $_REQUEST['user_id'], 0 );

	$plain_hash = $_REQUEST['school_id'] . $_REQUEST['icalendar'] . $user_id . Config( 'ICALENDAR_KEY' );

	if ( ! match_password( $_REQUEST['h'], $plain_hash ) )
	{
		return false;
	}

	$events = $assignments = [];

	if ( ! empty( $_REQUEST['schedule'] )
		&& file_exists( 'plugins/Calendar_Schedule_View/includes/CalendarScheduleView.fnc.php' ) )
	{
		$calendar = iCalendarCreate( $_REQUEST['icalendar'] . ' - ' . _( 'Schedule' ) );

		// Load our functions in place of the default ones if Schedule view is activated.
		require_once 'plugins/Calendar_Schedule_View/includes/CalendarScheduleView.fnc.php';

		$events = iCalendarScheduleEvents( $_REQUEST['school_id'], Config( 'SYEAR' ), $user_id );
	}
	else
	{
		$calendar = iCalendarCreate( $_REQUEST['icalendar'] );

		$events = iCalendarEvents( $_REQUEST['school_id'], Config( 'SYEAR' ) );

		if ( $user_id )
		{
			// @since 2.0 Add assignments to student / teacher calendar.
			$assignments = iCalendarAssignmentEvents( $_REQUEST['school_id'], Config( 'SYEAR' ), $user_id );
		}
	}

	foreach ( (array) $events as $event )
	{
		// Add event to calendar.
		$calendar->addComponent( $event );
	}

	foreach ( (array) $assignments as $assignment )
	{
		// @since 2.0 Add assignments to student / teacher calendar.
		$calendar->addComponent( $assignment );
	}

	return iCalendarOutput( $calendar );
}


/**
 * Create iCalendar
 *
 * @uses \Eluceo\iCal\Component\Calendar class
 *
 * @param  string $title Title.
 *
 * @return Calendar      Calendar object.
 */
function iCalendarCreate( $title = '' )
{
	$title = $title ? $title : SchoolInfo( 'TITLE' );

	// Create new calendar.
	$calendar = new \Eluceo\iCal\Component\Calendar( $title );

	return $calendar;
}

/**
 * Get Events for school & year.
 *
 * @param  int   $school_id School ID.
 * @param  int   $syear     School year.
 *
 * @uses iCalendarEvent()
 *
 * @return array            Calendar Events array.
 */
function iCalendarEvents( $school_id, $syear )
{
	$school_id = $school_id ? $school_id : UserSchool();

	$syear = $syear ? $syear : Config( 'SYEAR' );

	$events_RET = DBGet( "SELECT ID,SCHOOL_DATE,TITLE,DESCRIPTION
		FROM calendar_events
		WHERE SCHOOL_ID='" . (int) $school_id . "'
		AND SYEAR='" . $syear . "'" );

	$events = [];

	foreach ( (array) $events_RET as $event )
	{
		$ical_event = iCalendarEvent(
			$event['ID'],
			$event['SCHOOL_DATE'],
			$event['TITLE'],
			$event['DESCRIPTION']
		);

		if ( $ical_event )
		{
			$events[] = $ical_event;
		}
	}

	return $events;
}

/**
 * Get Schedule (events) for school, year and user.
 *
 * @uses iCalendarScheduleEventsDay()
 * @uses iCalendarEvent()
 *
 * @param  int   $school_id School ID.
 * @param  int   $syear     School Year.
 * @param  int   $user_id   User ID. <0 for students.
 *
 * @return array            Calendar Events (Classes).
 */
function iCalendarScheduleEvents( $school_id, $syear, $user_id )
{
	global $DatabaseType;

	$school_id = $school_id ? $school_id : UserSchool();

	$syear = $syear ? $syear : Config( 'SYEAR' );

	// Get school days for current month.
	$school_days_month_RET = DBGet( "SELECT SCHOOL_DATE
		FROM attendance_calendar
		WHERE SYEAR='" . $syear . "'
		AND SCHOOL_ID='" . (int) $school_id . "'
		AND CALENDAR_ID=(SELECT CALENDAR_ID
			FROM attendance_calendars
			WHERE DEFAULT_CALENDAR='Y'
			AND SYEAR='" . $syear . "'
			AND SCHOOL_ID='" . (int) $school_id . "')
		AND MINUTES>0
		AND SCHOOL_DATE BETWEEN CAST(CONCAT(CAST(CURRENT_DATE AS char(7)), '-01') AS DATE)
		AND CAST(CONCAT(CAST(CURRENT_DATE AS char(7)), '-01') AS DATE) + INTERVAL " .
		( $DatabaseType === 'mysql' ? '1 month - INTERVAL 1 day' : "'1 month - 1 day'" ) );

	$events = [];

	$teachers_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
		FROM staff
		WHERE SYEAR='" . $syear . "'
		AND (SCHOOLS IS NULL OR position('," . $school_id . ",' IN SCHOOLS)>0)", [], [ 'STAFF_ID' ] );

	foreach (  (array) $school_days_month_RET as $school_day )
	{
		$date = $school_day['SCHOOL_DATE'];

		$day_events = iCalendarScheduleEventsDay( $date, $school_id, $syear, $user_id );

		foreach ( (array) $day_events as $event )
		{
			$title = $event['SUBJECT_TITLE'] . ' - ' . $event['PERIOD_TITLE'];

			$description = $event['COURSE_TITLE'] . "\n" .
				$teachers_RET[ $event['TEACHER_ID'] ][1]['FULL_NAME'];

			if ( $event['ROOM'] )
			{
				$description .= "\n" . _( 'Room' ) . ': ' . $event['ROOM'];
			}

			$ical_event = iCalendarEvent(
				str_replace( '-', '', $date ) . $event['COURSE_PERIOD_ID'],
				$date,
				$title,
				$description
			);

			if ( $ical_event )
			{
				$events[] = $ical_event;
			}
		}
	}

	return $events;
}

/**
 * Get Schedule (classes) for day, school, year and user.
 *
 * @since RosarioSIS 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
 *
 * @param  string $date      School date.
 * @param  int    $school_id School ID.
 * @param  int    $syear     School Year.
 * @param  int    $user_id   User ID. <0 for students.
 *
 * @return array             Schedule for day (Classes).
 */
function iCalendarScheduleEventsDay( $date, $school_id, $syear, $user_id )
{
	global $DatabaseType;

	$where_sql = " AND (sp.BLOCK IS NULL
		AND position(substring('UMTWHFS' FROM " .
		( $DatabaseType === 'mysql' ?
			"DAYOFWEEK(acc.SCHOOL_DATE)" :
			"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
		" FOR 1) IN cpsp.DAYS)>0
		OR sp.BLOCK IS NOT NULL
		AND acc.BLOCK IS NOT NULL
		AND sp.BLOCK=acc.BLOCK)";

	$number_days_rotation = DBGetOne( "SELECT NUMBER_DAYS_ROTATION
		FROM schools
		WHERE ID = '" . $school_id . "'
		AND SYEAR = '" . $syear . "'" );

	if ( $number_days_rotation !== null )
	{
		$where_sql = " AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
			(SELECT CASE COUNT(school_date)% " . $number_days_rotation . " WHEN 0 THEN " . $number_days_rotation . " ELSE COUNT(school_date)% " . $number_days_rotation . " END AS day_number
			FROM attendance_calendar
			WHERE school_date>=(SELECT start_date FROM school_marking_periods WHERE start_date<=acc.SCHOOL_DATE AND end_date>=acc.SCHOOL_DATE AND mp='QTR' AND SCHOOL_ID=acc.SCHOOL_ID)
			AND school_date<=acc.SCHOOL_DATE
			AND SCHOOL_ID=acc.SCHOOL_ID)
			" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
			" FOR 1) IN cpsp.DAYS)>0 OR sp.BLOCK IS NOT NULL AND acc.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK)";
	}

	$profile = $user_id < 0 ? 'student' : 'admin';

	if ( $user_id > 0 )
	{
		$profile = DBGetOne( "SELECT PROFILE
			FROM staff
			WHERE STAFF_ID='" . (int) $user_id . "'
			AND SYEAR='" . $syear . "'" );
	}

	if ( $profile === 'teacher' )
	{
		// Display Teacher classes only.
		$where_sql .= " AND cp.TEACHER_ID='" . (int) $user_id . "'";
	}

	if ( $profile === 'student' )
	{
		// Display Student classes only.
		$where_sql .= " AND cp.COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID
			FROM schedule
			WHERE SYEAR='" . $syear . "'
			AND SCHOOL_ID='" . (int) $school_id . "'
			AND STUDENT_ID='" . ( $user_id * -1 ) . "'
			AND START_DATE<=CURRENT_DATE
			AND (END_DATE IS NULL OR END_DATE>=CURRENT_DATE))";
	}

	$course_periods_RET = DBGet( "SELECT cp.TITLE,cp.SHORT_NAME,cp.TEACHER_ID,cp.ROOM,
		c.TITLE AS COURSE_TITLE,cs.TITLE AS SUBJECT_TITLE,sp.TITLE AS PERIOD_TITLE,c.COURSE_ID,cp.COURSE_PERIOD_ID
	FROM attendance_calendar acc,course_periods cp,school_periods sp,course_period_school_periods cpsp,
	courses c,course_subjects cs
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND cp.COURSE_ID=c.COURSE_ID
	AND c.SUBJECT_ID=cs.SUBJECT_ID
	AND acc.SYEAR='" . $syear . "'
	AND cp.SCHOOL_ID='" . (int) $school_id . "'
	AND cp.SCHOOL_ID=acc.SCHOOL_ID
	AND cp.SYEAR=acc.SYEAR
	AND acc.SCHOOL_DATE='" . $date . "'
	AND cp.CALENDAR_ID=acc.CALENDAR_ID
	AND cp.MARKING_PERIOD_ID IN (SELECT MARKING_PERIOD_ID FROM school_marking_periods WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
	AND SCHOOL_ID=acc.SCHOOL_ID
	AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
	AND sp.PERIOD_ID=cpsp.PERIOD_ID" .
	$where_sql .
	" ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER" );

	return (array) $course_periods_RET;
}

/**
 * Get Assignments (events) for school, year and user (student and teacher).
 *
 * @since 2.0
 *
 * @uses iCalendarEvent()
 *
 * @param  int   $school_id School ID.
 * @param  int   $syear     School Year.
 * @param  int   $user_id   User ID. <0 for students.
 *
 * @return array            Calendar Events (Assignments).
 */
function iCalendarAssignmentEvents( $school_id, $syear, $user_id )
{
	global $DatabaseType;

	$school_id = $school_id ? $school_id : UserSchool();

	$syear = $syear ? $syear : Config( 'SYEAR' );

	$user_profile = 'student';

	if ( $user_id > 0 )
	{
		$user_profile = DBGetOne( "SELECT PROFILE FROM staff
			WHERE STAFF_ID='" . (int) $user_id . "'
			AND SYEAR='" . $syear . "'" );
	}

	if ( $user_profile !== 'student'
		&& $user_profile !== 'teacher' )
	{
		return [];
	}

	// Get Assignments.
	if ( $user_id < 0 )
	{
		$student_id = $user_id * -1;

		$assignments_sql = "SELECT a.ASSIGNMENT_ID AS ID,a.DUE_DATE AS SCHOOL_DATE,a.TITLE,
			a.STAFF_ID,a.DESCRIPTION,a.ASSIGNED_DATE,c.TITLE AS COURSE,a.SUBMISSION
			FROM gradebook_assignments a,schedule s,courses c
			WHERE (a.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID OR a.COURSE_ID=s.COURSE_ID)
			AND s.STUDENT_ID='" . (int) $student_id . "'
			AND (a.DUE_DATE BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
			AND (a.ASSIGNED_DATE<=CURRENT_DATE OR a.ASSIGNED_DATE IS NULL)
			AND (a.COURSE_ID=c.COURSE_ID
				OR c.COURSE_ID=(SELECT cp.COURSE_ID
					FROM course_periods cp
					WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))";
	}
	else
	{
		$assignments_sql = "SELECT a.ASSIGNMENT_ID AS ID,a.DUE_DATE AS SCHOOL_DATE,a.TITLE,
			a.STAFF_ID,a.DESCRIPTION,a.ASSIGNED_DATE,c.TITLE AS COURSE,a.SUBMISSION
			FROM gradebook_assignments a,courses c
			WHERE a.STAFF_ID='" . (int) $user_id . "'
			AND (a.COURSE_ID=c.COURSE_ID
				OR c.COURSE_ID=(SELECT cp.COURSE_ID
					FROM course_periods cp
					WHERE cp.COURSE_PERIOD_ID=a.COURSE_PERIOD_ID))";
	}

	// Due date between today and end of next month.
	$due_date_between_sql = " AND a.DUE_DATE IS NOT NULL
		AND a.DUE_DATE BETWEEN CAST(CONCAT(CAST(CURRENT_DATE AS char(7)), '-01') AS DATE)
		AND CAST(CONCAT(CAST(CURRENT_DATE AS char(7)), '-01') AS DATE) + INTERVAL " .
		( $DatabaseType === 'mysql' ? '2 month - INTERVAL 1 day' : "'2 month - 1 day'" );

	$assignments_RET = DBGet( $assignments_sql . $due_date_between_sql );

	$teachers_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
		FROM staff
		WHERE SYEAR='" . $syear . "'
		AND (SCHOOLS IS NULL OR position('," . $school_id . ",' IN SCHOOLS)>0)", [], [ 'STAFF_ID' ] );

	$events = [];

	foreach ( (array) $assignments_RET as $event )
	{
		$date = $event['SCHOOL_DATE'];

		$title = $event['TITLE'];

		$description = _( 'Due Date' ) . ': ' . ProperDate( $event['SCHOOL_DATE'] );

		if ( $event['ASSIGNED_DATE'] )
		{
			$description .= "\n" . _( 'Assigned Date' ) . ': ' . ProperDate( $event['ASSIGNED_DATE'] );
		}

		$description .= "\n" . _( 'Course' ) . ': ' . $event['COURSE'];

		if ( $user_profile !== 'teacher' )
		{
			$description .= "\n" . _( 'Teacher' ) . ': ' . $teachers_RET[ $event['STAFF_ID'] ][1]['FULL_NAME'];
		}

		if ( $event['DESCRIPTION'] )
		{
			$description .= "\n" . _( 'Notes' ) . ': ' . "\n" .
				html_entity_decode( str_replace(
					[ '<br />', '<br/>', '<br>' ],
					"\n",
					$event['DESCRIPTION']
				) );
		}

		$ical_event = iCalendarEvent(
			str_replace( '-', '', $date ) . $event['ID'],
			$date,
			$title,
			strip_tags( $description )
		);

		if ( $ical_event )
		{
			$events[] = $ical_event;
		}
	}

	return $events;
}

/**
 * iCalendar Event
 *
 * @uses \Eluceo\iCal\Component\Event class
 *
 * @param  string $id          Unique ID.
 * @param  string $date        Date.
 * @param  string $title       Title.
 * @param  string $description Description.
 *
 * @return Event               iCalendar Event.
 */
function iCalendarEvent( $id, $date, $title, $description = '' )
{
	if ( ! $title
		|| ! VerifyDate( $date ) )
	{
		return false;
	}

	// Create an event.
	$event = new \Eluceo\iCal\Component\Event();

	$event->setUniqueId( $id );

	$event->setDtStart(new \DateTime( $date ));

	$event->setDtEnd(new \DateTime( $date ));

	$event->setNoTime( true );

	$event->setSummary( $title );

	$event->setDescription( $description );

	$event->setDescriptionHTML( $description );

	return $event;
}

/**
 * iCalendar output (.ics)
 *
 * @param  Calendar $calendar iCalendar with events.
 *
 * @return boolean            False if headers already sent, else true.
 */
function iCalendarOutput( $calendar )
{
	if ( headers_sent() )
	{
		return false;
	}

	// Set headers.
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="cal.ics"' );

	// Output.
	echo $calendar->render();

	return true;
}

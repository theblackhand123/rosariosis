<?php
/**
 * Calendar Schedule View functions
 *
 * @package Calendar Schedule View plugin
 */

/**
 * Get Day Course Periods
 *
 * @since RosarioSIS 10.0 SQL use DAYOFWEEK() for MySQL or cast(extract(DOW)+1 AS int) for PostrgeSQL
 *
 * @param string $date    ISO date of calendar day.
 * @param int    $minutes Minutes.
 *
 * @return array Course Periods.
 */
function CalendarScheduleViewGetDayCoursePeriods( $date, $minutes )
{
	global $DatabaseType;

	static $course_periods = [];

	if ( isset( $course_periods[ $date ] ) )
	{
		return $course_periods[ $date ];
	}

	$course_periods[ $date ] = [];

	if ( empty( $minutes ) )
	{
		return $course_periods[ $date ];
	}

	$qtr_id = GetCurrentMP( 'QTR', $date, false );

	if ( ! $qtr_id )
	{
		// Date not in a school quarter.
		return $course_periods[ $date ];
	}

	$where_sql = " AND (sp.BLOCK IS NULL
		AND position(substring('UMTWHFS' FROM " .
		( $DatabaseType === 'mysql' ?
			"DAYOFWEEK(acc.SCHOOL_DATE)" :
			"cast(extract(DOW FROM acc.SCHOOL_DATE)+1 AS int)" ) .
		" FOR 1) IN cpsp.DAYS)>0
		OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";

	if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
	{
		$where_sql = " AND (sp.BLOCK IS NULL AND position(substring('MTWHFSU' FROM cast(
			(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS day_number
			FROM attendance_calendar
			WHERE SCHOOL_DATE>=(SELECT START_DATE
				FROM school_marking_periods
				WHERE START_DATE<=acc.SCHOOL_DATE
				AND END_DATE>=acc.SCHOOL_DATE
				AND MP='QTR'
				AND SCHOOL_ID=acc.SCHOOL_ID
				AND SYEAR=acc.SYEAR)
			AND SCHOOL_DATE<=acc.SCHOOL_DATE
			AND CALENDAR_ID=cp.CALENDAR_ID)
			" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
			" FOR 1) IN cpsp.DAYS)>0 OR (sp.BLOCK IS NOT NULL AND sp.BLOCK=acc.BLOCK))";
	}

	if ( User( 'PROFILE' ) === 'admin'
		&& ! empty( $_REQUEST['calendar_id'] ) )
	{
		// Display selected Calendar schedule for admins only.
		$where_sql .= " AND acc.CALENDAR_ID='" . (int) $_REQUEST['calendar_id'] . "'";
	}

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		// Display Teacher classes only.
		if ( version_compare( ROSARIO_VERSION, '6.9', '<' ) )
		{
			$where_sql .= " AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'";
		}
		else
		{
			// @since 6.9 Add Secondary Teacher.
			$where_sql .= " AND (cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
				OR SECONDARY_TEACHER_ID='" . User( 'STAFF_ID' ) . "')";
		}
	}

	if ( User( 'PROFILE' ) === 'parent'
		|| User( 'PROFILE' ) === 'student' )
	{
		// Display Student classes only.
		// Fix SQL student enrolled: check Schedule MP.
		$where_sql .= " AND cp.COURSE_PERIOD_ID IN(SELECT COURSE_PERIOD_ID
			FROM schedule
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND STUDENT_ID='" . UserStudentID() . "'
			AND START_DATE<='" . $date . "'
			AND (END_DATE IS NULL OR END_DATE>='" . $date . "')
			AND MARKING_PERIOD_ID IN(" . GetAllMP( 'QTR', $qtr_id ) . "))";
	}

	$course_periods_RET = DBGet( "SELECT cp.TITLE,cp.SHORT_NAME,cp.TEACHER_ID,cp.ROOM,
		c.TITLE AS COURSE_TITLE,cs.TITLE AS SUBJECT_TITLE,sp.TITLE AS PERIOD_TITLE,c.COURSE_ID,cp.COURSE_PERIOD_ID
	FROM attendance_calendar acc,course_periods cp,school_periods sp,course_period_school_periods cpsp,
	courses c,course_subjects cs
	WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
	AND cp.COURSE_ID=c.COURSE_ID
	AND c.SUBJECT_ID=cs.SUBJECT_ID
	AND acc.SYEAR='" . UserSyear() . "'
	AND cp.SCHOOL_ID='" . UserSchool() . "'
	AND cp.SCHOOL_ID=acc.SCHOOL_ID
	AND cp.SYEAR=acc.SYEAR
	AND acc.SCHOOL_DATE='" . $date . "'
	AND cp.CALENDAR_ID=acc.CALENDAR_ID
	AND cp.MARKING_PERIOD_ID IN(SELECT MARKING_PERIOD_ID
		FROM school_marking_periods
		WHERE (MP='FY' OR MP='SEM' OR MP='QTR')
		AND SCHOOL_ID=acc.SCHOOL_ID
		AND acc.SCHOOL_DATE BETWEEN START_DATE AND END_DATE)
	AND sp.PERIOD_ID=cpsp.PERIOD_ID" .
	$where_sql .
	" ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER", [], [ 'COURSE_PERIOD_ID' ] );

	$course_periods[ $date ] = (array) $course_periods_RET;

	return $course_periods[ $date ];
}


/**
 * Course Period HTML
 *
 * @param array $course_periods Course Period (multiple if various periods for same course).
 *
 * @return string HTML.
 */
function CalendarScheduleViewCoursePeriodHTML( $course_periods )
{
	static $course_colors = [],
		$color_i = 0;

	$cp_html = '';

	$course_period_i = 0;

	// @link http://clrs.cc/
	$colors = [ '#001f3f', '#FFDC00', '#01FF70', '#F012BE', '#0074D9', '#FF4136' ];

	foreach ( $course_periods as $course_period )
	{
		if ( ! $course_period_i++ )
		{
			$course_id = $course_period['COURSE_ID'];

			if ( ! isset( $course_colors[ $course_id ] ) )
			{
				$course_colors[ $course_id ] = $colors[ $color_i++ % count( $colors ) ];
			}

			if ( User( 'STAFF_ID' ) > 0 // Logged in, not in Public Page!
				&& ( User( 'PROFILE' ) === 'admin'
					|| User( 'PROFILE' ) === 'teacher' ) )
			{
				$label = $course_period['SHORT_NAME'] . ' <span class="size-1">';
			}
			else
			{
				$label = $course_period['SUBJECT_TITLE'] . ' <span class="size-1">';
			}

			// Popup.
			$title = $course_period['COURSE_TITLE'];

			$message = '';

			$message = GetTeacher( $course_period['TEACHER_ID'] );

			if ( $course_period['ROOM'] )
			{
				if ( $message )
				{
					$message .= '<br />';
				}

				$message .= _( 'Room' ) . ': ' . $course_period['ROOM'];
			}
		}
		else
		{
			$label .= ', ';
		}

		$label .= $course_period['PERIOD_TITLE'];
	}

	$label .= '</span>';

	$cp_html .= '<div style="border-left-color:' . $course_colors[ $course_id ] . '"><span>';

	$cp_html .= MakeTipMessage( $message, $title, $label ) . '</span></div>';

	return $cp_html;
}

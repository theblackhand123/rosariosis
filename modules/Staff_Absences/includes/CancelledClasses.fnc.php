<?php
/**
 * Cancelled Classes functions
 *
 * @package Staff Absences module
 */

/**
 * Get Cancelled days for Period
 *
 * @param string $start_date  Absence Start Date.
 * @param string $end_date    Absence End Date.
 * @param string $period_days Course Period School Period days.
 * @param string $block       Block Period?
 * @param int    $calendar_id Attendance Calendar ID.
 *
 * @return array Cancelled days (dates).
 */
function StaffAbsenceCancelledPeriodDays( $start_date, $end_date, $period_days, $block, $calendar_id )
{
	if ( ! VerifyDate( $start_date )
		|| ! VerifyDate( $end_date )
		|| ! $period_days
		|| $start_date > $end_date )
	{
		return [];
	}

	$cancelled_days = [];

	$date = $start_date;

	while ( $date <= $end_date )
	{
		if ( $block )
		{
			$period_day = StaffAbsenceIsBlockDay( $date, $calendar_id, $block );
		}
		elseif ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
		{
			$period_day = StaffAbsenceIsSchoolNumberedDay( $date, $period_days, $calendar_id )
				&& StaffAbsenceIsSchoolDay( $date, $calendar_id );
		}
		else
		{
			$period_day = StaffAbsenceIsPeriodDay( $date, $period_days )
				&& StaffAbsenceIsSchoolDay( $date, $calendar_id );
		}

		if ( $period_day )
		{
			$cancelled_days[] = $date;
		}

		$date = date( 'Y-m-d', strtotime( '+1 day', strtotime( $date ) ) );
	}

	return $cancelled_days;
}

/**
 * Is date on one of Period days?
 *
 * @param string $date        Date.
 * @param string $period_days Course Period School Period days.
 *
 * @return bool True if date is on one of Period days.
 */
function StaffAbsenceIsPeriodDay( $date, $period_days )
{
	if ( ! VerifyDate( $date )
		|| ! $period_days )
	{
		return false;
	}

	// Day of the week: 1 (for Monday) through 7 (for Sunday).
	$date_day = date( 'w', strtotime( $date ) ) ? date( 'w', strtotime( $date ) ) : 7;

	// Numbered days??
	$days_convert = [
		7 => 'U',
		1 => 'M',
		2 => 'T',
		3 => 'W',
		4 => 'H',
		5 => 'F',
		6 => 'S',
	];

	$date_day = $days_convert[ $date_day ];

	return strpos( $period_days, $date_day ) !== false;
}

/**
 * Is date corresponding to a numbered day?
 *
 * @param string $date        Date.
 * @param string $period_days Course Period School Period days.
 * @param int    $calendar_id Attendance Calendar ID.
 *
 * @return bool True if  date is corresponding to a numbered day.
 */
function StaffAbsenceIsSchoolNumberedDay( $date, $period_days, $calendar_id )
{
	global $DatabaseType;

	if ( ! VerifyDate( $date )
		|| ! $period_days
		|| ! $calendar_id )
	{
		return false;
	}

	$is_numbered_day = DBGetOne( "SELECT position(substring('MTWHFSU' FROM cast(
		(SELECT CASE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " WHEN 0 THEN " . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " ELSE COUNT(SCHOOL_DATE)%" . SchoolInfo( 'NUMBER_DAYS_ROTATION' ) . " END AS DAY_NUMBER
		FROM attendance_calendar
		WHERE CALENDAR_ID='" . (int) $calendar_id . "'
		AND SCHOOL_DATE>=(SELECT START_DATE
			FROM school_marking_periods
			WHERE START_DATE<='" . $date . "'
			AND END_DATE>='" . $date . "'
			AND MP='QTR'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "')
		AND SCHOOL_DATE<='" . $date . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "')
		" . ( $DatabaseType === 'mysql' ? "AS UNSIGNED)" : "AS INT)" ) .
		" FOR 1) IN '" . $period_days . "')>0" );

	// True value: MySQL returns 1 whereas PostgreSQL returns t.
	$true = $DatabaseType === 'mysql' ? '1' : 't';

	return $true === $is_numbered_day;
}

/**
 * Is date a School day in Calendar?
 *
 * @param string $date        Date.
 * @param int    $calendar_id Attendance Calendar ID.
 *
 * @return bool True if date is a School day in Calendar.
 */
function StaffAbsenceIsSchoolDay( $date, $calendar_id )
{
	if ( ! VerifyDate( $date )
		|| ! $calendar_id )
	{
		return false;
	}

	return (bool) DBGetOne( "SELECT 1
		FROM attendance_calendar
		WHERE CALENDAR_ID='" . (int) $calendar_id . "'
		AND SCHOOL_DATE='" . $date . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND MINUTES!='0'" );
}

/**
 * Is date a Block day in Calendar?
 *
 * @param string $date        Date.
 * @param int    $calendar_id Attendance Calendar ID.
 * @param string $block       Block Period.
 *
 * @return bool True if date is a Block day.
 */
function StaffAbsenceIsBlockDay( $date, $calendar_id, $block )
{
	if ( ! VerifyDate( $date )
		|| ! $calendar_id
		|| ! $block )
	{
		return false;
	}

	return (bool) DBGetOne( "SELECT 1
		FROM attendance_calendar
		WHERE CALENDAR_ID='" . (int) $calendar_id . "'
		AND SCHOOL_DATE='" . $date . "'
		AND BLOCK='" . $block . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND MINUTES!='0'" );
}

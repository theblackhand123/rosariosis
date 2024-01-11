<?php
/**
 * When calculating Daily Attendance, mark student Absent for the whole day
 * if he has been reported Absent (state code) for at least one Course Period
 *
 * @package Absent for the Day on First Absence plugin
 */

/**
 * When calculating Daily Attendance, mark student Absent for the whole day
 * if he has been reported Absent (state code) for at least one Course Period
 *
 * Filter &$total_present var passed by ref
 * Set $total_present to 0 if $total_absent > 0
 *
 * @uses Attendance/includes/UpdateAttendanceDaily.fnc.php|total_minutes_present Action hook.
 *
 * @return bool
 */
function AbsentForTheDayOnFirstAbsenceTotalMinutesPresent( $tag, &$total_present, $total_minutes, $total_absent, $total_half )
{
	if ( ! $total_absent
		|| $total_absent < 0 )
	{
		return false;
	}

	$total_present = 0;

	return true;
}

add_action(
	'Attendance/includes/UpdateAttendanceDaily.fnc.php|total_minutes_present',
	'AbsentForTheDayOnFirstAbsenceTotalMinutesPresent',
	5
);

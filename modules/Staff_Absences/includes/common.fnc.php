<?php
/**
 * Staff Absences common functions
 *
 * @package Staff Absences module
 */

/**
 * Get Staff Absence
 *
 * @param int $absence_id Absence ID.
 *
 * @return array Staff Absence.
 */
function StaffAbsenceGet( $absence_id )
{
	if ( (string) (int) $absence_id != $absence_id
		|| $absence_id < 1 )
	{
		return [];
	}

	$absence_RET = DBGet( "SELECT *
		FROM staff_absences
		WHERE ID='" . (int) $absence_id . "'
		AND SYEAR='" . UserSyear() . "'" );

	return issetVal( $absence_RET[1], [] );
}

/**
 * Make Staff Absence Date
 *
 * @param string $value  Staff Absence Start or End Date (tiemstamp).
 * @param string $column DB column name.
 *
 * @return string Staff Absence Date. For exmaple: "June 12 2020 - Morning".
 */
function StaffAbsenceMakeDate( $value, $column = 'START_DATE' )
{
	if ( ! $value )
	{
		return '';
	}

	$date = ProperDate( substr( $value, 0, 10 ) );

	$am_pm = substr( $value, 11, 2 ) < 12 ? // Hour < 12:00 is morning.
		dgettext( 'Staff_Absences', 'Morning' ) : dgettext( 'Staff_Absences', 'Afternoon' );

	return $date . ' &mdash; ' . $am_pm;
}

/**
 * Make Staff Name
 *
 * @param string $value  Staff ID.
 * @param string $column DB column name.
 *
 * @return string Staff full name + Photo tip message if $column != 'FULL_NAME'.
 */
function StaffAbsenceMakeName( $value, $column = 'FULL_NAME' )
{
	require_once 'ProgramFunctions/TipMessage.fnc.php';

	if ( ! $value )
	{
		return '';
	}

	$staff_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME,ROLLOVER_ID
		FROM staff
		WHERE STAFF_ID='" . (int) $value . "'
		AND SYEAR='" . UserSyear() . "'" );

	$full_name = $staff_RET[1]['FULL_NAME'];

	if ( $column === 'FULL_NAME' )
	{
		return $full_name;
	}

	$rollover_id = $staff_RET[1]['ROLLOVER_ID'];

	return MakeUserPhotoTipMessage( $value, $full_name, $rollover_id );
}


/**
 * Staff Absences DeCodeds
 * Decode codeds / exports type (custom staff absences) fields values.
 *
 * DBGet() callback function
 *
 * @uses DeCodeds() function.
 *
 * @param string $value  Value.
 * @param string $column Column.
 */
function StaffAbsencesDeCodeds( $value, $column )
{
	return DeCodeds( $value, $column, 'STAFF_ABSENCE' );
}

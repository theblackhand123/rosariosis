<?php
/**
 * Staff Absences Dashboard module
 *
 * @package RosarioSIS
 * @subpackage modules
 */

/**
 * Dashboard Default Staff Absences module
 *
 * @since 4.0
 *
 * @param  boolean $export   Exporting data, defaults to false. Optional.
 * @return string  Dashboard module HTML.
 */
function DashboardDefaultStaffAbsences()
{
	require_once 'ProgramFunctions/DashboardModule.fnc.php';

	$profile = User( 'PROFILE' );

	$data = '';

	if ( $profile === 'admin' )
	{
		$data = DashboardStaffAbsencesAdmin();
	}

	return DashboardModule( 'Staff_Absences', $data );
}

if ( ! function_exists( 'DashboardStaffAbsencesAdmin' ) )
{
	/**
	 * Dashboard data
	 * Staff Absences module & admin profile
	 *
	 * @since 4.0
	 *
	 * @return array Dashboard data
	 */
	function DashboardStaffAbsencesAdmin()
	{
		global $DatabaseType;

		for ( $i = 0; $i < 7; $i++ )
		{
			$sql_days = $i . ' DAY';

			$absences_RET = DBGet( "SELECT COUNT(ID) AS ABSENT,
			(CURRENT_DATE + INTERVAL " .
			( $DatabaseType === 'mysql' ? $sql_days : "'" . $sql_days . "'" ) . ") AS DAY
			FROM staff_absences
			WHERE SYEAR='" . UserSyear() . "'
			AND (NOW() + INTERVAL " .
			( $DatabaseType === 'mysql' ? $sql_days : "'" . $sql_days . "'" ) . ")>=START_DATE
			AND (NOW() + INTERVAL " .
			( $DatabaseType === 'mysql' ? $sql_days : "'" . $sql_days . "'" ) . ")<=END_DATE" );

			if ( ! $i )
			{
				$absences_today = (int) $absences_RET[1]['ABSENT'];

				// Absences today.
				$absences_data = [
					_( 'Absences' ) => $absences_today,
				];
			}

			if ( $absences_RET[1]['ABSENT'] )
			{
				$proper_date = ProperDate( $absences_RET[1]['DAY'], 'short' );

				// Absences by day.
				$absences_data[$proper_date] = (int) $absences_RET[1]['ABSENT'];
			}
		}

		$data = [];

		if ( $absences_today
			|| count( $absences_data ) > 1 )
		{
			$data = $absences_data;
		}

		return $data;
	}
}

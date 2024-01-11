<?php
/**
 * Module Functions
 * (Loaded on each page)
 *
 * @package Staff Absences module
 */

if ( ! function_exists( 'DashboardStaff_Absences' ) )
{
	/**
	 * Dashboard Staff Absences module
	 *
	 * @since 4.0
	 *
	 * @return string Dashboard module HTML.
	 */
	function DashboardStaff_Absences()
	{
		require_once 'modules/Staff_Absences/includes/Dashboard.inc.php';

		return DashboardDefaultStaffAbsences();
	}
}

/**
 * Staff Absences module Portal Alerts
 * Staff Absences new absences note.
 *
 * @since 2.9
 *
 * @uses misc/Portal.php|portal_alerts hook
 *
 * @return true if new absences note, else false.
 */
function StaffAbsencesPortalAlerts()
{
	global $note;

	if ( User( 'PROFILE' ) !== 'admin'
		|| ! AllowUse( 'Staff_Absences/Absences.php' )
		|| ! $_SESSION['LAST_LOGIN'] )
	{
		return false;
	}

	$last_login_date = mb_substr( $_SESSION['LAST_LOGIN'], 0, 10 );

	$absences_RET = DBGet( "SELECT COUNT(ID) AS COUNT,
		MIN(START_DATE) AS BEGIN_DATE,
		MAX(END_DATE) AS ENDING_DATE
		FROM staff_absences
		WHERE CREATED_AT BETWEEN '" . $_SESSION['LAST_LOGIN'] . "' AND CURRENT_TIMESTAMP
		AND CREATED_BY<>'" . User( 'STAFF_ID' ) . "'" );

	if ( ! empty( $absences_RET[1]['COUNT'] ) )
	{
		$message = '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Staff_Absences/Absences.php&start=' .
				mb_substr( $absences_RET[1]['BEGIN_DATE'], 0, 10 ) .
				'&end=' . mb_substr( $absences_RET[1]['ENDING_DATE'], 0, 10 ) ) :
			_myURLEncode( 'Modules.php?modname=Staff_Absences/Absences.php&start=' .
				mb_substr( $absences_RET[1]['BEGIN_DATE'], 0, 10 ) .
				'&end=' . mb_substr( $absences_RET[1]['ENDING_DATE'], 0, 10 ) ) ) . '">
			<span class="module-icon Staff_Absences"></span> ';

		$message .= sprintf(
			ngettext( '%d new staff absence', '%d new staff absences', $absences_RET[1]['COUNT'] ),
			$absences_RET[1]['COUNT']
		);

		$message .= '</a>';

		$note[] = $message;

		return true;
	}

	return false;
}

add_action( 'misc/Portal.php|portal_alerts', 'StaffAbsencesPortalAlerts', 0 );

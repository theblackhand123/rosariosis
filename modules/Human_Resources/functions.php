<?php
/**
 * Functions
 *
 * @package  Human Resources module
 */

// Rollover Human Resources Qualifications to next school year.
add_action( 'School_Setup/Rollover.php|rollover_after', 'HumanResourcesQualificationsRollover' );

/**
 * Human Resources Qualifications Rollover
 */
function HumanResourcesQualificationsRollover()
{
	if ( ! in_array( 'STAFF', array_keys( $_REQUEST['tables'] ) ) // Compat with RosarioSIS 9.3-.
		&& ! in_array( 'staff', array_keys( $_REQUEST['tables'] ) ) )
	{
		// Not Rolling staff, fail.
		return false;
	}

	$next_syear = UserSyear() + 1;

	// HUMAN RESOURCES ROLLOVER.
	$delete_sql = "DELETE FROM human_resources_skills
		WHERE STAFF_ID IN(SELECT STAFF_ID FROM staff WHERE SYEAR='" . $next_syear . "');";

	$delete_sql = "DELETE FROM human_resources_education
		WHERE STAFF_ID IN(SELECT STAFF_ID FROM staff WHERE SYEAR='" . $next_syear . "');";

	$delete_sql = "DELETE FROM human_resources_certifications
		WHERE STAFF_ID IN(SELECT STAFF_ID FROM staff WHERE SYEAR='" . $next_syear . "');";

	$delete_sql = "DELETE FROM human_resources_languages
		WHERE STAFF_ID IN(SELECT STAFF_ID FROM staff WHERE SYEAR='" . $next_syear . "');";

	DBQuery( $delete_sql );

	DBQuery( "INSERT INTO human_resources_skills (STAFF_ID,TITLE,DESCRIPTION)
		SELECT s.STAFF_ID,hr.TITLE,hr.DESCRIPTION
		FROM staff s,human_resources_skills hr
		WHERE hr.STAFF_ID=s.ROLLOVER_ID
		AND s.SYEAR='" . $next_syear . "'" );

	DBQuery( "INSERT INTO human_resources_education (STAFF_ID,QUALIFICATION,INSTITUTE,START_DATE,COMPLETED_ON)
		SELECT s.STAFF_ID,hr.QUALIFICATION,hr.INSTITUTE,hr.START_DATE,hr.COMPLETED_ON
		FROM staff s,human_resources_education hr
		WHERE hr.STAFF_ID=s.ROLLOVER_ID
		AND s.SYEAR='" . $next_syear . "'" );

	DBQuery( "INSERT INTO human_resources_certifications (STAFF_ID,TITLE,INSTITUTE,GRANTED_ON,VALID_THROUGH)
		SELECT s.STAFF_ID,hr.TITLE,hr.INSTITUTE,hr.GRANTED_ON,hr.VALID_THROUGH
		FROM staff s,human_resources_certifications hr
		WHERE hr.STAFF_ID=s.ROLLOVER_ID
		AND s.SYEAR='" . $next_syear . "'" );

	DBQuery( "INSERT INTO human_resources_languages (STAFF_ID,TITLE,READING,SPEAKING,WRITING,UNDERSTANDING)
		SELECT s.STAFF_ID,hr.TITLE,hr.READING,hr.SPEAKING,hr.WRITING,hr.UNDERSTANDING
		FROM staff s,human_resources_languages hr
		WHERE hr.STAFF_ID=s.ROLLOVER_ID
		AND s.SYEAR='" . $next_syear . "'" );

	return true;
}


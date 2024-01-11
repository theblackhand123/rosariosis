<?php
/**
 * Functions
 *
 * @package Billing Elements module
 */


// Rollover Billing Elements to next school year.
add_action( 'School_Setup/Rollover.php|rollover_after', 'BillingElementsRollover' );

/**
 * Billing Elements Rollover
 *
 * Deletes any Student Element referencing next school year Elements.
 */
function BillingElementsRollover()
{
	if ( ! in_array( 'SCHOOLS', array_keys( $_REQUEST['tables'] ) ) // Compat with RosarioSIS 9.3-.
		&& ! in_array( 'schools', array_keys( $_REQUEST['tables'] ) ) )
	{
		// Not Rolling schools, fail.
		return false;
	}

	$next_syear = UserSyear() + 1;

	// BILLING ELEMENTS ROLLOVER.
	$delete_sql = "DELETE FROM billing_student_elements
		WHERE ELEMENT_ID IN (SELECT ID FROM billing_elements
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . $next_syear . "');";

	$delete_sql .= "DELETE FROM billing_elements
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . $next_syear . "';";

	DBQuery( $delete_sql );

	// Only roll Elements with rollover='Y'.
	DBQuery( "INSERT INTO billing_elements (CATEGORY_ID,TITLE,REF,AMOUNT,
		DESCRIPTION,GRADE_LEVELS,COURSE_PERIOD_ID,ROLLOVER,SCHOOL_ID,SYEAR)
		SELECT CATEGORY_ID,TITLE,REF,AMOUNT,DESCRIPTION,GRADE_LEVELS,
		(SELECT COURSE_PERIOD_ID
			FROM course_periods
			WHERE be.COURSE_PERIOD_ID IS NOT NULL
			AND SYEAR='" . $next_syear . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND ROLLOVER_ID=be.COURSE_PERIOD_ID
			LIMIT 1),
		ROLLOVER,SCHOOL_ID,'" . $next_syear . "'
		FROM billing_elements be
		WHERE be.SYEAR='" . UserSyear() . "'
		AND be.SCHOOL_ID='" . UserSchool() . "'
		AND be.ROLLOVER='Y'" );

	return true;
}


add_action( 'Billing_Elements/Elements.php|purchase_element', 'BillingElementPurchaseAction' );

/**
 * Element Purchase Action
 * - Enroll student in Moodle Course.
 * - Enroll student in Iomad Course.
 *
 * @return bool
 */
function BillingElementPurchaseAction()
{
	global $RosarioPlugins;

	if ( empty( $RosarioPlugins['Moodle'] ) )
	{
		return false;
	}

	require_once 'modules/Billing_Elements/includes/common.fnc.php';
	require_once 'modules/Billing_Elements/includes/Elements.fnc.php';
	require_once 'plugins/Moodle/client.php';

	$return = BillingElementMoodleCourseEnroll( $_REQUEST['id'], UserStudentID() );

	if ( empty( $RosarioPlugins['Iomad'] )
		|| ! $return )
	{
		return $return;
	}

	return BillingElementIomadCourseEnroll( $_REQUEST['id'], UserStudentID() );
}

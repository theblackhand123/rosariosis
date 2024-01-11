<?php
/**
 * Relatives functions
 *
 * @package Relatives plugin
 */

function GetSiblingsSearchExtra( $student_id )
{
	$extra = [];

	$extra['new'] = true;

	$extra['SELECT'] = ',s.STUDENT_ID AS SCHEDULE_LINK';

	/**
	 * SQL result as comma separated list
	 *
	 * @deprecated since RosarioSIS 10.8
	 *
	 * @since RosarioSIS 10.0 Add MySQL support
	 * @link https://dev.mysql.com/doc/refman/5.7/en/aggregate-functions.html#function_group-concat
	 *
	 * @param string $column    SQL column.
	 * @param string $separator List separator, default to comma.
	 *
	 * @return string MySQL or PostgreSQL function
	 */
	$sql_comma_separated_result = function( $column, $separator = ',' )
	{
		global $DatabaseType;

		if ( $DatabaseType === 'mysql' )
		{
			return "GROUP_CONCAT(" . $column . " SEPARATOR '" . DBEscapeString( $separator ) . "')";
		}

		return "ARRAY_TO_STRING(ARRAY_AGG(" . $column . "), '" . DBEscapeString( $separator ) . "')";
	};

	if ( function_exists( 'DBSQLCommaSeparatedResult' ) )
	{
		// @since RosarioSIS 10.8
		$sql_comma_separated_result = 'DBSQLCommaSeparatedResult';
	}

	// Get Parents ID.
	$parents_list = DBGetOne( "SELECT " . $sql_comma_separated_result( 'su.STAFF_ID' ) . "
			FROM students_join_users su,staff st
			WHERE su.STUDENT_ID='" . (int) $student_id . "'
			AND st.STAFF_ID=su.STAFF_ID
			AND st.SYEAR='" . UserSyear() . "'" );

	$extra['WHERE'] = " AND s.STUDENT_ID IN (SELECT STUDENT_ID
		FROM students_join_users WHERE STAFF_ID IN(" . ( $parents_list ? $parents_list : '0' ) . ")
		AND STUDENT_ID<>'" . (int) $student_id . "')";

	$extra['columns_after'] = [ 'SCHEDULE_LINK' => _( 'Schedule' ) ];

	$extra['functions'] = [ 'SCHEDULE_LINK' => '_makeSiblingScheduleLink' ];

	$extra['singular'] = dgettext( 'Relatives', 'Sibling' );
	$extra['plural'] = dgettext( 'Relatives', 'Siblings' );

	$extra['Redirect'] = false;

	return $extra;
}

function _makeSiblingScheduleLink( $value, $column )
{
	if ( ! $value || ! AllowUse( 'Scheduling/Schedule.php' ) )
	{
		return '';
	}

	return '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( 'Modules.php?modname=Scheduling/Schedule.php&student_id=' . $value ) :
		_myURLEncode( 'Modules.php?modname=Scheduling/Schedule.php&student_id=' . $value ) ) . '">' .
		_( 'Schedule' ) . '</a>';
}

function GetParentsSearchExtra( $student_id )
{
	$extra = [];

	$extra['new'] = true;

	$extra['WHERE'] = " AND s.STAFF_ID IN (SELECT st.STAFF_ID
		FROM students_join_users su,staff st
		WHERE su.STUDENT_ID='" . (int) $student_id . "'
		AND st.STAFF_ID=su.STAFF_ID
		AND st.SYEAR='" . UserSyear() . "')";

	$extra['profile'] = 'parent';

	$extra['Redirect'] = false;

	$extra['options'] = [
		'search' => false,
		'save' => false,
	];

	// Fix link.
	$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=Users/User.php';

	return $extra;
}

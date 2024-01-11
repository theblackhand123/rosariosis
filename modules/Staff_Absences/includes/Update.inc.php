<?php
/**
 * Update database
 *
 * @package Staff Absences module
 */

// @since RosarioSIS 9.3 Add MySQL support
$staff_absence_course_periods_table_exists = DBGetOne( "SELECT 1
	FROM information_schema.tables
	WHERE table_schema=" . ( ! empty( $DatabaseType ) && $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
	AND table_name='staff_absence_course_periods';" );

if ( ! $staff_absence_course_periods_table_exists
	&& file_exists( 'modules/Staff_Absences/update_2.0.sql' ) )
{
	// SQL Update to 2.0.
	$update_sql = file_get_contents( 'modules/Staff_Absences/update_2.0.sql' );

	db_query( $update_sql );
}

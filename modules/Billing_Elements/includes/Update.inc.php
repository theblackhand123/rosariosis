<?php
/**
 * Update database
 *
 * @package Slovenian Class Diary module
 */

global $DatabaseType;

// @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
$grade_levels_column_exists = DBGetOne( "SELECT 1
	FROM information_schema.columns
	WHERE table_schema=" . ( $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
	AND table_name='billing_elements'
	AND column_name='grade_levels';" );

// Same file for MySQL & PostgreSQL
$update_sql_file = ( ! empty( $DatabaseType ) && $DatabaseType === 'mysql' ?
	'modules/Billing_Elements/update11.sql' :
	'modules/Billing_Elements/update11.sql' );

if ( ! $grade_levels_column_exists
	&& file_exists( $update_sql_file ) )
{
	// SQL Update to 11.0.
	$update_sql = file_get_contents( $update_sql_file );

	db_query( $update_sql );
}

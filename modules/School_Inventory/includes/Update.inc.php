<?php
/**
 * Update database
 *
 * @package School Inventory module
 */

// @since RosarioSIS 9.3 Add MySQL support
$school_inventory_snapshots_table_exists = DBGetOne( "SELECT 1
	FROM information_schema.tables
	WHERE table_schema=" . ( ! empty( $DatabaseType ) && $DatabaseType === 'mysql' ? 'DATABASE()' : 'CURRENT_SCHEMA()' ) . "
	AND table_name='school_inventory_snapshots';" );

if ( ! $school_inventory_snapshots_table_exists
	&& file_exists( 'modules/School_Inventory/update2.0.sql' ) )
{
	// SQL Update to 2.0.
	$update_sql = file_get_contents( 'modules/School_Inventory/update2.0.sql' );

	db_query( $update_sql );
}

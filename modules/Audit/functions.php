<?php
/**
 * Audit functions
 *
 * @package Audit
 */

/**
 * DB Query action: save SQL query to later save it in the Audit log
 *
 * @global array $_ROSARIO['AuditLog']
 *
 * @param string $tag 'database.inc.php|dbquery_after' hook.
 * @param string $sql SQL query from DBGet function.
 *
 * @return boolean False if not INSERT, UPATE or DELETE.
 */
function AuditLogDBQuery( $tag, $sql ) {

	global $_ROSARIO;

	if ( stripos( $sql, 'INSERT ' ) !== 0
		&& stripos( $sql, 'UPDATE ' ) !== 0
		&& stripos( $sql, 'DELETE ' ) !== 0 )
	{
		// Is SELECT.
		return false;
	}

	if ( empty( $_ROSARIO['AuditLog'] ) )
	{
		$_ROSARIO['AuditLog'] = [
			'INSERT' => [],
			'UPDATE' => [],
			'DELETE' => [],
		];
	}

	if ( stripos( $sql, 'INSERT ' ) === 0 )
	{
		$query_type = 'INSERT';

		$_ROSARIO['AuditLog']['INSERT'][] = $sql;
	}
	elseif ( stripos( $sql, 'UPDATE ' ) === 0 )
	{
		$query_type = 'UPDATE';

		$_ROSARIO['AuditLog']['UPDATE'][] = $sql;
	}
	elseif ( stripos( $sql, 'DELETE ' ) === 0 )
	{
		$query_type = 'DELETE';

		$_ROSARIO['AuditLog']['DELETE'][] = $sql;
	}

	return true;
}

add_action( 'database.inc.php|dbquery_after', 'AuditLogDBQuery', 2 );


/**
 * Warehouse Footer action: save the Audit Log SQL queries if any.
 *
 * @global array $_ROSARIO['AuditLog']
 *
 * @uses db_query and not DBQuery or we would end up in an infinite loop.
 *
 * @return False if no SQL queries to log.
 */
function AuditLogWarehouseFooterSave()
{
	global $_ROSARIO;

	if ( empty( $_ROSARIO['AuditLog'] )
		|| ! User( 'USERNAME' ) )
	{
		return false;
	}

	$sql_audit_log = '';

	foreach ( (array) $_ROSARIO['AuditLog'] as $query_type => $sql_queries )
	{
		if ( empty( $sql_queries ) )
		{
			continue;
		}

		$sql = implode( ";\n", $sql_queries );

		// Remove tabulations.
		$sql = str_replace( "\t", '', $sql );

		if ( ROSARIO_DEBUG )
		{
			echo '<!-- ' . print_r( $sql, true ) . ' -->';
		}

		$sql_audit_log .= "INSERT INTO audit_log(SYEAR,USERNAME,PROFILE,URL,QUERY_TYPE,DATA)
		VALUES('" . Config( 'SYEAR' ) . "','" . User( 'USERNAME' ) . "','" . User( 'PROFILE' ) . "','" .
		DBEscapeString( $_SERVER['REQUEST_URI'] ) . "','" . $query_type . "','" . DBEscapeString( $sql ) . "');";
	}

	if ( ! $sql_audit_log )
	{
		return false;
	}

	db_query( $sql_audit_log );

	return true;
}

add_action( 'Warehouse.php|footer', 'AuditLogWarehouseFooterSave', 0 );

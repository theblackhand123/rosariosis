<?php
/**
 * Insert into or Update DB functions
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Get DB INSERT SQL statement
 * Will skip columns with empty values ('', false, null)
 *
 * @example $sql = DBInsertSQL( 'config', [ 'CONFIG_VALUE' => $value, 'TITLE' => $item, 'SCHOOL_ID' => $school_id ] );
 *
 * @since 11.0
 *
 * @param string $table   DB table (unescaped).
 * @param array  $columns Columns (values escaped). Associative array, [ 'COLUMN' => 'value' ].
 *
 * @return string Empty if no values to insert, else SQL statement.
 */
function DBInsertSQL( $table, $columns )
{
	if ( ! $table
		|| ! $columns )
	{
		return '';
	}

	$sql = "INSERT INTO " . DBEscapeIdentifier( $table ) . " ";

	$fields = $values = '';

	$go = false;

	foreach ( (array) $columns as $column => $value )
	{
		if ( ! empty( $value ) || $value == '0' )
		{
			$fields .= DBEscapeIdentifier( $column ) . ',';
			$values .= "'" . $value . "',";
			$go = true;
		}
	}

	if ( ! $go )
	{
		return '';
	}

	$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ');';

	return $sql;
}

/**
 * INSERT into DB
 * Will skip columns with empty values ('', false, null)
 *
 * @example DBInsert( 'config', [ 'CONFIG_VALUE' => $value, 'TITLE' => $item, 'SCHOOL_ID' => $school_id ] );
 * @example $id = DBInsert( 'staff', [ 'SYEAR' => UserSyear(), 'PROFILE' => 'parent', ... ], 'id' );
 *
 * @since 11.0
 *
 * @uses DBInsertSQL()
 * @uses DBLastInsertID()
 *
 * @param string $table   DB table (unescaped).
 * @param array  $columns Columns (values escaped). Associative array, [ 'COLUMN' => 'value' ].
 * @param string $return  'true' will return true, 'id' will return DBLastInsertID(). Defaults to 'true'.
 *
 * @return bool|int True or last inserted ID.
 */
function DBInsert( $table, $columns, $return = 'true' )
{
	$sql = DBInsertSQL( $table, $columns );

	if ( ! $sql )
	{
		return false;
	}

	DBQuery( $sql );

	if ( $return === 'id' )
	{
		return DBLastInsertID();
	}

	return true;
}

/**
 * Get DB UPDATE SQL statement
 * Will set columns with empty values ('', false, null) to NULL
 *
 * @example $sql = DBUpdateSQL( 'config', [ 'CONFIG_VALUE' => $value ], [ 'TITLE' => $item, 'SCHOOL_ID' => (int) $school_id ] );
 *
 * @since 11.0
 *
 * @param string $table         DB table (unescaped).
 * @param array  $columns       Columns (values escaped). Associative array, [ 'COLUMN' => 'value' ].
 * @param array  $where_columns WHERE part columns. Associative array, [ 'COLUMN' => 'value' ].
 *
 * @return string Empty if no values to update, else SQL statement.
 */
function DBUpdateSQL( $table, $columns, $where_columns )
{
	if ( ! $table
		|| ! $columns
		|| ! $where_columns )
	{
		return '';
	}

	$sql = "UPDATE " . DBEscapeIdentifier( $table ) . " SET ";

	$fields = $values = '';

	foreach ( (array) $columns as $column => $value )
	{
		if ( ! empty( $value ) || $value == '0' )
		{
			$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
		}
		else
		{
			$sql .= DBEscapeIdentifier( $column ) . "=NULL,";
		}
	}

	$sql = mb_substr( $sql, 0, -1 ) . " WHERE ";

	foreach ( (array) $where_columns as $column => $value )
	{
		$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "' AND ";
	}

	$sql = mb_substr( $sql, 0, -5 ) . ";";

	return $sql;
}

/**
 * UPDATE into DB
 * Will set columns with empty values ('', false, null) to NULL
 *
 * @example DBUpdate( 'config', [ 'CONFIG_VALUE' => $value ], [ 'TITLE' => $item, 'SCHOOL_ID' => (int) $school_id ] );
 *
 * @since 11.0
 *
 * @uses DBUpdateSQL()
 *
 * @param string $table         DB table (unescaped).
 * @param array  $columns       Columns (values escaped). Associative array, [ 'COLUMN' => 'value' ].
 * @param array  $where_columns WHERE part columns. Associative array, [ 'COLUMN' => 'value' ].
 *
 * @return bool  False if no SQL else true.
 */
function DBUpdate( $table, $columns, $where_columns )
{
	$sql = DBUpdateSQL( $table, $columns, $where_columns );

	if ( ! $sql )
	{
		return false;
	}

	DBQuery( $sql );

	return true;
}

/**
 * INSERT INTO or UPDATE DB
 *
 * @example DBUpsert( 'config', [ 'CONFIG_VALUE' => $value ], [ 'TITLE' => $item, 'SCHOOL_ID' => (int) $school_id ], $mode );
 *
 * @since 11.0
 *
 * @uses DBInsert()
 * @uses DBUpdate()
 *
 * @param string $table         DB table (unescaped).
 * @param array  $columns       Columns (values escaped). Associative array, [ 'COLUMN' => 'value' ].
 * @param array  $where_columns WHERE part columns. If INSERT, will be added to $columns. Associative array, [ 'COLUMN' => 'value' ].
 * @param string $mode          'insert' will INSERT, 'insert_id' will INSERT & return DBLastInsertID(), 'update' will UPDATE.
 *
 * @return bool|int True or last inserted ID.
 */
function DBUpsert( $table, $columns, $where_columns, $mode )
{
	if ( $mode === 'insert' )
	{
		return DBInsert( $table, (array) $columns + (array) $where_columns );
	}

	if ( $mode === 'insert_id' )
	{
		return DBInsert( $table, (array) $columns + (array) $where_columns, 'id' );
	}

	if ( $mode === 'update' )
	{
		return DBUpdate( $table, $columns, $where_columns );
	}

	return false;
}

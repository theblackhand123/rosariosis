<?php
/**
 * Absence Fields
 *
 * @package Staff Absences module
 */

require_once 'ProgramFunctions/Fields.fnc.php';

DrawHeader( ProgramTitle() );

$_REQUEST['id'] = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false;

if ( isset( $_POST['tables'] )
	&& is_array( $_POST['tables'] )
	&& AllowEdit() )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'staff_absence_fields' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		// FJ fix SQL bug invalid sort order.
		if ( ( empty( $columns['SORT_ORDER'] )
				|| is_numeric( $columns['SORT_ORDER'] ) )
			&& ( empty( $columns['COLUMNS'] )
				|| is_numeric( $columns['COLUMNS'] ) ) )
		{
			if ( isset( $columns['SELECT_OPTIONS'] )
				&& $columns['SELECT_OPTIONS'] )
			{
				// @since 6.0 Trim select Options.
				$columns['SELECT_OPTIONS'] = trim( $columns['SELECT_OPTIONS'] );
			}

			// FJ added SQL constraint TITLE is not null.
			if ( ! isset( $columns['TITLE'] )
				|| ! empty( $columns['TITLE'] ) )
			{
				// Update Field.
				if ( $id !== 'new' )
				{
					$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";

					$go = true;
				}
				// New Field.
				else
				{
					$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

					// New Field.
					if ( mb_strtolower( $table ) === 'staff_absence_fields' )
					{
						$fields = '';

						$values = '';
					}

					$go = false;

					foreach ( (array) $columns as $column => $value )
					{
						if ( ! empty( $value )
							|| $value == '0' )
						{
							$fields .= DBEscapeIdentifier( $column ) . ',';

							$values .= "'" . $value . "',";

							$go = true;
						}
					}
					$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';
				}

				if ( $go )
				{
					DBQuery( $sql );

					if ( $id === 'new' )
					{
						if ( mb_strtolower( $table ) === 'staff_absence_fields' )
						{
							if ( function_exists( 'DBLastInsertID' ) )
							{
								$_REQUEST['id'] = DBLastInsertID();

								AddDBField( 'staff_absences', $_REQUEST['id'], $columns['TYPE'] );
							}
							else
							{
								// @deprecated since RosarioSIS 9.2.1.
								$_REQUEST['id'] = DBGetOne( "SELECT LASTVAL();" );

								// @deprecated since RosarioSIS 9.2.1.
								_StaffAbsencesAddDBField( 'staff_absences', $_REQUEST['id'], $columns['TYPE'] );
							}
						}
					}
				}
			}
			else
				$error[] = _( 'Please fill in the required fields' );
		}
		else
			$error[] = _( 'Please enter valid Numeric data.' );
	}

	// Unset tables & redirect URL.
	RedirectURL( 'tables' );
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Staff_Absences', 'Absence Field' ) ) )
		{
			DeleteDBField( 'staff_absences', $_REQUEST['id'] );

			$_REQUEST['modfunc'] = false;

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$RET = [];

	// ADDING & EDITING FORM.
	if ( $_REQUEST['id']
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = DBGet( "SELECT ID,(SELECT NULL) AS CATEGORY_ID,TITLE,TYPE,
			SELECT_OPTIONS,DEFAULT_SELECTION,SORT_ORDER,REQUIRED
			FROM staff_absence_fields
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		$RET = $RET[1];

		$title = ParseMLField( $RET['TITLE'] );
	}
	elseif ( $_REQUEST['id'] === 'new' )
	{
		$title = dgettext( 'Staff_Absences', 'New Absence Field' );

		$RET['ID'] = 'new';
	}

	echo GetFieldsForm(
		'staff_absence',
		$title,
		$RET,
		[]
	);

	// DISPLAY THE MENU.
	// FIELDS.
	$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SORT_ORDER
		FROM staff_absence_fields
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE", [ 'TYPE' => 'MakeFieldType' ] );

	echo '<div class="st">';

	FieldsMenuOutput( $fields_RET, $_REQUEST['id'], false );

	echo '</div>';
}

/**
 * Add Field to DB Table
 * And create INDEX
 *
 * @deprecated since RosarioSIS 9.2.1.
 *
 * @since 4.6 Add Files type
 * @since 5.0 SQL fix Change index suffix from '_IND' to '_IDX' to avoid collision.
 * @since 9.2.1 Change $sequence param to $field_id, adapted for use with DBLastInsertID()
 *
 * @example _StaffAbsencesAddDBField( 'SCHOOLS', $school_fields_id, $columns['TYPE'] );
 *
 * @param string  $table    DB Table name.
 * @param int     $field_id Field ID (or DB Sequence name: deprecated).
 * @param string  $type     Field Type: radio|text|exports|select|autos|edits|codeds|multiple|numeric|date|textarea|files.
 *
 * @return string Field ID or empty string
 */
function _StaffAbsencesAddDBField( $table, $field_id, $type )
{
	if ( ! AllowEdit()
		|| empty( $table )
		|| empty( $type ) )
	{
		return '';
	}

	if ( (string) (int) $field_id == $field_id )
	{
		$id = $field_id;
	}
	else
	{
		// Field ID is actually a DB Sequence name (old param).
		// So get ID from sequence for compatibility with old signature.
		$id = DBSeqNextID( $field_id );
	}

	if ( empty( $id ) )
	{
		return '';
	}

	$create_index = true;

	switch ( $type )
	{
		case 'radio':

			$sql_type = 'VARCHAR(1)';

		break;

		case 'multiple':
		case 'text':
		case 'exports':
		case 'select':
		case 'autos':

			$sql_type = 'TEXT';

		break;

		case 'numeric':

			$sql_type = 'NUMERIC(20,2)';
		break;


		case 'date':

			$sql_type = 'DATE';

		break;

		case 'textarea':
		case 'files':

			$sql_type = 'TEXT';

			$create_index = false;

		break;
	}

	DBQuery( 'ALTER TABLE ' . DBEscapeIdentifier( $table ) . ' ADD ' .
		DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) . ' ' . $sql_type );

	if ( $create_index )
	{
		// @since 5.0 SQL fix Change index suffix from '_IND' to '_IDX' to avoid collision.
		$index_name = $table === 'students' ? 'CUSTOM_IND' : $table . '_IDX';

		DBQuery( 'CREATE INDEX ' . DBEscapeIdentifier( $index_name . (int) $id ) .
			' ON ' . DBEscapeIdentifier( $table ) .
			' (' . DBEscapeIdentifier( 'CUSTOM_' . (int) $id ) . ')' );
	}

	return $id;
}

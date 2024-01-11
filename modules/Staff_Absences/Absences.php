<?php
/**
 * Staff Absences
 *
 * @package Staff Absences module
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

require_once 'modules/Staff_Absences/includes/common.fnc.php';
require_once 'modules/Staff_Absences/includes/StaffAbsences.fnc.php';

// @deprecated since 2.0.
require_once 'modules/Staff_Absences/includes/Update.inc.php';

DrawHeader( ProgramTitle() );

$_REQUEST['id'] = issetVal( $_REQUEST['id'] );

// Set start date.
$start_date = RequestedDate( 'start', ( empty( $_REQUEST['start'] ) ? DBDate() : $_REQUEST['start'] ) );

// Set end date.
$end_date = RequestedDate( 'end', issetVal( $_REQUEST['end'] ) );

// Add eventual Dates to $_REQUEST['tables'].
AddRequestedDates( 'tables', 'post' );

if ( AllowEdit()
	&& $_REQUEST['modfunc'] === 'save' )
{
	$id = $_REQUEST['id'];

	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'staff_absences' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	if ( ! empty( $_REQUEST['tables'] ) )
	{
		$_REQUEST['tables'][ $id ] = FilterCustomFieldsMarkdown( 'staff_absence_fields', 'tables', $id );

		foreach ( (array) $_REQUEST['tables'] as $id => $columns )
		{
			// FJ added SQL constraint START_DATE, END_DATE is not null.
			if ( ( ! isset( $columns['START_DATE'] )
					|| ! empty( $columns['START_DATE'] ) )
				&& ( ! isset( $columns['END_DATE'] )
					|| ! empty( $columns['END_DATE'] ) ) )
			{
				if ( isset( $columns['START_DATE'] ) )
				{
					// Add Time to Date: Morning starts at midnight, afternoon starts at noon.
					$columns['START_DATE'] .= $columns['START_DATE_AM_PM'] === 'AM' ?
						' 00:00:00' : ' 12:00:00';

					unset( $columns['START_DATE_AM_PM'] );
				}

				if ( isset( $columns['END_DATE'] ) )
				{
					// Add Time to Date: Morning ends before noon, afternoon ends before midnight.
					$columns['END_DATE'] .= $columns['END_DATE_AM_PM'] === 'AM' ?
						' 11:59:59' : ' 23:59:59';

					unset( $columns['END_DATE_AM_PM'] );
				}

				// Update Absence.
				$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

				$fields_RET = DBGet( "SELECT ID,TYPE
					FROM staff_absence_fields
					ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );

				$go = false;

				foreach ( (array) $columns as $column => $value )
				{
					if ( isset( $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] )
						&& $fields_RET[str_replace( 'CUSTOM_', '', $column )][1]['TYPE'] == 'numeric'
						&& $value != ''
						&& ! is_numeric( $value ) )
					{
						$error[] = _( 'Please enter valid Numeric data.' );
						continue;
					}

					if ( is_array( $value ) )
					{
						// Select Multiple from Options field type format.
						$value = implode( '||', $value ) ? '||' . implode( '||', $value ) : '';
					}

					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";

					$go = true;
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";

				if ( $go )
				{
					DBQuery( $sql );
				}
			}
			else
			{
				$error[] = _( 'Please fill in the required fields' );
			}
		}
	}

	$uploaded = FilesUploadUpdate(
		'staff_absences',
		'tables' . $id,
		$FileUploadsPath . 'Staff_Absences/' . (int) $id . '/'
	);

	// Remove existing Cancelled Course Periods first.
	DBQuery( "DELETE FROM staff_absence_course_periods
		WHERE STAFF_ABSENCE_ID='" . (int) $id . "'" );

	if ( isset( $_REQUEST['cancelledcp'] ) )
	{
		foreach ( $_REQUEST['cancelledcp'] as $course_period_id )
		{
			if ( ! $course_period_id )
			{
				// Fix regression since RosarioSIS 10.8.4, skip hidden empty input
				continue;
			}

			// SQL insert Cancelled Course Period.
			DBQuery( "INSERT INTO staff_absence_course_periods (STAFF_ABSENCE_ID,COURSE_PERIOD_ID)
				VALUES('" . $id . "','" . $course_period_id ."')" );
		}
	}

	// Unset tables, modfunc & redirect URL.
	RedirectURL( [ 'tables', 'modfunc' ] );
}

// Delete Absence.
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( dgettext( 'Staff_Absences', 'Absence' ) ) )
		{
			$delete_sql = "DELETE FROM staff_absence_course_periods
				WHERE STAFF_ABSENCE_ID='" . (int) $_REQUEST['id'] . "';";

			$delete_sql .= "DELETE FROM staff_absences
				WHERE ID='" . (int) $_REQUEST['id'] . "'
				AND SYEAR='" . UserSyear() . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
}


if ( $_REQUEST['modfunc'] === 'remove_file'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'File' ) ) )
	{
		$column = DBEscapeIdentifier( 'CUSTOM_' . $_REQUEST['field_id'] );

		// Security: sanitize filename with no_accents().
		$filename = no_accents( $_GET['filename'] );

		$file = $FileUploadsPath . 'Staff_Absences/' . $_REQUEST['id'] . '/' . $filename;

		DBQuery( "UPDATE staff_absences
			SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		if ( file_exists( $file ) )
		{
			unlink( $file );
		}

		// Unset modfunc, field_id, filename & redirect URL.
		RedirectURL( [ 'modfunc', 'field_id', 'filename' ] );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	// ADDING & EDITING FORM.
	if ( ! empty( $_REQUEST['id'] ) )
	{
		$RET = StaffAbsenceGet( $_REQUEST['id'] );

		$extra_fields = GetStaffAbsenceFields( $_REQUEST['id'] );

		echo StaffAbsenceGetForm(
			$RET,
			isset( $extra_fields ) ? $extra_fields : []
		);
	}
	else
	{
		$sql_where = " AND a.START_DATE>='" . $start_date . "'";

		if ( $end_date )
		{
			$sql_where .= " AND a.START_DATE<='" . $end_date . ' 23:59:59' . "'";
		}

		if ( User( 'PROFILE' ) !== 'admin' )
		{
			// Teacher: My Absences.
			$sql_where .= " AND a.STAFF_ID='" . User( 'STAFF_ID' ) . "'";
		}
		elseif ( User( 'SCHOOLS' )
			&& trim( User( 'SCHOOLS' ), ',' ) )
		{
			// Restrict Search All Schools to user schools.
			$sql_schools_like = explode( ',', trim( User( 'SCHOOLS' ), ',' ) );

			$sql_schools_like = implode( ",' IN s.SCHOOLS)>0 OR position(',", $sql_schools_like );

			$sql_schools_like = "position('," . $sql_schools_like . ",' IN s.SCHOOLS)>0";

			$sql_where .= " AND (s.SCHOOLS IS NULL OR " . $sql_schools_like . ") ";
		}

		$functions = [
			'FULL_NAME' => 'StaffAbsenceMakeName',
			'START_DATE' => 'StaffAbsenceMakeDate',
			'END_DATE' => 'StaffAbsenceMakeDate',
		];

		$LO_columns = [
			'FULL_NAME' => _( 'User' ),
			'STAFF_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
			'START_DATE' => _( 'Starts' ),
			'END_DATE' => _( 'Ends' ),
			'COUNT' => _( 'Days Absent' ),
		];

		if ( isset( $_REQUEST['expanded_view'] )
			&& $_REQUEST['expanded_view'] === 'true' )
		{
			// Expanded View: Add Staff Absence Fields.
			$fields_RET = DBGet( "SELECT ID,TITLE,TYPE
				FROM staff_absence_fields
				WHERE TYPE NOT IN('files','textarea')
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

			foreach ( (array) $fields_RET as $field )
			{
				$field_key = 'CUSTOM_' . $field['ID'];

				$functions[ $field_key ] = makeFieldTypeFunction( $field['TYPE'] );

				if ( $functions[ $field_key ] === 'DeCodeds' )
				{
					$functions[ $field_key ] = 'StaffAbsencesDeCodeds';
				}

				$LO_columns[ $field_key ] = ParseMLField( $field['TITLE'] );
			}
		}

		$sql_count = "SUM(ROUND(CAST((EXTRACT(EPOCH FROM (SELECT END_DATE - START_DATE
			FROM STAFF_ABSENCES
			WHERE ID=a.ID)) / 86400) AS DECIMAL), 1))";

		if ( $DatabaseType === 'mysql' )
		{
			// @since RosarioSIS 9.3 Add MySQL support
			$sql_count = "SUM(ROUND((TIMESTAMPDIFF(SECOND, (SELECT START_DATE
				FROM staff_absences
				WHERE ID=a.ID), (SELECT END_DATE
				FROM staff_absences
				WHERE ID=a.ID)) / 86400), 1))";
		}

		// ABSENCES.
		$absences_RET = DBGet( "SELECT a.*,a.STAFF_ID AS FULL_NAME,
			" . $sql_count . " AS COUNT
			FROM staff_absences a,staff s
			WHERE a.SYEAR='" . UserSyear() . "'
			AND s.SYEAR=a.SYEAR
			AND a.STAFF_ID=s.STAFF_ID" . $sql_where .
			" GROUP BY a.ID
			ORDER BY a.START_DATE",
			$functions
		);

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$_ROSARIO['allow_edit'] = true;
		}

		echo '<form action="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) :
			_myURLEncode( 'Modules.php?modname=' . $_REQUEST['modname'] ) ) . '" method="GET">';

		if ( ! isset( $_REQUEST['expanded_view'] )
			|| $_REQUEST['expanded_view'] !== 'true' )
		{
			$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'true' ] ) . '">' .
			_( 'Expanded View' ) . '</a>';
		}
		else
		{
			$expanded_view_header = '<a href="' . PreparePHP_SELF( $_REQUEST, [], [ 'expanded_view' => 'false' ] ) . '">' .
			_( 'Original View' ) . '</a>';
		}

		DrawHeader( $expanded_view_header );

		DrawHeader(
			_( 'From' ) . ' ' . DateInput( $start_date, 'start', '', false, false ) . ' - ' .
			_( 'To' ) . ' ' . DateInput( $end_date, 'end', '', false, true ) .
			Buttons( _( 'Go' ) )
		);

		echo '</form>';

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			$_ROSARIO['allow_edit'] = false;
		}

		StaffAbsencesListOutput( $absences_RET, $LO_columns );
	}
}

<?php
/**
 * Hostel
 *
 * @package Hostel module
 */

require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

require_once 'modules/Hostel/includes/common.fnc.php';
require_once 'modules/Hostel/includes/Hostel.fnc.php';

DrawHeader( ProgramTitle() );

$_REQUEST['building_id'] = issetVal( $_REQUEST['building_id'] );

$_REQUEST['id'] = issetVal( $_REQUEST['id'] );

if ( AllowEdit()
	&& $_REQUEST['modfunc'] === 'save' )
{
	$table = issetVal( $_REQUEST['table'] );

	if ( ! in_array( $table, [ 'hostel_buildings', 'hostel_rooms' ] ) )
	{
		// Security: SQL prevent INSERT or UPDATE on any table
		$table = '';

		$_REQUEST['tables'] = [];
	}

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	$id = ! empty( $_REQUEST['id'] ) ? $_REQUEST['id'] :  $_REQUEST['building_id'];

	if ( $id
		&& ! empty( $RosarioModules['Hostel_Premium'] ) )
	{
		$fields_table = $table === 'hostel_rooms' ? 'hostel_room_fields' : 'hostel_building_fields';

		$_REQUEST['tables'][ $id ] = FilterCustomFieldsMarkdown( $fields_table, 'tables', $id );

		$fields_RET = DBGet( "SELECT ID,TYPE
			FROM " . DBEscapeIdentifier( $fields_table ) . "
			ORDER BY SORT_ORDER IS NULL,SORT_ORDER", [], [ 'ID' ] );
	}

	foreach ( (array) $_REQUEST['tables'] as $id => $columns )
	{
		if ( isset( $columns['DESCRIPTION'] ) )
		{
			$columns['DESCRIPTION'] = DBEscapeString( SanitizeHTML( $_POST['tables'][ $id ]['DESCRIPTION'] ) );
		}

		// FJ fix SQL bug invalid sort order.
		if ( empty( $columns['SORT_ORDER'] )
			|| is_numeric( $columns['SORT_ORDER'] ) )
		{
			// FJ added SQL constraint TITLE is not null.
			if ( ( ! isset( $columns['TITLE'] )
					|| ! empty( $columns['TITLE'] ) )
				&& ( ! isset( $columns['CAPACITY'] )
					|| $columns['CAPACITY'] !== '' ) )
			{
				$go = false;

				// Update Room / Building.
				if ( $id !== 'new' )
				{
					if ( isset( $columns['BUILDING_ID'] )
						&& $columns['BUILDING_ID'] != $_REQUEST['building_id'] )
					{
						$_REQUEST['building_id'] = $columns['BUILDING_ID'];
					}

					$sql = 'UPDATE ' . DBEscapeIdentifier( $table ) . ' SET ';

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
				}
				// New Room / Building.
				else
				{
					$sql = 'INSERT INTO ' . DBEscapeIdentifier( $table ) . ' ';

					// New Room.
					if ( $table === 'hostel_rooms' )
					{
						if ( isset( $columns['BUILDING_ID'] ) )
						{
							$_REQUEST['building_id'] = $columns['BUILDING_ID'];

							unset( $columns['BUILDING_ID'] );
						}

						$fields = 'BUILDING_ID,';

						$values = "'" . $_REQUEST['building_id'] . "',";
					}
					// New Building.
					elseif ( $table === 'hostel_buildings' )
					{
						$fields = '';

						$values = '';
					}

					// School.
					/*$fields .= 'SCHOOL_ID,';

					$values .= "'" . UserSchool() . "',";*/

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
						if ( function_exists( 'DBLastInsertID' ) )
						{
							$new_id = DBLastInsertID();
						}
						else
						{
							// @deprecated since RosarioSIS 9.2.1.
							$new_id = DBGetOne( "SELECT LASTVAL();" );
						}

						if ( $table === 'hostel_rooms' )
						{
							$_REQUEST['id'] = $new_id;
						}
						elseif ( $table === 'hostel_buildings' )
						{
							$_REQUEST['building_id'] = $new_id;
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

	if ( $id )
	{
		$uploaded = FilesUploadUpdate(
			$table,
			'tables' . $id,
			$FileUploadsPath . 'Hostel/',
			// @since 10.4 Param added, to save Building file!
			( ! empty( $_REQUEST['id'] ) ? $_REQUEST['id'] :  $_REQUEST['building_id'] )
		);
	}

	// Unset tables & redirect URL.
	RedirectURL( [ 'tables', 'modfunc' ] );
}

// Delete Room / Building.
if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( intval( $_REQUEST['id'] ) > 0
		&& ! HostelRoomHasStudents( $_REQUEST['id'] ) )
	{
		if ( DeletePrompt( dgettext( 'Hostel', 'Room' ) ) )
		{
			$delete_sql = "DELETE FROM hostel_rooms
				WHERE ID='" . (int) $_REQUEST['id'] . /*"'
				AND SCHOOL_ID='" . UserSchool() . */"';";

			$delete_sql .= "DELETE FROM hostel_students
				WHERE ROOM_ID='" . (int) $_REQUEST['id'] . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}
	elseif ( isset( $_REQUEST['building_id'] )
		&& intval( $_REQUEST['building_id'] ) > 0
		&& ! HostelBuildingHasRooms( $_REQUEST['building_id'] ) )
	{
		if ( DeletePrompt( dgettext( 'Hostel', 'Building' ) ) )
		{
			DBQuery( "DELETE FROM hostel_buildings
				WHERE ID='" . (int) $_REQUEST['building_id'] . /*"'
				AND SCHOOL_ID='" . UserSchool() . */"'" );

			// Unset modfunc & building ID redirect URL.
			RedirectURL( [ 'modfunc', 'building_id' ] );
		}
	}
}

if ( $_REQUEST['modfunc'] === 'remove_file'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'File' ) ) )
	{
		$table = $_REQUEST['category'] === 'room' ? 'hostel_rooms' : 'hostel_buildings';

		$table_id = $_REQUEST['category'] === 'room' ? $_REQUEST['id'] : $_REQUEST['building_id'];

		$column = DBEscapeIdentifier( 'CUSTOM_' . $_REQUEST['field_id'] );

		// Security: sanitize filename with no_accents().
		$filename = no_accents( $_GET['filename'] );

		$file = $FileUploadsPath . 'Hostel/' . $filename;

		DBQuery( "UPDATE " . DBEscapeIdentifier( $table ) . "
			SET " . $column . "=REPLACE(" . $column . ", '" . DBEscapeString( $file ) . "||', '')
			WHERE ID='" . (int) $table_id  . "'" );

		if ( file_exists( $file ) )
		{
			unlink( $file );
		}

		// Unset modfunc, field_id, filename & redirect URL.
		RedirectURL( [ 'modfunc', 'field_id', 'filename' ] );
	}
}


// Assign Room to Student submit.
if ( $_REQUEST['modfunc'] === 'assign_submit'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['id'] )
		&& ! empty( $_REQUEST['student_id'] )
		&& HostelCanAssignRoomTo( $_REQUEST['id'], $_REQUEST['student_id'] ) )
	{
		$insert_sql = 'INSERT INTO hostel_students';

		$fields = 'ROOM_ID,STUDENT_ID';

		$values = "'" . $_REQUEST['id'] . "','" . $_REQUEST['student_id'] . "'";

		$insert_sql .= '(' . $fields . ') values(' . $values . ')';

		DBQuery( $insert_sql );

		$note[] = button( 'check', '', '', 'bigger' ) .
			dgettext( 'Hostel', 'The room was assigned to the selected student.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}


// Assign Room to Student view.
if ( $_REQUEST['modfunc'] === 'assign'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['id'] )
		&& HostelCanAssignRoom( $_REQUEST['id'] ) )
	{
		HostelDrawRoomHeader( $_REQUEST['id'] );

		$extra = isset( $extra ) ? $extra : [];

		$extra['action'] = '&id=' . $_REQUEST['id'];

		$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=Hostel/Hostel.php' . $extra['action'] .
			'&modfunc=assign_submit';

		$extra['new'] = true;

		// Do not redirect if only 1 student, display list.
		$extra['Redirect'] = false;

		// Exclude students already having a room in hostel.
		$extra['WHERE'] = " AND s.STUDENT_ID NOT IN(SELECT STUDENT_ID
			FROM hostel_students)";

		Search( 'student_id', $extra );
	}
	else
	{
		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

// Remove Student from Room.
if ( $_REQUEST['modfunc'] === 'remove_student'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['id'] )
		&& ! empty( $_REQUEST['student_id'] )
		&& ! HostelCanAssignRoomTo( $_REQUEST['id'], $_REQUEST['student_id'] )
		&& DeletePrompt( _( 'Student' ), dgettext( 'Hostel', 'Remove' ) ) )
	{
		$delete_sql = "DELETE FROM hostel_students
			WHERE STUDENT_ID='" . $_REQUEST['student_id'] . "'
			AND ROOM_ID='" . $_REQUEST['id'] . "'";

		DBQuery( $delete_sql );

		$note[] = button( 'check', '', '', 'bigger' ) .
			dgettext( 'Hostel', 'The student was removed from the room.' );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

// Remove Inactive Students from their Room.
if ( $_REQUEST['modfunc'] === 'remove_inactive_students'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['st_arr'] )
		&& ! empty( $_POST['st_arr'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$delete_sql = "DELETE FROM hostel_students
			WHERE STUDENT_ID IN(" . $st_list . ")";

		DBQuery( $delete_sql );

		$note[] = button( 'check', '', '', 'bigger' ) .
			dgettext( 'Hostel', 'The selected students were removed from their room.' );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
	else
	{
		echo '<form action="' . PreparePHP_SELF( [], [ 'building_id', 'id' ] ) . '" method="POST">';

		DrawHeader(
			dgettext( 'Hostel', 'Remove Inactive Students' ),
			SubmitButton( dgettext( 'Hostel', 'Remove selected students from their room' ) )
		);

		$LO_columns = [
			'CHECKBOX' => MakeChooseCheckbox( 'required', 'STUDENT_ID', 'st_arr' ),
			'FULL_NAME' => _( 'Student' ),
			'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
			'ROOM' => dgettext( 'Hostel', 'Room' ),
		];

		/**
		 * Students which are inactive in current school year
		 * Or students which are not enrolled in current school year
		 * but were enrolled in previous school years.
		 */
		$inactive_students_RET = DBGet( "SELECT s.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
			CAST(hs.CREATED_AT AS char(10)) AS SINCE,s.STUDENT_ID AS CHECKBOX,
			CONCAT(hb.TITLE,' - ',hr.TITLE) AS ROOM
			FROM students s,hostel_students hs,hostel_rooms hr,hostel_buildings hb
			WHERE s.STUDENT_ID=hs.STUDENT_ID
			AND hs.ROOM_ID=hr.ID
			AND hr.BUILDING_ID=hb.ID
			AND (EXISTS(SELECT 1
				FROM student_enrollment se
				WHERE hs.STUDENT_ID=se.STUDENT_ID
				AND se.SYEAR='" . UserSyear() . "'
				AND (se.END_DATE<CURRENT_DATE
					OR se.START_DATE>CURRENT_DATE))
			OR (NOT EXISTS(SELECT 1
				FROM student_enrollment se
				WHERE hs.STUDENT_ID=se.STUDENT_ID
				AND se.SYEAR='" . UserSyear() . "')
			AND EXISTS(SELECT 1
				FROM student_enrollment se
				WHERE hs.STUDENT_ID=se.STUDENT_ID
				AND se.SYEAR<'" . UserSyear() . "')))",
		[
			'FULL_NAME' => 'HostelMakeStudent',
			'SINCE' => 'ProperDate',
			'CHECKBOX' => 'MakeChooseCheckbox',
		]);

		ListOutput(
			$inactive_students_RET,
			$LO_columns,
			'Student',
			'Students'
		);

		echo '<br /><div class="center">' .
			SubmitButton( dgettext( 'Hostel', 'Remove selected students from their room' ) ) . '</div></form>';
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );
	echo ErrorMessage( $note, 'note' );

	$student_id = UserStudentID();

	HostelDrawStudentRoomHeader( $student_id );

	HostelDrawRemoveInactiveStudentsHeader();

	$RET = [];

	// ADDING & EDITING FORM.
	if ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] !== 'new' )
	{
		$RET = HostelGetRoom( $_REQUEST['id'] );

		$title = $RET['TITLE'];

		// Set Building ID if not set yet.
		if ( empty( $_REQUEST['building_id'] ) )
		{
			$_REQUEST['building_id'] =  $RET['BUILDING_ID'];
		}
	}
	elseif ( ! empty( $_REQUEST['building_id'] )
		&& $_REQUEST['building_id'] !== 'new'
		&& empty( $_REQUEST['id'] ) )
	{
		$RET = DBGet( "SELECT ID AS BUILDING_ID,TITLE,DESCRIPTION,SORT_ORDER
			FROM hostel_buildings
			WHERE ID='" . (int) $_REQUEST['building_id'] . "'" );

		$RET = $RET[1];

		$title = $RET['TITLE'];
	}
	elseif ( ! empty( $_REQUEST['id'] )
		&& $_REQUEST['id'] === 'new' )
	{
		$title = dgettext( 'Hostel', 'New Room' );

		$RET['ID'] = 'new';

		$RET['BUILDING_ID'] = isset( $_REQUEST['building_id'] ) ? $_REQUEST['building_id'] : null;
	}
	elseif ( $_REQUEST['building_id'] === 'new' )
	{
		$title = dgettext( 'Hostel',  'New Building' );

		$RET['BUILDING_ID'] = 'new';
	}

	$extra_fields = [];

	// Action hook for Premium module: extra fields.
	do_action( 'Hostel/Hostel.php|extra_fields', [ &$extra_fields ] );

	echo HostelGetRoomsForm(
		$title,
		$RET,
		$extra_fields
	);

	// CATEGORIES.
	$buildings_RET = DBGet( "SELECT hb.ID,hb.TITLE,hb.SORT_ORDER,
		(SELECT COUNT(hr.ID)
			FROM hostel_rooms hr
			WHERE hr.BUILDING_ID=hb.ID) AS ROOMS
		FROM hostel_buildings hb
		ORDER BY hb.SORT_ORDER IS NULL,hb.SORT_ORDER,hb.TITLE" );

	// DISPLAY THE MENU.
	echo '<div class="st">';

	HostelRoomsMenuOutput( $buildings_RET, $_REQUEST['building_id'] );

	echo '</div>';

	// ROOMS.
	if ( ! empty( $_REQUEST['building_id'] )
		&& $_REQUEST['building_id'] !== 'new'
		&& $buildings_RET )
	{
		$rooms_RET = DBGet( "SELECT hr.ID,hr.TITLE,hr.CAPACITY,
			CONCAT((SELECT COUNT(hs.STUDENT_ID)
				FROM hostel_students hs
				WHERE hs.ROOM_ID=hr.ID), '/', hr.CAPACITY) AS STUDENTS
			FROM hostel_rooms hr
			WHERE hr.BUILDING_ID='" . (int) $_REQUEST['building_id'] . /*"'
			AND SCHOOL_ID='" . UserSchool() . */"'
			ORDER BY hr.TITLE" );

		echo '<div class="st">';

		HostelRoomsMenuOutput( $rooms_RET, $_REQUEST['id'], $_REQUEST['building_id'] );

		echo '</div>';

		// STUDENTS (admin only).
		if ( ! empty( $_REQUEST['id'] )
			&& $_REQUEST['id'] !== 'new'
			&& $rooms_RET
			&& User( 'PROFILE' ) === 'admin' )
		{
			$students_RET = DBGet( "SELECT hs.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,
				CAST(hs.CREATED_AT AS char(10)) AS SINCE
				FROM hostel_students hs,students s
				WHERE hs.ROOM_ID='" . (int) $_REQUEST['id'] . "'
				AND s.STUDENT_ID=hs.STUDENT_ID
				ORDER BY FULL_NAME",
				[ 'FULL_NAME' => 'HostelMakeStudent', 'SINCE' => 'ProperDate' ] );

			echo '<div class="st">';

			HostelStudentsMenuOutput( $students_RET, $_REQUEST['id'] );

			echo '</div>';
		}
	}
}



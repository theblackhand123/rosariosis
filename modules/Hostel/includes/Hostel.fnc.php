<?php
/**
 * Hostel functions
 *
 * @package Hostel module
 */


/**
 * Get Room or Building Form
 *
 * @example echo HostelGetRoomsForm( $title, $RET );
 *
 * @uses DrawHeader()
 *
 * @param  string $title        Form Title.
 * @param  array  $RET          Room or Building Data.
 * @param  array  $extra_fields Extra fields for Building or Room.
 *
 * @return string Room or Building Form HTML
 */
function HostelGetRoomsForm( $title, $RET, $extra_fields = [] )
{
	$id = issetVal( $RET['ID'] );

	$building_id = issetVal( $RET['BUILDING_ID'] );

	if ( empty( $id )
		&& empty( $building_id ) )
	{
		return '';
	}

	$new = $id === 'new' || $building_id === 'new';

	$action = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $building_id
		&& $building_id !== 'new' )
	{
		$action .= '&building_id=' . $building_id;
	}

	if ( $id )
	{
		$action .= '&id=' . $id;
	}

	if ( $id )
	{
		$full_table = 'hostel_rooms';
	}
	else
	{
		$full_table = 'hostel_buildings';
	}

	$action .= '&table=' . $full_table . '&modfunc=save';

	$form = '<form action="' . URLEscape( $action ) . '" method="POST" enctype="multipart/form-data">';

	$allow_edit = AllowEdit();

	$div = $allow_edit;

	$delete_button = '';

	if ( $allow_edit
		&& ! $new
		&& ( $id || ! HostelBuildingHasRooms( $building_id ) )
		&& ( ! $id || ! HostelRoomHasStudents( $id ) ) )
	{
		$delete_URL = URLEscape( "Modules.php?modname=" . $_REQUEST['modname'] . '&modfunc=delete&building_id=' . $building_id . '&id=' . $id );

		$onclick_link = 'ajaxLink(' . json_encode( $delete_URL ) . ');';

		$delete_button = '<input type="button" value="' . AttrEscape( _( 'Delete' ) ) .
		'" onclick="' . AttrEscape( $onclick_link ) . '" /> ';
	}

	ob_start();

	DrawHeader( $title, $delete_button . SubmitButton() );

	$form .= ob_get_clean();

	$header = '<table class="width-100p valign-top fixed-col cellpadding-5"><tr class="st">';

	if ( $id )
	{
		// FJ room name required.
		$header .= '<td>' . TextInput(
			issetVal( $RET['TITLE'] ),
			'tables[' . $id . '][TITLE]',
			dgettext( 'Hostel', 'Room' ),
			'required maxlength=100',
			$div
		) . '</td>';

		if ( $building_id )
		{
			// CATEGORIES.
			$buildings_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
				FROM hostel_buildings
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

			foreach ( (array) $buildings_RET as $building )
			{
				$buildings_options[ $building['ID'] ] = $building['TITLE'];
			}

			$header .= '<td>' . SelectInput(
				$RET['BUILDING_ID'] ? $RET['BUILDING_ID'] : $building_id,
				'tables[' . $id . '][BUILDING_ID]',
				dgettext( 'Hostel', 'Building' ),
				$buildings_options,
				false,
				'required'
			) . '</td>';
		}

		$header .= '</tr><tr class="st">';

		$header .= '<td colspan="2">' . TinyMCEInput(
			issetVal( $RET['DESCRIPTION'] ),
			'tables[' . $id . '][DESCRIPTION]',
			_( 'Description' )
		) . '</td>';

		$header .= '</tr><tr class="st">';

		// CAPACITY.
		$header .= '<td>' . TextInput(
			( ! isset( $RET['CAPACITY'] ) ? '1' : $RET['CAPACITY'] ),
			'tables[' . $id . '][CAPACITY]',
			dgettext( 'Hostel', 'Capacity' ),
			'type="number" min="0" max="999" required',
			! $new
		) . '</td>';

		// PRICE.
		$header .= '<td>' . TextInput(
			[ issetVal( $RET['PRICE'] ), Currency( $RET['PRICE'] ) ],
			'tables[' . $id . '][PRICE]',
			_( 'Price' ),
			'type="number" step="0.01" min="-999999999" max="999999999"',
			! $new
		) . '</td></tr>';

		// Extra Fields.
		if ( ! empty( $extra_fields ) )
		{
			$header .= '<tr><td colspan="2"><hr /></td></tr><tr class="st">';

			$i = 0;

			foreach ( (array) $extra_fields as $extra_field )
			{
				if ( $i && $i % 2 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_fields ) - 1 ) )
				{
					$colspan = abs( ( $i % 2 ) - 2 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}

			$header .= '</tr>';
		}

		$header .= '</table>';
	}
	// Building Form.
	else
	{
		$title = isset( $RET['TITLE'] ) ? $RET['TITLE'] : '';

		// Title room.
		$header .= '<td>' . TextInput(
			$title,
			'tables[' . $building_id . '][TITLE]',
			_( 'Title' ),
			'required maxlength=255' . ( empty( $title ) ? ' size=20' : '' )
		) . '</td>';

		// Sort Order room.
		$header .= '<td>' . TextInput(
			( isset( $RET['SORT_ORDER'] ) ? $RET['SORT_ORDER'] : '' ),
			'tables[' . $building_id . '][SORT_ORDER]',
			_( 'Sort Order' ),
			' type="number" min="-9999" max="9999"'
		) . '</td>';

		$header .= '</tr><tr class="st">';

		$header .= '<td colspan="2">' . TinyMCEInput(
			issetVal( $RET['DESCRIPTION'] ),
			'tables[' . $building_id . '][DESCRIPTION]',
			_( 'Description' )
		) . '</td>';

		// Extra Fields.
		if ( ! empty( $extra_fields ) )
		{
			$i = 0;

			foreach ( (array) $extra_fields as $extra_field )
			{
				if ( $i % 2 === 0 )
				{
					$header .= '</tr><tr class="st">';
				}

				$colspan = 1;

				if ( $i === ( count( $extra_fields ) + 1 ) )
				{
					$colspan = abs( ( $i % 2 ) - 2 );
				}

				$header .= '<td colspan="' . $colspan . '">' . $extra_field . '</td>';

				$i++;
			}
		}

		$header .= '</tr></table>';
	}

	ob_start();

	DrawHeader( $header );

	$form .= ob_get_clean();

	$form .= '</form>';

	return $form;
}

if ( ! function_exists( 'HostelCustomFieldsForm' ) )
{
	// @todo Premium module.
	function HostelCustomFieldsForm( $id )
	{
		return '';
	}
}


/**
 * Outputs Rooms or Buildings Menu
 *
 * @example RoomsMenuOutput( $rooms_RET, $_REQUEST['id'], $_REQUEST['building_id'] );
 * @example RoomsMenuOutput( $buildings_RET, $_REQUEST['building_id'] );
 *
 * @uses ListOutput()
 *
 * @param array  $RET         Buildings (ID, TITLE, SORT_ORDER columns) or Rooms (+ REF column) RET.
 * @param string $id          Building ID or Room ID.
 * @param string $building_id Building ID (optional). Defaults to '0'.
 */
function HostelRoomsMenuOutput( $RET, $id, $building_id = '0' )
{
	if ( $RET
		&& $id
		&& $id !== 'new' )
	{
		foreach ( (array) $RET as $key => $value )
		{
			if ( $value['ID'] == $id )
			{
				$RET[ $key ]['row_color'] = Preferences( 'HIGHLIGHT' );
			}
		}
	}

	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	if ( ! $building_id )
	{
		$LO_columns = [
			'TITLE' => dgettext( 'Hostel', 'Building' ),
			'ROOMS' => dgettext( 'Hostel', 'Rooms' ),
		];
	}
	else
	{
		$LO_columns = [
			'TITLE' => dgettext( 'Hostel', 'Room' ),
			'STUDENTS' => _( 'Students' ),
		];
	}

	$LO_link = [];

	$LO_link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];

	if ( $building_id )
	{
		$LO_link['TITLE']['link'] .= '&building_id=' . $building_id;
	}

	$LO_link['TITLE']['variables'] = [ ( ! $building_id ? 'building_id' : 'id' ) => 'ID' ];

	$add_link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&building_id=';

	$add_link .= $building_id ? $building_id . '&id=new' : 'new';

	if ( $RET )
	{
		$LO_link['add']['html']['TITLE'] = button(
			'add',
			'',
			// @deprecated since RosarioSIS 11.1 use of double quotes around URL (if no other attributes).
			'"' . URLEscape( $add_link ) . '"'
		);
	}
	else
	{
		$LO_link['add']['link'] = $add_link;
	}

	if ( ! $building_id
		&& $RET )
	{
		// Count total Rooms.
		$total_rooms = 0;

		foreach ( (array) $RET as $building )
		{
			$total_rooms += (int) $building['ROOMS'];
		}

		$LO_link['add']['html']['ROOMS'] = $total_rooms;
	}
	elseif ( $RET )
	{
		// Count total Students/Capacity.
		$total_students = $total_capacity = 0;

		foreach ( (array) $RET as $room )
		{
			list( $students, $capacity ) = explode( '/', $room['STUDENTS'] );

			$total_students += (int) $students;

			$total_capacity += (int) $capacity;
		}

		$LO_link['add']['html']['STUDENTS'] = $total_students . '/' . $total_capacity;
	}

	// Move add button to top of list when > 20 entries.
	$LO_link['add']['first'] = 20;

	if ( ! $building_id )
	{
		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Hostel', 'Building' ),
			dgettext( 'Hostel', 'Buildings' ),
			$LO_link,
			[],
			$LO_options
		);
	}
	else
	{
		$LO_options['search'] = true;

		ListOutput(
			$RET,
			$LO_columns,
			dgettext( 'Hostel', 'Room' ),
			dgettext( 'Hostel', 'Rooms' ),
			$LO_link,
			[],
			$LO_options
		);
	}
}


/**
 * Outputs Students Menu
 *
 * @example HostelStudentsMenuOutput( $students_RET, $_REQUEST['id'] );
 *
 * @uses ListOutput()
 *
 * @param array  $RET     Students (ID, TITLE, SORT_ORDER columns).
 * @param string $room_id Room ID.
 */
function HostelStudentsMenuOutput( $RET, $room_id )
{
	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	$LO_columns = [
		'FULL_NAME' => _( 'Student' ),
		'SINCE' => dgettext( 'Hostel', 'Since' ),
	];

	$LO_link = [];

	if ( HostelCanAssignRoom( $room_id ) )
	{
		$LO_link['add']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&id=' . $room_id .
			'&modfunc=assign';
	}

	$LO_link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&id=' . $room_id .
		'&modfunc=remove_student';

	$LO_link['remove']['variables'] = [ 'student_id' => 'STUDENT_ID' ];

	// @since 10.1 Move add button to top of list when > 20 entries.
	$LO_link['add']['first'] = 20;

	ListOutput(
		$RET,
		$LO_columns,
		_( 'Student' ),
		_( 'Students' ),
		$LO_link,
		[],
		$LO_options
	);
}


/**
 * Building has Rooms?
 *
 * @param int $building_id Building ID.
 *
 * @return bool True if Building has Rooms.
 */
function HostelBuildingHasRooms( $building_id )
{
	if ( (string) (int) $building_id != $building_id
		|| $building_id < 1 )
	{
		return false;
	}

	$building_has_rooms = DBGet( "SELECT 1
		FROM hostel_rooms
		WHERE BUILDING_ID='" . (int) $building_id . /*"'
		AND SCHOOL_ID='" . UserSchool() . */"'
		LIMIT 1" );

	return (bool) $building_has_rooms;
}


/**
 * Room has Students?
 *
 * @param int $room_id Room ID.
 *
 * @return bool True if Room has Students.
 */
function HostelRoomHasStudents( $room_id )
{
	if ( (string) (int) $room_id != $room_id
		|| $room_id < 1 )
	{
		return false;
	}

	$room_has_students = DBGetOne( "SELECT 1
		FROM hostel_students
		WHERE ROOM_ID='" . (int) $room_id . /*"'
		AND SCHOOL_ID='" . UserSchool() . */"'
		LIMIT 1" );

	return (bool) $room_has_students;
}


// Allow User to Assign if Allow Edit & room has available beds!
function HostelCanAssignRoom( $room_id )
{
	if ( (string) (int) $room_id != $room_id
		|| $room_id < 1 )
	{
		return false;
	}

	$available_beds = HostelGetRoomAvailableBeds( $room_id );

	return $available_beds > 0
		&& AllowEdit();
}

// Allow User to Assign if room has available beds & student not already in a room!
function HostelCanAssignRoomTo( $room_id, $student_id )
{
	if ( ! HostelCanAssignRoom( $room_id ) )
	{
		return false;
	}

	$student_already_in_hostel = DBGetOne( "SELECT 1
		FROM hostel_students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	return ! $student_already_in_hostel;
}


/**
 * Draw Remove Inactive Students Header
 * Only if there are inactive students with a room
 */
function HostelDrawRemoveInactiveStudentsHeader()
{
	if ( ! AllowEdit() )
	{
		return;
	}

	/**
	 * Students which are inactive in current school year
	 * Or students which are not enrolled in current school year
	 * but were enrolled in previous school years.
	 */
	$inactive_students_in_hostel = DBGetOne( "SELECT 1
		FROM hostel_students hs
		WHERE EXISTS(SELECT 1
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
			AND se.SYEAR<'" . UserSyear() . "'))" );

	if ( ! $inactive_students_in_hostel )
	{
		return;
	}

	$link = '<a href="' . URLEscape( 'Modules.php?modname=Hostel/Hostel.php&modfunc=remove_inactive_students' ) .
		'">' . dgettext( 'Hostel', 'Remove Inactive Students' ) . '</a>';

	DrawHeader( '', $link );
}

/**
 * Make Student full name column:
 * Add Photo tip message
 * Add link to Student Info only if has enrollment in school year
 *
 * @param string $value  Student full name.
 * @param string $column Column name.
 *
 * @return string
 */
function HostelMakeStudent( $value, $column = 'FULL_NAME' )
{
	global $THIS_RET;

	$value = makePhotoTipMessage( $value, $column );

	// Add link to Student Info only if student has enrollment in school year.
	if ( ! DBGetOne( "SELECT 1
		FROM student_enrollment
		WHERE STUDENT_ID='" . $THIS_RET['STUDENT_ID'] . "'
		AND SYEAR='" . UserSyear() . "'" ) )
	{
		return $value;
	}

	return '<a href="' . URLEscape( 'Modules.php?modname=Students/Student.php&student_id=' . $THIS_RET['STUDENT_ID'] ) .
		'">' . $value . '</a>';
}

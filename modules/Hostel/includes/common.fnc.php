<?php
/**
 * Hostel common functions
 *
 * @package Hostel module
 */


function HostelGetRoom( $room_id, $reset = false )
{
	static $rooms = [];

	if ( (string) (int) $room_id != $room_id
		|| $room_id < 1 )
	{
		return [];
	}

	if ( isset( $rooms[ $room_id ] )
		&& ! $reset )
	{
		return $rooms[ $room_id ];
	}

	$room_RET = DBGet( "SELECT ID,BUILDING_ID,TITLE,
		DESCRIPTION,CAPACITY,PRICE,CREATED_AT,
		(SELECT TITLE
			FROM hostel_buildings
			WHERE ID=BUILDING_ID) AS BUILDING_TITLE
		FROM hostel_rooms
		WHERE ID='" . (int) $room_id . /*"'
		AND SCHOOL_ID='" . UserSchool() . */"'" );

	$rooms[ $room_id ] = ( ! $room_RET ? [] : $room_RET[1] );

	return $rooms[ $room_id ];
}

function HostelGetStudentRoomID( $student_id )
{
	if ( (string) (int) $student_id != $student_id
		|| $student_id < 1 )
	{
		return [];
	}

	$student_room_id = DBGetOne( "SELECT ROOM_ID
		FROM hostel_students
		WHERE STUDENT_ID='" . (int) $student_id . "'" );

	return (int) $student_room_id;
}

if ( ! function_exists( 'HostelDrawRoomHeader' ) )
{
	/**
	 * Room header when on the Search student screen (to assign room)
	 *
	 * @param int $room_id Room ID
	 */
	function HostelDrawRoomHeader( $room_id )
	{
		$room = HostelGetRoom( $room_id );

		if ( ! $room )
		{
			return;
		}

		$title = $room['BUILDING_TITLE'] . ' - ';

		$title .= '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Hostel/Hostel.php&building_id=' .
				$room['BUILDING_ID'] . '&id=' . $room['ID'] ) :
			_myURLEncode( 'Modules.php?modname=Hostel/Hostel.php&building_id=' .
				$room['BUILDING_ID'] . '&id=' . $room['ID'] ) ) . '">' . $room['TITLE'] . '</a>';

		DrawHeader( $title );
	}
}

if ( ! function_exists( 'HostelDrawStudentRoomHeader' ) )
{
	/**
	 * Student's Room header
	 * Only displayed if student has a room
	 *
	 * @param int $student_id Student ID
	 */
	function HostelDrawStudentRoomHeader( $student_id )
	{
		$room_id = HostelGetStudentRoomID( $student_id );

		if ( ! $room_id )
		{
			return;
		}

		$room = HostelGetRoom( $room_id );

		if ( ! $room )
		{
			return;
		}

		$title = dgettext( 'Hostel', "Student's Room" ) . ': ';

		if ( User( 'PROFILE' ) === 'student' )
		{
			$title = dgettext( 'Hostel', 'My Room' ) . ': ';
		}

		$title .= '<a href="' . URLEscape( 'Modules.php?modname=Hostel/Hostel.php&building_id=' .
				$room['BUILDING_ID'] . '&id=' . $room['ID'] ) . '">' . $room['TITLE'] . '</a>';

		DrawHeader( $title );
	}
}

/**
 * Get Room Available Beds
 *
 * @param int $room_id Room ID.
 *
 * @return int Available beds.
 */
function HostelGetRoomAvailableBeds( $room_id )
{
	if ( (string) (int) $room_id != $room_id
		|| $room_id < 1 )
	{
		return false;
	}

	$room_students_count = DBGetOne( "SELECT COUNT(STUDENT_ID)
		FROM hostel_students
		WHERE ROOM_ID='" . (int) $room_id . "'" );

	$room = HostelGetRoom( $room_id );

	return $room['CAPACITY'] - $room_students_count;
}

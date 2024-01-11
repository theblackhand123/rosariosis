<?php
/**
 * Meet program
 *
 * @package Jitsi Meet module
 */

require_once 'modules/Jitsi_Meet/includes/common.fnc.php';
require_once 'modules/Jitsi_Meet/includes/Meet.fnc.php';

DrawHeader( ProgramTitle() );

$room_id = JitsiMeetCheckRoomID( issetVal( $_REQUEST['id'] ) );

if ( ! $room_id )
{
	// List user rooms.
	$user_rooms = JitsiMeetGetUserRooms();

	JitsiMeetUserRoomsMenuOutput( $user_rooms );
}
else
{
	$params = JitsiMeetGetRoomParams( $room_id );

	echo JitsiMeetJSHTML( $params );
}

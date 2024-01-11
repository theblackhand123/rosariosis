<?php
/**
 * Meet functions
 *
 * @package Jitsi Meet module
 */

/**
 * Get logged in User (or Student) Rooms
 *
 * @return array $rooms_RET
 */
function JitsiMeetGetUserRooms()
{
	if ( User( 'STAFF_ID' ) )
	{
		$rooms_sql = "SELECT ID,TITLE,SUBJECT,CREATED_AT,
		(SELECT " . DisplayNameSQL() . " FROM staff WHERE OWNER_ID=STAFF_ID) AS OWNER_NAME
		FROM jitsi_meet_rooms
		WHERE OWNER_ID='" . User( 'STAFF_ID' ) . "'
		OR USERS LIKE '%," . User( 'STAFF_ID' ) . ",%'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY CREATED_AT DESC";
	}
	else
	{
		$rooms_sql = "SELECT ID,TITLE,SUBJECT,CREATED_AT,OWNER_ID,
		(SELECT " . DisplayNameSQL() . " FROM staff WHERE OWNER_ID=STAFF_ID) AS OWNER_NAME
		FROM jitsi_meet_rooms
		WHERE students LIKE '%," . UserStudentID() . ",%'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY CREATED_AT DESC";
	}

	$rooms_RET = DBGet( $rooms_sql, [ 'CREATED_AT' => 'ProperDateTime' ] );

	return $rooms_RET;
}

/**
 * User Rooms Menu Output
 *
 * @uses ListOutput()
 *
 * @param array $RET User Rooms.
 */
function JitsiMeetUserRoomsMenuOutput( $RET )
{
	$LO_options = [ 'save' => false, 'search' => false, 'responsive' => false ];

	$LO_columns = [
		'TITLE' => dgettext( 'Jitsi_Meet', 'Room' ),
		'SUBJECT' => _( 'Description' ),
		'OWNER_NAME' => _( 'User' ),
		'CREATED_AT' => _( 'Date' ),
	];

	$LO_link = [];

	$LO_link['TITLE']['link'] = PreparePHP_SELF(
		[],
		[ 'id' ]
	);

	$LO_link['TITLE']['variables'] = [ 'id' => 'ID' ];

	ListOutput(
		$RET,
		$LO_columns,
		dgettext( 'Jitsi_Meet', 'Room' ),
		dgettext( 'Jitsi_Meet', 'Rooms' ),
		$LO_link,
		[],
		$LO_options
	);
}

/**
 * Check User has right to access the Room
 *
 * @param int $room_id Room ID.
 *
 * @return int Room ID or 0.
 */
function JitsiMeetCheckRoomID( $room_id )
{
	$room_RET = DBGet( "SELECT ID,TITLE,PASSWORD,START_AUDIO_ONLY,STUDENTS,USERS,OWNER_ID
	FROM jitsi_meet_rooms
	WHERE ID='" . (int) $room_id . "'
	AND SYEAR='" . UserSyear() . "'" );

	if ( ! $room_RET )
	{
		return 0;
	}

	$room = $room_RET[1];

	if ( User( 'STAFF_ID' )
		&& $room['OWNER_ID'] != User( 'STAFF_ID' )
		&& mb_strpos( $room['USERS'], ',' . User( 'STAFF_ID' ) . ',' ) === false )
	{
		return 0;
	}

	if ( ! User( 'STAFF_ID' )
		&& UserStudentID()
		&& mb_strpos( $room['STUDENTS'], ',' . UserStudentID() . ',' ) === false )
	{
		return 0;
	}

	return $room_id;
}

/**
 * Get Jitsi Room Parameters
 *
 * @uses JitsiMeetPhotoURL()
 * @uses Config( 'JITSI_MEET_*' )
 *
 * @param int $room_id Room ID.
 *
 * @return array Jitsi Room Parameters
 */
function JitsiMeetGetRoomParams( $room_id )
{
	global $locale;

	$room_RET = DBGet( "SELECT ID,TITLE,SUBJECT,PASSWORD,START_AUDIO_ONLY,STUDENTS,USERS
		FROM jitsi_meet_rooms
		WHERE ID='" . (int) $room_id . "'
		AND SYEAR='" . UserSyear() . "'" );

	if ( ! $room_RET )
	{
		return [];
	}

	$room = $room_RET[1];

	$language = mb_substr( $locale, 0, 2 );

	$photo_url = JitsiMeetPhotoURL();

	return [
		'room' => $room['TITLE'],
		'domain' => Config( 'JITSI_MEET_DOMAIN' ),
		'width' => Config( 'JITSI_MEET_WIDTH' ),
		'height' => Config( 'JITSI_MEET_HEIGHT' ),
		'start_audio_only' => (bool) $room['START_AUDIO_ONLY'],
		'default_language' => $language,
		'brand_watermark_link' => Config( 'JITSI_MEET_BRAND_WATERMARK_LINK' ),
		'settings' => Config( 'JITSI_MEET_SETTINGS' ),
		'disable_video_quality_label' => (bool) Config( 'JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL' ),
		'toolbar' => Config( 'JITSI_MEET_TOOLBAR' ),
		'user' => User( 'NAME' ),
		'subject' => $room['SUBJECT'],
		'avatar' => $photo_url,
		'password' => $room['PASSWORD'],
		// JaaS 8x8.vc new settings
		'app_id' => Config( 'JITSI_MEET_JAAS_APP_ID' ),
		'jwt' => Config( 'JITSI_MEET_JAAS_JWT' )
	];
}

/**
 * Jitsi Room default Settings
 *
 * @return array Default settings.
 */
function JitsMeetDefaultSettings()
{
	return [
		'enabled' => true,
		'room' => '',
		'domain' => 'meet.jit.si',
		'film_strip_only' => false,
		'width' => '100%',
		'height' => 700,
		'start_audio_only' => false,
		'parent_node' => '#meet',
		'default_language' => 'en',
		'background_color' => '#464646',
		'show_watermark' => true,
		'show_brand_watermark' => false,
		'brand_watermark_link' => '',
		'settings' => 'devices,language',
		'disable_video_quality_label' => false,
		'toolbar' => 'microphone,camera,hangup,desktop,fullscreen,profile,chat,recording,settings,raisehand,videoquality,tileview'
	];
}

/**
 * User or Student Photo URL
 *
 * @uses JitsiMeetSiteURL()
 *
 * @return string Empty or Photo URL.
 */
function JitsiMeetPhotoURL()
{
	global $UserPicturesPath,
		$StudentPicturesPath;

	if ( User( 'STAFF_ID' ) )
	{
		// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
		$picture_path = (array) glob( $UserPicturesPath . UserSyear() . '/' . User( 'STAFF_ID' ) . '.*jpg' );

		$picture_path = end( $picture_path );
	}
	else
	{
		// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
		$picture_path = (array) glob( $StudentPicturesPath . '*/' . UserStudentID() . '.*jpg' );

		$picture_path = end( $picture_path );
	}

	if ( ! $picture_path )
	{
		return '';
	}

	return JitsiMeetSiteURL() . $picture_path;
}

/**
 * Jitsi Meet Javascript & HTML code
 *
 * @uses JitsMeetDefaultSettings()
 * @uses JitsiMeetInitTemplate()
 *
 * @link https://wordpress.org/plugins/webinar-and-video-conference-with-jitsi-meet/
 *
 * @param array $params Room parameters.
 *
 * @return string Javascript & HTML code
 */
function JitsiMeetJSHTML( $params )
{
	$params = array_replace_recursive( JitsMeetDefaultSettings(), $params );

	$script = sprintf(
		JitsiMeetInitTemplate(),
		$params['domain'],
		$params['settings'],
		$params['toolbar'],
		$params['domain'] === '8x8.vc' ?
			// 8x8.vc: It must be in the format: “<AppID>/<room>”
			$params['app_id'] . '/' . $params['room'] :
			$params['room'],
		$params['width'],
		$params['height'],
		$params['parent_node'],
		$params['jwt'],
		$params['start_audio_only'] ? 1 : 0,
		$params['default_language'],
		$params['film_strip_only'] ? 1 : 0,
		$params['background_color'],
		$params['show_watermark'] && $params['domain'] === '8x8.vc' ? 1 : 0, // show_watermark if on official domain.
		$params['brand_watermark_link'] ? 1 : 0, // show_brand_watermark if has link.
		$params['brand_watermark_link'],
		$params['disable_video_quality_label'] ? 1 : 0,
		isset( $params['user'] ) ? $params['user'] : '',
		$params['subject'],
		isset( $params['avatar'] ) ? $params['avatar'] : '',
		isset( $params['password'] ) ? $params['password'] : ''
	);

	return '<div id="meet"></div>
		<script src="https://8x8.vc/external_api.js"></script>
		<script>function waitForJitsiMeet() {
			if (typeof JitsiMeetExternalAPI !== "undefined") {
				' . $script . '
			} else {
				setTimeout(waitForJitsiMeet, 250);
			}
		}
		waitForJitsiMeet();</script>';
}

/**
 * Jitsi Meet Javascript Init template
 *
 * @link https://community.jitsi.org/t/setting-the-room-password-on-creation-using-the-jitsi-meet-api/19426/4
 * @link https://community.jitsi.org/t/lock-failed-on-jitsimeetexternalapi/32060/16
 *
 * @return string Javascript Init template
 */
function JitsiMeetInitTemplate()
{
	return 'const domain = "%1$s";
		const settings = "%2$s";
		const toolbar = "%3$s";
		const options = {
			roomName: "%4$s",
			width: "%5$s",
			height: %6$d,
			parentNode: document.querySelector("%7$s"),
			jwt: "%8$s",
			configOverwrite: {
				startAudioOnly: %9$b === 1,
				defaultLanguage: "%10$s",
			},
			interfaceConfigOverwrite: {
				filmStripOnly: %11$b === 1,
				DEFAULT_BACKGROUND: "%12$s",
				DEFAULT_REMOTE_DISPLAY_NAME: "",
				SHOW_JITSI_WATERMARK: %13$b === 1,
				SHOW_WATERMARK_FOR_GUESTS: %13$b === 1,
				SHOW_BRAND_WATERMARK: %14$b === 1,
				BRAND_WATERMARK_LINK: "%15$s",
				LANG_DETECTION: true,
				CONNECTION_INDICATOR_DISABLED: false,
				VIDEO_QUALITY_LABEL_DISABLED: %16$b === 1,
				SETTINGS_SECTIONS: settings.split(","),
				TOOLBAR_BUTTONS: toolbar.split(","),
			},
		};
		const api = new JitsiMeetExternalAPI(domain, options);
		api.executeCommand("displayName", "%17$s");
		api.executeCommand("subject", "%18$s");
		api.executeCommand("avatarUrl", "%19$s");
		window.api = api;

		setTimeout(function(){
			api.addEventListener("videoConferenceJoined", function(event){
				setTimeout(function(){
					api.executeCommand("password", "%20$s");
				}, 300);
			});
			api.addEventListener("passwordRequired", function(event){
				api.executeCommand("password", "%20$s");
			});
		}, 10);';
}

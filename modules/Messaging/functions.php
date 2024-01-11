<?php
/**
 * Module Functions
 * (Loaded on each page)
 *
 * @package Messaging module
 */


/**
 * Messaging module Portal Alerts.
 * Messaging new messages note.
 *
 * @uses misc/Portal.php|portal_alerts hook
 *
 * @return true if new messages note, else false.
 */
function MessagingPortalAlerts()
{
	global $note;

	if ( ! AllowUse( 'Messaging/Messages.php' ) )
	{
		return false;
	}

	require_once 'modules/Messaging/includes/Common.fnc.php';

	$user = GetCurrentMessagingUser();

	$new_msg = DBGetOne( "SELECT COUNT(*) AS COUNT
		FROM messagexuser mxu, messages m
		WHERE mxu.USER_ID='" . (int) $user['user_id'] . "'
		AND mxu.KEY='" . $user['key'] . "'
		AND STATUS='unread'
		AND m.MESSAGE_ID=mxu.MESSAGE_ID
		AND m.SYEAR='" . UserSyear() .  "'
		AND m.SCHOOL_ID='" . UserSchool() . "'" );

	if ( $new_msg )
	{
		// Add note.
		$note[] = '<a href="Modules.php?modname=Messaging/Messages.php">
			<img src="modules/Messaging/icon.png" class="button bigger" /> ' .
			sprintf(
				dngettext( 'Messaging', '%d new message', '%d new messages', $new_msg ),
				$new_msg
			) . '</a>';

		return true;
	}

	return false;
}

add_action( 'misc/Portal.php|portal_alerts', 'MessagingPortalAlerts', 0 );

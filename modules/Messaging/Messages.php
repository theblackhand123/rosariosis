<?php
/**
 * Messages
 *
 * @package Messaging module
 */

// Include Common functions.
require_once 'modules/Messaging/includes/Common.fnc.php';

// Include Messages functions.
require_once 'modules/Messaging/includes/Messages.fnc.php';

$title = ProgramTitle();

if ( SchoolInfo( 'SCHOOLS_NB' ) > 1
	&& User( 'PROFILE' ) !== 'student' )
{
	// If more than 1 school, mention current school.
	$title .= ' <small>' . SchoolInfo( 'TITLE' ) . '</small>';
}

DrawHeader( $title );

if ( $_REQUEST['modfunc'] === 'archive' )
{
	$archived = MessageArchive( $_REQUEST['message_id'] );

	if ( $archived )
	{
		$note[] = button( 'check', '', '', 'bigger' ) . '&nbsp;' . dgettext( 'Messaging', 'Message archived.' );
	}

	if ( function_exists( 'RedirectURL' ) )
	{
		// @since 3.3.
		RedirectURL( 'modfunc' );
	}
}

if ( isset( $note ) )
{
	echo ErrorMessage( $note, 'note' );
}

if ( isset( $_REQUEST['view'] )
	&& $_REQUEST['view'] === 'message'
	&& MessageOutput( $_REQUEST['message_id'] ) )
{
	// Display message.
	// exit;
}
else
{
	// Display messages list.
	$views_data = GetMessagesViewsData();

	// Get current view.
	$current_view = 'unread';

	if ( isset( $_REQUEST['view'] )
		&& in_array( $_REQUEST['view'], array_keys( $views_data ) ) )
	{
		$current_view = $_REQUEST['view'];
	}

	$views_left = '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $views_data['unread']['link'] ) : _myURLEncode( $views_data['unread']['link'] ) ) . '">' .
		( $current_view === 'unread' ?
			'<b>' . $views_data['unread']['label'] . '</b>' : $views_data['unread']['label'] ) .
		'</a> |&nbsp;<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $views_data['read']['link'] ) : _myURLEncode( $views_data['read']['link'] ) ) . '">' .
		( $current_view === 'read' ?
			'<b>' . $views_data['read']['label'] . '</b>' : $views_data['read']['label'] ) .
		'</a> |&nbsp;<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $views_data['archived']['link'] ) : _myURLEncode( $views_data['archived']['link'] ) ) . '">' .
		( $current_view === 'archived' ?
			'<b>' . $views_data['archived']['label'] . '</b>' : $views_data['archived']['label'] ) . '</a>';

	$views_right = '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $views_data['sent']['link'] ) : _myURLEncode( $views_data['sent']['link'] ) ) . '">' .
		( $current_view === 'sent' ?
			'<b>' . $views_data['sent']['label'] . '</b>' : $views_data['sent']['label'] ) . '</a>';

	DrawHeader( $views_left, $views_right );

	// Display View header.
	DrawHeader( '<b>' . $views_data[ $current_view ]['plural'] . '</b>' );

	// Display View.
	MessagesListOutput( $current_view );
}

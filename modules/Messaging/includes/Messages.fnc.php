<?php
/**
 * Messages functions
 * List output
 * Views
 *
 * @package Messaging module
 */

function MessagesListOutput( $view )
{
	$views_data = GetMessagesViewsData();

	// Check View.
	if ( ! $view
		|| ! in_array( $view, array_keys( $views_data ) ) )
	{
		return false;
	}

	$view_data = $views_data[ $view ];

	$current_user = GetCurrentMessagingUser();

	$columns_sql = 'm.' . implode( ', m.', array_keys( $view_data['columns'] ) );

	if ( isset( $view_data['columns']['ARCHIVE'] ) )
	{
		$columns_sql = str_replace( 'm.ARCHIVE', "m.MESSAGE_ID AS ARCHIVE", $columns_sql );
	}

	$view_sql = "SELECT m.MESSAGE_ID, mxu.STATUS, " . $columns_sql . "
		FROM messages m, messagexuser mxu
		WHERE m.SYEAR='" . UserSyear() . "'
		AND m.SCHOOL_ID='" . UserSchool() . "'
		AND m.MESSAGE_ID=mxu.MESSAGE_ID
		AND mxu.KEY='" . $current_user['key'] . "'
		AND mxu.USER_ID='" . (int) $current_user['user_id'] . "'
		AND mxu.STATUS='" . $view . "'";

	$view_RET = DBGet(
		$view_sql,
		[
			'ARCHIVE' => '_makeArchiveLink',
			'DATETIME' => '_makeMessageDate',
			'FROM' => '_makeMessageFrom',
			'RECIPIENTS' => '_makeMessageRecipients',
			'SUBJECT' => '_makeMessageSubject',
		]
	);

	ListOutput( $view_RET, $view_data['columns'], $view_data['singular'], $view_data['plural'] );

	return true;
}


function GetMessagesViewsData()
{
	static $views_data = [];

	if ( ! $views_data )
	{
		$link_base = PreparePHP_SELF( [], [ 'view', 'message_id' ] );

		$columns_sent = [
			'SUBJECT' => dgettext( 'Messaging', 'Subject' ),
			'RECIPIENTS' => dgettext( 'Messaging', 'Recipients' ),
			'DATETIME' => _( 'Date' ),
		];

		$columns = [
			'SUBJECT' => dgettext( 'Messaging', 'Subject' ),
			'FROM' => dgettext( 'Messaging', 'From' ),
			'DATETIME' => _( 'Date' ),
		];

		$columns_read = [
			'ARCHIVE' => '<span class="a11y-hidden">' . dgettext( 'Messaging', 'Archive' ) . '</span>',
			'SUBJECT' => dgettext( 'Messaging', 'Subject' ),
			'FROM' => dgettext( 'Messaging', 'From' ),
			'DATETIME' => _( 'Date' ),
		];

		$views_data = [
			'unread' => [
				'label' => dgettext( 'Messaging', 'Unread' ),
				'singular' => dgettext( 'Messaging', 'Unread message' ),
				'plural' => dgettext( 'Messaging', 'Unread messages' ),
				'link' =>  $link_base . '&amp;view=unread',
				'columns' => $columns,
			],
			'read' => [
				'label' => dgettext( 'Messaging', 'Read' ),
				'singular' => dgettext( 'Messaging', 'Read message' ),
				'plural' => dgettext( 'Messaging', 'Read messages' ),
				'link' =>  $link_base . '&amp;view=read',
				'columns' => $columns_read,
			],
			'archived' => [
				'label' => dgettext( 'Messaging', 'Archived' ),
				'singular' => dgettext( 'Messaging', 'Archived message' ),
				'plural' => dgettext( 'Messaging', 'Archived messages' ),
				'link' =>  $link_base . '&amp;view=archived',
				'columns' => $columns,
			],
			'sent' => [
				'label' => dgettext( 'Messaging', 'Sent' ),
				'singular' => dgettext( 'Messaging', 'Sent message' ),
				'plural' => dgettext( 'Messaging', 'Sent messages' ),
				'link' =>  $link_base . '&amp;view=sent',
				'columns' => $columns_sent,
			],
		];
	}

	return $views_data;
}


function _makeArchiveLink( $value, $column )
{
	$msg_id = $value;

	$views = GetMessagesViewsData();

	$archive_link = $views['read']['link'] . '&message_id=' . $msg_id . '&modfunc=archive';

	return '<a href="' . ( function_exists( 'URLEscape' ) ?
		URLEscape( $archive_link ) :
		_myURLEncode( $archive_link ) ) . '"><b>' . dgettext( 'Messaging', 'Archive' ) . '</b></a>';
}


function _makeMessageDate( $value, $column )
{
	if ( function_exists( 'ProperDateTime' ) )
	{
		// Since 2.9.
		return ProperDateTime( $value, 'short' );
	}

	return ProperDate( mb_substr( $value, 0, 10 ) ) . mb_substr( $value, 10 );
}


function _makeMessageDateHeader( $value, $column )
{
	return _( 'Date' ) . ': ' . _makeMessageDate( $value, $column );
}


function _makeMessageFrom( $value, $column )
{
	$from = unserialize( $value );

	// TODO: add Photo tooltip (if function_exists())!
	return $from['name'];
}


function _makeMessageFromHeader( $value, $column )
{
	return dgettext( 'Messaging', 'From' ) . ': ' . _makeMessageFrom( $value, $column );
}


function _makeMessageRecipients( $value, $column )
{
	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	// Truncate value to 36 chars.
	return mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $value ) : htmlspecialchars( $value, ENT_QUOTES ) ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';
}


function _makeMessageRecipientsHeader( $value, $column )
{
	global $THIS_RET;

	$recipients_trucated = _makeMessageRecipients( $value, $column );

	if ( isset( $THIS_RET['STATUS'] )
		&& $THIS_RET['STATUS'] === 'sent' )
	{
		// If sent, display percentage read.
		$msg_id = $THIS_RET['MESSAGE_ID'];

		$read_label = _getSentMessageReadPercent( $THIS_RET['MESSAGE_ID'] );

		if ( $read_label )
		{
			$recipients_trucated .= ' (' . $read_label . ')';
		}
	}

	// TODO: give option to view ALL recipients.
	return dgettext(  'Messaging', 'To' ) . ': ' . $recipients_trucated;
}


function _makeMessageSubject( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	$msg_id = $THIS_RET['MESSAGE_ID'];

	$status = $THIS_RET['STATUS'];

	$view_message_link = PreparePHP_SELF(
		[],
		[],
		[ 'view' => 'message', 'message_id' => $msg_id ]
	);

	$extra = '';

	if ( $status === 'unread' )
	{
		$extra = ' style="font-weight:bold;"';
	}

	// Truncate value to 36 chars.
	$subject = mb_strlen( $value ) <= 36 ?
		$value :
		'<span title="' . ( function_exists( 'AttrEscape' ) ? AttrEscape( $value ) : htmlspecialchars( $value, ENT_QUOTES ) ) . '">' . mb_substr( $value, 0, 33 ) . '...</span>';

	return '<a href="' . $view_message_link . '"' . $extra . '>' .
		$subject . '</a>';
}


function _makeMessageSubjectHeader( $value, $column )
{
	return dgettext( 'Messaging', 'Subject' ) . ': <b>' . $value . '</b>';
}


function _makeMessageData( $value, $column )
{
	$data = unserialize( $value );

	$msg = $data['message'];

	if ( version_compare( ROSARIO_VERSION, '2.9-alpha', '<' ) )
	{
		// Not MarkDown.
		$msg = nl2br( $msg );
	}

	return '<div class="tinymce-html" style="padding: 10px;">' . $msg .	'</div>';
}


function MessageOutput( $msg_id )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return false;
	}

	$current_user = GetCurrentMessagingUser();

	// Get Message data.
	$msg_sql = "SELECT m.MESSAGE_ID, m.DATETIME, m.FROM, m.RECIPIENTS, m.SUBJECT, m.DATA, mxu.STATUS
		FROM messages m, messagexuser mxu
		WHERE m.SYEAR='" . UserSyear() . "'
		AND m.SCHOOL_ID='" . UserSchool() . "'
		AND m.MESSAGE_ID='" . (int) $msg_id . "'
		AND m.MESSAGE_ID=mxu.MESSAGE_ID
		AND mxu.KEY='" . $current_user['key'] . "'
		AND mxu.USER_ID='" . (int) $current_user['user_id'] . "'
		LIMIT 1";

	$msg_RET = DBGet(
		$msg_sql,
		[
			'DATETIME' => '_makeMessageDateHeader',
			'FROM' => '_makeMessageFromHeader',
			'RECIPIENTS' => '_makeMessageRecipientsHeader',
			'SUBJECT' => '_makeMessageSubjectHeader',
			'DATA' => '_makeMessageData',
		]
	);

	if ( ! isset( $msg_RET[1] ) )
	{
		return false;
	}

	$msg = $msg_RET[1];

	// Back to ? text & link.
	$views_data = GetMessagesViewsData();

	$back_to_text = $views_data[ $msg['STATUS'] ]['plural'];

	$back_to_link = $views_data[ $msg['STATUS'] ]['link'];

	$header_right = [];

	if ( $msg['STATUS'] === 'read'
		|| $msg['STATUS'] === 'unread' )
	{
		$view_archive_message_link = PreparePHP_SELF(
			[],
			[],
			[ 'view' => 'message', 'message_id' => $msg_id, 'modfunc' => 'archive' ]
		);

		$header_right[] = '<a href="' . $view_archive_message_link . '">' .
		dgettext( 'Messaging', 'Archive' ) . '</a>';
	}

	if ( $msg['STATUS'] !== 'sent' )
	{
		$header_right[] = '<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( 'Modules.php?modname=Messaging/Write.php&reply_to_id=' . $msg_id ) :
			_myURLEncode( 'Modules.php?modname=Messaging/Write.php&reply_to_id=' . $msg_id ) ) . '">' .
		dgettext( 'Messaging', 'Reply' ) . '</a>';
	}

	// Back to link & Reply link & Archive link.
	DrawHeader(
		'<a href="' . ( function_exists( 'URLEscape' ) ?
			URLEscape( $back_to_link ) :
			_myURLEncode( $back_to_link ) ) . '">' .
			sprintf( dgettext( 'Messaging', 'Back to %s' ), $back_to_text ) . '</a>',
		implode( ' | ', $header_right )
	);

	DrawHeader( $msg['FROM'] );

	DrawHeader( $msg['SUBJECT'] );

	DrawHeader( $msg['RECIPIENTS'] );

	DrawHeader( $msg['DATETIME'] );

	echo $msg['DATA'];

	// If status === 'unread', change to 'read'.
	if ( $msg['STATUS'] === 'unread' )
	{
		_changeMessageStatus( $msg_id, 'read' );
	}

	return true;
}


function _changeMessageStatus( $msg_id, $status )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return false;
	}

	$views_data = GetMessagesViewsData();

	$status_list = array_keys( $views_data );

	// Check status.
	if ( ! in_array( $status, $status_list ) )
	{
		return false;
	}

	$current_user = GetCurrentMessagingUser();

	// Fix MySQL syntax error, escape KEY column, is reserved keyword
	$status_sql = "UPDATE messagexuser
		SET STATUS='" . $status . "'
		WHERE " . DBEscapeIdentifier( 'KEY' ) . "='" . $current_user['key'] . "'
		AND USER_ID='" . (int) $current_user['user_id'] . "'
		AND MESSAGE_ID='" . (int) $msg_id . "'";

	$status_RET = DBQuery( $status_sql );

	return (bool) $status_RET;
}


function _getSentMessageReadPercent( $msg_id )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return '';
	}

	$read_percent_sql = "SELECT
		(SELECT count(USER_ID) FROM messagexuser
			WHERE STATUS NOT IN ( 'sent', 'unread' )
			AND MESSAGE_ID='" . (int) $msg_id . "') AS " . DBEscapeIdentifier( 'READ' ) . ",
		(SELECT count(USER_ID) FROM messagexuser
			WHERE STATUS<>'sent'
			AND MESSAGE_ID='" . (int) $msg_id . "') AS RECIPIENTS_TOTAL";

	$read_percent_RET = DBGet( $read_percent_sql );

	if ( ! isset( $read_percent_RET[1]['READ'] ) )
	{
		return '';
	}

	$read = $read_percent_RET[1]['READ'];

	$recipients_total = $read_percent_RET[1]['RECIPIENTS_TOTAL'];

	$read_percent = number_format( ( $read / $recipients_total ) * 100 );

	$views_data = GetMessagesViewsData();

	if ( $read_percent == 100 )
	{
		$read_label = $views_data['read']['label'];
	}
	elseif ( $read_percent == 0 )
	{
		$read_label = $views_data['unread']['label'];
	}
	else
	{
		$read_label = sprintf( dgettext( 'Messaging', '%s Read' ), $read_percent . '%' );
	}

	return $read_label;
}


function MessageArchive( $msg_id )
{
	// Check message ID.
	if ( ! $msg_id
		|| (string) (int) $msg_id !== $msg_id
		|| $msg_id < 1 )
	{
		return false;
	}

	$current_user = GetCurrentMessagingUser();

	// Check message status (read) & message user.
	$msg_check_sql = "SELECT 1 FROM messagexuser mxu,messages m
		WHERE mxu.MESSAGE_ID='" . (int) $msg_id . "'
		AND mxu.MESSAGE_ID=m.MESSAGE_ID
		AND mxu.STATUS='read'
		AND m.SCHOOL_ID='" . UserSchool() . "'
		AND m.SYEAR='" . UserSyear() . "'
		AND mxu.USER_ID='" . (int) $current_user['user_id'] . "'
		AND mxu.KEY='" . $current_user['key'] . "'";

	$msg_check_RET = DBGet( $msg_check_sql );

	if ( ! $msg_check_RET )
	{
		return false;
	}

	// Archive message.
	$msg_archive_sql = "UPDATE messagexuser SET STATUS='archived'
		WHERE MESSAGE_ID='" . (int) $msg_id . "'
		AND USER_ID='" . (int) $current_user['user_id'] . "'
		AND " . DBEscapeIdentifier( 'KEY' ) . "='" . $current_user['key'] . "'";

	DBQuery( $msg_archive_sql );

	return true;
}
